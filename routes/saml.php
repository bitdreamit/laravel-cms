<?php

use App\Http\Controllers\Auth\SamlController;
use Illuminate\Support\Facades\Route;

Route::get('/saml/metadata/{idpId}', [SamlController::class, 'metadata']);
Route::get('/saml/login/{idpId}', [SamlController::class, 'login']);
Route::post('/saml/acs', [SamlController::class, 'acs']);  // Assertion Consumer Service
Route::get('/saml/sls', [SamlController::class, 'sls']);  // Single Logout Service
Route::post('/saml/sls', [SamlController::class, 'sls']);
