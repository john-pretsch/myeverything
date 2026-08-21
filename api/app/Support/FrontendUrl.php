<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Resolves which configured frontend origin (FRONTEND_URLS) an outgoing
 * link (e.g. a password reset email) should point at. Both the production
 * domain and localhost are valid simultaneously (see CLAUDE.md), so we
 * can't hardcode one — instead we trust the request's Origin header only
 * if it's already in the same allowlist CORS uses, and otherwise fall back
 * to the first configured origin.
 */
class FrontendUrl
{
    public static function resolve(Request $request): string
    {
        $allowed = array_filter(array_map(
            'trim',
            explode(',', (string) env('FRONTEND_URLS', 'http://localhost:3000')),
        ));

        $origin = $request->headers->get('Origin');

        if ($origin && in_array(rtrim($origin, '/'), array_map(fn ($url) => rtrim($url, '/'), $allowed), true)) {
            return rtrim($origin, '/');
        }

        return rtrim($allowed[array_key_first($allowed)] ?? 'http://localhost:3000', '/');
    }
}
