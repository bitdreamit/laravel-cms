<?php

use App\Http\Controllers\Collab\CollabController;
use Illuminate\Support\Facades\Route;

// Yjs sync WebSocket route (handled by Reverb)
Route::get('/collab/{sessionId}/connect', [CollabController::class, 'connect']);
Route::post('/collab/{sessionId}/sync', [CollabController::class, 'sync']);
Route::post('/collab/{sessionId}/presence', [CollabController::class, 'presence']);
Route::post('/collab/{sessionId}/force-lock', [CollabController::class, 'forceLock']);
Route::delete('/collab/{sessionId}/disconnect', [CollabController::class, 'disconnect']);
