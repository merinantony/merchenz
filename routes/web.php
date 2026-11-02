<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardWebController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\UserPlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/merchants/1/dashboard');
});

Route::get('/merchants/{id}/dashboard', [DashboardWebController::class, 'show'])
    ->name('merchant.dashboard');

// Multi-Tenant SaaS Onboarding & Registration
Route::get('/onboarding', [OnboardingController::class, 'create'])->name('onboarding.create');
Route::get('/register', [OnboardingController::class, 'create'])->name('register');
Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

// Unified Authentication (Super Admin, Tenant Admin, Regular User)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Super Admin Global Platform Monitoring (bypasses tenant scope, sees all users and all tenants)
Route::get('/super-admin/dashboard', [SuperAdminController::class, 'dashboard'])
    ->middleware(['auth', 'role:super_admin'])
    ->name('superadmin.dashboard');

// Regular Tenant User (Restricted to assigned plan only)
Route::get('/my-plan', [UserPlanController::class, 'show'])
    ->middleware(['auth'])
    ->name('user.plan');

// System Availability & Error Preview Routes
Route::get('/unavailable', function () {
    return response()->view('errors.503', [], 503);
})->name('system.unavailable');
