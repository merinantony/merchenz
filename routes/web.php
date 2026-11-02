<?php

use App\Http\Controllers\DashboardWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/merchants/1/dashboard');
});

Route::get('/merchants/{id}/dashboard', [DashboardWebController::class, 'show'])
    ->name('merchant.dashboard');

