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
        $uuid = Str::uuid()->toString();
        $date = fake()->dateTimeBetween('-30 days', 'now');
        $qty = fake()->numberBetween(1, 100);

        return [
            'customer_id' => Customer::factory(),
            'event_id' => $uuid,
            'idempotency_key' => $uuid,
            'units' => $qty,
            'quantity' => $qty,
            'event_type' => 'api_call',
            'timestamp' => $date,
            'recorded_at' => $date,
        ];
    }
}
