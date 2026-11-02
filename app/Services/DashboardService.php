<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\DailyUsageSummary;
use App\Models\Merchant;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard analytics service.
 *
 * Caches results for 5 minutes with merchant-scoped keys.
 * The dashboard shows:
 * 1. Top 5 customers by usage this month
 * 2. Projected overage revenue for the current cycle
 * 3. Churn risk: customers whose usage dropped >50% MoM
 */
class DashboardService
{
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private readonly BillingService $billingService,
        private readonly PlanCacheService $planCache
    ) {}

    /**
     * Get complete dashboard data for a merchant.
     */
    public function getDashboard(int $merchantId): array
    {
        $cacheKey = "dashboard:{$merchantId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($merchantId) {
            $merchant = Merchant::findOrFail($merchantId);

            return [
                'merchant' => [
                    'id' => $merchant->id,
                    'name' => $merchant->name,
                ],
                'top_customers' => $this->getTopCustomers($merchantId),
                'projected_overage' => $this->getProjectedOverageRevenue($merchantId),
                'churn_risk' => $this->getChurnRiskCustomers($merchantId),
                'system_status' => $this->getSystemStatus(),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Top 5 customers by usage this billing month.
     */
    public function getTopCustomers(int $merchantId, int $limit = 5): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $results = DailyUsageSummary::query()
            ->join('customers', 'daily_usage_summaries.customer_id', '=', 'customers.id')
            ->where('customers.merchant_id', $merchantId)
            ->whereBetween('daily_usage_summaries.usage_date', [$startOfMonth, $endOfMonth])
            ->groupBy('daily_usage_summaries.customer_id', 'customers.name')
            ->select([
                'daily_usage_summaries.customer_id',
                'customers.name',
                DB::raw('SUM(daily_usage_summaries.total_quantity) as total_usage'),
            ])
            ->orderByDesc('total_usage')
            ->limit($limit)
            ->get();

        // Enrich with allowance percentage
        return $results->map(function ($row) {
            $customer = Customer::find($row->customer_id);
            $subscription = $customer?->activeSubscription;
            $plan = $subscription ? $this->planCache->find($subscription->plan_id) : null;
            $includedUnits = $plan?->included_units ?? 0;

            return [
                'customer_id' => $row->customer_id,
                'name' => $row->name,
                'usage' => (int) $row->total_usage,
                'allowance_pct' => $includedUnits > 0
                    ? round(($row->total_usage / $includedUnits) * 100, 1)
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Projected overage revenue across all active subscriptions for this merchant.
     */
    public function getProjectedOverageRevenue(int $merchantId): array
    {
        $subscriptions = Subscription::query()
            ->join('customers', 'subscriptions.customer_id', '=', 'customers.id')
            ->where('customers.merchant_id', $merchantId)
            ->where('subscriptions.status', 'active')
            ->select('subscriptions.*')
            ->get();

        $totalProjectedRevenue = 0;
        $totalUsageSoFar = 0;
        $totalIncludedUnits = 0;
        $activePlan = null;

        foreach ($subscriptions as $subscription) {
            $projection = $this->billingService->projectOverage($subscription);
            $totalProjectedRevenue += $projection['projected_overage_revenue'];
            $totalUsageSoFar += $projection['usage_so_far'];
            $totalIncludedUnits += $projection['included_units'];

            if (!$activePlan) {
                $activePlan = $this->planCache->find($subscription->plan_id);
            }
        }

        return [
            'total_projected_overage_revenue' => round($totalProjectedRevenue, 2),
            'total_usage_so_far' => $totalUsageSoFar,
            'total_included_units' => $totalIncludedUnits,
            'active_plan' => $activePlan ? [
                'name' => $activePlan->name,
                'billing_cycle_days' => $activePlan->billing_cycle_days,
            ] : null,
        ];
    }

    /**
     * Customers whose usage dropped >50% month-over-month.
     *
     * Compares current month usage to previous month usage.
     * A >50% drop signals potential churn risk.
     */
    public function getChurnRiskCustomers(int $merchantId): array
    {
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        $prevMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $prevMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Current month usage per customer
        $currentUsage = DailyUsageSummary::query()
            ->join('customers', 'daily_usage_summaries.customer_id', '=', 'customers.id')
            ->where('customers.merchant_id', $merchantId)
            ->whereBetween('daily_usage_summaries.usage_date', [$currentMonthStart, $currentMonthEnd])
            ->groupBy('daily_usage_summaries.customer_id')
            ->select([
                'daily_usage_summaries.customer_id',
                DB::raw('SUM(daily_usage_summaries.total_quantity) as total_usage'),
            ])
            ->pluck('total_usage', 'customer_id');

        // Previous month usage per customer
        $previousUsage = DailyUsageSummary::query()
            ->join('customers', 'daily_usage_summaries.customer_id', '=', 'customers.id')
            ->where('customers.merchant_id', $merchantId)
            ->whereBetween('daily_usage_summaries.usage_date', [$prevMonthStart, $prevMonthEnd])
            ->groupBy('daily_usage_summaries.customer_id')
            ->select([
                'daily_usage_summaries.customer_id',
                DB::raw('SUM(daily_usage_summaries.total_quantity) as total_usage'),
            ])
            ->pluck('total_usage', 'customer_id');

        $churnRisk = [];

        foreach ($previousUsage as $customerId => $prevUsageTotal) {
            $currentUsageTotal = $currentUsage->get($customerId, 0);

            if ($prevUsageTotal > 0) {
                $dropPct = (($prevUsageTotal - $currentUsageTotal) / $prevUsageTotal) * 100;

                if ($dropPct > 50) {
                    $customer = Customer::find($customerId);
                    $churnRisk[] = [
                        'customer_id' => $customerId,
                        'name' => $customer?->name ?? 'Unknown',
                        'previous_usage' => (int) $prevUsageTotal,
                        'current_usage' => (int) $currentUsageTotal,
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
     * System status metadata for the dashboard.
     */
    private function getSystemStatus(): array
    {
        return [
            'plan_pricing_cache' => 'Redis, TTL 10m',
            'nightly_aggregation_job' => 'queued, chunked (5k rows/batch)',
            'usage_endpoint' => 'rate-limited, 120 req/min per API key',
            'dashboard_cache' => 'Redis, TTL 5m',
        ];
    }
}
