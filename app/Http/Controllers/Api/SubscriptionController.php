<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * POST /api/subscriptions
     *
     * Subscribe a customer to a merchant's plan.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'plan_id' => 'required|integer|exists:plans,id',
            'started_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
        ]);

        $plan = \App\Models\Plan::findOrFail($validated['plan_id']);
        $cycleDays = $plan->billing_cycle_days ?: ($plan->billing_cycle ?: 30);

        $startedAt = !empty($validated['started_at']) ? Carbon::parse($validated['started_at']) : Carbon::now()->startOfMonth();
        $endsAt = !empty($validated['ends_at']) ? Carbon::parse($validated['ends_at']) : (clone $startedAt)->addDays($cycleDays);

        $customer = \App\Models\Customer::findOrFail($validated['customer_id']);

        $subscription = Subscription::create([
            'merchant_id' => $customer->merchant_id ?: $plan->merchant_id,
            'customer_id' => $customer->id,
            'plan_id' => $plan->id,
            'started_at' => $startedAt,
            'ends_at' => $endsAt,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Subscription created successfully.',
            'data' => $subscription->load(['plan', 'customer']),
        ], 201);
    }

    /**
     * POST /api/subscriptions/{id}/change-plan
     *
     * Handle mid-cycle plan upgrade/downgrade.
     * Records the change in subscription_changes for split billing.
     */
    public function changePlan(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'new_plan_id' => 'required|integer|exists:plans,id',
        ]);

        $subscription = Subscription::findOrFail($id);

        if ($subscription->status !== 'active') {
            return response()->json([
                'message' => 'Can only change plan on an active subscription.',
            ], 422);
        }

        $oldPlanId = $subscription->plan_id;
        $changedAt = Carbon::now();

        // Record the change for billing audit
        SubscriptionChange::create([
            'subscription_id' => $subscription->id,
            'old_plan_id' => $oldPlanId,
            'new_plan_id' => $validated['new_plan_id'],
            'changed_at' => $changedAt,
        ]);

        // Update the subscription's current plan
        $subscription->update([
            'plan_id' => $validated['new_plan_id'],
        ]);

        return response()->json([
            'message' => 'Plan changed successfully.',
            'data' => [
                'subscription_id' => $subscription->id,
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => (int) $validated['new_plan_id'],
                'changed_at' => $changedAt->toIso8601String(),
            ],
        ]);
    }
}
