<?php

namespace Tests\Feature;

use App\Jobs\AggregateDailyUsageJob;
use App\Jobs\GenerateCycleInvoicesJob;
use App\Models\Customer;
use App\Models\DailyUsageAggregation;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use App\Models\UsageEvent;
use App\Services\CalculateBillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingJobsAndProrationTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;
    private Customer $customer;
    private CalculateBillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::create([
            'name' => 'ScaleCorp',
            'email' => 'admin@scalecorp.io',
            'api_key' => 'scale_key_' . Str::random(32),
        ]);

        $this->customer = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Acme Logistics',
            'email' => 'billing@acme.io',
        ]);

        $this->billingService = app(CalculateBillingService::class);
    }

    public function test_aggregate_daily_usage_job_chunks_and_moves_data_to_daily_usage_aggregations(): void
    {
        $targetDate = '2026-11-01';

        // Insert multiple usage events for target date
        UsageEvent::create([
            'customer_id' => $this->customer->id,
            'event_id' => 'evt_agg_1',
            'units' => 1200,
            'timestamp' => Carbon::parse("{$targetDate} 08:30:00"),
        ]);

        UsageEvent::create([
            'customer_id' => $this->customer->id,
            'event_id' => 'evt_agg_2',
            'units' => 800,
            'timestamp' => Carbon::parse("{$targetDate} 15:45:00"),
        ]);

        // Run AggregateDailyUsageJob with 5000 chunk size
        $job = new AggregateDailyUsageJob($targetDate, 5000);
        $job->handle();

        // Verify daily_usage_aggregations table is populated
        $agg = DailyUsageAggregation::where('customer_id', $this->customer->id)
            ->where('usage_date', $targetDate)
            ->first();

        $this->assertNotNull($agg);
        $this->assertEquals(2000, $agg->total_units);

        // Idempotency: running a second time should not double-count
        $job->handle();
        $this->assertEquals(1, DailyUsageAggregation::where('customer_id', $this->customer->id)->where('usage_date', $targetDate)->count());
        $this->assertEquals(2000, $agg->fresh()->total_units);
    }

    public function test_calculate_billing_service_handles_mid_cycle_upgrade_with_prorated_rates_and_overage(): void
    {
        // Plan A (Starter): $50 base, 10,000 units included, $0.02 overage rate
        $starterPlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Starter',
            'base_price' => 50.00,
            'billing_cycle' => 30,
            'included_usage_units' => 10000,
            'overage_rate_per_unit' => 0.0200,
        ]);

        // Plan B (Enterprise): $200 base, 50,000 units included, $0.005 overage rate
        $enterprisePlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Enterprise',
            'base_price' => 200.00,
            'billing_cycle' => 30,
            'included_usage_units' => 50000,
            'overage_rate_per_unit' => 0.0050,
        ]);

        $cycleStart = Carbon::parse('2026-11-01');
        $cycleEnd = Carbon::parse('2026-12-01'); // 30 days

        $subscription = Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $enterprisePlan->id, // Current plan after upgrade
            'started_at' => $cycleStart,
            'ends_at' => $cycleEnd,
            'status' => 'active',
        ]);

        // Mid-cycle upgrade happened on Nov 16 (15 days under Starter, 15 days under Enterprise)
        $changeDate = Carbon::parse('2026-11-16');
        SubscriptionChange::create([
            'subscription_id' => $subscription->id,
            'old_plan_id' => $starterPlan->id,
            'new_plan_id' => $enterprisePlan->id,
            'changed_at' => $changeDate,
        ]);

        // Usage during Segment 1 (Starter Plan, Nov 01 - Nov 15): 8,000 units
        // Prorated Starter allowance: 10,000 * (15/30) = 5,000 units
        // Starter Overage: 8,000 - 5,000 = 3,000 units @ $0.02 = $60.00
        // Starter Prorated Base: $50 * (15/30) = $25.00
        // Segment 1 Total = $25.00 + $60.00 = $85.00
        DailyUsageAggregation::create([
            'customer_id' => $this->customer->id,
            'usage_date' => '2026-11-05',
            'total_units' => 8000,
        ]);

        // Usage during Segment 2 (Enterprise Plan, Nov 16 - Dec 01): 30,000 units
        // Prorated Enterprise allowance: 50,000 * (15/30) = 25,000 units
        // Enterprise Overage: 30,000 - 25,000 = 5,000 units @ $0.005 = $25.00
        // Enterprise Prorated Base: $200 * (15/30) = $100.00
        // Segment 2 Total = $100.00 + $25.00 = $125.00
        DailyUsageAggregation::create([
            'customer_id' => $this->customer->id,
            'usage_date' => '2026-11-20',
            'total_units' => 30000,
        ]);

        $invoice = $this->billingService->generateInvoice($subscription);

        // Assert 2 line items generated
        $this->assertCount(2, $invoice->lineItems);

        // Segment 1 (Starter)
        $line1 = $invoice->lineItems->firstWhere('plan_id', $starterPlan->id);
        $this->assertNotNull($line1);
        $this->assertEquals(25.00, (float) $line1->base_amount);
        $this->assertEquals(5000, $line1->included_units);
        $this->assertEquals(8000, $line1->usage_units);
        $this->assertEquals(3000, $line1->overage_units);
        $this->assertEquals(60.00, (float) $line1->overage_amount);
        $this->assertEquals(85.00, (float) $line1->line_total);

        // Segment 2 (Enterprise)
        $line2 = $invoice->lineItems->firstWhere('plan_id', $enterprisePlan->id);
        $this->assertNotNull($line2);
        $this->assertEquals(100.00, (float) $line2->base_amount);
        $this->assertEquals(25000, $line2->included_units);
        $this->assertEquals(30000, $line2->usage_units);
        $this->assertEquals(5000, $line2->overage_units);
        $this->assertEquals(25.00, (float) $line2->overage_amount);
        $this->assertEquals(125.00, (float) $line2->line_total);

        // Total Invoice verification:
        // Total Base: $25 + $100 = $125.00
        // Total Overage: $60 + $25 = $85.00
        // Total Amount: $125 + $85 = $210.00
        $this->assertEquals(125.00, (float) $invoice->base_amount);
        $this->assertEquals(8000, $invoice->overage_units);
        $this->assertEquals(85.00, (float) $invoice->overage_amount);
        $this->assertEquals(210.00, (float) $invoice->total_amount);
    }

    public function test_calculate_billing_service_handles_mid_cycle_downgrade(): void
    {
        $enterprisePlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Enterprise',
            'base_price' => 300.00,
            'billing_cycle' => 30,
            'included_usage_units' => 60000,
            'overage_rate_per_unit' => 0.0050,
        ]);

        $basicPlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Basic',
            'base_price' => 100.00,
            'billing_cycle' => 30,
            'included_usage_units' => 10000,
            'overage_rate_per_unit' => 0.0200,
        ]);

        $subscription = Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $basicPlan->id,
            'started_at' => Carbon::parse('2026-11-01'),
            'ends_at' => Carbon::parse('2026-12-01'),
            'status' => 'active',
        ]);

        SubscriptionChange::create([
            'subscription_id' => $subscription->id,
            'old_plan_id' => $enterprisePlan->id,
            'new_plan_id' => $basicPlan->id,
            'changed_at' => Carbon::parse('2026-11-16'),
        ]);

        // Enterprise segment (15 days): 20,000 units (within 30,000 prorated included)
        DailyUsageAggregation::create([
            'customer_id' => $this->customer->id,
            'usage_date' => '2026-11-08',
            'total_units' => 20000,
        ]);

        // Basic segment (15 days): 7,000 units (exceeds 5,000 prorated included by 2,000)
        DailyUsageAggregation::create([
            'customer_id' => $this->customer->id,
            'usage_date' => '2026-11-22',
            'total_units' => 7000,
        ]);

        $invoice = $this->billingService->generateInvoice($subscription);

        $segEnterprise = $invoice->lineItems->firstWhere('plan_id', $enterprisePlan->id);
        $this->assertEquals(150.00, (float) $segEnterprise->base_amount);
        $this->assertEquals(0, $segEnterprise->overage_units);

        $segBasic = $invoice->lineItems->firstWhere('plan_id', $basicPlan->id);
        $this->assertEquals(50.00, (float) $segBasic->base_amount);
        $this->assertEquals(2000, $segBasic->overage_units);
        $this->assertEquals(40.00, (float) $segBasic->overage_amount); // 2000 * 0.02

        $this->assertEquals(200.00, (float) $invoice->base_amount);
        $this->assertEquals(40.00, (float) $invoice->overage_amount);
        $this->assertEquals(240.00, (float) $invoice->total_amount);
    }

    public function test_generate_cycle_invoices_job_runs_at_cycle_end_and_expires_subscription(): void
    {
        $plan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Monthly Flat',
            'base_price' => 75.00,
            'billing_cycle' => 30,
            'included_usage_units' => 10000,
            'overage_rate_per_unit' => 0.01,
        ]);

        // Subscription ended yesterday
        $subscription = Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $plan->id,
            'started_at' => Carbon::now()->subDays(31),
            'ends_at' => Carbon::now()->subDay(),
            'status' => 'active',
        ]);

        DailyUsageAggregation::create([
            'customer_id' => $this->customer->id,
            'usage_date' => Carbon::now()->subDays(10)->toDateString(),
            'total_units' => 5000,
        ]);

        $job = new GenerateCycleInvoicesJob();
        $job->handle($this->billingService);

        // Verify invoice was created
        $invoice = $subscription->invoices()->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(75.00, (float) $invoice->total_amount);

        // Verify subscription is marked expired
        $this->assertEquals('expired', $subscription->fresh()->status);

        // Idempotency: re-running job must not create duplicate invoices
        $job->handle($this->billingService);
        $this->assertEquals(1, $subscription->invoices()->count());
    }
}
