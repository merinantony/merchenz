<?php

namespace App\DTOs;

use Carbon\Carbon;

/**
 * Data Transfer Object for usage event recording.
 * Ensures type safety and validation at the boundary.
 */
readonly class UsageEventDTO
{
    public function __construct(
        public int $customerId,
        public string $idempotencyKey,
        public int $quantity,
        public string $eventType,
        public Carbon $recordedAt,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            customerId: $validated['customer_id'],
            idempotencyKey: $validated['idempotency_key'],
            quantity: $validated['quantity'] ?? 1,
            eventType: $validated['event_type'] ?? 'api_call',
            recordedAt: Carbon::parse($validated['recorded_at'] ?? now()),
        );
    }
}
