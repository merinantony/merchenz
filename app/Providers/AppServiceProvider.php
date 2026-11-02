<?php

namespace App\Providers;

use App\Models\Plan;
use App\Observers\PlanObserver;
use App\Services\PlanCacheService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind PlanCacheService as a singleton so all consumers share the same instance
        $this->app->singleton(PlanCacheService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register the Plan observer for cache invalidation on plan mutations
        Plan::observe(PlanObserver::class);
    }
}
