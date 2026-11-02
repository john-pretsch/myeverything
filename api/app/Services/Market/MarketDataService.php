<?php

namespace App\Services\Market;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketDataService
{
    private const USER_AGENT = 'Mozilla/5.0 (compatible; myeverything-dashboard/1.0)';

    /** @var array<int, array{code: string, name: string}> */
    private const CURRENCIES = [
        ['code' => 'CAD', 'name' => 'Canadian Dollar'],
        ['code' => 'EUR', 'name' => 'Euro'],
        ['code' => 'GBP', 'name' => 'British Pound'],
        ['code' => 'JPY', 'name' => 'Japanese Yen'],
        ['code' => 'AUD', 'name' => 'Australian Dollar'],
        ['code' => 'CHF', 'name' => 'Swiss Franc'],
    ];

    /** @var array<int, array{id: string, symbol: string, name: string}> */
    private const CRYPTO = [
        ['id' => 'bitcoin', 'symbol' => 'BTC', 'name' => 'Bitcoin'],
        ['id' => 'ethereum', 'symbol' => 'ETH', 'name' => 'Ethereum'],
        ['id' => 'solana', 'symbol' => 'SOL', 'name' => 'Solana'],
        ['id' => 'ripple', 'symbol' => 'XRP', 'name' => 'XRP'],
        ['id' => 'dogecoin', 'symbol' => 'DOGE', 'name' => 'Dogecoin'],
    ];

    /** @var array<int, array{symbol: string, name: string, exchange: string}> */
    private const INDICES = [
        ['symbol' => '^GSPTSE', 'name' => 'S&P/TSX Composite', 'exchange' => 'TSX'],
        ['symbol' => '^NYA', 'name' => 'NYSE Composite', 'exchange' => 'NYSE'],
        ['symbol' => '^IXIC', 'name' => 'NASDAQ Composite', 'exchange' => 'NASDAQ'],
    ];

    public function overview(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('market:currencies');
            Cache::forget('market:crypto');
            Cache::forget('market:indices');
            Cache::forget('market:movers');
        }

