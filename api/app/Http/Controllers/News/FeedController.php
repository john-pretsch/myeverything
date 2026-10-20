<?php

namespace App\Http\Controllers\News;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsArticleResource;
use App\Models\NewsArticle;
use App\Models\NewsSource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    /** Each point of net tag-vote affinity shifts an article's effective recency by this many hours. */
    private const AFFINITY_HOURS = 6;

    /** Caps how many search terms are ANDed together, to bound query size. */
    private const MAX_SEARCH_TERMS = 8;

    public function index(Request $request)
    {
        $user = $request->user();
        $limit = min((int) $request->integer('limit', 50), 100);
        $query = trim((string) $request->string('q'));
        $topicId = $request->integer('topic');
        $sourceId = $request->integer('source');
        $tagId = $request->integer('tag');
        $pinnedIds = collect($request->input('pinned', []))->map(fn ($id) => (int) $id)->filter()->values();

        $sources = $user
            ? $user->newsSources()
            : NewsSource::where('is_default', true);

        if ($topicId) {
            $sources->whereHas('topics', fn ($q) => $q->where('topics.id', $topicId));
        }

        if ($sourceId) {
            $sources->where('news_sources.id', $sourceId);
        }

        $sourceIds = $user ? $sources->pluck('news_sources.id') : $sources->pluck('id');

        if ($sourceIds->isEmpty()) {
            return NewsArticleResource::collection(collect());
        }

        $articlesQuery = NewsArticle::with('source.topics')
            ->whereIn('news_source_id', $sourceIds);

        if ($tagId) {
            $articlesQuery->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId));
        }

        // Every term must appear in the title or summary (any order, either
        // field); "double quoted" segments are matched as a single phrase.
        foreach ($this->searchTerms($query) as $term) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
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

        // Tags are shared/global — every viewer sees every tag applied to
        // an article, regardless of who applied it.
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
            ->values();

        // With no topic filter, round-robin one article per topic (in
        // relevance order within each topic) instead of a straight merge,
        // so a fast-publishing topic can't dominate a run of consecutive
        // articles. Interleave before truncating to $limit so mixing
        // holds across the whole visible window, not just a pre-cut slice.
        if (! $topicId) {
            $articles = $this->interleaveByTopic($articles);
        }

        // Freshly refreshed articles go first (still in relevance order),
        // regardless of interleaving or the limit.
        if ($pinnedIds->isNotEmpty()) {
            [$pinned, $rest] = $articles->partition(fn (NewsArticle $a) => $pinnedIds->contains($a->id));
            $articles = $pinned->concat($rest)->values();
        }

        $articles = $articles->take($limit);

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

    /**
     * Groups already-ranked articles by their primary topic (or a "no
     * topic" bucket) and round-robins across the buckets, preserving each
     * bucket's internal order — so consecutive articles alternate topics
     * instead of clumping, until the shorter buckets run out.
     *
     * @param  Collection<int, NewsArticle>  $articles
     * @return Collection<int, NewsArticle>
     */
    private function interleaveByTopic($articles)
    {
        $buckets = [];
        $bucketOrder = [];

        foreach ($articles as $article) {
            $key = $article->source->topics->first()?->id ?? 0;

            if (! isset($buckets[$key])) {
                $buckets[$key] = [];
                $bucketOrder[] = $key;
            }

            $buckets[$key][] = $article;
        }

        $result = [];
        $remaining = true;

        while ($remaining) {
            $remaining = false;

            foreach ($bucketOrder as $key) {
                if (! empty($buckets[$key])) {
                    $result[] = array_shift($buckets[$key]);
                    $remaining = true;
                }
            }
        }

        return collect($result);
    }

    /**
     * @return list<string>
     */
    private function searchTerms(string $query): array
    {
        preg_match_all('/"([^"]+)"|(\S+)/u', $query, $matches, PREG_SET_ORDER);

        $terms = [];
        foreach ($matches as $match) {
            $term = trim($match[1] !== '' ? $match[1] : ($match[2] ?? ''), "\" \t");
            if ($term !== '') {
                $terms[] = $term;
            }
        }

        return array_slice($terms, 0, self::MAX_SEARCH_TERMS);
    }
}
