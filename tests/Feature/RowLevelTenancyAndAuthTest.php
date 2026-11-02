<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Models\User;
use App\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RowLevelTenancyAndAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_page_renders_with_dark_and_white_theme(): void
    {
        $response = $this->get(route('onboarding.create'));

        $response->assertStatus(200);
        $response->assertSee('Dark');
        $response->assertSee('White');
        $response->assertDontSee('BLACK & RED');
        $response->assertDontSee('RED & WHITE');
        $response->assertSee('Launch Your SaaS Company');
    }

    public function test_login_page_renders_with_dark_and_white_theme(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Dark');
        $response->assertSee('White');
        $response->assertDontSee('BLACK & RED');
        $response->assertDontSee('RED & WHITE');
        $response->assertSee('Sign In');
        $response->assertSee('MerchenZ');
    }

    public function test_onboarding_registers_company_admin_and_attaches_selected_plan(): void
    {
        $seedMerchant = Merchant::create([
            'name' => 'Platform Seed Merchant',
            'email' => 'seed@merchenz.io',
            'api_key' => 'seed_key_001',
        ]);

        $globalPlan = Plan::create([
            'merchant_id' => $seedMerchant->id,
            'name' => 'Growth Tier',
            'base_price' => 149.00,
            'billing_cycle' => 30,
            'billing_cycle_days' => 30,
            'included_usage_units' => 50000,
            'included_units' => 50000,
            'overage_rate_per_unit' => 0.0200,
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Jane Founder',
            'email' => 'jane@innovate.co',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
            'company_name' => 'Innovate Corp',
            'company_email' => 'contact@innovate.co',
            'phone' => '+1 800 555 0199',
            'address' => '100 Innovation Way, Silicon Valley, CA',
            'plan_id' => $globalPlan->id,
        ];

        $response = $this->post(route('onboarding.store'), $payload);

        $this->assertDatabaseHas('merchants', [
            'company_name' => 'Innovate Corp',
            'email' => 'contact@innovate.co',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@innovate.co',
            'role' => User::ROLE_ADMIN,
        ]);

        $merchant = Merchant::where('company_name', 'Innovate Corp')->first();
        $this->assertNotNull($merchant);

        // Should redirect to merchant dashboard
        $response->assertRedirect(route('merchant.dashboard', ['id' => $merchant->id]));
    }

    public function test_row_level_tenancy_isolates_data_between_merchants(): void
    {
        // Merchant A
        $merchantA = Merchant::create(['name' => 'Company A', 'email' => 'a@a.com', 'api_key' => 'key_a']);
        $adminA = User::create([
            'name' => 'Admin A',
            'email' => 'admin@a.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
            'merchant_id' => $merchantA->id,
        ]);
        $customerA = Customer::create(['merchant_id' => $merchantA->id, 'name' => 'Customer A', 'email' => 'cust@a.com']);

        // Merchant B
        $merchantB = Merchant::create(['name' => 'Company B', 'email' => 'b@b.com', 'api_key' => 'key_b']);
        $adminB = User::create([
            'name' => 'Admin B',
            'email' => 'admin@b.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
            'merchant_id' => $merchantB->id,
        ]);
        $customerB = Customer::create(['merchant_id' => $merchantB->id, 'name' => 'Customer B', 'email' => 'cust@b.com']);

        // When authenticated as Admin A, TenantScope restricts queries to Company A
        $this->actingAs($adminA);
        $customers = Customer::all();
        $this->assertCount(1, $customers);
        $this->assertEquals($customerA->id, $customers->first()->id);

        // When authenticated as Admin B, TenantScope restricts queries to Company B
        $this->actingAs($adminB);
        $customers = Customer::all();
        $this->assertCount(1, $customers);
        $this->assertEquals($customerB->id, $customers->first()->id);
    }

    public function test_super_admin_bypasses_tenant_scope_and_sees_all_data(): void
    {
        $superAdmin = User::create([
            'name' => 'Platform Super Admin',
            'email' => 'super@merchenz.io',
            'password' => bcrypt('password'),
            'role' => User::ROLE_SUPER_ADMIN,
            'merchant_id' => null,
        ]);

        $merchantA = Merchant::create(['name' => 'Company A', 'email' => 'a@a.com', 'api_key' => 'key_a_2']);
        $merchantB = Merchant::create(['name' => 'Company B', 'email' => 'b@b.com', 'api_key' => 'key_b_2']);

        Customer::create(['merchant_id' => $merchantA->id, 'name' => 'Customer 1', 'email' => 'c1@a.com']);
        Customer::create(['merchant_id' => $merchantB->id, 'name' => 'Customer 2', 'email' => 'c2@b.com']);

        $this->actingAs($superAdmin);

        // Super Admin query includes all rows across all tenants
        $allCustomers = Customer::all();
        $this->assertCount(2, $allCustomers);

        $response = $this->get(route('superadmin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Platform Super Admin');
        $response->assertSee('Company A');
        $response->assertSee('Company B');
        $response->assertSee('Dark');
        $response->assertSee('White');
    }

    public function test_login_redirects_by_role_tier(): void
    {
        $merchant = Merchant::create(['name' => 'Test Corp', 'email' => 't@t.com', 'api_key' => 'k1']);

        $superAdmin = User::create([
            'name' => 'Super',
            'email' => 'super@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
            'merchant_id' => $merchant->id,
        ]);

        $user = User::create([
            'name' => 'User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_USER,
            'merchant_id' => $merchant->id,
        ]);

        // 1. Super Admin -> superadmin.dashboard
        $response = $this->post(route('login.post'), [
            'email' => 'super@test.com',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('superadmin.dashboard'));
        Auth::logout();

        // 2. Company Admin -> merchant.dashboard
        $response = $this->post(route('login.post'), [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('merchant.dashboard', ['id' => $merchant->id]));
        Auth::logout();

        // 3. Regular User -> user.plan
        $response = $this->post(route('login.post'), [
            'email' => 'user@test.com',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('user.plan'));
        Auth::logout();
    }

    public function test_regular_user_can_view_assigned_plan_only(): void
    {
        $merchant = Merchant::create([
            'name' => 'Acme Express',
            'company_name' => 'Acme Express Corp',
            'email' => 'contact@acmeexpress.com',
            'api_key' => 'acme_key_99',
        ]);

        $plan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Pro Enterprise Tier',
            'base_price' => 299.00,
            'billing_cycle' => 30,
            'billing_cycle_days' => 30,
            'included_usage_units' => 75000,
            'included_units' => 75000,
            'overage_rate_per_unit' => 0.0150,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'merchant_id' => $merchant->id,
            'name' => 'Express Account',
            'email' => 'account@acmeexpress.com',
        ]);

        Subscription::create([
            'customer_id' => $customer->id,
            'merchant_id' => $merchant->id,
            'plan_id' => $plan->id,
            'started_at' => Carbon::now()->startOfMonth(),
            'ends_at' => Carbon::now()->endOfMonth(),
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Mark User',
            'email' => 'mark@acmeexpress.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_USER,
            'merchant_id' => $merchant->id,
        ]);

        $this->actingAs($user);

        // Visiting /my-plan succeeds
        $response = $this->get(route('user.plan'));
        $response->assertStatus(200);
        $response->assertSee('Pro Enterprise Tier');
        $response->assertSee('Acme Express Corp');
        $response->assertSee('Mark User');
        $response->assertSee('75,000');
        $response->assertSee('$299.00');
        $response->assertSee('Dark');
        $response->assertSee('White');
        $response->assertDontSee('BLACK & RED');
        $response->assertDontSee('RED & WHITE');

        // Attempting to visit super admin dashboard gives 403 Forbidden
        $unauthorizedResponse = $this->get(route('superadmin.dashboard'));
        $unauthorizedResponse->assertStatus(403);
    }
}
