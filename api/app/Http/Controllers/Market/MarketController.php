<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Controller;
use App\Services\Market\MarketDataService;
use Illuminate\Http\JsonResponse;

class MarketController extends Controller
{
    public function overview(MarketDataService $market): JsonResponse
    {
        return response()->json([
            'data' => $market->overview(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
