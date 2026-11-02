<?php

namespace App\DTOs;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

/**
 * Data Transfer Object for usage event recording.
 * Enforces strict typing, immutability, and boundary normalization.
 */
readonly class UsageEventDTO implements Arrayable
{
    public string $eventId;
    public string $idempotencyKey;
    public int $units;
    public int $quantity;
    public Carbon $timestamp;
    public Carbon $recordedAt;

    public function __construct(
        public int $customerId,
        string $eventId,
        int $units = 1,
        public string $eventType = 'api_call',
        ?Carbon $timestamp = null,
        ?string $idempotencyKey = null,
        ?int $quantity = null,
        ?Carbon $recordedAt = null,
    ) {
        $key = $eventId ?: ($idempotencyKey ?: Str::uuid()->toString());
        $this->eventId = $key;
        $this->idempotencyKey = $idempotencyKey ?: $key;

        $qty = $units > 0 ? $units : ($quantity && $quantity > 0 ? $quantity : 1);
        $this->units = $qty;
        $this->quantity = $quantity && $quantity > 0 ? $quantity : $qty;

        $date = $timestamp ?? ($recordedAt ?? now());
        $this->timestamp = $date;
        $this->recordedAt = $recordedAt ?? $date;
    }

    /**
     * Create DTO from validated request payload.
     *
     * @param array<string, mixed> $validated
     */
    public static function fromRequest(array $validated): self
    {
        $eventId = (string) ($validated['event_id'] ?? $validated['idempotency_key'] ?? Str::uuid()->toString());
        $idempotencyKey = (string) ($validated['idempotency_key'] ?? $eventId);
        $units = isset($validated['units']) ? (int) $validated['units'] : (isset($validated['quantity']) ? (int) $validated['quantity'] : 1);
        $quantity = isset($validated['quantity']) ? (int) $validated['quantity'] : $units;
        $eventType = (string) ($validated['event_type'] ?? 'api_call');
        
        $rawTime = $validated['timestamp'] ?? $validated['recorded_at'] ?? now();
        $timestamp = $rawTime instanceof Carbon ? $rawTime : Carbon::parse($rawTime);
        $recordedAt = $timestamp;

        return new self(
            customerId: (int) $validated['customer_id'],
            eventId: $eventId,
            units: $units,
            eventType: $eventType,
            timestamp: $timestamp,
            idempotencyKey: $idempotencyKey,
            quantity: $quantity,
            recordedAt: $recordedAt,
        );
    }

    /**
     * Convert DTO to associative array for persistence.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'customer_id' => $this->customerId,
            'event_id' => $this->eventId,
            'idempotency_key' => $this->idempotencyKey,
            'units' => $this->units,
            'quantity' => $this->quantity,
            'event_type' => $this->eventType,
            'timestamp' => $this->timestamp,
            'recorded_at' => $this->recordedAt,
        ];
    }
}
