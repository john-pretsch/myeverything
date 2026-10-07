<?php

use App\Http\Controllers\Admin\TopicController as AdminTopicController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\BrainiacController;
use App\Http\Controllers\GigLeadController;
use App\Http\Controllers\Market\MarketController;
use App\Http\Controllers\News\FeedController;
use App\Http\Controllers\News\SourceController;
use App\Http\Controllers\News\TagController;
use App\Http\Controllers\News\TopicController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ResumeTailoringController;
use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [SsoController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
});

Route::prefix('news')->group(function () {
    Route::get('/sources', [SourceController::class, 'index']);
    Route::get('/feed', [FeedController::class, 'index']);
    Route::get('/tags', [TagController::class, 'index']);
    Route::get('/topics', [TopicController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/sources', [SourceController::class, 'store']);
        Route::delete('/sources/{source}', [SourceController::class, 'destroy']);
        Route::patch('/sources/reorder', [SourceController::class, 'reorder']);

        Route::post('/tags', [TagController::class, 'store']);
        Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
        Route::post('/tags/{tag}/vote', [TagController::class, 'vote']);
        Route::delete('/tags/{tag}/vote', [TagController::class, 'clearVote']);
        Route::post('/articles/{article}/tags', [TagController::class, 'attach']);
        Route::delete('/articles/{article}/tags/{tag}', [TagController::class, 'detach']);
    });
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/topics')->group(function () {
    Route::get('/', [AdminTopicController::class, 'index']);
    Route::post('/', [AdminTopicController::class, 'store']);
    Route::patch('/{topic}', [AdminTopicController::class, 'update']);
    Route::delete('/{topic}', [AdminTopicController::class, 'destroy']);
    Route::post('/{topic}/sources', [AdminTopicController::class, 'attachSource']);
    Route::delete('/{topic}/sources/{source}', [AdminTopicController::class, 'detachSource']);
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

Route::middleware('auth:sanctum')->prefix('brainiac')->group(function () {
    Route::get('/attempts', [BrainiacController::class, 'index']);
    Route::post('/attempts', [BrainiacController::class, 'store']);
    Route::get('/attempts/{attempt}', [BrainiacController::class, 'show']);
    Route::post('/attempts/{attempt}/answers', [BrainiacController::class, 'answer']);
    Route::post('/attempts/{attempt}/complete', [BrainiacController::class, 'complete']);
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
    Route::post('/profile-image', [ResumeController::class, 'storeProfileImage']);
    Route::get('/{resume}/download', [ResumeController::class, 'download']);
    Route::patch('/{resume}/primary', [ResumeController::class, 'makePrimary']);
    Route::delete('/{resume}', [ResumeController::class, 'destroy']);
});

// Reached via a signed URL (issued only inside the authenticated
// ResumeResource response), not auth:sanctum — see ResumeController::viewSigned.
Route::get('/resumes/{resume}/view-signed', [ResumeController::class, 'viewSigned'])
    ->middleware('signed')
    ->name('resumes.view-signed');

Route::get('/assets/images/{filename}', [ResumeController::class, 'image']);
