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
use App\Models\UsageEvent;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaAndModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchants_table_and_model(): void
    {
        $this->assertTrue(Schema::hasTable('merchants'));
        $this->assertTrue(Schema::hasColumns('merchants', ['id', 'name', 'email', 'api_key', 'created_at', 'updated_at']));

        $merchant = Merchant::create([
            'name' => 'Test Tenant',
            'email' => 'tenant@example.com',
            'api_key' => 'key_test_123',
        ]);

        $this->assertDatabaseHas('merchants', ['email' => 'tenant@example.com']);
        $this->assertEmpty($merchant->plans);
        $this->assertEmpty($merchant->customers);
    }

    public function test_plans_table_and_model_structure(): void
    {
        $this->assertTrue(Schema::hasTable('plans'));
        $this->assertTrue(Schema::hasColumns('plans', [
            'id', 'merchant_id', 'name', 'base_price', 'billing_cycle', 'included_usage_units', 'overage_rate_per_unit'
        ]));

        $merchant = Merchant::create([
            'name' => 'Tenant 1',
            'email' => 'tenant1@example.com',
            'api_key' => 'key_1',
        ]);

        $plan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Pro Metered',
            'base_price' => 99.00,
            'billing_cycle' => 30,
            'included_usage_units' => 100000,
            'overage_rate_per_unit' => 0.0050,
        ]);

        $this->assertEquals(99.00, $plan->base_price);
        $this->assertEquals(30, $plan->billing_cycle);
        $this->assertEquals(100000, $plan->included_usage_units);
        $this->assertEquals(0.0050, $plan->overage_rate_per_unit);
        $this->assertEquals($merchant->id, $plan->merchant->id);
    }

    public function test_customers_table_and_belongs_to_merchant(): void
    {
        $this->assertTrue(Schema::hasTable('customers'));
        $this->assertTrue(Schema::hasColumns('customers', ['id', 'merchant_id', 'name', 'email']));

        $merchant = Merchant::create(['name' => 'M1', 'email' => 'm1@example.com', 'api_key' => 'k1']);
        $customer = Customer::create(['merchant_id' => $merchant->id, 'name' => 'Cust 1', 'email' => 'c1@example.com']);

        $this->assertEquals($merchant->id, $customer->merchant->id);
        $this->assertTrue($merchant->customers->contains($customer));
    }

    public function test_subscriptions_and_mid_cycle_changes(): void
    {
        $this->assertTrue(Schema::hasTable('subscriptions'));
        $this->assertTrue(Schema::hasColumns('subscriptions', ['id', 'customer_id', 'plan_id', 'started_at', 'ends_at', 'status']));
        $this->assertTrue(Schema::hasTable('subscription_changes'));
        $this->assertTrue(Schema::hasColumns('subscription_changes', ['id', 'subscription_id', 'old_plan_id', 'new_plan_id', 'changed_at']));

        $merchant = Merchant::create(['name' => 'M1', 'email' => 'm1@example.com', 'api_key' => 'k1']);
        $planA = Plan::create(['merchant_id' => $merchant->id, 'name' => 'A', 'base_price' => 50, 'billing_cycle' => 30, 'included_usage_units' => 1000, 'overage_rate_per_unit' => 0.01]);
        $planB = Plan::create(['merchant_id' => $merchant->id, 'name' => 'B', 'base_price' => 100, 'billing_cycle' => 30, 'included_usage_units' => 5000, 'overage_rate_per_unit' => 0.005]);
        $customer = Customer::create(['merchant_id' => $merchant->id, 'name' => 'C', 'email' => 'c@example.com']);

        $start = Carbon::parse('2026-11-01 00:00:00');
        $end = Carbon::parse('2026-11-30 23:59:59');

        $sub = Subscription::create([
            'customer_id' => $customer->id,
            'plan_id' => $planA->id,
            'started_at' => $start,
            'ends_at' => $end,
            'status' => 'active',
        ]);

        $this->assertEquals($planA->id, $sub->plan->id);
        $this->assertEquals($customer->id, $sub->customer->id);

        // Record mid-cycle plan change
        $changeDate = Carbon::parse('2026-11-15 12:00:00');
        $sub->update(['plan_id' => $planB->id]);
        $change = SubscriptionChange::create([
            'subscription_id' => $sub->id,
            'old_plan_id' => $planA->id,
            'new_plan_id' => $planB->id,
            'changed_at' => $changeDate,
        ]);

        $this->assertCount(1, $sub->changes);
        $this->assertEquals($planA->id, $change->oldPlan->id);
        $this->assertEquals($planB->id, $change->newPlan->id);
    }

    public function test_usage_events_table_and_idempotency(): void
    {
        $this->assertTrue(Schema::hasTable('usage_events'));
        $this->assertTrue(Schema::hasColumns('usage_events', ['id', 'customer_id', 'event_id', 'units', 'timestamp']));

        $merchant = Merchant::create(['name' => 'M1', 'email' => 'm1@example.com', 'api_key' => 'k1']);
        $customer = Customer::create(['merchant_id' => $merchant->id, 'name' => 'C', 'email' => 'c@example.com']);

        $event = UsageEvent::create([
            'customer_id' => $customer->id,
            'event_id' => 'evt_unique_12345',
            'units' => 42,
            'timestamp' => now(),
        ]);

        $this->assertEquals('evt_unique_12345', $event->event_id);
        $this->assertEquals(42, $event->units);
        $this->assertEquals($customer->id, $event->customer->id);

        // Idempotency: duplicate event_id must fail unique constraint
        $this->expectException(QueryException::class);
        UsageEvent::create([
            'customer_id' => $customer->id,
            'event_id' => 'evt_unique_12345',
            'units' => 10,
            'timestamp' => now(),
        ]);
    }

    public function test_invoices_table_and_calculations(): void
    {
        $this->assertTrue(Schema::hasTable('invoices'));
        $this->assertTrue(Schema::hasColumns('invoices', [
            'id', 'subscription_id', 'customer_id', 'period_start', 'period_end', 'base_amount', 'overage_units', 'overage_amount', 'total_amount', 'status'
        ]));

        $merchant = Merchant::create(['name' => 'M1', 'email' => 'm1@example.com', 'api_key' => 'k1']);
        $plan = Plan::create(['merchant_id' => $merchant->id, 'name' => 'A', 'base_price' => 50, 'billing_cycle' => 30, 'included_usage_units' => 1000, 'overage_rate_per_unit' => 0.01]);
        $customer = Customer::create(['merchant_id' => $merchant->id, 'name' => 'C', 'email' => 'c@example.com']);
        $sub = Subscription::create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'started_at' => now()->startOfMonth(),
            'ends_at' => now()->endOfMonth(),
        ]);

        $invoice = Invoice::create([
            'subscription_id' => $sub->id,
            'customer_id' => $customer->id,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'base_amount' => 50.00,
            'overage_units' => 500,
            'overage_amount' => 5.00,
            'total_amount' => 55.00,
            'status' => 'draft',
            'generated_at' => now(),
        ]);

        $this->assertEquals(55.00, $invoice->total_amount);
        $this->assertEquals($sub->id, $invoice->subscription->id);
        $this->assertEquals($customer->id, $invoice->customer->id);
    }

    public function test_scale_requirement_daily_usage_aggregations_table_and_model(): void
    {
        $this->assertTrue(Schema::hasTable('daily_usage_aggregations'));
        $this->assertTrue(Schema::hasColumns('daily_usage_aggregations', ['id', 'customer_id', 'usage_date', 'total_units']));

        $merchant = Merchant::create(['name' => 'M1', 'email' => 'm1@example.com', 'api_key' => 'k1']);
        $customer = Customer::create(['merchant_id' => $merchant->id, 'name' => 'C', 'email' => 'c@example.com']);

        $agg = DailyUsageAggregation::create([
            'customer_id' => $customer->id,
            'usage_date' => '2026-11-01',
            'total_units' => 25000,
        ]);

        $this->assertEquals('2026-11-01', $agg->usage_date->toDateString());
        $this->assertEquals(25000, $agg->total_units);
        $this->assertEquals($customer->id, $agg->customer->id);
        $this->assertTrue($customer->dailyUsageAggregations->contains($agg));
    }
}

