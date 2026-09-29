<?php

namespace App\Http\Controllers\News;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsArticleResource;
use App\Models\NewsArticle;
use App\Models\NewsSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    /** Each point of net tag-vote affinity shifts an article's effective recency by this many hours. */
    private const AFFINITY_HOURS = 6;

    public function index(Request $request)
    {
        $user = $request->user();
        $limit = min((int) $request->integer('limit', 50), 100);
        $query = trim((string) $request->string('q'));
        $topic = trim((string) $request->string('topic'));

        $sources = $user
            ? $user->newsSources()
            : NewsSource::where('is_default', true);

        if ($topic !== '') {
            $sources->where('topic', $topic);
        }

        $sourceIds = $user ? $sources->pluck('news_sources.id') : $sources->pluck('id');

        if ($sourceIds->isEmpty()) {
            return NewsArticleResource::collection(collect());
        }

        $articlesQuery = NewsArticle::with('source')
            ->whereIn('news_source_id', $sourceIds);

        if ($query !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
            $articlesQuery->where(function ($inner) use ($escaped) {
                $inner->where('title', 'like', "%{$escaped}%")
                    ->orWhere('summary', 'like', "%{$escaped}%");
            });
        }

        $articles = $articlesQuery
            ->orderByDesc('published_at')
            ->limit(300)
            ->get();

        $tagVotes = $user
            ? DB::table('tag_votes')->where('user_id', $user->id)->pluck('direction', 'tag_id')
            : collect();

        $tagsByArticle = DB::table('news_article_tag')
            ->join('tags', 'tags.id', '=', 'news_article_tag.tag_id')
            ->whereIn('news_article_tag.news_article_id', $articles->pluck('id'))
            ->select('news_article_tag.news_article_id', 'tags.id as tag_id', 'tags.name as tag_name')
            ->get()
            ->groupBy('news_article_id');

        $articles = $articles
            ->sortByDesc(function (NewsArticle $article) use ($tagVotes, $tagsByArticle) {
                $published = $article->published_at?->timestamp ?? 0;
                $tagIds = ($tagsByArticle->get($article->id) ?? collect())->pluck('tag_id');
                $boost = (int) $tagIds->sum(fn ($tagId) => $tagVotes->get($tagId) ?? 0);

                return $published + $boost * self::AFFINITY_HOURS * 3600;
            })
            ->values()
            ->take($limit);

        $articles->each(function (NewsArticle $article) use ($tagVotes, $tagsByArticle) {
            $article->article_tags = ($tagsByArticle->get($article->id) ?? collect())
                ->map(fn ($row) => [
                    'id' => $row->tag_id,
                    'name' => $row->tag_name,
                    'viewer_vote' => $tagVotes->has($row->tag_id) ? (int) $tagVotes->get($row->tag_id) : null,
                ])
                ->values()
                ->all();
        });

        return NewsArticleResource::collection($articles);
    }
}
