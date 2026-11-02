<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $units = fake()->randomElement([10000, 50000, 100000, 250000, 500000]);
        return [
            'merchant_id' => Merchant::factory(),
            'name' => fake()->randomElement(['Starter', 'Growth', 'Enterprise', 'Pro', 'Scale']),
            'base_price' => fake()->randomElement([29.99, 79.99, 149.99, 299.99, 499.99]),
            'billing_cycle' => 30,
            'billing_cycle_days' => 30,
            'included_usage_units' => $units,
            'included_units' => $units,
            'overage_rate_per_unit' => fake()->randomFloat(4, 0.001, 0.05),
            'is_active' => true,
        ];
    }
}
