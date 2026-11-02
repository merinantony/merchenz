<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Facades\Cache;

/**
 * Cache-aside service for Plan/pricing lookups.
 *
 * Invalidation Strategy:
 * - TTL-based: 10-minute expiry ensures eventual consistency even if invalidation is missed.
 * - Explicit invalidation: On plan create/update/delete, we call forget() to immediately
 *   remove stale data. This is done in PlanObserver (registered in AppServiceProvider).
 * - Cache key pattern: "plan:{id}" for individual plans, "merchant:{id}:plans" for merchant plan lists.
 *
 * Trade-off: We accept a 10-minute staleness window in exchange for dramatically reducing
 * DB reads on the hot path (every usage event needs to look up the plan for validation).
 * In production, we'd use Redis with pub/sub for cross-instance invalidation.
 */
class PlanCacheService
{
    /** @var int Cache TTL in seconds (10 minutes) */
    private const CACHE_TTL = 600;

    /**
     * Get a plan by ID, with caching.
     */
    public function find(int $planId): ?Plan
    {
        return Cache::remember(
            $this->planKey($planId),
            self::CACHE_TTL,
            fn () => Plan::find($planId)
        );
    }

    /**
     * Get a plan by ID or fail, with caching.
     */
    public function findOrFail(int $planId): Plan
    {
        return Cache::remember(
            $this->planKey($planId),
            self::CACHE_TTL,
            fn () => Plan::findOrFail($planId)
        );
    }

    /**
     * Get all active plans for a merchant, with caching.
     */
    public function getForMerchant(int $merchantId): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember(
            $this->merchantPlansKey($merchantId),
            self::CACHE_TTL,
            fn () => Plan::where('merchant_id', $merchantId)
                ->where('is_active', true)
                ->get()
        );
    }

    /**
     * Invalidate a specific plan's cache.
     */
    public function invalidate(Plan $plan): void
    {
        Cache::forget($this->planKey($plan->id));
        Cache::forget($this->merchantPlansKey($plan->merchant_id));
    }

    /**
     * Warm cache for a plan (e.g., after creation/update).
     */
    public function warm(Plan $plan): void
    {
        Cache::put($this->planKey($plan->id), $plan, self::CACHE_TTL);
    }

    private function planKey(int $planId): string
    {
        return "plan:{$planId}";
    }

    private function merchantPlansKey(int $merchantId): string
    {
        return "merchant:{$merchantId}:plans";
    }
}
