<?php

namespace Tests\Feature;

use App\Actions\RecordUsageAction;
use App\DTOs\UsageEventDTO;
use App\Jobs\RecordUsageJob;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UsageEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecordUsageApiTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $merchant = Merchant::create([
            'name' => 'Acme Tech',
            'email' => 'acme@example.com',
            'api_key' => 'acme_test_api_key_123',
        ]);

        $plan = Plan::create([
            'merchant_id' => $merchant->id,
            'name' => 'Growth',
            'base_price' => 149.99,
            'billing_cycle' => 30,
            'included_usage_units' => 250000,
            'overage_rate_per_unit' => 0.0010,
        ]);

        $this->customer = Customer::create([
            'merchant_id' => $merchant->id,
            'name' => 'Beta Logistics',
            'email' => 'beta@logistics.example',
        ]);

        Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $plan->id,
            'started_at' => Carbon::now()->startOfMonth(),
            'ends_at' => Carbon::now()->endOfMonth(),
            'status' => 'active',
        ]);
    }

    public function test_api_controller_records_usage_with_event_id_and_units(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'event_id' => 'evt_req_abc_123',
            'units' => 25,
            'event_type' => 'document_parsed',
            'timestamp' => '2026-11-02T10:00:00Z',
        ];

        $response = $this->postJson('/api/usage', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Usage event recorded.',
                'data' => [
                    'customer_id' => $this->customer->id,
                    'event_id' => 'evt_req_abc_123',
                    'units' => 25,
                    'event_type' => 'document_parsed',
                    'created' => true,
                ],
            ]);

        $this->assertDatabaseHas('usage_events', [
            'customer_id' => $this->customer->id,
            'event_id' => 'evt_req_abc_123',
            'units' => 25,
        ]);
    }

    public function test_api_controller_strictly_idempotent_ignoring_retries_without_double_counting(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'event_id' => 'evt_idempotent_999',
            'units' => 50,
        ];

        // First attempt -> 201 Created
        $response1 = $this->postJson('/api/usage', $payload);
        $response1->assertStatus(201)
            ->assertJson([
                'message' => 'Usage event recorded.',
                'data' => ['created' => true],
            ]);

        // Retried attempt with the exact same event_id -> 200 OK (safely ignored, not re-counted)
        $response2 = $this->postJson('/api/usage', $payload);
        $response2->assertStatus(200)
            ->assertJson([
                'message' => 'Duplicate event — already recorded.',
                'data' => [
                    'event_id' => 'evt_idempotent_999',
                    'units' => 50,
                    'created' => false,
                ],
            ]);

        // Database count must strictly be 1 — no double counting
        $this->assertEquals(1, UsageEvent::where('event_id', 'evt_idempotent_999')->count());
    }

    public function test_form_request_validates_customer_and_event_id(): void
    {
        // Missing customer_id and event_id
        $response = $this->postJson('/api/usage', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'event_id']);

        // Non-existent customer
        $responseCustomer = $this->postJson('/api/usage', [
            'customer_id' => 999999,
            'event_id' => 'evt_invalid_cust',
        ]);
        $responseCustomer->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);
    }

    public function test_dto_instantiation_and_serialization(): void
    {
        $dto = UsageEventDTO::fromRequest([
            'customer_id' => $this->customer->id,
            'event_id' => 'evt_dto_test',
            'units' => 15,
            'event_type' => 'ai_inference',
            'timestamp' => '2026-11-02T12:00:00Z',
        ]);

        $this->assertEquals($this->customer->id, $dto->customerId);
        $this->assertEquals('evt_dto_test', $dto->eventId);
        $this->assertEquals(15, $dto->units);
        $this->assertEquals('ai_inference', $dto->eventType);

        $array = $dto->toArray();
        $this->assertArrayHasKey('event_id', $array);
        $this->assertArrayHasKey('units', $array);
        $this->assertEquals('evt_dto_test', $array['event_id']);
    }

    public function test_record_usage_action_directly(): void
    {
        $action = new RecordUsageAction();

        $dto = UsageEventDTO::fromRequest([
            'customer_id' => $this->customer->id,
            'event_id' => 'evt_action_direct_1',
            'units' => 10,
        ]);

        $result1 = $action->execute($dto);
        $this->assertTrue($result1['created']);
        $this->assertNotNull($result1['event']);

        // Re-execute with identical event_id
        $result2 = $action->execute($dto);
        $this->assertFalse($result2['created']);
        $this->assertEquals($result1['event']->id, $result2['event']->id);
        $this->assertEquals(1, UsageEvent::where('event_id', 'evt_action_direct_1')->count());
    }

    public function test_fast_queue_dispatching_for_minimal_latency(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/usage', [
            'customer_id' => $this->customer->id,
            'event_id' => 'evt_async_queued_1',
            'units' => 5,
            'async' => true,
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'message' => 'Usage event queued for processing.',
                'data' => [
                    'event_id' => 'evt_async_queued_1',
                    'queued' => true,
                ],
            ]);

        Queue::assertPushed(RecordUsageJob::class, function ($job) {
            return $job->dto->eventId === 'evt_async_queued_1';
        });
    }

    public function test_endpoint_rate_limiting_enforces_120_requests_per_minute(): void
    {
        $ip = '198.51.100.42';

        // Perform 120 calls within rate limit
        for ($i = 1; $i <= 120; $i++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->postJson('/api/usage', [
                    'customer_id' => $this->customer->id,
                    'event_id' => "evt_rate_limit_{$i}",
                    'units' => 1,
                ]);

            $this->assertEquals(201, $response->getStatusCode(), "Request {$i} should succeed within 120 req/min limit.");
        }

        // 121st call must hit HTTP 429 Too Many Requests
        $exceededResponse = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/usage', [
                'customer_id' => $this->customer->id,
                'event_id' => 'evt_rate_limit_121',
                'units' => 1,
            ]);

        $exceededResponse->assertStatus(429);
    }
}

