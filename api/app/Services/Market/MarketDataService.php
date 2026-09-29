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
}
