<?php

namespace App\Services;

use App\Models\DailyUsageSummary;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Core billing logic: proration, overage calculation, and invoice generation.
 *
 * Billing Segments:
 * When a customer changes plans mid-cycle, the billing period is split into segments.
 * Each segment is billed independently:
 *   - Prorated base price = plan.base_price * (segment_days / total_cycle_days)
 *   - Prorated included units = plan.included_units * (segment_days / total_cycle_days)
 *   - Overage = max(0, actual_usage - prorated_included) * plan.overage_rate_per_unit
 *
 * This ensures fair billing: usage before a plan change is billed at the old rate,
 * and usage after at the new rate.
 */
class BillingService
{
    public function __construct(
        private readonly PlanCacheService $planCache
    ) {}

    /**
     * Generate an invoice for a subscription's billing cycle.
     *
     * Handles:
     * - Simple case: no plan changes during the cycle
     * - Complex case: one or more mid-cycle plan changes with split billing
     * - Proration for subscriptions starting mid-cycle
     */
    public function generateInvoice(Subscription $subscription): Invoice
    {
        return DB::transaction(function () use ($subscription) {
            $subscription->load(['changes' => function ($q) {
                $q->orderBy('changed_at', 'asc');
            }]);

            $segments = $this->buildBillingSegments($subscription);

            $totalBase = 0;
            $totalOverageUnits = 0;
            $totalOverageAmount = 0;

            $invoice = Invoice::create([
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
                'period_start' => $subscription->started_at->toDateString(),
                'period_end' => $subscription->ends_at->toDateString(),
                'base_amount' => 0,
                'overage_units' => 0,
                'overage_amount' => 0,
                'total_amount' => 0,
                'status' => 'draft',
                'generated_at' => now(),
            ]);

            foreach ($segments as $segment) {
                $lineItem = $this->calculateSegment(
                    $invoice,
                    $segment['plan_id'],
                    Carbon::parse($segment['start']),
                    Carbon::parse($segment['end']),
                    $subscription->getCycleDays(),
                    $subscription->customer_id
                );

                $totalBase += (float) $lineItem->base_amount;
                $totalOverageUnits += $lineItem->overage_units;
                $totalOverageAmount += (float) $lineItem->overage_amount;
            }

            $invoice->update([
                'base_amount' => round($totalBase, 2),
                'overage_units' => $totalOverageUnits,
                'overage_amount' => round($totalOverageAmount, 2),
                'total_amount' => round($totalBase + $totalOverageAmount, 2),
            ]);

            return $invoice->fresh(['lineItems']);
        });
    }

    /**
     * Build billing segments from subscription + any mid-cycle changes.
     *
     * Returns an array of segments, each with:
     *   - plan_id: the plan active during this segment
     *   - start: segment start date
     *   - end: segment end date
     */
    public function buildBillingSegments(Subscription $subscription): array
    {
        $segments = [];
        $currentPlanId = $subscription->plan_id;
        $currentStart = $subscription->started_at;

        // If there are plan changes, resolve the original plan from the first change
        $changes = $subscription->changes->sortBy('changed_at');

        if ($changes->isNotEmpty()) {
            // The subscription's current plan_id is the *latest* plan.
            // Walk changes to reconstruct the timeline.
            $currentPlanId = $changes->first()->old_plan_id;
        }

        foreach ($changes as $change) {
            $segments[] = [
                'plan_id' => $currentPlanId,
                'start' => $currentStart->toDateString(),
                'end' => Carbon::parse($change->changed_at)->toDateString(),
            ];

            $currentPlanId = $change->new_plan_id;
            $currentStart = Carbon::parse($change->changed_at);
        }

        // Final segment: from last change (or start) to cycle end
        $segments[] = [
            'plan_id' => $currentPlanId,
            'start' => $currentStart->toDateString(),
            'end' => $subscription->ends_at->toDateString(),
        ];

        return $segments;
    }

    /**
     * Calculate billing for a single segment.
     */
    public function calculateSegment(
        Invoice $invoice,
        int $planId,
        Carbon $segmentStart,
        Carbon $segmentEnd,
        int $totalCycleDays,
        int $customerId
    ): InvoiceLineItem {
        $plan = $this->planCache->findOrFail($planId);

        $segmentDays = max(1, (int) $segmentStart->diffInDays($segmentEnd));
        $prorationFactor = $totalCycleDays > 0 ? $segmentDays / $totalCycleDays : 1;

        $proratedBase = round($plan->base_price * $prorationFactor, 2);
        $proratedIncluded = (int) floor($plan->included_units * $prorationFactor);

        // Get actual usage for this segment from daily summaries
        $actualUsage = $this->getUsageForPeriod($customerId, $segmentStart, $segmentEnd);

        $overageUnits = max(0, $actualUsage - $proratedIncluded);
        $overageAmount = round($overageUnits * $plan->overage_rate_per_unit, 2);

        return InvoiceLineItem::create([
            'invoice_id' => $invoice->id,
            'plan_id' => $planId,
            'segment_start' => $segmentStart->toDateString(),
            'segment_end' => $segmentEnd->toDateString(),
            'base_amount' => $proratedBase,
            'usage_units' => $actualUsage,
            'included_units' => $proratedIncluded,
            'overage_units' => $overageUnits,
            'overage_amount' => $overageAmount,
            'line_total' => round($proratedBase + $overageAmount, 2),
            'description' => "Plan: {$plan->name} ({$segmentStart->format('M d')} - {$segmentEnd->format('M d')})",
        ]);
    }

    /**
     * Get total usage for a customer within a date range from daily summaries.
     */
    public function getUsageForPeriod(int $customerId, Carbon $start, Carbon $end): int
    {
        return (int) DailyUsageSummary::where('customer_id', $customerId)
            ->where('usage_date', '>=', $start->toDateString())
            ->where('usage_date', '<', $end->toDateString())
            ->sum('total_quantity');
    }

    /**
     * Calculate projected overage for a subscription (used in dashboard).
     */
    public function projectOverage(Subscription $subscription): array
    {
        $plan = $this->planCache->findOrFail($subscription->plan_id);
        $cycleStart = $subscription->started_at;
        $cycleEnd = $subscription->ends_at;
        $totalCycleDays = $subscription->getCycleDays();

        $daysElapsed = max(1, (int) $cycleStart->diffInDays(now()));
        $usageSoFar = $this->getUsageForPeriod(
            $subscription->customer_id,
            $cycleStart,
            Carbon::now()
        );

        // Linear projection: estimated total = (usage_so_far / days_elapsed) * total_cycle_days
        $projectedTotal = $totalCycleDays > 0
            ? (int) round(($usageSoFar / $daysElapsed) * $totalCycleDays)
            : $usageSoFar;

        $projectedOverage = max(0, $projectedTotal - $plan->included_units);
        $projectedOverageRevenue = round($projectedOverage * $plan->overage_rate_per_unit, 2);

        return [
            'usage_so_far' => $usageSoFar,
            'included_units' => $plan->included_units,
            'projected_total' => $projectedTotal,
            'projected_overage_units' => $projectedOverage,
            'projected_overage_revenue' => $projectedOverageRevenue,
        ];
    }
}
