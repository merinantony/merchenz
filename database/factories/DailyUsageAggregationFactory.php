<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\DailyUsageAggregation;
use Illuminate\Database\Eloquent\Factories\Factory;

class DailyUsageAggregationFactory extends Factory
{
    protected $model = DailyUsageAggregation::class;

    public function definition(): array
    {
        $units = fake()->numberBetween(100, 50000);

        return [
            'customer_id' => Customer::factory(),
            'usage_date' => fake()->date(),
            'total_units' => $units,
            'total_quantity' => $units,
        ];
    }
}

