<?php

namespace App\Jobs;

use App\Models\DailyUsageAggregation;
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
 * AggregateDailyUsageJob
 *
 * Runs nightly, processing raw usage_events in chunks of 5,000 rows,
 * moving aggregated totals into daily_usage_aggregations.
 *
 * Characteristics:
 * - Chunked processing (5,000 rows at a time) to avoid memory pressure on large event tables.
 * - Upserts into daily_usage_aggregations table to be strictly idempotent.
 * - Allows targetDate parameter for historical re-runs and backfills.
 */
class AggregateDailyUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly ?string $targetDate = null,
        public readonly int $chunkSize = 5000
    ) {
        $this->onQueue('aggregation');
    }

    public function handle(): void
    {
        $date = $this->targetDate
            ? Carbon::parse($this->targetDate)
            : Carbon::yesterday();

        $dateStr = $date->toDateString();

        Log::info("AggregateDailyUsageJob: Starting aggregation for {$dateStr} (chunk size: {$this->chunkSize})");

        // Aggregate usage events grouped by customer_id for the target date.
        // Handles both quantity and units column variations gracefully.
        $aggregated = UsageEvent::query()
            ->where(function ($q) use ($dateStr) {
                $q->whereDate('timestamp', $dateStr)
                  ->orWhereDate('recorded_at', $dateStr);
            })
            ->groupBy('customer_id')
            ->select([
                'customer_id',
                DB::raw('SUM(CASE WHEN quantity IS NOT NULL AND quantity > 0 THEN quantity WHEN units IS NOT NULL AND units > 0 THEN units ELSE 1 END) as total_units'),
            ])
            ->get();

        $processed = 0;

        // Process in chunks of 5,000 rows
        foreach ($aggregated->chunk($this->chunkSize) as $chunk) {
            $upsertData = $chunk->map(function ($row) use ($dateStr) {
                $units = (int) $row->total_units;

                return [
                    'customer_id' => $row->customer_id,
                    'usage_date' => $dateStr,
                    'total_units' => $units,
                    'total_quantity' => $units,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            // Upsert into daily_usage_aggregations table (idempotent ON CONFLICT UPDATE)
            DailyUsageAggregation::upsert(
                $upsertData,
                ['customer_id', 'usage_date'],
                ['total_units', 'total_quantity', 'updated_at']
            );

            // Keep daily_usage_summaries in sync for backward compatibility
            DailyUsageSummary::upsert(
                $upsertData,
                ['customer_id', 'usage_date'],
                ['total_quantity', 'total_units', 'updated_at']
            );

            $processed += count($upsertData);
        }

        Log::info("AggregateDailyUsageJob: Successfully processed {$processed} customer aggregations for {$dateStr}");
    }
}
