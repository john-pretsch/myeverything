<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\GigLeadController;
use App\Http\Controllers\Market\MarketController;
use App\Http\Controllers\News\FeedbackController;
use App\Http\Controllers\News\FeedController;
use App\Http\Controllers\News\SourceController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ResumeTailoringController;
use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;

Route::get('/config', [ConfigController::class, 'index']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/two-factor-challenge', [AuthController::class, 'twoFactorChallenge']);

Route::get('/auth/sso/redirect', [SsoController::class, 'redirect']);
Route::get('/auth/sso/callback', [SsoController::class, 'callback']);

Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])
    ->middleware('throttle:6,1');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])
    ->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::middleware('feature:two_factor_auth')->prefix('user/two-factor-authentication')->group(function () {
        Route::post('/', [TwoFactorAuthenticationController::class, 'store']);
        Route::delete('/', [TwoFactorAuthenticationController::class, 'destroy']);
        Route::post('/confirm', [TwoFactorAuthenticationController::class, 'confirm']);
        Route::get('/recovery-codes', [TwoFactorAuthenticationController::class, 'recoveryCodes']);
        Route::post('/recovery-codes', [TwoFactorAuthenticationController::class, 'regenerateRecoveryCodes']);
    });
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

Route::middleware('auth:sanctum')->prefix('todos')->group(function () {
    Route::get('/', [TodoController::class, 'index']);
    Route::post('/', [TodoController::class, 'store']);
    Route::patch('/reorder', [TodoController::class, 'reorder']);
    Route::patch('/{todo}', [TodoController::class, 'update']);
    Route::delete('/{todo}', [TodoController::class, 'destroy']);
    Route::post('/{todo}/toggle', [TodoController::class, 'toggle']);
});

Route::middleware('auth:sanctum')->prefix('gig-leads')->group(function () {
    Route::get('/', [GigLeadController::class, 'index']);
    Route::post('/', [GigLeadController::class, 'store']);
    Route::patch('/{gigLead}', [GigLeadController::class, 'update']);
    Route::post('/{gigLead}/fetch-details', [GigLeadController::class, 'fetchDetails']);
    Route::post('/{gigLead}/tailor-resume', [ResumeTailoringController::class, 'preview']);
    Route::post('/{gigLead}/tailor-resume/accept', [ResumeTailoringController::class, 'accept']);
    Route::delete('/{gigLead}', [GigLeadController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->prefix('resumes')->group(function () {
    Route::get('/', [ResumeController::class, 'index']);
    Route::post('/', [ResumeController::class, 'store']);
    Route::get('/{resume}/download', [ResumeController::class, 'download']);
    Route::delete('/{resume}', [ResumeController::class, 'destroy']);
});

// Reached via a signed URL (issued only inside the authenticated
// ResumeResource response), not auth:sanctum — see ResumeController::viewSigned.
Route::get('/resumes/{resume}/view-signed', [ResumeController::class, 'viewSigned'])
    ->middleware('signed')
    ->name('resumes.view-signed');
