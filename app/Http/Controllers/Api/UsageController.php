<?php

namespace App\Http\Controllers\Api;

use App\DTOs\UsageEventDTO;
use App\Http\Controllers\Controller;
use App\Services\UsageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsageController extends Controller
{
    public function __construct(
        private readonly UsageService $usageService
    ) {}

    /**
     * POST /api/usage
     *
     * Record a usage event. Idempotent — retried requests won't double-count.
     * Rate-limited via middleware (120 req/min per API key).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'idempotency_key' => 'required|string|max:128',
            'quantity' => 'sometimes|integer|min:1',
            'event_type' => 'sometimes|string|max:50',
            'recorded_at' => 'sometimes|date',
        ]);

        $dto = UsageEventDTO::fromRequest($validated);
        $result = $this->usageService->recordEvent($dto);

        $statusCode = $result['created'] ? 201 : 200;
        $message = $result['created'] ? 'Usage event recorded.' : 'Duplicate event — already recorded.';

        return response()->json([
            'message' => $message,
            'data' => [
                'id' => $result['event']->id,
                'customer_id' => $result['event']->customer_id,
                'idempotency_key' => $result['event']->idempotency_key,
                'quantity' => $result['event']->quantity,
                'event_type' => $result['event']->event_type,
                'recorded_at' => $result['event']->recorded_at->toIso8601String(),
                'created' => $result['created'],
            ],
        ], $statusCode);
    }
}
