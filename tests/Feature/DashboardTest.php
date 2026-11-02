<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyUsageSummary;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the merchant dashboard endpoint.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->merchant = Merchant::factory()->create();
    }

    /** @test */
    public function it_returns_dashboard_data(): void
    {
        $plan = Plan::factory()->create([
            'merchant_id' => $this->merchant->id,
            'included_units' => 50000,
        ]);

        $customer = Customer::factory()->create(['merchant_id' => $this->merchant->id]);

        Subscription::create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'started_at' => Carbon::now()->startOfMonth(),
            'ends_at' => Carbon::now()->endOfMonth(),
            'status' => 'active',
        ]);

        DailyUsageSummary::create([
            'customer_id' => $customer->id,
            'usage_date' => Carbon::now()->toDateString(),
            'total_quantity' => 1000,
        ]);

        $response = $this->getJson("/api/merchants/{$this->merchant->id}/dashboard");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'merchant' => ['id', 'name'],
                    'top_customers',
                    'projected_overage',
                    'churn_risk',
                    'system_status',
                    'generated_at',
                ],
            ]);
    }

    /** @test */
    public function it_returns_top_5_customers_by_usage(): void
    {
        $plan = Plan::factory()->create([
            'merchant_id' => $this->merchant->id,
            'included_units' => 100000,
        ]);

        // Create 7 customers with varying usage
        $usages = [5000, 3000, 8000, 1000, 7000, 2000, 6000];

        foreach ($usages as $usage) {
            $customer = Customer::factory()->create(['merchant_id' => $this->merchant->id]);
            Subscription::create([
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'started_at' => Carbon::now()->startOfMonth(),
                'ends_at' => Carbon::now()->endOfMonth(),
                'status' => 'active',
            ]);
            DailyUsageSummary::create([
                'customer_id' => $customer->id,
                'usage_date' => Carbon::now()->toDateString(),
                'total_quantity' => $usage,
            ]);
        }

        $service = app(DashboardService::class);
        $topCustomers = $service->getTopCustomers($this->merchant->id);

        $this->assertCount(5, $topCustomers);
        // First should be highest usage
        $this->assertEquals(8000, $topCustomers[0]['usage']);
    }

    /** @test */
    public function it_detects_churn_risk_customers(): void
    {
        $plan = Plan::factory()->create([
            'merchant_id' => $this->merchant->id,
            'included_units' => 100000,
        ]);

        $customer = Customer::factory()->create(['merchant_id' => $this->merchant->id]);
        Subscription::create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'started_at' => Carbon::now()->startOfMonth(),
            'ends_at' => Carbon::now()->endOfMonth(),
            'status' => 'active',
        ]);

        // Last month: 10000 usage
        DailyUsageSummary::create([
            'customer_id' => $customer->id,
            'usage_date' => Carbon::now()->subMonth()->startOfMonth()->addDays(5)->toDateString(),
            'total_quantity' => 10000,
        ]);

        // This month: 3000 usage (70% drop)
        DailyUsageSummary::create([
            'customer_id' => $customer->id,
            'usage_date' => Carbon::now()->startOfMonth()->addDays(5)->toDateString(),
            'total_quantity' => 3000,
        ]);

        $service = app(DashboardService::class);
        $churnRisk = $service->getChurnRiskCustomers($this->merchant->id);

        $this->assertCount(1, $churnRisk);
        $this->assertEquals($customer->id, $churnRisk[0]['customer_id']);
        $this->assertEquals(70.0, $churnRisk[0]['drop_percentage']);
    }

    /** @test */
    public function it_does_not_flag_customers_with_normal_usage(): void
    {
        $plan = Plan::factory()->create([
            'merchant_id' => $this->merchant->id,
            'included_units' => 100000,
        ]);

        $customer = Customer::factory()->create(['merchant_id' => $this->merchant->id]);
        Subscription::create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'started_at' => Carbon::now()->startOfMonth(),
            'ends_at' => Carbon::now()->endOfMonth(),
            'status' => 'active',
        ]);

        // Last month: 10000
        DailyUsageSummary::create([
            'customer_id' => $customer->id,
            'usage_date' => Carbon::now()->subMonth()->startOfMonth()->addDays(5)->toDateString(),
            'total_quantity' => 10000,
        ]);

        // This month: 8000 (only 20% drop)
        DailyUsageSummary::create([
            'customer_id' => $customer->id,
            'usage_date' => Carbon::now()->startOfMonth()->addDays(5)->toDateString(),
            'total_quantity' => 8000,
        ]);

        $service = app(DashboardService::class);
        $churnRisk = $service->getChurnRiskCustomers($this->merchant->id);

        $this->assertEmpty($churnRisk);
    }
}
