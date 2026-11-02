<?php

namespace App\Http\Controllers\Api;

use App\Actions\RecordUsageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordUsageRequest;
use Illuminate\Http\JsonResponse;

class UsageController extends Controller
{
    public function __construct(
        private readonly RecordUsageAction $recordUsageAction
    ) {}

    /**
     * POST /api/usage
     *
     * Record a customer usage event.
     * Features:
     * - Heavily rate-limited (120 req/min).
     * - Strictly idempotent using event_id (retried requests safely ignored).
     * - Minimal response time with optional async queue dispatching.
     */
    public function store(RecordUsageRequest $request): JsonResponse
    {
        $dto = $request->toDTO();

        // If client specifically requests asynchronous background queuing
        if ($request->boolean('async') || $request->header('X-Queue') === 'true') {
            $this->recordUsageAction->dispatch($dto);

            return response()->json([
                'message' => 'Usage event queued for processing.',
                'data' => [
                    'event_id' => $dto->eventId,
                    'idempotency_key' => $dto->idempotencyKey,
                    'customer_id' => $dto->customerId,
                    'units' => $dto->units,
                    'quantity' => $dto->quantity,
                    'queued' => true,
                ],
            ], 202);
        }

        // Fast idempotent execution
        $result = $this->recordUsageAction->execute($dto);

        $statusCode = $result['created'] ? 201 : 200;
        $message = $result['created'] ? 'Usage event recorded.' : 'Duplicate event — already recorded.';

        return response()->json([
            'message' => $message,
            'data' => [
                'id' => $result['event']?->id,
                'customer_id' => $result['event']?->customer_id ?? $dto->customerId,
                'event_id' => $result['event']?->event_id ?? $dto->eventId,
                'idempotency_key' => $result['event']?->idempotency_key ?? $dto->idempotencyKey,
                'units' => $result['event']?->units ?? $dto->units,
                'quantity' => $result['event']?->quantity ?? $dto->quantity,
                'event_type' => $result['event']?->event_type ?? $dto->eventType,
                'timestamp' => $result['event']?->timestamp?->toIso8601String() ?? $dto->timestamp->toIso8601String(),
                'recorded_at' => $result['event']?->recorded_at?->toIso8601String() ?? $dto->recordedAt->toIso8601String(),
                'created' => $result['created'],
            ],
        ], $statusCode);
    }
}
