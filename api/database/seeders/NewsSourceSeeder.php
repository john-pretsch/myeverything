<?php

namespace Database\Seeders;

use App\Models\NewsSource;
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
                'topic' => null,
            ],
            [
                'name' => 'CBC',
                'site_url' => 'https://www.cbc.ca',
                'feed_url' => 'https://rss.cbc.ca/lineup/topstories.xml',
                'topic' => null,
            ],
            [
                'name' => 'BBC',
                'site_url' => 'https://www.bbc.com/news',
                'feed_url' => 'http://feeds.bbci.co.uk/news/world/rss.xml',
                'topic' => null,
            ],
            [
                'name' => 'Al Jazeera',
                'site_url' => 'https://www.aljazeera.com',
                'feed_url' => 'https://www.aljazeera.com/xml/rss/all.xml',
                'topic' => null,
            ],
            [
                'name' => 'Techdirt',
                'site_url' => 'https://www.techdirt.com',
                'feed_url' => 'https://www.techdirt.com/techdirt_rss.xml',
                'topic' => null,
            ],
            [
                'name' => 'Bay Observer',
                'site_url' => 'https://bayobserver.ca',
                'feed_url' => 'https://bayobserver.ca/feed/',
                'topic' => 'local',
            ],
            [
                'name' => 'Hamilton Independent',
                'site_url' => 'https://hamiltonindependent.ca',
                'feed_url' => 'https://hamiltonindependent.ca/feed/',
                'topic' => 'local',
            ],
            [
                'name' => 'The Local',
                'site_url' => 'https://thelocal.to',
                'feed_url' => 'https://thelocal.to/feed/',
                'topic' => 'local',
            ],
            [
                'name' => 'The Hacker News',
                'site_url' => 'https://thehackernews.com',
                'feed_url' => 'https://feeds.feedburner.com/TheHackersNews',
                'topic' => 'hackery',
            ],
            [
                'name' => 'BleepingComputer',
                'site_url' => 'https://www.bleepingcomputer.com',
                'feed_url' => 'https://www.bleepingcomputer.com/feed/',
                'topic' => 'hackery',
            ],
            [
                'name' => 'Dark Reading',
                'site_url' => 'https://www.darkreading.com',
                'feed_url' => 'https://www.darkreading.com/rss.xml',
                'topic' => 'hackery',
            ],
            [
                'name' => 'Krebs on Security',
                'site_url' => 'https://krebsonsecurity.com',
                'feed_url' => 'https://krebsonsecurity.com/feed/',
                'topic' => 'hackery',
            ],
            [
                'name' => 'Bellingcat',
                'site_url' => 'https://www.bellingcat.com',
                'feed_url' => 'https://www.bellingcat.com/feed/',
                'topic' => 'world',
            ],
            [
                'name' => 'ICIJ',
                'site_url' => 'https://www.icij.org',
                'feed_url' => 'https://www.icij.org/feed/',
                'topic' => 'world',
            ],
            [
                'name' => 'OCCRP',
                'site_url' => 'https://www.occrp.org',
                'feed_url' => 'https://www.occrp.org/en/feed',
                'topic' => 'world',
            ],
            [
                'name' => 'Democracy Now',
                'site_url' => 'https://www.democracynow.org',
                'feed_url' => 'https://www.democracynow.org/democracynow.rss',
                'topic' => 'world',
            ],
            [
                'name' => 'ProPublica',
                'site_url' => 'https://www.propublica.org',
                'feed_url' => 'https://www.propublica.org/feeds/propublica/main',
                'topic' => 'world',
            ],
            [
                'name' => 'The Intercept',
                'site_url' => 'https://theintercept.com',
                'feed_url' => 'https://theintercept.com/feed/',
                'topic' => 'world',
            ],
        ];

        $newlyDefaulted = collect();

        foreach ($sources as $source) {
            $topic = $source['topic'];
            $attributes = array_diff_key($source, ['topic' => null]);

            $existed = NewsSource::where('feed_url', $source['feed_url'])->exists();

            $newsSource = NewsSource::updateOrCreate(
                ['feed_url' => $attributes['feed_url']],
                $attributes,
            );

            // is_default/topic aren't mass-assignable (see NewsSource's
            // fillable list — that's deliberate so a user's own add-source
            // request can never set them), so the catalog-seeding values
            // have to go through forceFill instead.
            $newsSource->forceFill(['is_default' => true, 'topic' => $topic])->save();

            if (! $existed) {
                $newlyDefaulted->push($newsSource);
            }
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
