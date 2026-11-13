<?php

namespace App\Services\Alerts;

use App\Models\NewsArticle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlertService
{
    private const USER_AGENT = 'Mozilla/5.0 (compatible; myeverything-dashboard/1.0)';

    private const KEV_URL = 'https://www.cisa.gov/sites/default/files/feeds/known_exploited_vulnerabilities.json';

    private const KEV_WINDOW_DAYS = 7;

    private const KEV_MAX_ALERTS = 4;

    private const NEWS_WINDOW_HOURS = 6;

    /** Distinct sources that must cover the same story to call it breaking. */
    private const NEWS_MIN_SOURCES = 3;

    /** Significant title words two articles must share to count as the same story. */
    private const NEWS_MIN_SHARED_WORDS = 3;

    private const NEWS_MAX_ALERTS = 3;

    private const BREAKING_MARKER = '/^\s*(breaking|just in|urgent|live)\b/i';

    private const STOPWORDS = [
        'about', 'after', 'again', 'also', 'amid', 'been', 'before', 'could', 'from', 'have', 'here',
        'into', 'more', 'most', 'news', 'over', 'says', 'said', 'than', 'that', 'their', 'there',
        'these', 'they', 'this', 'what', 'when', 'where', 'which', 'while', 'will', 'with', 'would',
        'your', 'live', 'breaking', 'update', 'latest', 'video', 'watch',
    ];

    /**
     * @return list<array{id: string, kind: string, severity: string, title: string, summary: ?string, url: ?string, source: string, published_at: ?string}>
     */
    public function alerts(): array
    {
        return Cache::remember('dashboard:alerts', now()->addMinutes(5), function () {
            $alerts = [
                ...$this->safely('SANS infocon', fn () => $this->infocon()),
                ...$this->safely('CISA KEV', fn () => $this->knownExploited()),
                ...$this->safely('breaking news', fn () => $this->breakingNews()),
            ];

            $rank = ['critical' => 0, 'high' => 1, 'info' => 2];
            usort($alerts, fn ($a, $b) => $rank[$a['severity']] <=> $rank[$b['severity']]);

            return $alerts;
        });
    }

    /** One failing source must not blank the others. */
    private function safely(string $label, callable $source): array
    {
        try {
            return $source();
        } catch (\Throwable $e) {
            Log::warning("Alert source failed: {$label}", ['error' => $e->getMessage()]);

            return [];
        }
    }

    private function infocon(): array
    {
        $response = Http::timeout(8)->withUserAgent(self::USER_AGENT)->get('https://isc.sans.edu/api/infocon?json');
        $response->throw();

        $status = strtolower((string) $response->json('status'));
        if ($status === '' || $status === 'green') {
            return [];
        }

        return [[
            'id' => "sans:{$status}",
            'kind' => 'security',
            'severity' => in_array($status, ['orange', 'red'], true) ? 'critical' : 'high',
            'title' => 'Internet threat level raised to '.strtoupper($status),
            'summary' => 'The SANS Internet Storm Center has raised its global threat level above normal.',
            'url' => 'https://isc.sans.edu/',
            'source' => 'SANS ISC',
            'published_at' => null,
        ]];
    }

    private function knownExploited(): array
    {
        $recent = Cache::remember('dashboard:kev', now()->addMinutes(30), function () {
            $response = Http::timeout(20)->withUserAgent(self::USER_AGENT)->get(self::KEV_URL);
            $response->throw();

            $cutoff = now()->subDays(self::KEV_WINDOW_DAYS)->toDateString();

            return collect($response->json('vulnerabilities', []))
                ->filter(fn (array $v) => ($v['dateAdded'] ?? '') >= $cutoff)
                ->map(fn (array $v) => [
                    'cve' => $v['cveID'],
                    'vendor' => $v['vendorProject'] ?? '',
                    'product' => $v['product'] ?? '',
                    'name' => $v['vulnerabilityName'] ?? '',
                    'description' => $v['shortDescription'] ?? null,
                    'added' => $v['dateAdded'],
                    'ransomware' => ($v['knownRansomwareCampaignUse'] ?? '') === 'Known',
                ])
                ->values()
                ->all();
        });

        return collect($recent)
            ->sortBy([['ransomware', 'desc'], ['added', 'desc']])
            ->take(self::KEV_MAX_ALERTS)
            ->map(fn (array $v) => [
                'id' => "kev:{$v['cve']}",
                'kind' => 'security',
                'severity' => $v['ransomware'] ? 'critical' : 'high',
                'title' => trim("{$v['vendor']} {$v['product']}").": actively exploited ({$v['cve']})",
                'summary' => $v['description'] ?? $v['name'],
                'url' => "https://nvd.nist.gov/vuln/detail/{$v['cve']}",
                'source' => 'CISA KEV'.($v['ransomware'] ? ' · used in ransomware' : ''),
                'published_at' => Carbon::parse($v['added'])->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * A story is "breaking" when several independent default sources are
     * covering it right now, or a headline carries an explicit marker.
     */
    private function breakingNews(): array
    {
        $articles = NewsArticle::query()
            ->with('source:id,name')
            ->whereHas('source', fn ($q) => $q->where('is_default', true))
            ->where('published_at', '>=', now()->subHours(self::NEWS_WINDOW_HOURS))
            ->orderBy('published_at')
            ->get(['id', 'news_source_id', 'title', 'url', 'summary', 'published_at']);

        $clusters = [];
        foreach ($articles as $article) {
            $words = $this->significantWords($article->title);

            foreach ($clusters as &$cluster) {
                if (count(array_intersect($cluster['words'], $words)) >= self::NEWS_MIN_SHARED_WORDS) {
                    $cluster['sources'][$article->news_source_id] = true;
                    $cluster['members'][] = $article->id;

                    continue 2;
                }
            }
            unset($cluster);

            $clusters[] = [
                'seed' => $article,
                'words' => $words,
                'sources' => [$article->news_source_id => true],
                'members' => [$article->id],
            ];
        }
        unset($cluster);

        $alerts = collect($clusters)
            ->filter(fn (array $c) => count($c['sources']) >= self::NEWS_MIN_SOURCES)
            ->sortByDesc(fn (array $c) => count($c['sources']))
            ->take(self::NEWS_MAX_ALERTS)
            ->map(function (array $c) {
                $count = count($c['sources']);

                return [
                    'id' => "news:{$c['seed']->id}",
                    'kind' => 'news',
                    'severity' => $count >= 5 ? 'critical' : 'high',
                    'title' => $c['seed']->title,
                    'summary' => "Reported by {$count} sources in the last ".self::NEWS_WINDOW_HOURS.' hours.',
                    'url' => $c['seed']->url,
                    'source' => $c['seed']->source->name,
                    'published_at' => $c['seed']->published_at?->toIso8601String(),
                    'members' => $c['members'],
                ];
            })
            ->values();

        $covered = $alerts->pluck('members')->flatten()->all();

        $marked = $articles
            ->filter(fn (NewsArticle $a) => preg_match(self::BREAKING_MARKER, $a->title) === 1 && ! in_array($a->id, $covered, true))
            ->sortByDesc('published_at')
            ->take(self::NEWS_MAX_ALERTS)
            ->map(fn (NewsArticle $a) => [
                'id' => "news:{$a->id}",
                'kind' => 'news',
                'severity' => 'info',
                'title' => $a->title,
                'summary' => null,
                'url' => $a->url,
                'source' => $a->source->name,
                'published_at' => $a->published_at?->toIso8601String(),
            ]);

        return $alerts
            ->map(fn (array $a) => array_diff_key($a, ['members' => true]))
            ->concat($marked)
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function significantWords(string $title): array
    {
        preg_match_all('/[a-z0-9]{4,}/', mb_strtolower($title), $m);

        return array_values(array_unique(array_diff($m[0], self::STOPWORDS)));
    }
}
