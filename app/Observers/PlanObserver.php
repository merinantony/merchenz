<?php

namespace App\Observers;

use App\Models\Plan;
use App\Services\PlanCacheService;
use Illuminate\Support\Facades\Cache;

/**
 * Eloquent Observer for Plan model.
 *
 * Clears plan pricing and lookup cache keys whenever a Plan's price or attributes are updated.
 *
 * Cache keys invalidated:
 * - "plan:{id}"
 * - "plan:pricing:{id}"
 * - "merchant:{merchant_id}:plans"
 * - "dashboard:metrics:{merchant_id}"
 * - "dashboard:{merchant_id}"
 */
class PlanObserver
{
    public function __construct(
        private readonly PlanCacheService $planCache
    ) {}

    /**
     * Handle the Plan "created" event.
     */
    public function created(Plan $plan): void
    {
        $this->planCache->invalidate($plan);
        $this->clearDashboardCache($plan->merchant_id);
    }

    /**
     * Handle the Plan "updated" event.
     *
     * Clears this specific plan's cache keys whenever a Plan's price
     * (base_price, overage_rate_per_unit, etc.) is updated.
     */
    public function updated(Plan $plan): void
    {
        $this->planCache->invalidate($plan);
        $this->clearDashboardCache($plan->merchant_id);
    }

    /**
     * Handle the Plan "deleted" event.
     */
    public function deleted(Plan $plan): void
    {
        $this->planCache->invalidate($plan);
        $this->clearDashboardCache($plan->merchant_id);
    }

    /**
     * Clear merchant-level dashboard cache when plan pricing changes.
     */
    private function clearDashboardCache(?int $merchantId): void
    {
        if ($merchantId) {
            Cache::forget("dashboard:{$merchantId}");
            Cache::forget("dashboard:metrics:{$merchantId}");
        }
    }
}
