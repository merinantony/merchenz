<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyUsageSummary;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Jobs\AggregateUsageJob;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests for the usage recording endpoint and aggregation job.
 */
class UsageAndAggregationTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;
    private Customer $customer;
    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->plan = Plan::factory()->create(['merchant_id' => $this->merchant->id]);
        $this->customer = Customer::factory()->create(['merchant_id' => $this->merchant->id]);

        Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $this->plan->id,
            'started_at' => Carbon::now()->startOfMonth(),
            'ends_at' => Carbon::now()->endOfMonth(),
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_records_a_usage_event(): void
    {
        $response = $this->postJson('/api/usage', [
            'customer_id' => $this->customer->id,
            'idempotency_key' => 'test-key-001',
            'quantity' => 5,
            'event_type' => 'api_call',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Usage event recorded.',
                'data' => [
                    'customer_id' => $this->customer->id,
                    'idempotency_key' => 'test-key-001',
                    'quantity' => 5,
                    'created' => true,
                ],
            ]);

        $this->assertDatabaseHas('usage_events', [
            'customer_id' => $this->customer->id,
            'idempotency_key' => 'test-key-001',
        ]);
    }

    /** @test */
    public function it_is_idempotent_on_duplicate_requests(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'idempotency_key' => 'duplicate-key-001',
            'quantity' => 10,
        ];

        // First call
        $response1 = $this->postJson('/api/usage', $payload);
        $response1->assertStatus(201);

        // Second call with same idempotency key
        $response2 = $this->postJson('/api/usage', $payload);
        $response2->assertStatus(200)
            ->assertJson([
                'message' => 'Duplicate event — already recorded.',
                'data' => [
                    'created' => false,
                ],
            ]);

        // Only one event in the database
        $this->assertEquals(1, UsageEvent::where('idempotency_key', 'duplicate-key-001')->count());
    }

    /** @test */
    public function it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/usage', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'idempotency_key']);
    }

    /** @test */
    public function it_validates_customer_exists(): void
    {
        $response = $this->postJson('/api/usage', [
            'customer_id' => 99999,
            'idempotency_key' => 'test-key-002',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);
    }

    /** @test */
    public function it_defaults_quantity_to_one(): void
    {
        $response = $this->postJson('/api/usage', [
            'customer_id' => $this->customer->id,
            'idempotency_key' => 'default-qty-key',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('usage_events', [
            'idempotency_key' => 'default-qty-key',
            'quantity' => 1,
        ]);
    }

    /** @test */
    public function aggregation_job_creates_daily_summaries(): void
    {
        $date = Carbon::parse('2024-01-15');

        // Create multiple usage events for the same date
        for ($i = 0; $i < 5; $i++) {
            UsageEvent::create([
                'customer_id' => $this->customer->id,
                'idempotency_key' => Str::uuid()->toString(),
                'quantity' => 100,
                'event_type' => 'api_call',
                'recorded_at' => $date->copy()->addHours($i),
            ]);
        }

        // Run aggregation for that date
        $job = new AggregateUsageJob('2024-01-15');
        $job->handle();

        // Should have one daily summary with total = 500
        $summary = DailyUsageSummary::where('customer_id', $this->customer->id)
            ->where('usage_date', '2024-01-15')
            ->first();

        $this->assertNotNull($summary);
        $this->assertEquals(500, $summary->total_quantity);
    }

    /** @test */
    public function aggregation_job_is_idempotent(): void
    {
        $date = Carbon::parse('2024-01-15');

        UsageEvent::create([
            'customer_id' => $this->customer->id,
            'idempotency_key' => Str::uuid()->toString(),
            'quantity' => 200,
            'event_type' => 'api_call',
            'recorded_at' => $date,
        ]);

        // Run twice
        $job = new AggregateUsageJob('2024-01-15');
        $job->handle();
        $job->handle();

        // Should still have just one summary
        $count = DailyUsageSummary::where('customer_id', $this->customer->id)
            ->where('usage_date', '2024-01-15')
            ->count();

        $this->assertEquals(1, $count);
    }

    /** @test */
    public function aggregation_job_handles_multiple_customers(): void
    {
        $customer2 = Customer::factory()->create(['merchant_id' => $this->merchant->id]);
        $date = Carbon::parse('2024-01-15');

        UsageEvent::create([
            'customer_id' => $this->customer->id,
            'idempotency_key' => Str::uuid()->toString(),
            'quantity' => 100,
            'event_type' => 'api_call',
            'recorded_at' => $date,
        ]);

        UsageEvent::create([
            'customer_id' => $customer2->id,
            'idempotency_key' => Str::uuid()->toString(),
            'quantity' => 200,
            'event_type' => 'api_call',
            'recorded_at' => $date,
        ]);

        $job = new AggregateUsageJob('2024-01-15');
        $job->handle();

        $this->assertEquals(100, DailyUsageSummary::where('customer_id', $this->customer->id)->first()->total_quantity);
        $this->assertEquals(200, DailyUsageSummary::where('customer_id', $customer2->id)->first()->total_quantity);
    }
}
