<?php

namespace App\Http\Controllers;

use App\Services\Alerts\AlertService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function alerts(AlertService $alerts): JsonResponse
    {
        return response()->json([
            'data' => $alerts->alerts(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }
}
