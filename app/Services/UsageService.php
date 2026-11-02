<?php

namespace App\Services;

use App\DTOs\UsageEventDTO;
use App\Models\UsageEvent;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Service responsible for recording usage events.
 *
 * Idempotency is enforced at the DB level via a UNIQUE constraint on idempotency_key.
 * If a duplicate request arrives, we catch the constraint violation and return the
 * existing event instead of creating a new one. This makes the endpoint safe to
 * call at high throughput with retries — a retried request will never double-count.
 */
class UsageService
{
    /**
     * Record a usage event idempotently.
     *
     * @return array{event: UsageEvent, created: bool}
     */
    public function recordEvent(UsageEventDTO $dto): array
    {
        try {
            $event = UsageEvent::create([
                'customer_id' => $dto->customerId,
                'idempotency_key' => $dto->idempotencyKey,
                'quantity' => $dto->quantity,
                'event_type' => $dto->eventType,
                'recorded_at' => $dto->recordedAt,
            ]);

            return ['event' => $event, 'created' => true];
        } catch (UniqueConstraintViolationException $e) {
            // Idempotency: return the existing event on duplicate key
            $existing = UsageEvent::where('idempotency_key', $dto->idempotencyKey)->first();

            return ['event' => $existing, 'created' => false];
        }
    }
}
