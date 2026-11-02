<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DailyUsageAggregation;
use App\Models\DailyUsageSummary;
use App\Models\Merchant;
use App\Models\Subscription;
use App\Models\UsageEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * DashboardMetricsService
 *
 * Provides real-time and cached dashboard analytics for merchants:
 * 1. Current cycle usage vs allowance.
 * 2. Top 5 customers by usage this cycle.
 * 3. Projected overage revenue.
 * 4. Churn Risk: Customers whose usage dropped >50% month-over-month.
 */
class DashboardMetricsService
{
    protected const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        protected readonly BillingService $billingService,
        protected readonly PlanCacheService $planCache
    ) {}

    /**
     * Get aggregated metrics for a merchant returning JSON-ready array.
     *
     * @return array<string, mixed>
     */
    public function getMetrics(int $merchantId): array
    {
        $cacheKey = "dashboard:metrics:{$merchantId}";

        return Cache::remember($cacheKey, static::CACHE_TTL, function () use ($merchantId) {
            $merchant = Merchant::findOrFail($merchantId);

            return [
                'merchant' => [
                    'id' => $merchant->id,
                    'name' => $merchant->name,
                ],
                'current_cycle_usage_vs_allowance' => $this->getCurrentCycleUsageVsAllowance($merchantId),
                'top_customers' => $this->getTopCustomers($merchantId, 5),
                'projected_overage' => $this->getProjectedOverageRevenue($merchantId),
                'churn_risk' => $this->getChurnRiskCustomers($merchantId),
                'system_status' => $this->getSystemStatus(),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Alias for backward compatibility with getDashboard().
     *
     * @return array<string, mixed>
     */
    public function getDashboard(int $merchantId): array
    {
        return $this->getMetrics($merchantId);
    }

    /**
     * Metric 1: Current cycle usage vs allowance.
     *
     * Compares actual recorded usage against included plan allowance
     * across all active subscriptions in the current billing cycle.
     *
     * @return array<string, mixed>
     */
    public function getCurrentCycleUsageVsAllowance(int $merchantId): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $subscriptions = Subscription::query()
            ->join('customers', 'subscriptions.customer_id', '=', 'customers.id')
            ->where('customers.merchant_id', $merchantId)
            ->where('subscriptions.status', 'active')
            ->select('subscriptions.*')
            ->get();

        $totalUsage = 0;
        $totalAllowance = 0;
        $customerBreakdown = [];

        foreach ($subscriptions as $subscription) {
            $cycleStart = $subscription->started_at ?? $startOfMonth;

            $plan = $this->planCache->find($subscription->plan_id);
            $includedUnits = $plan ? ($plan->included_units ?: ($plan->included_usage_units ?: 0)) : 0;

            $usage = $this->getUsageForCustomerPeriod(
                $subscription->customer_id,
                $cycleStart,
                Carbon::now()
            );

            $totalUsage += $usage;
            $totalAllowance += $includedUnits;

            $customer = $subscription->customer ?? Customer::find($subscription->customer_id);

            $allowancePct = $includedUnits > 0
                ? round(($usage / $includedUnits) * 100, 1)
                : 0.0;

            $customerBreakdown[] = [
                'customer_id' => $subscription->customer_id,
                'customer_name' => $customer?->name ?? "Customer #{$subscription->customer_id}",
                'usage' => $usage,
                'included_allowance' => $includedUnits,
                'allowance_pct' => $allowancePct,
                'remaining_units' => max(0, $includedUnits - $usage),
            ];
        }

        // If no active subscriptions are found, sum direct customer usage in current cycle
        if ($subscriptions->isEmpty()) {
            $customers = Customer::where('merchant_id', $merchantId)->get();
            foreach ($customers as $customer) {
                $usage = $this->getUsageForCustomerPeriod($customer->id, $startOfMonth, Carbon::now());
                $totalUsage += $usage;
            }
        }

        $overallPct = $totalAllowance > 0
            ? round(($totalUsage / $totalAllowance) * 100, 1)
            : 0.0;

        return [
            'usage_so_far' => $totalUsage,
            'total_usage' => $totalUsage,
            'included_allowance' => $totalAllowance,
            'total_allowance' => $totalAllowance,
            'allowance_pct' => $overallPct,
            'usage_percentage' => $overallPct,
            'remaining_units' => max(0, $totalAllowance - $totalUsage),
            'active_subscriptions_count' => $subscriptions->count(),
            'customer_breakdown' => $customerBreakdown,
        ];
    }

    /**
     * Metric 2: Top 5 customers by usage this cycle.
     *
     * @return array<int, array{customer_id: int, name: string, usage: int, included_units: int, allowance_pct: float}>
     */
    public function getTopCustomers(int $merchantId, int $limit = 5): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $customers = Customer::where('merchant_id', $merchantId)->get();
        $ranked = [];

        foreach ($customers as $customer) {
            $usage = $this->getUsageForCustomerPeriod($customer->id, $startOfMonth, $endOfMonth);
            if ($usage > 0) {
                $subscription = $customer->activeSubscription;
                $plan = $subscription ? $this->planCache->find($subscription->plan_id) : null;
                $includedUnits = $plan ? ($plan->included_units ?: ($plan->included_usage_units ?: 0)) : 0;

                $ranked[] = [
                    'customer_id' => $customer->id,
                    'name' => $customer->name,
                    'usage' => (int) $usage,
                    'included_units' => (int) $includedUnits,
                    'allowance_pct' => $includedUnits > 0
                        ? round(($usage / $includedUnits) * 100, 1)
                        : 0.0,
                ];
            }
        }

        usort($ranked, fn ($a, $b) => $b['usage'] <=> $a['usage']);

        return array_slice($ranked, 0, $limit);
    }

    /**
     * Metric 3: Projected overage revenue for the current cycle.
     *
     * @return array<string, mixed>
     */
    public function getProjectedOverageRevenue(int $merchantId): array
    {
        $subscriptions = Subscription::query()
            ->join('customers', 'subscriptions.customer_id', '=', 'customers.id')
            ->where('customers.merchant_id', $merchantId)
            ->where('subscriptions.status', 'active')
            ->select('subscriptions.*')
            ->get();

        $totalProjectedRevenue = 0.0;
        $totalUsageSoFar = 0;
        $totalIncludedUnits = 0;
        $totalProjectedTotal = 0;
        $totalProjectedOverageUnits = 0;
        $activePlan = null;

        foreach ($subscriptions as $subscription) {
            $projection = $this->billingService->projectOverage($subscription);
            $totalProjectedRevenue += (float) ($projection['projected_overage_revenue'] ?? 0);
            $totalUsageSoFar += (int) ($projection['usage_so_far'] ?? 0);
            $totalIncludedUnits += (int) ($projection['included_units'] ?? 0);
            $totalProjectedTotal += (int) ($projection['projected_total'] ?? 0);
            $totalProjectedOverageUnits += (int) ($projection['projected_overage_units'] ?? 0);

            if (!$activePlan) {
                $activePlan = $this->planCache->find($subscription->plan_id);
            }
        }

        return [
            'projected_overage_revenue' => round($totalProjectedRevenue, 2),
            'total_projected_overage_revenue' => round($totalProjectedRevenue, 2),
            'total_usage_so_far' => $totalUsageSoFar,
            'usage_so_far' => $totalUsageSoFar,
            'total_included_units' => $totalIncludedUnits,
            'included_units' => $totalIncludedUnits,
            'projected_total_usage' => $totalProjectedTotal,
            'projected_overage_units' => $totalProjectedOverageUnits,
            'active_plan' => $activePlan ? [
                'name' => $activePlan->name,
                'billing_cycle_days' => $activePlan->billing_cycle_days,
                'base_price' => (float) $activePlan->base_price,
                'overage_rate_per_unit' => (float) $activePlan->overage_rate_per_unit,
            ] : null,
        ];
    }

    /**
     * Metric 4: Churn Risk - Customers whose usage dropped >50% month-over-month.
     *
     * @return array<int, array{customer_id: int, name: string, previous_usage: int, current_usage: int, drop_percentage: float}>
     */
    public function getChurnRiskCustomers(int $merchantId): array
    {
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        $prevMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $prevMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $customers = Customer::where('merchant_id', $merchantId)->get();
        $churnRisk = [];

        foreach ($customers as $customer) {
            $prevUsage = $this->getUsageForCustomerPeriod($customer->id, $prevMonthStart, $prevMonthEnd);
            $currentUsage = $this->getUsageForCustomerPeriod($customer->id, $currentMonthStart, $currentMonthEnd);

            if ($prevUsage > 0) {
                $dropPct = (($prevUsage - $currentUsage) / $prevUsage) * 100;

                if ($dropPct > 50) {
                    $churnRisk[] = [
                        'customer_id' => $customer->id,
                        'name' => $customer->name,
                        'previous_usage' => (int) $prevUsage,
                        'current_usage' => (int) $currentUsage,
                        'drop_percentage' => round($dropPct, 1),
                    ];
                }
            }
        }

        // Sort by drop percentage descending
        usort($churnRisk, fn ($a, $b) => $b['drop_percentage'] <=> $a['drop_percentage']);

        return $churnRisk;
    }

    /**
     * Get usage for a customer over a date period, querying aggregated tables and raw events.
     */
    public function getUsageForCustomerPeriod(int $customerId, Carbon $start, Carbon $end): int
    {
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();
        $startDt = $start->copy()->startOfDay()->toDateTimeString();
        $endDt = $end->copy()->endOfDay()->toDateTimeString();

        $aggSum = (int) DailyUsageAggregation::where('customer_id', $customerId)
            ->where(function ($q) use ($startDate, $endDate, $startDt, $endDt) {
                $q->whereBetween('usage_date', [$startDate, $endDate])
                  ->orWhereBetween('usage_date', [$startDt, $endDt])
                  ->orWhere(function ($sub) use ($startDate, $endDate) {
                      $sub->whereDate('usage_date', '>=', $startDate)
                          ->whereDate('usage_date', '<=', $endDate);
                  });
            })
            ->sum('total_units');

        $sumSum = (int) DailyUsageSummary::where('customer_id', $customerId)
            ->where(function ($q) use ($startDate, $endDate, $startDt, $endDt) {
                $q->whereBetween('usage_date', [$startDate, $endDate])
                  ->orWhereBetween('usage_date', [$startDt, $endDt])
                  ->orWhere(function ($sub) use ($startDate, $endDate) {
                      $sub->whereDate('usage_date', '>=', $startDate)
                          ->whereDate('usage_date', '<=', $endDate);
                  });
            })
            ->sum('total_quantity');

        $rawSum = (int) UsageEvent::where('customer_id', $customerId)
            ->whereBetween('recorded_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->sum(DB::raw('COALESCE(units, quantity, 1)'));

        return max($aggSum, $sumSum, $rawSum);
    }

    /**
     * System status metadata for operational visibility.
     *
     * @return array<string, string>
     */
    protected function getSystemStatus(): array
    {
        return [
            'plan_pricing_cache' => 'Redis, TTL 10m',
            'nightly_aggregation_job' => 'queued, chunked (5k rows/batch)',
            'usage_endpoint' => 'rate-limited, 120 req/min per API key',
            'dashboard_cache' => 'Redis, TTL 5m',
        ];
    }
}
