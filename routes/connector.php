<?php

use App\Http\Controllers\Api\ConnectorController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Connector registration & SSO bridge
Route::post('/register', [ConnectorController::class, 'register']);
Route::post('/sso/bridge', [ConnectorController::class, 'ssoBridge']);
Route::get('/status', [ConnectorController::class, 'status']);

// Incoming webhooks from host apps
Route::post('/webhooks/incoming', [WebhookController::class, 'receive']);

// Webhook subscriptions
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/webhooks/subscriptions', [WebhookController::class, 'subscriptions']);
    Route::post('/webhooks/subscriptions', [WebhookController::class, 'subscribe']);
    Route::delete('/webhooks/subscriptions/{id}', [WebhookController::class, 'unsubscribe']);
});
