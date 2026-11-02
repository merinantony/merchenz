<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\UsageEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UsageEventFactory extends Factory
{
    protected $model = UsageEvent::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'idempotency_key' => Str::uuid()->toString(),
            'quantity' => fake()->numberBetween(1, 100),
            'event_type' => 'api_call',
            'recorded_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
