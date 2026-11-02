<?php

namespace App\Jobs;

use App\Actions\RecordUsageAction;
use App\DTOs\UsageEventDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fast queue worker job for asynchronous usage event ingestion.
 * Keeps API response times near zero (<10ms) by offloading database insertion to background workers.
 */
class RecordUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public readonly UsageEventDTO $dto
    ) {
        $this->onQueue('usage');
    }

    public function handle(RecordUsageAction $action): void
    {
        $action->execute($this->dto);
    }
}

