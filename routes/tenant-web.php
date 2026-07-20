<?php

use App\Http\Controllers\Public\EntryController;
use App\Http\Controllers\Public\FormController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Web Routes
|--------------------------------------------------------------------------
|
| These routes serve the public-facing tenant website.
| The subdomain-to-collection routing decision is made inside the
| EntryController at request time, NOT here at route-registration time.
| This is critical: route files must NOT call app()->bound('current.domain') ? (app()->bound('current.domain') ? app('current.domain') : null) : null at
| the top level, because that would fail during composer install /
| package:discover when no request context exists.
|
*/

// Home page — controller decides whether to show home or collection index
Route::get('/', [EntryController::class, 'home']);

// Collection routing — the controller handles both modes:
//   - Subdomain mode: /{slug} → entry in default_collection_handle
//   - Standard mode:  /{collectionHandle}/{slug} → entry in that collection
Route::get('/{slug}', [EntryController::class, 'collectionShow'])
    ->where('slug', '[a-z0-9\-]+');

// Category/term pages
Route::get('/category/{term}', [EntryController::class, 'collectionTerm']);

// Form submissions (public)
Route::post('/forms/{formHandle}/submit', [FormController::class, 'submit']);

// Robots.txt (V4 supports per-domain override)
Route::get('/robots.txt', function () {
    $domain = app()->bound('current.domain') ? (app()->bound('current.domain') ? app('current.domain') : null) : null;
    $override = $domain?->getConfigValue('robots_txt_override');
    return response($override ?? "User-agent: *\nDisallow: /admin/\n")
        ->header('Content-Type', 'text/plain');
});
