<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Cache-aside service for Plan/pricing lookups using Redis.
 *
 * Cache Keys:
 * - "plan:{id}": Cached Plan Eloquent model
 * - "plan:pricing:{id}": Cached pricing attributes array
 * - "merchant:{id}:plans": Active plans for a merchant
 *
 * Invalidation Strategy:
 * - TTL-based: 10-minute expiry (600s) ensures eventual consistency.
 * - Explicit invalidation: On plan create/update/delete, PlanObserver immediately
 *   clears the specific cache keys to guarantee zero stale price reads.
 */
class PlanCacheService
{
    /** @var int Cache TTL in seconds (10 minutes) */
    public const CACHE_TTL = 600;

    /**
     * Get a plan by ID, with Redis caching.
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
     * Get a plan by ID or fail, with Redis caching.
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
     * Get pricing lookup metadata with Redis caching.
     *
     * @return array{id: int, name: string, base_price: float, overage_rate_per_unit: float, included_units: int, billing_cycle_days: int}|null
     */
    public function getPricing(int $planId): ?array
    {
        return Cache::remember(
            $this->pricingKey($planId),
            self::CACHE_TTL,
            function () use ($planId) {
                $plan = Plan::find($planId);
                if (!$plan) {
                    return null;
                }

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'base_price' => (float) $plan->base_price,
                    'overage_rate_per_unit' => (float) $plan->overage_rate_per_unit,
                    'included_units' => (int) ($plan->included_units ?: ($plan->included_usage_units ?: 0)),
                    'billing_cycle_days' => (int) ($plan->billing_cycle ?: ($plan->billing_cycle_days ?: 30)),
                ];
            }
        );
    }

    /**
     * Get all active plans for a merchant, with Redis caching.
     */
    public function getForMerchant(int $merchantId): Collection
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
     * Invalidate all cache keys for a specific plan.
     */
    public function invalidate(Plan $plan): void
    {
        Cache::forget($this->planKey($plan->id));
        Cache::forget($this->pricingKey($plan->id));
        Cache::forget("plan:{$plan->id}:pricing");
        Cache::forget($this->merchantPlansKey($plan->merchant_id));
    }

    /**
     * Clear plan cache by model or ID.
     */
    public function clearPlanCache(int|Plan $plan): void
    {
        $id = $plan instanceof Plan ? $plan->id : $plan;
        $merchantId = $plan instanceof Plan ? $plan->merchant_id : Plan::find($id)?->merchant_id;

        Cache::forget($this->planKey($id));
        Cache::forget($this->pricingKey($id));
        Cache::forget("plan:{$id}:pricing");

        if ($merchantId) {
            Cache::forget($this->merchantPlansKey($merchantId));
        }
    }

    /**
     * Warm cache for a plan (e.g., after creation/update).
     */
    public function warm(Plan $plan): void
    {
        Cache::put($this->planKey($plan->id), $plan, self::CACHE_TTL);
    }

    public function planKey(int $planId): string
    {
        return "plan:{$planId}";
    }

    public function pricingKey(int $planId): string
    {
        return "plan:pricing:{$planId}";
    }

    public function merchantPlansKey(int $merchantId): string
    {
        return "merchant:{$merchantId}:plans";
    }
}
