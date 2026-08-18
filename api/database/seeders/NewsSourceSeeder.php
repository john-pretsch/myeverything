<?php

namespace Database\Seeders;

use App\Models\NewsSource;
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
        ];

        foreach ($sources as $source) {
            NewsSource::updateOrCreate(
                ['feed_url' => $source['feed_url']],
                [...$source, 'is_default' => true],
            );
        }
    }
}
