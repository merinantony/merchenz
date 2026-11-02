<?php

namespace App\DTOs;

use Carbon\Carbon;
use Illuminate\Support\Str;

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
        public ?string $eventId = null,
        public ?int $units = null,
        public ?Carbon $timestamp = null,
    ) {}

    public static function fromRequest(array $validated): self
    {
        $key = $validated['idempotency_key'] ?? $validated['event_id'] ?? Str::uuid()->toString();
        $qty = $validated['quantity'] ?? $validated['units'] ?? 1;
        $date = Carbon::parse($validated['recorded_at'] ?? $validated['timestamp'] ?? now());

        return new self(
            customerId: $validated['customer_id'],
            idempotencyKey: $key,
            quantity: $qty,
            eventType: $validated['event_type'] ?? 'api_call',
            recordedAt: $date,
            eventId: $key,
            units: $qty,
            timestamp: $date,
        );
    }
}
