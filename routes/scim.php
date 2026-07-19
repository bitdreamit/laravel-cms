<?php

use App\Http\Controllers\Api\Scim\UserController as ScimUserController;
use App\Http\Controllers\Api\Scim\GroupController as ScimGroupController;
use Illuminate\Support\Facades\Route;

// SCIM 2.0 User endpoints
Route::get('/Users', [ScimUserController::class, 'index']);
Route::post('/Users', [ScimUserController::class, 'store']);
Route::get('/Users/{id}', [ScimUserController::class, 'show']);
Route::put('/Users/{id}', [ScimUserController::class, 'update']);
Route::patch('/Users/{id}', [ScimUserController::class, 'patch']);
Route::delete('/Users/{id}', [ScimUserController::class, 'destroy']);

// SCIM 2.0 Group endpoints
Route::get('/Groups', [ScimGroupController::class, 'index']);
Route::post('/Groups', [ScimGroupController::class, 'store']);
Route::get('/Groups/{id}', [ScimGroupController::class, 'show']);
Route::put('/Groups/{id}', [ScimGroupController::class, 'update']);
Route::patch('/Groups/{id}', [ScimGroupController::class, 'patch']);
Route::delete('/Groups/{id}', [ScimGroupController::class, 'destroy']);
