<?php

namespace App\Services\News;

use App\Models\NewsArticle;
use App\Models\NewsSource;
use App\Models\Tag;
use App\Support\HostSafety;
use Illuminate\Support\Facades\Http;

class NewsSourceFetcher
{
    public function __construct(
        private readonly RssParser $parser,
        private readonly ArticleTaggingService $tagger,
    ) {}

    /**
     * Fetch one source's feed and store its articles. At most $taggingBudget
     * newly created articles get an LLM tagging call (decremented in place,
     * so a caller fetching many sources can share one budget).
     *
     * @return array{ids: list<int>, count: int, error: ?string} ids = articles created by this fetch
     */
    public function fetch(NewsSource $source, int &$taggingBudget): array
    {
        if (! HostSafety::isSafeFeedUrl($source->feed_url)) {
            $error = 'Feed URL is not a public http(s) host.';
            $source->update(['last_fetch_error' => $error]);

            return ['ids' => [], 'count' => 0, 'error' => $error];
        }

        try {
            $response = Http::timeout(10)
                ->withUserAgent('myeverything-dashboard/1.0')
                ->get($source->feed_url);

            if (! $response->successful()) {
                $error = "HTTP {$response->status()}";
                $source->update(['last_fetch_error' => $error]);

                return ['ids' => [], 'count' => 0, 'error' => $error];
            }

            $items = $this->parser->parse($response->body());
            $newIds = [];

            foreach ($items as $item) {
                $article = NewsArticle::updateOrCreate(
                    ['news_source_id' => $source->id, 'guid' => $item['guid']],
                    [
                        'title' => $item['title'],
                        'url' => $item['url'],
                        'summary' => $item['summary'],
                        'image_url' => $item['image_url'],
                        'published_at' => $item['published_at'],
                    ],
                );

                if (! $article->wasRecentlyCreated) {
                    continue;
                }

                $newIds[] = $article->id;

                if ($taggingBudget > 0) {
                    $this->autoTagArticle($article);
                    $taggingBudget--;
                }
            }

            $source->update(['last_fetched_at' => now(), 'last_fetch_error' => null]);

            if ($source->description === null) {
                $source->update(['description' => $this->parser->channelDescription($response->body())]);
            }

            return ['ids' => $newIds, 'count' => count($items), 'error' => null];
        } catch (\Throwable $e) {
            $source->update(['last_fetch_error' => $e->getMessage()]);

            return ['ids' => [], 'count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Best-effort — a failed/empty suggestion just leaves the article
     * untagged, it never affects article storage (which already
     * succeeded before this runs).
     */
    private function autoTagArticle(NewsArticle $article): void
    {
        $names = $this->tagger->suggestTags($article->title, $article->summary);

        foreach ($names as $name) {
            $tag = Tag::firstOrCreate(['name' => $name]);

            $article->tags()->syncWithoutDetaching([$tag->id => ['applied_by_user_id' => null]]);
        }
    }
}
