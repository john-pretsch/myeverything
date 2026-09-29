<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Models\NewsSource;
use App\Models\Tag;
use App\Services\News\ArticleTaggingService;
use App\Services\News\RssParser;
use App\Support\HostSafety;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('news:fetch')]
#[Description('Fetch the latest articles for every news source')]
class FetchNewsCommand extends Command
{
    /** Caps how many articles get an LLM tagging call in a single run, so one slow/expensive run can't stack up. */
    private const MAX_TAGGED_PER_RUN = 30;

    private int $taggedCount = 0;

    public function handle(RssParser $parser, ArticleTaggingService $tagger): int
    {
        $sources = NewsSource::all();

        foreach ($sources as $source) {
            $this->fetchSource($source, $parser, $tagger);
        }

        NewsArticle::where('published_at', '<', now()->subDays(30))->delete();

        return self::SUCCESS;
    }

    private function fetchSource(NewsSource $source, RssParser $parser, ArticleTaggingService $tagger): void
    {
        if (! HostSafety::isSafeFeedUrl($source->feed_url)) {
            $source->update(['last_fetch_error' => 'Feed URL is not a public http(s) host.']);
            $this->error("Skipping {$source->name}: unsafe feed URL");

            return;
        }

        try {
            $response = Http::timeout(10)
                ->withUserAgent('myeverything-dashboard/1.0')
                ->get($source->feed_url);

            if (! $response->successful()) {
                $source->update(['last_fetch_error' => "HTTP {$response->status()}"]);
                $this->error("Failed to fetch {$source->name}: HTTP {$response->status()}");

                return;
            }

            $items = $parser->parse($response->body());

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

                if ($article->wasRecentlyCreated && $this->taggedCount < self::MAX_TAGGED_PER_RUN) {
                    $this->autoTagArticle($article, $tagger);
                    $this->taggedCount++;
                }
            }

            $source->update(['last_fetched_at' => now(), 'last_fetch_error' => null]);
            $this->info("Fetched {$source->name}: ".count($items).' items');
        } catch (\Throwable $e) {
            $source->update(['last_fetch_error' => $e->getMessage()]);
            $this->error("Failed to fetch {$source->name}: {$e->getMessage()}");
        }
    }

    /**
     * Best-effort — a failed/empty suggestion just leaves the article
     * untagged, it never affects article storage (which already
     * succeeded before this runs).
     */
    private function autoTagArticle(NewsArticle $article, ArticleTaggingService $tagger): void
    {
        $names = $tagger->suggestTags($article->title, $article->summary);

        foreach ($names as $name) {
            $tag = Tag::firstOrCreate(['name' => $name]);

            $article->tags()->syncWithoutDetaching([$tag->id => ['applied_by_user_id' => null]]);
        }
    }
}
