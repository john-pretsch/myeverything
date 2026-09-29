<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Controller;
use App\Services\Market\MarketDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function overview(Request $request, MarketDataService $market): JsonResponse
    {
        return response()->json([
            'data' => $market->overview(fresh: $request->boolean('fresh')),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
