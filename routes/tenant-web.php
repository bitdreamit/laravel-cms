<?php

use App\Http\Controllers\Public\EntryController;
use App\Http\Controllers\Public\FormController;
use Illuminate\Support\Facades\Route;

// Home page
Route::get('/', [EntryController::class, 'home']);

// Subdomain-to-collection routing (V4)
// If the current domain has a default_collection_handle set, / routes to that collection's index
$domain = app('current.domain');
if ($domain && $domain->default_collection_handle) {
    Route::get('/', [EntryController::class, 'collectionIndex']);
    Route::get('/{slug}', [EntryController::class, 'collectionShow'])->where('slug', '[a-z0-9\-]+');
    Route::get('/category/{term}', [EntryController::class, 'collectionTerm']);
} else {
    // Default V3 routing — collection routes
    Route::get('/{collectionHandle}', [EntryController::class, 'collectionIndex']);
    Route::get('/{collectionHandle}/{slug}', [EntryController::class, 'collectionShow'])->where('slug', '[a-z0-9\-]+');
}

// Form submissions (public)
Route::post('/forms/{formHandle}/submit', [FormController::class, 'submit']);

// Robots.txt (V4 supports per-domain override)
Route::get('/robots.txt', function () {
    $domain = app('current.domain');
    $override = $domain?->getConfigValue('robots_txt_override');
    return response($override ?? "User-agent: *\nDisallow: /admin/\n")->header('Content-Type', 'text/plain');
});
