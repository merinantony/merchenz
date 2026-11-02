<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyUsageAggregation;
use App\Models\DailyUsageSummary;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Services\DashboardMetricsService;
use App\Services\PlanCacheService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;
    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create([
            'name' => 'Acme SaaS Corp',
        ]);

        $this->plan = Plan::factory()->create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Growth Plan',
            'base_price' => 100.00,
            'billing_cycle' => 30,
            'billing_cycle_days' => 30,
            'included_usage_units' => 50000,
            'included_units' => 50000,
            'overage_rate_per_unit' => 0.0500,
        ]);
    }

    /**
     * Test 1: Current cycle usage vs allowance.
     */
    public function test_current_cycle_usage_vs_allowance_metric(): void
    {
        $customer1 = Customer::factory()->create(['merchant_id' => $this->merchant->id]);
        $customer2 = Customer::factory()->create(['merchant_id' => $this->merchant->id]);

        Subscription::create([
            'customer_id' => $customer1->id,
            'plan_id' => $this->plan->id,
            'started_at' => Carbon::now()->startOfMonth(),
            'ends_at' => Carbon::now()->endOfMonth(),
            'status' => 'active',
        ]);

        Subscription::create([
            'customer_id' => $customer2->id,
            'plan_id' => $this->plan->id,
            'started_at' => Carbon::now()->startOfMonth(),
            'ends_at' => Carbon::now()->endOfMonth(),
            'status' => 'active',
        ]);

        // Customer 1 used 15,000 units
        DailyUsageAggregation::create([
            'customer_id' => $customer1->id,
            'usage_date' => Carbon::now()->toDateString(),
            'total_units' => 15000,
            'total_quantity' => 15000,
        ]);

        // Customer 2 used 25,000 units
        DailyUsageAggregation::create([
            'customer_id' => $customer2->id,
            'usage_date' => Carbon::now()->toDateString(),
            'total_units' => 25000,
            'total_quantity' => 25000,
        ]);

        $service = app(DashboardMetricsService::class);
        $metric = $service->getCurrentCycleUsageVsAllowance($this->merchant->id);

        // Total usage: 15,000 + 25,000 = 40,000
        $this->assertEquals(40000, $metric['usage_so_far']);
        // Total allowance: 50,000 * 2 = 100,000
        $this->assertEquals(100000, $metric['included_allowance']);
        // Allowance pct: 40%
        $this->assertEquals(40.0, $metric['allowance_pct']);
        // Remaining units: 60,000
        $this->assertEquals(60000, $metric['remaining_units']);
        $this->assertCount(2, $metric['customer_breakdown']);
    }

    /**
     * Test 2: Top 5 customers by usage this cycle.
     */
    public function test_top_5_customers_by_usage_metric(): void
    {
        // Create 7 customers with distinct usage amounts
        $usages = [12000, 3000, 25000, 8000, 45000, 1000, 18000];

        foreach ($usages as $usage) {
            $customer = Customer::factory()->create(['merchant_id' => $this->merchant->id]);
            Subscription::create([
                'customer_id' => $customer->id,
                'plan_id' => $this->plan->id,
                'started_at' => Carbon::now()->startOfMonth(),
                'ends_at' => Carbon::now()->endOfMonth(),
                'status' => 'active',
            ]);

            DailyUsageAggregation::create([
                'customer_id' => $customer->id,
                'usage_date' => Carbon::now()->toDateString(),
                'total_units' => $usage,
                'total_quantity' => $usage,
            ]);
        }

        $service = app(DashboardMetricsService::class);
        $top5 = $service->getTopCustomers($this->merchant->id, 5);

        $this->assertCount(5, $top5);
        // Ordered descending: 45000, 25000, 18000, 12000, 8000
        $this->assertEquals(45000, $top5[0]['usage']);
        $this->assertEquals(25000, $top5[1]['usage']);
        $this->assertEquals(18000, $top5[2]['usage']);
        $this->assertEquals(12000, $top5[3]['usage']);
        $this->assertEquals(8000, $top5[4]['usage']);
        // Verify allowance_pct
        $this->assertEquals(90.0, $top5[0]['allowance_pct']); // 45000 / 50000 * 100
    }

    /**
     * Test 3: Projected overage revenue metric.
     */
    public function test_projected_overage_revenue_metric(): void
    {
        $customer = Customer::factory()->create(['merchant_id' => $this->merchant->id]);

        // 30-day cycle that started 15 days ago
        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'plan_id' => $this->plan->id,
            'started_at' => Carbon::now()->subDays(15),
            'ends_at' => Carbon::now()->addDays(15),
            'status' => 'active',
        ]);

        // Customer has already used 40,000 units in 15 days (plan includes 50,000)
        DailyUsageAggregation::create([
            'customer_id' => $customer->id,
            'usage_date' => Carbon::now()->subDays(5)->toDateString(),
            'total_units' => 40000,
            'total_quantity' => 40000,
        ]);

        $service = app(DashboardMetricsService::class);
        $projection = $service->getProjectedOverageRevenue($this->merchant->id);

        $this->assertGreaterThan(0, $projection['projected_overage_revenue']);
        $this->assertEquals(40000, $projection['total_usage_so_far']);
        $this->assertEquals(50000, $projection['total_included_units']);
        $this->assertNotNull($projection['active_plan']);
        $this->assertEquals('Growth Plan', $projection['active_plan']['name']);
    }

    /**
     * Test 4: Churn Risk - Customers whose usage dropped >50% month-over-month.
     */
    public function test_churn_risk_customers_metric(): void
    {
        // Customer A: previous month 10,000, current month 2,000 (80% drop -> churn risk)
        $customerA = Customer::factory()->create([
            'merchant_id' => $this->merchant->id,
            'name' => 'At Risk Corp',
        ]);

        // Customer B: previous month 10,000, current month 8,000 (20% drop -> healthy, not at risk)
        $customerB = Customer::factory()->create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Healthy Corp',
        ]);

        // Customer C: previous month 20,000, current month 5,000 (75% drop -> churn risk)
        $customerC = Customer::factory()->create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Severe Risk LLC',
        ]);

        // Last month usage
        $prevDate = Carbon::now()->subMonth()->startOfMonth()->addDays(5)->toDateString();
        DailyUsageAggregation::create([
            'customer_id' => $customerA->id,
            'usage_date' => $prevDate,
            'total_units' => 10000,
            'total_quantity' => 10000,
        ]);
        DailyUsageAggregation::create([
            'customer_id' => $customerB->id,
            'usage_date' => $prevDate,
            'total_units' => 10000,
            'total_quantity' => 10000,
        ]);
        DailyUsageAggregation::create([
            'customer_id' => $customerC->id,
            'usage_date' => $prevDate,
            'total_units' => 20000,
            'total_quantity' => 20000,
        ]);

        // Current month usage
        $currDate = Carbon::now()->startOfMonth()->addDays(2)->toDateString();
        DailyUsageAggregation::create([
            'customer_id' => $customerA->id,
            'usage_date' => $currDate,
            'total_units' => 2000,
            'total_quantity' => 2000,
        ]);
        DailyUsageAggregation::create([
            'customer_id' => $customerB->id,
            'usage_date' => $currDate,
            'total_units' => 8000,
            'total_quantity' => 8000,
        ]);
        DailyUsageAggregation::create([
            'customer_id' => $customerC->id,
            'usage_date' => $currDate,
            'total_units' => 5000,
            'total_quantity' => 5000,
        ]);

        $service = app(DashboardMetricsService::class);
        $churnRisk = $service->getChurnRiskCustomers($this->merchant->id);

        // Only Customer A (80%) and Customer C (75%) should be in churn risk; Customer B (20%) excluded
        $this->assertCount(2, $churnRisk);

        // Sorted descending by drop percentage: Customer A (80.0%) then Customer C (75.0%)
        $this->assertEquals($customerA->id, $churnRisk[0]['customer_id']);
        $this->assertEquals(80.0, $churnRisk[0]['drop_percentage']);
        $this->assertEquals(10000, $churnRisk[0]['previous_usage']);
        $this->assertEquals(2000, $churnRisk[0]['current_usage']);

        $this->assertEquals($customerC->id, $churnRisk[1]['customer_id']);
        $this->assertEquals(75.0, $churnRisk[1]['drop_percentage']);
    }

    /**
     * Test 5: API Controller returns JSON with all four metrics.
     */
    public function test_api_controller_returns_json_with_all_metrics(): void
    {
        $response = $this->getJson("/api/merchants/{$this->merchant->id}/metrics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'merchant' => ['id', 'name'],
                'current_cycle_usage_vs_allowance' => [
                    'usage_so_far',
                    'included_allowance',
                    'allowance_pct',
                    'remaining_units',
                ],
                'top_customers',
                'projected_overage' => [
                    'projected_overage_revenue',
                    'total_usage_so_far',
                    'total_included_units',
                ],
                'churn_risk',
                'system_status',
                'generated_at',
            ]);
    }

    /**
     * Test 6: Caching Requirement - Cache plan/pricing lookups and clear key on Plan price update.
     */
    public function test_plan_pricing_cached_and_cleared_by_observer_on_price_update(): void
    {
        $planCache = app(PlanCacheService::class);
        $planKey = $planCache->planKey($this->plan->id);
        $pricingKey = $planCache->pricingKey($this->plan->id);

        // Clear any leftover cache
        Cache::forget($planKey);
        Cache::forget($pricingKey);

        $this->assertFalse(Cache::has($planKey));

        // 1. Initial lookup warms cache
        $cachedPlan = $planCache->find($this->plan->id);
        $this->assertNotNull($cachedPlan);
        $this->assertEquals(100.00, (float) $cachedPlan->base_price);
        $this->assertTrue(Cache::has($planKey));

        // Warm pricing lookup
        $cachedPricing = $planCache->getPricing($this->plan->id);
        $this->assertNotNull($cachedPricing);
        $this->assertEquals(100.00, $cachedPricing['base_price']);
        $this->assertTrue(Cache::has($pricingKey));

        // 2. Update Plan's base price
        $this->plan->update([
            'base_price' => 199.99,
        ]);

        // 3. Eloquent Observer MUST clear this specific cache key
        $this->assertFalse(Cache::has($planKey), "Plan cache key [{$planKey}] was not cleared after price update!");
        $this->assertFalse(Cache::has($pricingKey), "Plan pricing cache key [{$pricingKey}] was not cleared after price update!");

        // 4. Next lookup fetches fresh price
        $freshPlan = $planCache->find($this->plan->id);
        $this->assertEquals(199.99, (float) $freshPlan->base_price);

        // 5. Updating overage_rate_per_unit also clears the cache key
        $this->assertTrue(Cache::has($planKey));
        $this->plan->update([
            'overage_rate_per_unit' => 0.0800,
        ]);
        $this->assertFalse(Cache::has($planKey), "Plan cache key was not cleared after overage rate update!");
    }
}
