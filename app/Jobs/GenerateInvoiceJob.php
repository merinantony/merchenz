<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job that generates invoices for subscriptions whose billing cycle has ended.
 *
 * Dispatched after the nightly aggregation job completes.
 * Processes subscriptions in chunks to avoid memory issues with large tenant bases.
 */
class GenerateInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly ?int $subscriptionId = null
    ) {}

    public function handle(BillingService $billingService): void
    {
        if ($this->subscriptionId) {
            // Generate invoice for a specific subscription
            $subscription = Subscription::findOrFail($this->subscriptionId);
            $this->processSubscription($subscription, $billingService);
            return;
        }

        // Find all active subscriptions whose cycle has ended
        $query = Subscription::where('status', 'active')
            ->where('ends_at', '<=', Carbon::now());

        $query->chunk(100, function ($subscriptions) use ($billingService) {
            foreach ($subscriptions as $subscription) {
                $this->processSubscription($subscription, $billingService);
            }
        });
    }

    private function processSubscription(Subscription $subscription, BillingService $billingService): void
    {
        try {
            // Check if invoice already exists for this period (idempotency)
            $existingInvoice = $subscription->invoices()
                ->where('period_start', $subscription->started_at->toDateString())
                ->where('period_end', $subscription->ends_at->toDateString())
                ->first();

            if ($existingInvoice) {
                Log::info("GenerateInvoiceJob: Invoice already exists for subscription {$subscription->id}");
                return;
            }

            $invoice = $billingService->generateInvoice($subscription);

            // Mark the subscription as expired after invoicing
            $subscription->update(['status' => 'expired']);

            Log::info("GenerateInvoiceJob: Generated invoice #{$invoice->id} for subscription {$subscription->id}, total: {$invoice->total_amount}");
        } catch (\Throwable $e) {
            Log::error("GenerateInvoiceJob: Failed for subscription {$subscription->id}: {$e->getMessage()}");
            throw $e;
        }
    }
}
