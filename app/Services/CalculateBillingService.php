<?php

namespace App\Services;

use App\Models\DailyUsageAggregation;
use App\Models\DailyUsageSummary;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionChange;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CalculateBillingService
 *
 * Implements core billing math, proration, and invoice generation:
 * - Handles mid-cycle plan upgrades and downgrades with split billing.
 * - Usage before the plan change is billed at the old plan's rate and prorated allowance.
 * - Usage after the plan change is billed at the new plan's rate and prorated allowance.
 * - Calculates prorated base prices and overage costs with mathematical precision.
 * - Queries pre-aggregated daily_usage_aggregations to scale efficiently.
 */
class CalculateBillingService
{
    public function __construct(
        protected readonly PlanCacheService $planCache
    ) {}

    /**
     * Generate an invoice for a subscription's billing cycle.
     * Handles single-plan cycles and mid-cycle plan changes with split billing.
     */
    public function generateInvoice(Subscription $subscription): Invoice
    {
        return DB::transaction(function () use ($subscription) {
            $subscription->load(['changes' => function ($q) {
                $q->orderBy('changed_at', 'asc');
            }]);

            $segments = $this->buildBillingSegments($subscription);

            $totalBase = 0.0;
            $totalOverageUnits = 0;
            $totalOverageAmount = 0.0;

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

            $totalCycleDays = $subscription->getCycleDays();

            foreach ($segments as $segment) {
                $lineItem = $this->calculateSegment(
                    $invoice,
                    $segment['plan_id'],
                    Carbon::parse($segment['start']),
                    Carbon::parse($segment['end']),
                    $totalCycleDays,
                    $subscription->customer_id
                );

                $totalBase += (float) $lineItem->base_amount;
                $totalOverageUnits += (int) $lineItem->overage_units;
                $totalOverageAmount += (float) $lineItem->overage_amount;
            }

            $roundedBase = round($totalBase, 2);
            $roundedOverage = round($totalOverageAmount, 2);
            $roundedTotal = round($roundedBase + $roundedOverage, 2);

            $invoice->update([
                'base_amount' => $roundedBase,
                'overage_units' => $totalOverageUnits,
                'overage_amount' => $roundedOverage,
                'total_amount' => $roundedTotal,
            ]);

            return $invoice->fresh(['lineItems']);
        });
    }

    /**
     * Build billing segments timeline from subscription and any mid-cycle changes.
     *
     * Example:
     * Cycle: Jan 01 -> Jan 31
     * Change on Jan 16 from Basic to Pro
     * Segment 1: Basic plan, Jan 01 -> Jan 16 (15 days)
     * Segment 2: Pro plan, Jan 16 -> Jan 31 (15 days)
     *
     * @return array<int, array{plan_id: int, start: string, end: string}>
     */
    public function buildBillingSegments(Subscription $subscription): array
    {
        $segments = [];
        $currentPlanId = $subscription->plan_id;
        $currentStart = $subscription->started_at;

        $changes = $subscription->changes->sortBy('changed_at');

        if ($changes->isNotEmpty()) {
            // Reconstruct timeline starting with original plan before the first change
            $currentPlanId = $changes->first()->old_plan_id;
        }

        foreach ($changes as $change) {
            $changeDate = Carbon::parse($change->changed_at);

            $segments[] = [
                'plan_id' => $currentPlanId,
                'start' => $currentStart->toDateString(),
                'end' => $changeDate->toDateString(),
            ];

            $currentPlanId = $change->new_plan_id;
            $currentStart = $changeDate;
        }

        // Final segment from last change to cycle end
        $segments[] = [
            'plan_id' => $currentPlanId,
            'start' => $currentStart->toDateString(),
            'end' => $subscription->ends_at->toDateString(),
        ];

        return $segments;
    }

    /**
     * Calculate billing math for a single segment (proration & overage).
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
        $prorationFactor = $totalCycleDays > 0 ? ($segmentDays / $totalCycleDays) : 1.0;

        // Base price proration
        $proratedBase = round($plan->base_price * $prorationFactor, 2);

        // Included allowance proration
        $planIncluded = $plan->included_units ?: ($plan->included_usage_units ?: 0);
        $proratedIncluded = (int) floor($planIncluded * $prorationFactor);

        // Actual usage within this segment
        $actualUsage = $this->getUsageForPeriod($customerId, $segmentStart, $segmentEnd);

        // Overage calculation at this plan's specific overage rate
        $overageUnits = max(0, $actualUsage - $proratedIncluded);
        $overageAmount = round($overageUnits * (float) $plan->overage_rate_per_unit, 2);
        $lineTotal = round($proratedBase + $overageAmount, 2);

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
            'line_total' => $lineTotal,
            'description' => "Plan: {$plan->name} ({$segmentStart->format('M d')} - {$segmentEnd->format('M d')})",
        ]);
    }

    /**
     * Get total customer usage for a date range from daily_usage_aggregations or daily_usage_summaries.
     */
    public function getUsageForPeriod(int $customerId, Carbon $start, Carbon $end): int
    {
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $aggSum = (int) DailyUsageAggregation::where('customer_id', $customerId)
            ->where('usage_date', '>=', $startDate)
            ->where('usage_date', '<', $endDate)
            ->sum('total_units');

        $sumSum = (int) DailyUsageSummary::where('customer_id', $customerId)
            ->where('usage_date', '>=', $startDate)
            ->where('usage_date', '<', $endDate)
            ->sum('total_quantity');

        return max($aggSum, $sumSum);
    }

    /**
     * Calculate projected overage for active subscription (used by dashboard analytics).
     *
     * @return array<string, mixed>
     */
    public function projectOverage(Subscription $subscription): array
    {
        $plan = $this->planCache->findOrFail($subscription->plan_id);
        $cycleStart = $subscription->started_at;
        $totalCycleDays = $subscription->getCycleDays();

        $daysElapsed = max(1, (int) $cycleStart->diffInDays(now()));
        $usageSoFar = $this->getUsageForPeriod(
            $subscription->customer_id,
            $cycleStart,
            Carbon::now()
        );

        $projectedTotal = $totalCycleDays > 0
            ? (int) round(($usageSoFar / $daysElapsed) * $totalCycleDays)
            : $usageSoFar;

        $planIncluded = $plan->included_units ?: ($plan->included_usage_units ?: 0);
        $projectedOverage = max(0, $projectedTotal - $planIncluded);
        $projectedOverageRevenue = round($projectedOverage * (float) $plan->overage_rate_per_unit, 2);

        return [
            'usage_so_far' => $usageSoFar,
            'included_units' => $planIncluded,
            'projected_total' => $projectedTotal,
            'projected_overage_units' => $projectedOverage,
            'projected_overage_revenue' => $projectedOverageRevenue,
        ];
    }
}
