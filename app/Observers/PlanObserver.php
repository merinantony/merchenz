<?php

namespace App\Observers;

use App\Models\Plan;
use App\Services\PlanCacheService;

/**
 * Observer that invalidates the plan cache whenever a plan is modified.
 *
 * Registered in AppServiceProvider to ensure cache consistency.
 * Works in conjunction with the TTL-based expiry in PlanCacheService.
 */
class PlanObserver
{
    public function __construct(
        private readonly PlanCacheService $planCache
    ) {}

    public function created(Plan $plan): void
    {
        $this->planCache->invalidate($plan);
    }

    public function updated(Plan $plan): void
    {
        $this->planCache->invalidate($plan);
    }

    public function deleted(Plan $plan): void
    {
        $this->planCache->invalidate($plan);
    }
}