        return [
            'currencies' => $this->currencies(),
            'crypto' => $this->crypto(),
            'indices' => $this->indices(),
            'movers' => $this->movers(),
        ];
    }

    private function currencies(): array
    {
        return Cache::remember('market:currencies', now()->addMinutes(5), function () {
            try {
                $symbols = implode(',', array_column(self::CURRENCIES, 'code'));
                $response = Http::timeout(10)
                    ->withUserAgent(self::USER_AGENT)
                    ->get('https://api.frankfurter.dev/v1/latest', [
                        'base' => 'USD',
                        'symbols' => $symbols,
                    ]);

                if (! $response->successful()) {
                    throw new \RuntimeException("HTTP {$response->status()}");
                }

                $rates = $response->json('rates', []);

                $data = [];
                foreach (self::CURRENCIES as $currency) {
                    if (isset($rates[$currency['code']])) {
                        $data[] = [
                            'code' => $currency['code'],
                            'name' => $currency['name'],
                            'rate' => $rates[$currency['code']],
                        ];
                    }
                }

                return ['base' => 'USD', 'rates' => $data, 'error' => null];
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch currency rates', ['error' => $e->getMessage()]);

                return ['base' => 'USD', 'rates' => [], 'error' => 'Currency rates are unavailable right now.'];
            }
        });
    }

    private function crypto(): array
    {
        return Cache::remember('market:crypto', now()->addMinutes(2), function () {
            try {
                $ids = implode(',', array_column(self::CRYPTO, 'id'));
                $response = Http::timeout(10)
                    ->withUserAgent(self::USER_AGENT)
                    ->get('https://api.coingecko.com/api/v3/simple/price', [
                        'ids' => $ids,
                        'vs_currencies' => 'usd',
                        'include_24hr_change' => 'true',
                    ]);

                if (! $response->successful()) {
                    throw new \RuntimeException("HTTP {$response->status()}");
                }

                $prices = $response->json();

                $data = [];
                foreach (self::CRYPTO as $coin) {
                    if (isset($prices[$coin['id']])) {
                        $data[] = [
                            'symbol' => $coin['symbol'],
                            'name' => $coin['name'],
                            'price_usd' => $prices[$coin['id']]['usd'] ?? null,
                            'change_24h_pct' => $prices[$coin['id']]['usd_24h_change'] ?? null,
                        ];
                    }
                }

                return ['items' => $data, 'error' => null];
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch crypto prices', ['error' => $e->getMessage()]);

                return ['items' => [], 'error' => 'Crypto prices are unavailable right now.'];
            }
        });
    }

    private function indices(): array
    {
        return Cache::remember('market:indices', now()->addMinutes(5), function () {
            $data = [];
            $failures = 0;

            foreach (self::INDICES as $index) {
                try {
                    $response = Http::timeout(10)
                        ->withUserAgent(self::USER_AGENT)
                        ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$index['symbol']}");

                    if (! $response->successful()) {
                        throw new \RuntimeException("HTTP {$response->status()}");
                    }

                    $meta = $response->json('chart.result.0.meta');

                    if (! $meta || ! isset($meta['regularMarketPrice'])) {
                        throw new \RuntimeException('Missing quote data');
                    }

                    $price = $meta['regularMarketPrice'];
                    $previousClose = $meta['previousClose'] ?? $meta['chartPreviousClose'] ?? null;
                    $changePct = $previousClose
                        ? (($price - $previousClose) / $previousClose) * 100
                        : null;

                    $data[] = [
                        'symbol' => $index['symbol'],
                        'name' => $index['name'],
                        'exchange' => $index['exchange'],
                        'price' => $price,
                        'previous_close' => $previousClose,
                        'change_pct' => $changePct,
                        'currency' => $meta['currency'] ?? null,
                    ];
                } catch (\Throwable $e) {
                    $failures++;
                    Log::warning("Failed to fetch index {$index['symbol']}", ['error' => $e->getMessage()]);
                }
            }

            return [
                'items' => $data,
                'error' => $failures > 0 && $data === [] ? 'Market indices are unavailable right now.' : null,
            ];
        });
    }

    private function movers(): array
    {
        return Cache::remember('market:movers', now()->addMinutes(5), function () {
            $gainers = $this->fetchMoverScreen('day_gainers');
            $losers = $this->fetchMoverScreen('day_losers');

            return [
                'gainers' => $gainers ?? [],
                'losers' => $losers ?? [],
                'error' => $gainers === null && $losers === null
                    ? 'Top movers are unavailable right now.'
                    : null,
            ];
        });
    }

    /**
     * @return array<int, array{symbol: string, name: string, price: float, change_pct: float, exchange: ?string}>|null
     */
    private function fetchMoverScreen(string $screenId): ?array
    {
        try {
            $response = Http::timeout(10)
                ->withUserAgent(self::USER_AGENT)
                ->get('https://query1.finance.yahoo.com/v1/finance/screener/predefined/saved', [
                    'formatted' => 'false',
                    'lang' => 'en-US',
                    'region' => 'US',
                    'scrIds' => $screenId,
                    'count' => 5,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException("HTTP {$response->status()}");
            }

            $quotes = $response->json('finance.result.0.quotes', []);

            return collect($quotes)
                ->filter(fn ($quote) => isset($quote['symbol'], $quote['regularMarketPrice'], $quote['regularMarketChangePercent']))
                ->map(fn ($quote) => [
                    'symbol' => $quote['symbol'],
                    'name' => $quote['shortName'] ?? $quote['longName'] ?? $quote['symbol'],
                    'price' => $quote['regularMarketPrice'],
                    'change_pct' => $quote['regularMarketChangePercent'],
                    'exchange' => $quote['fullExchangeName'] ?? null,
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning("Failed to fetch market movers ({$screenId})", ['error' => $e->getMessage()]);

            return null;
        }
    }

    /** Chart range => Yahoo bar interval, and how long a cached chart stays fresh (minutes). */
    private const CHART_RANGES = [
        '1d' => ['interval' => '5m', 'ttl' => 2],
        '5d' => ['interval' => '15m', 'ttl' => 5],
        '1mo' => ['interval' => '1d', 'ttl' => 15],
        '3mo' => ['interval' => '1d', 'ttl' => 30],
        '6mo' => ['interval' => '1d', 'ttl' => 30],
        'ytd' => ['interval' => '1d', 'ttl' => 30],
        '1y' => ['interval' => '1d', 'ttl' => 30],
        '5y' => ['interval' => '1wk', 'ttl' => 60],
        'max' => ['interval' => '1mo', 'ttl' => 60],
    ];

    public static function chartRanges(): array
    {
        return array_keys(self::CHART_RANGES);
    }

    /**
     * Company profile for the stock detail modal. Cached for a day; returns
     * null fields (never throws) when the provider is unavailable.
     *
     * @return array{symbol: string, description: ?string, sector: ?string, industry: ?string, website: ?string}|null
     */
    public function stockProfile(string $symbol): ?array
    {
        $symbol = strtoupper($symbol);

        return Cache::remember("market:profile:{$symbol}", now()->addDay(), function () use ($symbol) {
            try {
                $profile = $this->yahooAuthenticated(
                    "https://query1.finance.yahoo.com/v10/finance/quoteSummary/{$symbol}",
                    ['modules' => 'assetProfile'],
                )->json('quoteSummary.result.0.assetProfile');

                if (! $profile) {
                    return null;
                }

                return [
                    'symbol' => $symbol,
                    'description' => isset($profile['longBusinessSummary'])
                        ? $this->briefSummary($profile['longBusinessSummary'])
                        : null,
                    'sector' => $profile['sector'] ?? null,
                    'industry' => $profile['industry'] ?? null,
                    'website' => $profile['website'] ?? null,
                ];
            } catch (\Throwable $e) {
                Log::warning("Failed to fetch stock profile {$symbol}", ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    /**
     * Price history for one chart range.
     *
     * @return array{symbol: string, range: string, currency: ?string, previous_close: ?float, points: array<int, array{t: int, c: float}>}|null
     */
    public function stockChart(string $symbol, string $range): ?array
    {
        $symbol = strtoupper($symbol);
        $config = self::CHART_RANGES[$range];

        return Cache::remember("market:chart:{$symbol}:{$range}", now()->addMinutes($config['ttl']), function () use ($symbol, $range, $config) {
            try {
                $response = Http::timeout(10)
                    ->withUserAgent(self::USER_AGENT)
                    ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}", [
                        'range' => $range,
                        'interval' => $config['interval'],
                    ]);

                if (! $response->successful()) {
                    throw new \RuntimeException("HTTP {$response->status()}");
                }

                $result = $response->json('chart.result.0');
                $timestamps = $result['timestamp'] ?? [];
                $closes = $result['indicators']['quote'][0]['close'] ?? [];

                $points = [];
                foreach ($timestamps as $i => $t) {
                    if (isset($closes[$i])) {
                        $points[] = ['t' => $t, 'c' => round($closes[$i], 4)];
                    }
                }

                if ($points === []) {
                    throw new \RuntimeException('No price history');
                }

                return [
                    'symbol' => $symbol,
                    'range' => $range,
                    'currency' => $result['meta']['currency'] ?? null,
                    'previous_close' => $result['meta']['chartPreviousClose'] ?? null,
                    'points' => $points,
                ];
            } catch (\Throwable $e) {
                Log::warning("Failed to fetch stock chart {$symbol} {$range}", ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    /** First few sentences of a company summary, capped in length. */
    private function briefSummary(string $text): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+(?=[A-Z])/u', trim($text)) ?: [$text];
        $brief = '';

        foreach (array_slice($sentences, 0, 3) as $sentence) {
            if ($brief !== '' && mb_strlen($brief.' '.$sentence) > 450) {
                break;
            }
            $brief = trim($brief.' '.$sentence);
        }

        return mb_strlen($brief) > 500 ? rtrim(mb_substr($brief, 0, 497)).'...' : $brief;
    }

    /**
     * GET a Yahoo endpoint that requires a session cookie + crumb (the
     * quoteSummary family). The crumb is cached; on a 401 it is re-fetched
     * once before giving up.
     */
    private function yahooAuthenticated(string $url, array $query): \Illuminate\Http\Client\Response
    {
        foreach ([false, true] as $forceNew) {
            [$cookie, $crumb] = $this->yahooSession($forceNew);

            $response = Http::timeout(10)
                ->withUserAgent(self::USER_AGENT)
                ->withHeaders(['Cookie' => $cookie])
                ->get($url, $query + ['crumb' => $crumb]);

            if ($response->status() !== 401) {
                break;
            }
        }

        if (! $response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()}");
        }

        return $response;
    }

    /** @return array{0: string, 1: string} [cookie header value, crumb] */
    private function yahooSession(bool $forceNew): array
    {
        if ($forceNew) {
            Cache::forget('market:yahoo-session');
        }

        return Cache::remember('market:yahoo-session', now()->addHour(), function () {
            $cookieResponse = Http::timeout(10)->withUserAgent(self::USER_AGENT)->get('https://fc.yahoo.com');

            $cookie = collect($cookieResponse->cookies()->toArray())
                ->map(fn ($c) => "{$c['Name']}={$c['Value']}")
                ->implode('; ');

            if ($cookie === '') {
                throw new \RuntimeException('No Yahoo session cookie');
            }

            $crumb = Http::timeout(10)
                ->withUserAgent(self::USER_AGENT)
                ->withHeaders(['Cookie' => $cookie])
                ->get('https://query1.finance.yahoo.com/v1/test/getcrumb')
                ->body();

            if ($crumb === '' || str_contains($crumb, '<')) {
                throw new \RuntimeException('No Yahoo crumb');
            }

            return [$cookie, $crumb];
        });
    }
}
