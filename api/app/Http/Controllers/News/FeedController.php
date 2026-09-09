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
    /** Each point of source affinity shifts an article's effective recency by this many hours. */
    private const AFFINITY_HOURS = 6;

    public function index(Request $request)
    {
        $user = $request->user();
        $limit = min((int) $request->integer('limit', 50), 100);
        $query = trim((string) $request->string('q'));

        $sourceIds = $user
            ? $user->newsSources()->pluck('news_sources.id')
            : NewsSource::where('is_default', true)->pluck('id');

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

        $affinity = $user
            ? DB::table('news_article_feedback')
                ->where('user_id', $user->id)
                ->select('news_source_id', DB::raw('SUM(direction) as score'))
                ->groupBy('news_source_id')
                ->pluck('score', 'news_source_id')
            : collect();

        $viewerFeedback = $user
            ? DB::table('news_article_feedback')
                ->where('user_id', $user->id)
                ->whereIn('news_article_id', $articles->pluck('id'))
                ->pluck('direction', 'news_article_id')
            : collect();

        $articles = $articles
            ->sortByDesc(function (NewsArticle $article) use ($affinity) {
                $published = $article->published_at?->timestamp ?? 0;
                $boost = (int) ($affinity->get($article->news_source_id) ?? 0);

                return $published + $boost * self::AFFINITY_HOURS * 3600;
            })
            ->values()
            ->take($limit);

        $articles->each(function (NewsArticle $article) use ($viewerFeedback) {
            $article->viewer_feedback = $viewerFeedback->has($article->id)
                ? (int) $viewerFeedback->get($article->id)
                : null;
        });

        return NewsArticleResource::collection($articles);
    }
}
