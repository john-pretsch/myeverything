<?php

namespace Database\Seeders;

use App\Models\NewsSource;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Seeder;

class NewsSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            [
                'name' => 'WSJ',
                'site_url' => 'https://www.wsj.com',
                'feed_url' => 'https://feeds.a.dj.com/rss/RSSWorldNews.xml',
            ],
            [
                'name' => 'CBC',
                'site_url' => 'https://www.cbc.ca',
                'feed_url' => 'https://rss.cbc.ca/lineup/topstories.xml',
            ],
            [
                'name' => 'BBC',
                'site_url' => 'https://www.bbc.com/news',
                'feed_url' => 'http://feeds.bbci.co.uk/news/world/rss.xml',
            ],
            [
                'name' => 'Al Jazeera',
                'site_url' => 'https://www.aljazeera.com',
                'feed_url' => 'https://www.aljazeera.com/xml/rss/all.xml',
            ],
            [
                'name' => 'Techdirt',
                'site_url' => 'https://www.techdirt.com',
                'feed_url' => 'https://www.techdirt.com/techdirt_rss.xml',
            ],
            [
                'name' => 'Bay Observer',
                'site_url' => 'https://bayobserver.ca',
                'feed_url' => 'https://bayobserver.ca/feed/',
            ],
            [
                'name' => 'Hamilton Independent',
                'site_url' => 'https://hamiltonindependent.ca',
                'feed_url' => 'https://hamiltonindependent.ca/feed/',
            ],
            [
                'name' => 'The Local',
                'site_url' => 'https://thelocal.to',
                'feed_url' => 'https://thelocal.to/feed/',
            ],
            [
                'name' => 'The Hacker News',
                'site_url' => 'https://thehackernews.com',
                'feed_url' => 'https://feeds.feedburner.com/TheHackersNews',
            ],
            [
                'name' => 'BleepingComputer',
                'site_url' => 'https://www.bleepingcomputer.com',
                'feed_url' => 'https://www.bleepingcomputer.com/feed/',
            ],
            [
                'name' => 'Dark Reading',
                'site_url' => 'https://www.darkreading.com',
                'feed_url' => 'https://www.darkreading.com/rss.xml',
            ],
            [
                'name' => 'Krebs on Security',
                'site_url' => 'https://krebsonsecurity.com',
                'feed_url' => 'https://krebsonsecurity.com/feed/',
            ],
            [
                'name' => 'Bellingcat',
                'site_url' => 'https://www.bellingcat.com',
                'feed_url' => 'https://www.bellingcat.com/feed/',
            ],
            [
                'name' => 'ICIJ',
                'site_url' => 'https://www.icij.org',
                'feed_url' => 'https://www.icij.org/feed/',
            ],
            [
                'name' => 'OCCRP',
                'site_url' => 'https://www.occrp.org',
                'feed_url' => 'https://www.occrp.org/en/feed',
            ],
            [
                'name' => 'Democracy Now',
                'site_url' => 'https://www.democracynow.org',
                'feed_url' => 'https://www.democracynow.org/democracynow.rss',
            ],
            [
                'name' => 'ProPublica',
                'site_url' => 'https://www.propublica.org',
                'feed_url' => 'https://www.propublica.org/feeds/propublica/main',
            ],
            [
                'name' => 'The Intercept',
                'site_url' => 'https://theintercept.com',
                'feed_url' => 'https://theintercept.com/feed/',
            ],
            [
                'name' => 'CNBC',
                'site_url' => 'https://www.cnbc.com',
                'feed_url' => 'https://www.cnbc.com/id/100003114/device/rss/rss.html',
            ],
            [
                'name' => 'Investing.com',
                'site_url' => 'https://www.investing.com',
                'feed_url' => 'https://www.investing.com/rss/news.rss',
            ],
            [
                'name' => 'The Block',
                'site_url' => 'https://www.theblock.co',
                'feed_url' => 'https://www.theblock.co/rss.xml',
            ],
            [
                'name' => 'CoinDesk',
                'site_url' => 'https://www.coindesk.com',
                'feed_url' => 'https://www.coindesk.com/arc/outboundfeeds/rss/',
            ],
            [
                'name' => 'CryptoSlate',
                'site_url' => 'https://cryptoslate.com',
                'feed_url' => 'https://cryptoslate.com/feed/',
            ],
            [
                'name' => 'Forbes',
                'site_url' => 'https://www.forbes.com',
                'feed_url' => 'https://www.forbes.com/business/feed/',
            ],
            [
                'name' => 'MarketWatch',
                'site_url' => 'https://www.marketwatch.com',
                'feed_url' => 'https://feeds.content.dowjones.io/public/rss/mw_topstories',
            ],
        ];

        $newlyDefaulted = collect();

        foreach ($sources as $attributes) {
            $existed = NewsSource::where('feed_url', $attributes['feed_url'])->exists();

            $newsSource = NewsSource::updateOrCreate(
                ['feed_url' => $attributes['feed_url']],
                $attributes,
            );

            // is_default isn't mass-assignable (see NewsSource's fillable
            // list — that's deliberate so a user's own add-source request
            // can never set it), so the catalog-seeding value has to go
            // through forceFill instead.
            $newsSource->forceFill(['is_default' => true])->save();

            if (! $existed) {
                $newlyDefaulted->push($newsSource);
            }
        }

        $topicAssignments = [
            'Local' => ['Bay Observer', 'Hamilton Independent', 'The Local'],
            'Hackery' => ['The Hacker News', 'BleepingComputer', 'Dark Reading', 'Krebs on Security'],
            'World' => ['Bellingcat', 'ICIJ', 'OCCRP', 'Democracy Now', 'ProPublica', 'The Intercept'],
            'Fin' => ['CNBC', 'Investing.com', 'The Block', 'CoinDesk', 'CryptoSlate', 'Forbes', 'WSJ', 'MarketWatch'],
        ];

        foreach ($topicAssignments as $topicName => $sourceNames) {
            $topic = Topic::firstOrCreate(['name' => $topicName]);
            $sourceIds = NewsSource::whereIn('name', $sourceNames)->pluck('id');
            $topic->sources()->syncWithoutDetaching($sourceIds);
        }

        if ($newlyDefaulted->isEmpty()) {
            return;
        }

        // New is_default sources only auto-attach to users created from now
        // on (see SsoController::callback); backfill everyone who already
        // has an account so they see the new topic too.
        User::all()->each(function (User $user) use ($newlyDefaulted) {
            $alreadyHas = $user->newsSources()->pluck('news_sources.id');
            $toAttach = $newlyDefaulted->reject(fn ($s) => $alreadyHas->contains($s->id));

            if ($toAttach->isEmpty()) {
                return;
            }

            $nextPosition = (int) ($user->newsSources()->max('position') ?? -1) + 1;

            $user->newsSources()->attach(
                $toAttach->values()->mapWithKeys(
                    fn ($s, $i) => [$s->id => ['position' => $nextPosition + $i]],
                ),
            );
        });
    }
}
