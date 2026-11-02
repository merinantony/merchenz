<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_redirects_root_to_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/merchants/1/dashboard');
    }

    public function test_dashboard_renders_with_dark_and_white_theme_switcher(): void
    {
        $merchant = Merchant::create([
            'name' => 'Demo Tenant',
            'email' => 'demo@example.com',
            'api_key' => 'demo_key_123',
        ]);

        $plan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Starter',
            'base_price' => 49.99,
            'billing_cycle' => 30,
            'included_usage_units' => 50000,
            'overage_rate_per_unit' => 0.002,
        ]);

        $customer = Customer::create([
            'merchant_id' => $merchant->id,
            'name' => 'Demo Customer',
            'email' => 'customer@example.com',
        ]);

        Subscription::create([
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'started_at' => Carbon::now()->startOfMonth(),
            'ends_at' => Carbon::now()->endOfMonth(),
            'status' => 'active',
        ]);

        $response = $this->get("/merchants/{$merchant->id}/dashboard");

        $response->assertStatus(200);
        $response->assertSee('theme-switcher');
        $response->assertSee('btn-theme-dark');
        $response->assertSee('btn-theme-light');
        $response->assertSee('Dark');
        $response->assertSee('White');
    }
}
