<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyUsageAggregation;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use App\Services\CalculateBillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests for CalculateBillingService.
 *
 * Verifies core billing math, usage metering calculations, and edge cases:
 * - Step 1: Standard monthly billing with no overage.
 * - Step 2: Overage billing (exceeding included allowance).
 * - Step 3: The mid-cycle upgrade proration edge case.
 */
class CalculateBillingServiceTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;
    private Customer $customer;
    private CalculateBillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::create([
            'name' => 'Nexus Cloud Services',
            'email' => 'admin@nexuscloud.io',
            'api_key' => 'nexus_key_' . Str::random(32),
        ]);

        $this->customer = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Acme Corporation',
            'email' => 'finance@acmeworks.com',
        ]);

        $this->billingService = app(CalculateBillingService::class);
    }

    /**
     * =========================================================================
     * STEP 1: Standard Monthly Billing with No Overage
     * =========================================================================
     *
     * Performance & Architectural Trade-off:
     * - Pre-aggregated Daily Summaries: Rather than scanning raw usage event
     *   tables (which can easily contain millions of rows), CalculateBillingService
     *   queries the `daily_usage_aggregations` table. This reduces disk I/O from
     *   O(N) millions of event rows to an O(D) bounded scan where D <= 31 days per cycle.
     * - Fast-Path Zero Overage: When actual usage <= included allowance, overage
     *   units and amounts evaluate to 0 without running floating-point rate math.
     */
    public function test_standard_monthly_billing_with_no_overage(): void
    {
        // 1. Arrange: Standard Plan ($50.00 base, 10,000 units included, $0.02 overage rate)
        $plan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Basic Tier',
            'base_price' => 50.00,
            'billing_cycle' => 30,
            'billing_cycle_days' => 30,
            'included_usage_units' => 10000,
            'included_units' => 10000,
            'overage_rate_per_unit' => 0.0200,
            'is_active' => true,
        ]);

        $cycleStart = Carbon::parse('2026-11-01');
        $cycleEnd = Carbon::parse('2026-12-01'); // 30 days cycle

        $subscription = Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $plan->id,
            'started_at' => $cycleStart,
            'ends_at' => $cycleEnd,
            'status' => 'active',
        ]);

        // Customer uses 4,250 units in total (well within the 10,000 allowance)
        // Stored in pre-aggregated table populated by nightly chunked aggregation job
        DailyUsageAggregation::create([
            'customer_id' => $this->customer->id,
            'usage_date' => '2026-11-08',
            'total_units' => 2500,
            'total_quantity' => 2500,
        ]);
        DailyUsageAggregation::create([
            'customer_id' => $this->customer->id,
            'usage_date' => '2026-11-20',
            'total_units' => 1750,
            'total_quantity' => 1750,
        ]);

        // 2. Act: Generate the cycle invoice
        $invoice = $this->billingService->generateInvoice($subscription);

        // 3. Assert: Standard base price billed, zero overage charged
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(50.00, (float) $invoice->base_amount);
        $this->assertEquals(0, $invoice->overage_units);
        $this->assertEquals(0.00, (float) $invoice->overage_amount);
        $this->assertEquals(50.00, (float) $invoice->total_amount);
        $this->assertEquals('draft', $invoice->status);

        // Verify detailed line item
        $this->assertCount(1, $invoice->lineItems);
        $lineItem = $invoice->lineItems->first();
        $this->assertEquals($plan->id, $lineItem->plan_id);
        $this->assertEquals(50.00, (float) $lineItem->base_amount);
        $this->assertEquals(4250, $lineItem->usage_units);
        $this->assertEquals(10000, $lineItem->included_units);
        $this->assertEquals(0, $lineItem->overage_units);
        $this->assertEquals(0.00, (float) $lineItem->overage_amount);
        $this->assertEquals(50.00, (float) $lineItem->line_total);
    }

    /**
     * =========================================================================
     * STEP 2: Overage Billing (Exceeding Included Allowance)
     * =========================================================================
     *
     * Performance & Architectural Trade-off:
     * - Redis Cache-Aside for Rate Lookups: Plan attributes (base price, included units,
     *   overage rate) are fetched through PlanCacheService. During batch invoice runs
     *   for thousands of subscriptions on the same plan, lookups hit Redis/in-memory cache
     *   rather than issuing redundant SQL SELECTs against the `plans` table.
     * - Decimal Precision Math: Overage is computed with 4-decimal currency rates
     *   and rounded to 2 decimal places only at the line-item and invoice totals to avoid
     *   accumulating floating-point truncation errors.
     */
    public function test_overage_billing_when_usage_exceeds_allowance(): void
    {
        // 1. Arrange: Growth Plan ($100.00 base, 20,000 units included, $0.05 per overage unit)
        $plan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Growth Tier',
            'base_price' => 100.00,
            'billing_cycle' => 30,
            'billing_cycle_days' => 30,
            'included_usage_units' => 20000,
            'included_units' => 20000,
            'overage_rate_per_unit' => 0.0500,
            'is_active' => true,
        ]);

        $cycleStart = Carbon::parse('2026-11-01');
        $cycleEnd = Carbon::parse('2026-12-01');

        $subscription = Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $plan->id,
            'started_at' => $cycleStart,
            'ends_at' => $cycleEnd,
            'status' => 'active',
        ]);

        // Customer uses 35,000 total units (20,000 allowance + 15,000 overage units)
        // 15,000 overage units * $0.05 = $750.00 overage revenue
        DailyUsageAggregation::create([
            'customer_id' => $this->customer->id,
            'usage_date' => '2026-11-10',
            'total_units' => 18000,
            'total_quantity' => 18000,
        ]);
        DailyUsageAggregation::create([
            'customer_id' => $this->customer->id,
            'usage_date' => '2026-11-22',
            'total_units' => 17000,
            'total_quantity' => 17000,
        ]);

        // 2. Act: Generate the cycle invoice
        $invoice = $this->billingService->generateInvoice($subscription);

        // 3. Assert: Base ($100.00) + Overage ($750.00) = $850.00
        $this->assertEquals(100.00, (float) $invoice->base_amount);
        $this->assertEquals(15000, $invoice->overage_units);
        $this->assertEquals(750.00, (float) $invoice->overage_amount);
        $this->assertEquals(850.00, (float) $invoice->total_amount);

        // Line Item breakdown assertion
        $lineItem = $invoice->lineItems->first();
        $this->assertEquals(35000, $lineItem->usage_units);
        $this->assertEquals(20000, $lineItem->included_units);
        $this->assertEquals(15000, $lineItem->overage_units);
        $this->assertEquals(750.00, (float) $lineItem->overage_amount);
        $this->assertEquals(850.00, (float) $lineItem->line_total);
    }

    /**
     * =========================================================================
     * STEP 3: Mid-Cycle Upgrade Proration Edge Case
     * =========================================================================
     *
     * Performance & Architectural Trade-off:
     * - Discrete Timeline Segmentation: Slicing the billing cycle into discrete
     *   chronological segments (Segment 1: Starter, Segment 2: Enterprise) preserves
     *   perfect mathematical and accounting transparency. Each segment gets its own
     *   auditable line item detailing exact days, prorated base, prorated allowance,
     *   and rate-matched overage.
     * - Floor Proration for Allowances: Included allowances are prorated using
     *   `floor($planAllowance * $factor)` to prevent granting fractional usage units
     *   that could lead to sub-unit rounding ambiguities.
     * - DB Transaction Wrap: All segment calculation, line-item insertion, and invoice
     *   state updates occur within a single database transaction, ensuring atomicity
     *   and avoiding partially written invoices during cycle billing runs.
     */
    public function test_mid_cycle_upgrade_proration_with_split_rates_and_overage(): void
    {
        // 1. Arrange: Two plans representing the upgrade path
        // Plan A (Starter): $60.00 base, 10,000 units included, $0.03 overage rate
        $starterPlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Starter Plan',
            'base_price' => 60.00,
            'billing_cycle' => 30,
            'billing_cycle_days' => 30,
            'included_usage_units' => 10000,
            'included_units' => 10000,
            'overage_rate_per_unit' => 0.0300,
            'is_active' => true,
        ]);

        // Plan B (Enterprise): $180.00 base, 50,000 units included, $0.01 overage rate
        $enterprisePlan = Plan::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Enterprise Plan',
            'base_price' => 180.00,
            'billing_cycle' => 30,
            'billing_cycle_days' => 30,
            'included_usage_units' => 50000,
            'included_units' => 50000,
            'overage_rate_per_unit' => 0.0100,
            'is_active' => true,
        ]);

        $cycleStart = Carbon::parse('2026-11-01');
        $cycleEnd = Carbon::parse('2026-12-01'); // 30 days total
        $upgradeDate = Carbon::parse('2026-11-16'); // Exact midpoint: 15 days on Plan A, 15 days on Plan B

        // Active subscription is currently on the Enterprise plan
        $subscription = Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $enterprisePlan->id,
            'started_at' => $cycleStart,
            'ends_at' => $cycleEnd,
            'status' => 'active',
        ]);

        // Record the mid-cycle upgrade event in subscription_changes
        SubscriptionChange::create([
            'subscription_id' => $subscription->id,
            'old_plan_id' => $starterPlan->id,
            'new_plan_id' => $enterprisePlan->id,
            'changed_at' => $upgradeDate,
        ]);

        // ---------------------------------------------------------------------
        // Segment 1 (Nov 01 - Nov 16 = 15 days on Starter Plan):
        // - Proration factor: 15 / 30 = 0.50
        // - Prorated base price: $60.00 * 0.50 = $30.00
        // - Prorated allowance: 10,000 * 0.50 = 5,000 units
        // - Actual usage: 8,000 units
        // - Segment 1 Overage: 8,000 - 5,000 = 3,000 units @ $0.03 = $90.00
        // - Segment 1 Line Total: $30.00 base + $90.00 overage = $120.00
        // ---------------------------------------------------------------------
        DailyUsageAggregation::create([
            'customer_id' => $this->customer->id,
            'usage_date' => '2026-11-05',
            'total_units' => 5000,
            'total_quantity' => 5000,
        ]);
        DailyUsageAggregation::create([
            'customer_id' => $this->customer->id,
            'usage_date' => '2026-11-12',
            'total_units' => 3000,
            'total_quantity' => 3000,
        ]);

        // ---------------------------------------------------------------------
        // Segment 2 (Nov 16 - Dec 01 = 15 days on Enterprise Plan):
        // - Proration factor: 15 / 30 = 0.50
        // - Prorated base price: $180.00 * 0.50 = $90.00
        // - Prorated allowance: 50,000 * 0.50 = 25,000 units
        // - Actual usage: 35,000 units
        // - Segment 2 Overage: 35,000 - 25,000 = 10,000 units @ $0.01 = $100.00
        // - Segment 2 Line Total: $90.00 base + $100.00 overage = $190.00
        // ---------------------------------------------------------------------
        DailyUsageAggregation::create([
            'customer_id' => $this->customer->id,
            'usage_date' => '2026-11-18',
            'total_units' => 15000,
            'total_quantity' => 15000,
        ]);
        DailyUsageAggregation::create([
            'customer_id' => $this->customer->id,
            'usage_date' => '2026-11-25',
            'total_units' => 20000,
            'total_quantity' => 20000,
        ]);

        // 2. Act: Generate the prorated invoice
        $invoice = $this->billingService->generateInvoice($subscription);

        // 3. Assert: Check line-item proration and total invoice amounts
        $this->assertCount(2, $invoice->lineItems);

        // Verify Segment 1 (Starter Plan)
        $line1 = $invoice->lineItems->firstWhere('plan_id', $starterPlan->id);
        $this->assertNotNull($line1, 'Missing invoice line item for Starter Plan segment.');
        $this->assertEquals('2026-11-01', $line1->segment_start->toDateString());
        $this->assertEquals('2026-11-16', $line1->segment_end->toDateString());
        $this->assertEquals(30.00, (float) $line1->base_amount);
        $this->assertEquals(5000, $line1->included_units);
        $this->assertEquals(8000, $line1->usage_units);
        $this->assertEquals(3000, $line1->overage_units);
        $this->assertEquals(90.00, (float) $line1->overage_amount);
        $this->assertEquals(120.00, (float) $line1->line_total);

        // Verify Segment 2 (Enterprise Plan)
        $line2 = $invoice->lineItems->firstWhere('plan_id', $enterprisePlan->id);
        $this->assertNotNull($line2, 'Missing invoice line item for Enterprise Plan segment.');
        $this->assertEquals('2026-11-16', $line2->segment_start->toDateString());
        $this->assertEquals('2026-12-01', $line2->segment_end->toDateString());
        $this->assertEquals(90.00, (float) $line2->base_amount);
        $this->assertEquals(25000, $line2->included_units);
        $this->assertEquals(35000, $line2->usage_units);
        $this->assertEquals(10000, $line2->overage_units);
        $this->assertEquals(10000 * 0.01, (float) $line2->overage_amount);
        $this->assertEquals(100.00, (float) $line2->overage_amount);
        $this->assertEquals(190.00, (float) $line2->line_total);

        // Verify Invoice Aggregates:
        // Total Base: $30.00 + $90.00 = $120.00
        // Total Overage Units: 3,000 + 10,000 = 13,000 units
        // Total Overage Amount: $90.00 + $100.00 = $190.00
        // Total Invoice Amount: $120.00 + $190.00 = $310.00
        $this->assertEquals(120.00, (float) $invoice->base_amount);
        $this->assertEquals(13000, $invoice->overage_units);
        $this->assertEquals(190.00, (float) $invoice->overage_amount);
        $this->assertEquals(310.00, (float) $invoice->total_amount);
    }
}
