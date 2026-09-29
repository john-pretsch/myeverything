<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Models\NewsSource;
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
    public function handle(RssParser $parser): int
    {
        $sources = NewsSource::all();

        foreach ($sources as $source) {
            $this->fetchSource($source, $parser);
        }

        NewsArticle::where('published_at', '<', now()->subDays(30))->delete();

        return self::SUCCESS;
    }

    private function fetchSource(NewsSource $source, RssParser $parser): void
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
                NewsArticle::updateOrCreate(
                    ['news_source_id' => $source->id, 'guid' => $item['guid']],
                    [
                        'title' => $item['title'],
                        'url' => $item['url'],
                        'summary' => $item['summary'],
                        'image_url' => $item['image_url'],
                        'published_at' => $item['published_at'],
                    ],
                );
            }

            $source->update(['last_fetched_at' => now(), 'last_fetch_error' => null]);
            $this->info("Fetched {$source->name}: ".count($items).' items');
        } catch (\Throwable $e) {
            $source->update(['last_fetch_error' => $e->getMessage()]);
            $this->error("Failed to fetch {$source->name}: {$e->getMessage()}");
        }
    }
}
