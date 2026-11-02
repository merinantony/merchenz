<?php

use App\Http\Controllers\Api\DashboardMetricsController;
use App\Http\Controllers\Api\MerchantDashboardController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\UsageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rate limiting is applied to the usage endpoint via throttle middleware.
| The dashboard and subscription endpoints are not rate-limited for now,
| but could be in production.
|
*/

// Usage event recording — rate limited: 120 requests/minute per IP
Route::post('/usage', [UsageController::class, 'store'])
    ->middleware('throttle:usage');

// Merchant dashboard & metrics API
Route::get('/merchants/{id}/dashboard', [MerchantDashboardController::class, 'show']);
Route::get('/merchants/{id}/metrics', [DashboardMetricsController::class, 'show']);
Route::get('/dashboard/metrics', [DashboardMetricsController::class, 'index']);

// Subscription management
Route::post('/subscriptions/{id}/change-plan', [SubscriptionController::class, 'changePlan']);
