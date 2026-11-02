<?php

namespace Tests\Feature;

use App\Jobs\AggregateDailyUsageJob;
use App\Jobs\GenerateCycleInvoicesJob;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctumApiAndRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_registration_for_merchant_admin_issues_sanctum_token(): void
    {
        $payload = [
            'name' => 'Michael Scott',
            'email' => 'michael@dundermifflin.com',
            'password' => 'paper12345',
            'company_name' => 'Dunder Mifflin Paper Co.',
            'phone' => '+1 (555) 012-3456',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'token',
            'token_type',
            'user' => ['id', 'name', 'email', 'role', 'merchant_id'],
            'merchant' => ['id', 'company_name', 'api_key'],
        ]);

        $this->assertEquals('Bearer', $response->json('token_type'));
        $this->assertNotEmpty($response->json('token'));
        $this->assertEquals(User::ROLE_ADMIN, $response->json('user.role'));

        $this->assertDatabaseHas('merchants', [
            'company_name' => 'Dunder Mifflin Paper Co.',
            'email' => 'michael@dundermifflin.com',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'michael@dundermifflin.com',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_api_login_issues_sanctum_bearer_token(): void
    {
        $merchant = Merchant::create([
            'name' => 'Vance Refrigeration',
            'email' => 'bob@vancerefrig.com',
            'api_key' => 'vance_key_1',
        ]);

        $user = User::create([
            'name' => 'Bob Vance',
            'email' => 'bob@vancerefrig.com',
            'password' => bcrypt('chilly1234'),
            'role' => User::ROLE_ADMIN,
            'merchant_id' => $merchant->id,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'bob@vancerefrig.com',
            'password' => 'chilly1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'token',
            'token_type',
            'user' => ['id', 'name', 'email', 'role', 'merchant_id'],
            'role',
            'merchant_id',
        ]);

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        // Access protected endpoint with Bearer token
        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $meResponse->assertStatus(200);
        $meResponse->assertJsonPath('user.email', 'bob@vancerefrig.com');
        $meResponse->assertJsonPath('merchant.id', $merchant->id);

        // Logout revokes token from database
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $logoutResponse->assertStatus(200);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);

        // Clear in-memory guard cache
        app('auth')->forgetGuards();

        // Subsequent call with revoked token is unauthenticated
        $unauthResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $unauthResponse->assertStatus(401);
    }

    public function test_admin_merchant_persona_can_define_and_update_plans(): void
    {
        $merchant = Merchant::create([
            'name' => 'SaaS Provider Inc',
            'email' => 'admin@saasprovider.io',
            'api_key' => 'provider_api_key',
        ]);

        $admin = User::create([
            'name' => 'Platform Tenant Admin',
            'email' => 'admin@saasprovider.io',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
            'merchant_id' => $merchant->id,
        ]);

        $token = $admin->createToken('admin-token')->plainTextToken;

        // Admin defines subscription plan: base price, included units, overage rate
        $createPlanResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/plans', [
                'name' => 'Custom API Tier',
                'base_price' => 199.00,
                'included_units' => 80000,
                'overage_rate_per_unit' => 0.0125,
                'billing_cycle_days' => 30,
            ]);

        $createPlanResponse->assertStatus(201);
        $createPlanResponse->assertJsonPath('data.name', 'Custom API Tier');
        $this->assertEquals(199.00, (float) $createPlanResponse->json('data.base_price'));
        $this->assertEquals(0.0125, (float) $createPlanResponse->json('data.overage_rate_per_unit'));

        $planId = $createPlanResponse->json('data.id');

        // Admin updates pricing
        $updateResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/plans/{$planId}", [
                'base_price' => 249.00,
                'overage_rate_per_unit' => 0.0150,
            ]);

        $updateResponse->assertStatus(200);
        $this->assertEquals(249.00, (float) $updateResponse->json('data.base_price'));
    }

    public function test_customer_persona_subscribes_and_generates_usage_events(): void
    {
        $merchant = Merchant::create([
            'name' => 'API Host',
            'email' => 'host@api.com',
            'api_key' => 'live_host_key',
        ]);

        $plan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Developer Plan',
            'base_price' => 49.00,
            'billing_cycle' => 30,
            'billing_cycle_days' => 30,
            'included_usage_units' => 10000,
            'included_units' => 10000,
            'overage_rate_per_unit' => 0.0200,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'merchant_id' => $merchant->id,
            'name' => 'End Customer Corp',
            'email' => 'client@customer.com',
        ]);

        // Customer subscribes to plan
        $subResponse = $this->postJson('/api/subscriptions', [
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
        ]);

        $subResponse->assertStatus(201);
        $subResponse->assertJsonPath('data.status', 'active');
        $subResponse->assertJsonPath('data.plan_id', $plan->id);

        // Customer generates usage event
        $usageResponse = $this->postJson('/api/usage', [
            'customer_id' => $customer->id,
            'event_id' => 'evt_sub_001',
            'units' => 25,
            'event_type' => 'document_parsed',
        ]);

        $usageResponse->assertStatus(201);
        $usageResponse->assertJsonPath('data.created', true);

        // Duplicate event is ignored idempotently
        $retryResponse = $this->postJson('/api/usage', [
            'customer_id' => $customer->id,
            'event_id' => 'evt_sub_001',
            'units' => 25,
            'event_type' => 'document_parsed',
        ]);

        $retryResponse->assertStatus(200);
        $retryResponse->assertJsonPath('data.created', false);
    }

    public function test_queued_chunked_job_aggregates_daily_usage_and_generates_prorated_invoices(): void
    {
        $merchant = Merchant::create([
            'name' => 'Billing Master',
            'email' => 'master@billing.com',
            'api_key' => 'master_key_1',
        ]);

        $planA = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Plan Starter',
            'base_price' => 100.00,
            'billing_cycle' => 30,
            'billing_cycle_days' => 30,
            'included_usage_units' => 1000,
            'included_units' => 1000,
            'overage_rate_per_unit' => 0.10,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'merchant_id' => $merchant->id,
            'name' => 'Logistics Client',
            'email' => 'client@logistics.com',
        ]);

        $cycleStart = Carbon::parse('2026-10-01');
        $cycleEnd = Carbon::parse('2026-10-31'); // Exactly 30 days

        $subscription = Subscription::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'plan_id' => $planA->id,
            'started_at' => $cycleStart,
            'ends_at' => $cycleEnd,
            'status' => 'active',
        ]);

        $targetDate = '2026-10-15';

        // Create raw usage events
        UsageEvent::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'event_id' => 'agg_test_01',
            'units' => 750,
            'quantity' => 750,
            'timestamp' => Carbon::parse($targetDate)->setHour(10),
            'recorded_at' => Carbon::parse($targetDate)->setHour(10),
        ]);

        UsageEvent::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'event_id' => 'agg_test_02',
            'units' => 500,
            'quantity' => 500,
            'timestamp' => Carbon::parse($targetDate)->setHour(14),
            'recorded_at' => Carbon::parse($targetDate)->setHour(14),
        ]);

        // 1. Run AggregateDailyUsageJob synchronously
        $aggJob = new AggregateDailyUsageJob($targetDate, 5000);
        $aggJob->handle();

        $this->assertDatabaseHas('daily_usage_aggregations', [
            'customer_id' => $customer->id,
            'usage_date' => $targetDate,
            'total_units' => 1250,
        ]);

        // 2. Run GenerateCycleInvoicesJob synchronously
        $invoiceJob = new GenerateCycleInvoicesJob($subscription->id);
        $invoiceJob->handle(app(\App\Services\CalculateBillingService::class));

        // Invoice should be produced with correct proration & overage math
        $this->assertDatabaseHas('invoices', [
            'subscription_id' => $subscription->id,
            'customer_id' => $customer->id,
        ]);

        $invoice = \App\Models\Invoice::where('subscription_id', $subscription->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(100.00, (float) $invoice->base_amount);
        // Total units 1250 - 1000 included = 250 overage units * $0.10 = $25 overage
        $this->assertEquals(250, (int) $invoice->overage_units);
        $this->assertEquals(25.00, (float) $invoice->overage_amount);
        $this->assertEquals(125.00, (float) $invoice->total_amount);

        // Subscription should now be expired
        $this->assertEquals('expired', $subscription->fresh()->status);
    }
}
