<?php

namespace App\Http\Controllers;

class ConfigController extends Controller
{
    public function index()
    {
        return response()->json([
            'features' => [
                'two_factor_auth' => config('features.two_factor_auth'),
            ],
        ]);
    }
}
