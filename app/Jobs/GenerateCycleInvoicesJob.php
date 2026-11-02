<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\CalculateBillingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * GenerateCycleInvoicesJob
 *
 * Runs at the end of a billing cycle to produce itemized invoices.
 *
 * Features:
 * - Uses CalculateBillingService for mid-cycle proration & overage math.
 * - Strictly idempotent: checks for existing cycle invoices to avoid double billing.
 * - Chunked subscription processing to handle high tenant volume smoothly.
 */
class GenerateCycleInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly ?int $subscriptionId = null
    ) {
        $this->onQueue('invoicing');
    }

    public function handle(CalculateBillingService $billingService): void
    {
        if ($this->subscriptionId) {
            // Generate invoice for a specific subscription
            $subscription = Subscription::findOrFail($this->subscriptionId);
            $this->processSubscription($subscription, $billingService);
            return;
        }

        // Find all active subscriptions whose billing cycle has ended
        $query = Subscription::where('status', 'active')
            ->where('ends_at', '<=', Carbon::now());

        $query->chunk(100, function ($subscriptions) use ($billingService) {
            foreach ($subscriptions as $subscription) {
                $this->processSubscription($subscription, $billingService);
            }
        });
    }

    private function processSubscription(Subscription $subscription, CalculateBillingService $billingService): void
    {
        try {
            // Idempotency: check if invoice already generated for this billing cycle period
            $existingInvoice = $subscription->invoices()
                ->where('period_start', $subscription->started_at->toDateString())
                ->where('period_end', $subscription->ends_at->toDateString())
                ->first();

            if ($existingInvoice) {
                Log::info("GenerateCycleInvoicesJob: Invoice #{$existingInvoice->id} already exists for subscription {$subscription->id}");
                return;
            }

            // Calculate billing and create invoice with split segments
            $invoice = $billingService->generateInvoice($subscription);

            // Mark the subscription status as expired upon successful billing
            $subscription->update(['status' => 'expired']);

            Log::info("GenerateCycleInvoicesJob: Generated invoice #{$invoice->id} for subscription {$subscription->id}, total: ₹{$invoice->total_amount}");
        } catch (\Throwable $e) {
            Log::error("GenerateCycleInvoicesJob: Failed for subscription {$subscription->id}: {$e->getMessage()}");
            throw $e;
        }
    }
}
