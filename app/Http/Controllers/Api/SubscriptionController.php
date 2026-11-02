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
