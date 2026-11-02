<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Controller;
use App\Services\Market\MarketDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketController extends Controller
{
    public function overview(Request $request, MarketDataService $market): JsonResponse
    {
        return response()->json([
            'data' => $market->overview(fresh: $request->boolean('fresh')),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function stockProfile(string $symbol, MarketDataService $market): JsonResponse
    {
        $this->validateSymbol($symbol);

        return response()->json(['data' => $market->stockProfile($symbol)]);
    }

    public function stockChart(Request $request, string $symbol, MarketDataService $market): JsonResponse
    {
        $this->validateSymbol($symbol);

        $validated = $request->validate([
            'range' => ['sometimes', 'string', Rule::in(MarketDataService::chartRanges())],
        ]);

        $chart = $market->stockChart($symbol, $validated['range'] ?? '1y');

        if ($chart === null) {
            return response()->json(['message' => 'Price history is unavailable right now.'], 502);
        }

        return response()->json(['data' => $chart]);
    }

    /** Symbols are interpolated into upstream URLs, so only ticker-shaped input is allowed. */
    private function validateSymbol(string $symbol): void
    {
        abort_unless(preg_match('/^[A-Za-z0-9.\-]{1,12}$/', $symbol) === 1, 404);
    }
}
