<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DailyUsageAggregation;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Models\User;
use App\Services\TenantDatabaseManager;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MultiTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenantDbManager = app(TenantDatabaseManager::class);

        // 1. Create or ensure Super Admin exists
        User::firstOrCreate(
            ['email' => 'superadmin@merchenz.io'],
            [
                'name' => 'Platform Super Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_SUPER_ADMIN,
                'merchant_id' => null,
            ]
        );

        // 2. Global Plans Catalog
        $starter = Plan::firstOrCreate(
            ['name' => 'Starter Tier'],
            [
                'merchant_id' => 1,
                'base_price' => 49.00,
                'billing_cycle' => 30,
                'billing_cycle_days' => 30,
                'included_usage_units' => 10000,
                'included_units' => 10000,
                'overage_rate_per_unit' => 0.0500,
                'is_active' => true,
            ]
        );

        $growth = Plan::firstOrCreate(
            ['name' => 'Growth Tier'],
            [
                'merchant_id' => 1,
                'base_price' => 149.00,
                'billing_cycle' => 30,
                'billing_cycle_days' => 30,
                'included_usage_units' => 50000,
                'included_units' => 50000,
                'overage_rate_per_unit' => 0.0200,
                'is_active' => true,
            ]
        );

        $enterprise = Plan::firstOrCreate(
            ['name' => 'Enterprise Tier'],
            [
                'merchant_id' => 1,
                'base_price' => 499.00,
                'billing_cycle' => 30,
                'billing_cycle_days' => 30,
                'included_usage_units' => 250000,
                'included_units' => 250000,
                'overage_rate_per_unit' => 0.0050,
                'is_active' => true,
            ]
        );

        // 3. Demo Merchant / Company
        $merchant = Merchant::firstOrCreate(
            ['email' => 'admin@acmelogistics.io'],
            [
                'name' => 'Acme Logistics',
                'company_name' => 'Acme Logistics Global Inc.',
                'phone' => '+1 (555) 019-2834',
                'address' => '500 Tech Parkway, Suite 1200, Austin, TX',
                'api_key' => 'acme_live_' . Str::random(32),
                'status' => 'active',
            ]
        );

        // Provision isolated client database for this merchant
        $tenantDbManager->createTenantDatabase($merchant);

        // 4. Company Admin
        User::firstOrCreate(
            ['email' => 'admin@acmelogistics.io'],
            [
                'name' => 'Sarah Connor',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'merchant_id' => $merchant->id,
            ]
        );

        // 5. Regular User (can see his plan only)
        User::firstOrCreate(
            ['email' => 'user@acmelogistics.io'],
            [
                'name' => 'Alex Miller',
                'password' => Hash::make('password'),
                'role' => User::ROLE_USER,
                'merchant_id' => $merchant->id,
            ]
        );

        // 6. Setup demo customer, subscription, and usage in both global & tenant DB
        $customer = Customer::firstOrCreate(
            ['email' => 'billing@acmelogistics.io'],
            [
                'merchant_id' => $merchant->id,
                'name' => 'Primary Tenant Account',
            ]
        );

        $cycleStart = Carbon::now()->startOfMonth();
        $cycleEnd = Carbon::now()->endOfMonth();

        $sub = Subscription::firstOrCreate(
            ['customer_id' => $customer->id, 'plan_id' => $growth->id],
            [
                'merchant_id' => $merchant->id,
                'started_at' => $cycleStart,
                'ends_at' => $cycleEnd,
                'status' => 'active',
            ]
        );

        // Record usage aggregation in both Global DB and Client DB
        DailyUsageAggregation::withoutGlobalScopes()->updateOrCreate(
            ['customer_id' => $customer->id, 'usage_date' => Carbon::now()->startOfDay()],
            [
                'merchant_id' => $merchant->id,
                'total_units' => 18500,
                'total_quantity' => 18500,
            ]
        );

        // Populate isolated client DB with identical isolated data
        $tenantDbManager->runInTenantContext($merchant, function () use ($customer, $sub, $growth, $cycleStart, $cycleEnd) {
            $tCustomer = Customer::on('tenant')->firstOrCreate(
                ['email' => $customer->email],
                ['name' => $customer->name]
            );

            Subscription::on('tenant')->updateOrCreate(
                ['customer_id' => $tCustomer->id, 'plan_id' => $growth->id],
                [
                    'started_at' => $cycleStart,
                    'ends_at' => $cycleEnd,
                    'status' => 'active',
                ]
            );

            DailyUsageAggregation::on('tenant')->updateOrCreate(
                ['customer_id' => $tCustomer->id, 'usage_date' => Carbon::now()->startOfDay()],
                [
                    'total_units' => 18500,
                    'total_quantity' => 18500,
                ]
            );
        });
    }
}

