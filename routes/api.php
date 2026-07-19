<?php

use App\Http\Controllers\Api\RagApiController;
use App\Http\Controllers\Api\ExperimentApiController;
use App\Http\Controllers\Api\FormAnalyticsController;
use Illuminate\Support\Facades\Route;

// Public RAG endpoint (chat widget)
Route::post('/rag/ask', [RagApiController::class, 'ask']);
Route::post('/rag/feedback/{queryId}', [RagApiController::class, 'feedback']);

// A/B Experiment tracking
Route::post('/experiments/{id}/convert', [ExperimentApiController::class, 'convert']);

// Form analytics event tracking (anonymous)
Route::post('/forms/{formId}/analytics-event', [FormAnalyticsController::class, 'track']);

// V3 Collections + Entries API ( Sanctum auth)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/collections/{handle}/entries', [\App\Http\Controllers\Api\EntryController::class, 'index']);
    Route::get('/collections/{handle}/entries/{slug}', [\App\Http\Controllers\Api\EntryController::class, 'show']);
    Route::post('/collections/{handle}/entries', [\App\Http\Controllers\Api\EntryController::class, 'store']);
    Route::put('/collections/{handle}/entries/{slug}', [\App\Http\Controllers\Api\EntryController::class, 'update']);
    Route::delete('/collections/{handle}/entries/{slug}', [\App\Http\Controllers\Api\EntryController::class, 'destroy']);

    // GraphQL
    Route::post('/graphql', [\App\Http\Controllers\Api\GraphQLController::class, 'handle']);
});
