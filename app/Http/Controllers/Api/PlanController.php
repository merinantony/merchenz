<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PlanCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function __construct(
        protected readonly PlanCacheService $planCacheService
    ) {}

    /**
     * List all plans defined for the tenant.
     * GET /api/plans
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $merchantId = $user ? $user->merchant_id : ($request->input('merchant_id') ?: 1);

        $plans = $merchantId
            ? $this->planCacheService->getMerchantPlans($merchantId)
            : Plan::all();

        return response()->json([
            'data' => $plans,
        ]);
    }

    /**
     * Define a new subscription plan (Admin / Merchant core persona).
     * POST /api/plans
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'overage_rate_per_unit' => ['required', 'numeric', 'min:0'],
            'included_units' => ['nullable', 'integer', 'min:0'],
            'included_usage_units' => ['nullable', 'integer', 'min:0'],
            'billing_cycle_days' => ['nullable', 'integer', 'min:1'],
            'merchant_id' => ['nullable', 'exists:merchants,id'],
        ]);

        $merchantId = $user?->merchant_id ?: ($validated['merchant_id'] ?? 1);
        $included = $validated['included_units'] ?? ($validated['included_usage_units'] ?? 10000);

        $plan = Plan::create([
            'merchant_id' => $merchantId,
            'name' => $validated['name'],
            'base_price' => $validated['base_price'],
            'overage_rate_per_unit' => $validated['overage_rate_per_unit'],
            'included_units' => $included,
            'included_usage_units' => $included,
            'billing_cycle' => $validated['billing_cycle_days'] ?? 30,
            'billing_cycle_days' => $validated['billing_cycle_days'] ?? 30,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Subscription plan created successfully.',
            'data' => $plan,
        ], 201);
    }

    /**
     * View a specific plan (served via Cache-aside with Redis/driver).
     * GET /api/plans/{id}
     */
    public function show(int $id): JsonResponse
    {
        $plan = $this->planCacheService->getPlan($id);

        if (!$plan) {
            return response()->json(['message' => 'Plan not found.'], 404);
        }

        return response()->json([
            'data' => $plan,
        ]);
    }

    /**
     * Update an existing plan (triggers PlanObserver cache invalidation).
     * PUT /api/plans/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'overage_rate_per_unit' => ['sometimes', 'numeric', 'min:0'],
            'included_units' => ['sometimes', 'integer', 'min:0'],
            'included_usage_units' => ['sometimes', 'integer', 'min:0'],
            'billing_cycle_days' => ['sometimes', 'integer', 'min:1'],
        ]);

        if (isset($validated['included_units'])) {
            $validated['included_usage_units'] = $validated['included_units'];
        }

        $plan->update($validated);

        return response()->json([
            'message' => 'Plan updated successfully. Pricing cache cleared.',
            'data' => $plan->fresh(),
        ]);
    }
}

