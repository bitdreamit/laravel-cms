<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Platform owner dashboard (central domain only)
Route::middleware(['auth'])->prefix('platform')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Platform\DashboardController::class, 'index'])->name('platform.dashboard');
    Route::get('/tenants', [\App\Http\Controllers\Platform\TenantController::class, 'index'])->name('platform.tenants.index');
    Route::get('/billing', [\App\Http\Controllers\Platform\BillingController::class, 'index'])->name('platform.billing.index');
    Route::get('/plans', [\App\Http\Controllers\Platform\PlanController::class, 'index'])->name('platform.plans.index');
});

// Admin redirect (if authenticated, go to dashboard, else go to login)
Route::get('/admin', function () {
    if (auth()->check()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('login');
})->name('admin.index');
