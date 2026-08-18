<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Market\MarketController;
use App\Http\Controllers\News\FeedbackController;
use App\Http\Controllers\News\FeedController;
use App\Http\Controllers\News\SourceController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
});

Route::prefix('news')->group(function () {
    Route::get('/sources', [SourceController::class, 'index']);
    Route::get('/feed', [FeedController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/sources', [SourceController::class, 'store']);
        Route::delete('/sources/{source}', [SourceController::class, 'destroy']);
        Route::patch('/sources/reorder', [SourceController::class, 'reorder']);
        Route::post('/articles/{article}/feedback', [FeedbackController::class, 'store']);
        Route::delete('/articles/{article}/feedback', [FeedbackController::class, 'destroy']);
    });
});

Route::get('/market/overview', [MarketController::class, 'overview']);
