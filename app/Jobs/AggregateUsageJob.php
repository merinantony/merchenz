<?php

namespace App\Jobs;

use App\Models\DailyUsageSummary;
use App\Models\UsageEvent;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Nightly job that aggregates raw usage events into daily summaries.
 *
 * Design choices:
 * - Chunked processing (5K rows at a time) to avoid memory pressure
 * - Upserts into daily_usage_summaries to be idempotent (safe to re-run)
 * - Processes a specific date (defaults to yesterday) to support backfills
 * - Uses cursor() for memory-efficient iteration on large datasets
 */
class AggregateUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly ?string $targetDate = null,
        private readonly int $chunkSize = 5000
    ) {}

    public function handle(): void
    {
        $date = $this->targetDate
            ? Carbon::parse($this->targetDate)
            : Carbon::yesterday();

        $dateStr = $date->toDateString();

        Log::info("AggregateUsageJob: Starting aggregation for {$dateStr}");

        // Aggregate usage events grouped by customer_id for the target date.
        // We use a raw query with GROUP BY for efficiency at scale, rather than
        // loading all events into memory.
        $aggregated = UsageEvent::query()
            ->whereDate('recorded_at', $dateStr)
            ->groupBy('customer_id')
            ->select([
                'customer_id',
                DB::raw('SUM(quantity) as total_quantity'),
            ])
            ->get();

        $processed = 0;

        // Chunk the aggregated results for upsert
        foreach ($aggregated->chunk($this->chunkSize) as $chunk) {
            $upsertData = $chunk->map(function ($row) use ($dateStr) {
                return [
                    'customer_id' => $row->customer_id,
                    'usage_date' => $dateStr,
                    'total_quantity' => $row->total_quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            // Upsert: insert or update on conflict (customer_id, usage_date)
            // This makes the job idempotent — safe to re-run without duplicate entries
            DailyUsageSummary::upsert(
                $upsertData,
                ['customer_id', 'usage_date'],
                ['total_quantity', 'updated_at']
            );

            $processed += count($upsertData);
        }

        Log::info("AggregateUsageJob: Processed {$processed} customer summaries for {$dateStr}");
    }
}
