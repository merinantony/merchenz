<?php

namespace App\Actions;

use App\DTOs\UsageEventDTO;
use App\Jobs\RecordUsageJob;
use App\Models\UsageEvent;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * Action class responsible for recording usage events.
 *
 * Requirements:
 * 1. Strict idempotency using event_id (deduplication at DB and application levels).
 * 2. Safely ignores duplicate retried requests without double-counting.
 * 3. High-throughput performance with option for fast asynchronous queue dispatching.
 */
class RecordUsageAction
{
    /**
     * Synchronously execute the usage recording action with strict idempotency.
     *
     * @return array{event: ?UsageEvent, created: bool}
     */
    public function execute(UsageEventDTO $dto): array
    {
        try {
            // Attempt atomic insert
            $event = UsageEvent::create([
                'customer_id' => $dto->customerId,
                'event_id' => $dto->eventId,
                'idempotency_key' => $dto->idempotencyKey,
                'units' => $dto->units,
                'quantity' => $dto->quantity,
                'event_type' => $dto->eventType,
                'timestamp' => $dto->timestamp,
                'recorded_at' => $dto->recordedAt,
            ]);

            Log::info("RecordUsageAction: Recorded new usage event", [
                'event_id' => $dto->eventId,
                'customer_id' => $dto->customerId,
                'units' => $dto->units,
            ]);

            return [
                'event' => $event,
                'created' => true,
            ];
        } catch (UniqueConstraintViolationException | QueryException $e) {
            // Strict Idempotency: Duplicate event_id / idempotency_key detected.
            // Safely ignore without double-counting and return the existing record.
            Log::info("RecordUsageAction: Duplicate event safely ignored without double-counting", [
                'event_id' => $dto->eventId,
                'idempotency_key' => $dto->idempotencyKey,
            ]);

            $existing = UsageEvent::where('event_id', $dto->eventId)
                ->orWhere('idempotency_key', $dto->idempotencyKey)
                ->first();

            return [
                'event' => $existing,
                'created' => false,
            ];
        }
    }

    /**
     * Dispatch usage recording to a fast background queue for minimal API latency.
     */
    public function dispatch(UsageEventDTO $dto): void
    {
        RecordUsageJob::dispatch($dto);
    }
}
