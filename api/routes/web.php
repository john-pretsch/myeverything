<?php

use App\Http\Controllers\Auth\SsoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// These live under /api/ (nginx sends /api/* here) but are registered as web
// routes on purpose: the callback's Referer is sso.jepflow.io, which isn't a
// Sanctum stateful domain, so under statefulApi() it would get no session.
Route::get('/api/auth/sso/redirect', [SsoController::class, 'redirect']);
Route::get('/api/auth/sso/callback', [SsoController::class, 'callback']);
