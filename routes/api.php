<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardMetricsController;
use App\Http\Controllers\Api\MerchantDashboardController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\UsageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — MerchenZ Metering Engine
|--------------------------------------------------------------------------
|
| - Authentication via Laravel Sanctum (Bearer Token)
| - Rate-limited usage ingestion endpoint (120 req/min per key/IP)
| - Merchant / Admin persona: Plan definition & Dashboard metrics
| - User / Customer persona: Subscription & Usage generation
|
*/

// ==========================================
// 1. Authentication (Laravel Sanctum)
// ==========================================
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/register', [AuthController::class, 'register']); // Alias for Postman simplicity

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/login', [AuthController::class, 'login']); // Alias for Postman simplicity

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);
});

// ==========================================
// 2. Usage Event Ingestion (Rate-Limited: 120 req/min, Idempotent)
// ==========================================
Route::post('/usage', [UsageController::class, 'store'])
    ->middleware('throttle:usage');

// ==========================================
// 3. Plan Definition (Admin / Merchant Persona)
// ==========================================
Route::get('/plans', [PlanController::class, 'index']);
Route::get('/plans/{id}', [PlanController::class, 'show']);
Route::post('/plans', [PlanController::class, 'store']);
Route::put('/plans/{id}', [PlanController::class, 'update']);

// ==========================================
// 4. Subscriptions & Mid-Cycle Changes (User / Customer Persona)
// ==========================================
Route::post('/subscriptions', [SubscriptionController::class, 'store']);
Route::post('/subscriptions/{id}/change-plan', [SubscriptionController::class, 'changePlan']);

// ==========================================
// 5. Merchant Dashboard & Analytics Metrics (JSON)
// ==========================================
Route::get('/merchants/{id}/dashboard', [MerchantDashboardController::class, 'show']);
Route::get('/merchants/{id}/metrics', [DashboardMetricsController::class, 'show']);
Route::get('/dashboard/metrics', [DashboardMetricsController::class, 'index']);
