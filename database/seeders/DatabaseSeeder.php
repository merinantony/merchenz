<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\DailyUsageSummary;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the database with demo data for the merchant dashboard.
     *
     * Creates "Acme Corp" merchant with plans, customers, subscriptions,
     * usage events, and daily summaries — enough data to populate the dashboard.
     */
    public function run(): void
    {
        // Create the main demo merchant
        $merchant = Merchant::create([
            'name' => 'Acme Corp',
            'email' => 'admin@acmecorp.com',
            'api_key' => 'acme_test_key_' . Str::random(48),
        ]);

        // Create plans
        $starterPlan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Starter',
            'base_price' => 49.99,
            'billing_cycle_days' => 30,
            'included_units' => 50000,
            'overage_rate_per_unit' => 0.0020,
            'is_active' => true,
        ]);

        $growthPlan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Growth',
            'base_price' => 149.99,
            'billing_cycle_days' => 30,
            'included_units' => 250000,
            'overage_rate_per_unit' => 0.0010,
            'is_active' => true,
        ]);

        $enterprisePlan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Enterprise',
            'base_price' => 499.99,
            'billing_cycle_days' => 30,
            'included_units' => 1000000,
            'overage_rate_per_unit' => 0.0005,
            'is_active' => true,
        ]);

        // Create customers with realistic company names
        $customerNames = [
            'Beta Retail Pvt Ltd',
            'Craft Foods Co.',
            'Delta Mart',
            'Nova Traders',
            'QuickMart',
            'Sunrise Logistics',
            'TechWave Solutions',
            'Urban Goods Inc.',
        ];

        $customers = [];
        foreach ($customerNames as $name) {
            $customers[] = Customer::create([
                'merchant_id' => $merchant->id,
                'name' => $name,
                'email' => Str::slug($name) . '@example.com',
            ]);
        }

        $now = Carbon::now();
        $cycleStart = $now->copy()->startOfMonth();
        $cycleEnd = $now->copy()->endOfMonth();

        // Assign subscriptions — spread across plans
        $planAssignments = [
            0 => $growthPlan,     // Beta Retail — Growth
            1 => $growthPlan,     // Craft Foods — Growth
            2 => $growthPlan,     // Delta Mart — Growth
            3 => $starterPlan,    // Nova Traders — Starter
            4 => $starterPlan,    // QuickMart — Starter
            5 => $enterprisePlan, // Sunrise Logistics — Enterprise
            6 => $growthPlan,     // TechWave — Growth
            7 => $starterPlan,    // Urban Goods — Starter
        ];

        foreach ($customers as $i => $customer) {
            Subscription::create([
                'customer_id' => $customer->id,
                'plan_id' => $planAssignments[$i]->id,
                'started_at' => $cycleStart,
                'ends_at' => $cycleEnd,
                'status' => 'active',
            ]);
        }

        // Generate usage data for this month and last month
        // This month: varied usage to create interesting dashboard data
        $thisMonthUsage = [
            38700,  // Beta Retail — high usage
            31050,  // Craft Foods — medium-high
            27000,  // Delta Mart — medium
            12000,  // Nova Traders — medium
            8500,   // QuickMart — low
            95000,  // Sunrise Logistics — high (enterprise)
            22000,  // TechWave — medium
            15000,  // Urban Goods — medium
        ];

        // Last month: some customers had much higher usage (for churn risk detection)
        $lastMonthUsage = [
            35000,  // Beta Retail — similar
            29000,  // Craft Foods — similar
            25000,  // Delta Mart — similar
            31000,  // Nova Traders — 62% drop! (churn risk)
            18000,  // QuickMart — 53% drop! (churn risk)
            90000,  // Sunrise Logistics — similar
            20000,  // TechWave — similar
            14000,  // Urban Goods — similar
        ];

        foreach ($customers as $i => $customer) {
            // Seed this month's daily usage
            $this->seedMonthlyUsage(
                $customer,
                $cycleStart,
                $now,
                $thisMonthUsage[$i]
            );

            // Seed last month's daily usage
            $lastMonthStart = $cycleStart->copy()->subMonth();
            $lastMonthEnd = $cycleStart->copy()->subDay();
            $this->seedMonthlyUsage(
                $customer,
                $lastMonthStart,
                $lastMonthEnd,
                $lastMonthUsage[$i]
            );
        }
    }

    /**
     * Generate daily usage events and summaries for a customer within a date range.
     */
    private function seedMonthlyUsage(
        Customer $customer,
        Carbon $start,
        Carbon $end,
        int $totalUsage
    ): void {
        $days = max(1, (int) $start->diffInDays($end));
        $dailyAverage = (int) ($totalUsage / $days);

        for ($day = 0; $day < $days; $day++) {
            $date = $start->copy()->addDays($day);

            if ($date->gt(Carbon::now())) {
                break;
            }

            // Add some randomness to daily usage (±30%)
            $variance = $dailyAverage * 0.3;
            $dailyUsage = max(1, (int) ($dailyAverage + rand(-$variance, $variance)));

            // Create daily usage summary
            DailyUsageSummary::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'usage_date' => $date->toDateString(),
                ],
                [
                    'total_quantity' => $dailyUsage,
                ]
            );

            // Create a few representative usage events per day
            $eventsPerDay = min(5, max(1, (int) ($dailyUsage / 100)));
            $remaining = $dailyUsage;

            for ($e = 0; $e < $eventsPerDay; $e++) {
                $qty = ($e === $eventsPerDay - 1) ? $remaining : (int) ($remaining / ($eventsPerDay - $e));
                $remaining -= $qty;

                if ($qty <= 0) continue;

                UsageEvent::create([
                    'customer_id' => $customer->id,
                    'idempotency_key' => Str::uuid()->toString(),
                    'quantity' => $qty,
                    'event_type' => 'api_call',
                    'recorded_at' => $date->copy()->addHours(rand(8, 20))->addMinutes(rand(0, 59)),
                ]);
            }
        }
    }
}
