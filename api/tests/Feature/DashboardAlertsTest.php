<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\NewsSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function source(string $name, bool $default = true): NewsSource
    {
        return NewsSource::forceCreate([
            'name' => $name,
            'site_url' => "https://{$name}.test",
            'feed_url' => "https://{$name}.test/rss",
            'is_default' => $default,
        ]);
    }

    private function article(NewsSource $source, string $title): NewsArticle
    {
        return NewsArticle::create([
            'news_source_id' => $source->id,
            'guid' => md5($source->id.$title),
            'title' => $title,
            'url' => "https://{$source->name}.test/".md5($title),
            'published_at' => now()->subMinutes(30),
        ]);
    }

    private function fakeUpstreams(array $kev = [], string $infocon = 'green'): void
    {
        Http::fake([
            'isc.sans.edu/*' => Http::response(['status' => $infocon]),
            'www.cisa.gov/*' => Http::response(['vulnerabilities' => $kev]),
        ]);
    }

    public function test_story_covered_by_three_sources_is_an_alert(): void
    {
        $this->fakeUpstreams();
        foreach (['a', 'b', 'c'] as $name) {
            $this->article($this->source($name), "Earthquake strikes coastal Chile, tsunami warning issued ({$name})");
        }
        $this->article($this->source('d'), 'Local bakery wins regional award for sourdough');

        $alerts = $this->getJson('/api/dashboard/alerts')->assertOk()->json('data');

        $this->assertCount(1, $alerts);
        $this->assertSame('news', $alerts[0]['kind']);
        $this->assertStringContainsString('Reported by 3 sources', $alerts[0]['summary']);
    }

    public function test_two_sources_are_not_enough_and_custom_sources_are_ignored(): void
    {
        $this->fakeUpstreams();
        $this->article($this->source('a'), 'Earthquake strikes coastal Chile, tsunami warning issued');
        $this->article($this->source('b'), 'Earthquake strikes coastal Chile, tsunami warning issued again');
        $this->article($this->source('custom', default: false), 'Earthquake strikes coastal Chile, tsunami warning issued');

        $this->assertSame([], $this->getJson('/api/dashboard/alerts')->json('data'));
    }

    public function test_breaking_marker_is_an_info_alert(): void
    {
        $this->fakeUpstreams();
        $this->article($this->source('a'), 'BREAKING: Prime minister resigns');

        $alerts = $this->getJson('/api/dashboard/alerts')->json('data');

        $this->assertSame('info', $alerts[0]['severity']);
    }

    public function test_recent_kev_entries_rank_ransomware_first(): void
    {
        $today = now()->toDateString();
        $this->fakeUpstreams([
            ['cveID' => 'CVE-2026-1', 'vendorProject' => 'Acme', 'product' => 'Gate', 'dateAdded' => $today, 'knownRansomwareCampaignUse' => 'Unknown', 'shortDescription' => 'x'],
            ['cveID' => 'CVE-2026-2', 'vendorProject' => 'Beta', 'product' => 'VPN', 'dateAdded' => $today, 'knownRansomwareCampaignUse' => 'Known', 'shortDescription' => 'y'],
            ['cveID' => 'CVE-2020-3', 'vendorProject' => 'Old', 'product' => 'Thing', 'dateAdded' => '2020-01-01', 'knownRansomwareCampaignUse' => 'Known'],
        ]);

        $alerts = $this->getJson('/api/dashboard/alerts')->json('data');

        $this->assertSame(['kev:CVE-2026-2', 'kev:CVE-2026-1'], array_column($alerts, 'id'));
        $this->assertSame('critical', $alerts[0]['severity']);
    }

    public function test_raised_infocon_is_an_alert_and_a_failing_source_does_not_blank_the_rest(): void
    {
        Http::fake([
            'isc.sans.edu/*' => Http::response(['status' => 'yellow']),
            'www.cisa.gov/*' => Http::response('nope', 500),
        ]);

        $alerts = $this->getJson('/api/dashboard/alerts')->assertOk()->json('data');

        $this->assertSame(['sans:yellow'], array_column($alerts, 'id'));
    }
}
