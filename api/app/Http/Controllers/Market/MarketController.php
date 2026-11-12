<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
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

    public function movers(Request $request, MarketDataService $market): JsonResponse
    {
        $validated = $request->validate([
            'count' => ['sometimes', 'integer', Rule::in(MarketDataService::MOVER_COUNTS)],
            'region' => ['sometimes', 'string', Rule::in(MarketDataService::MOVER_REGIONS)],
        ]);

        return response()->json([
            'data' => $market->movers(
                $validated['count'] ?? MarketDataService::MOVER_COUNTS[0],
                $validated['region'] ?? MarketDataService::MOVER_REGIONS[0],
                $request->boolean('fresh'),
            ),
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

    public function preferences(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->moverPreferences($request)]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'movers_count' => ['required', 'integer', Rule::in(MarketDataService::MOVER_COUNTS)],
            'movers_region' => ['required', 'string', Rule::in(MarketDataService::MOVER_REGIONS)],
        ]);

        UserPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['preferences' => [...$this->savedPreferences($request), ...$validated]],
        );

        return response()->json(['data' => $this->moverPreferences($request)]);
    }

    private function savedPreferences(Request $request): array
    {
        return UserPreference::find($request->user()->id)?->preferences ?? [];
    }

    /** @return array{movers_count: int, movers_region: string} */
    private function moverPreferences(Request $request): array
    {
        $saved = $this->savedPreferences($request);

        return [
            'movers_count' => in_array($saved['movers_count'] ?? null, MarketDataService::MOVER_COUNTS, true)
                ? $saved['movers_count']
                : MarketDataService::MOVER_COUNTS[0],
            'movers_region' => in_array($saved['movers_region'] ?? null, MarketDataService::MOVER_REGIONS, true)
                ? $saved['movers_region']
                : MarketDataService::MOVER_REGIONS[0],
        ];
    }
}
