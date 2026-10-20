<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Models\NewsSource;
use App\Services\News\NewsSourceFetcher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('news:fetch')]
#[Description('Fetch the latest articles for every news source')]
class FetchNewsCommand extends Command
{
    /** Caps how many articles get an LLM tagging call in a single run, so one slow/expensive run can't stack up. */
    private const MAX_TAGGED_PER_RUN = 30;

    public function handle(NewsSourceFetcher $fetcher): int
    {
        $taggingBudget = self::MAX_TAGGED_PER_RUN;

        foreach (NewsSource::all() as $source) {
            $result = $fetcher->fetch($source, $taggingBudget);

            if ($result['error'] !== null) {
                $this->error("Failed to fetch {$source->name}: {$result['error']}");
            } else {
                $this->info("Fetched {$source->name}: {$result['count']} items");
            }
        }

        NewsArticle::where('published_at', '<', now()->subDays(30))->delete();

        return self::SUCCESS;
    }
}
