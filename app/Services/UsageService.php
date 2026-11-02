<?php

namespace App\Services;

use App\Actions\RecordUsageAction;
use App\DTOs\UsageEventDTO;

/**
 * Service wrapper for usage event recording, delegating to RecordUsageAction.
 */
class UsageService
{
    public function __construct(
        private readonly RecordUsageAction $action
    ) {}

    /**
     * Record a usage event idempotently.
     *
     * @return array{event: ?\App\Models\UsageEvent, created: bool}
     */
    public function recordEvent(UsageEventDTO $dto): array
    {
        return $this->action->execute($dto);
    }

    /**
     * Dispatch usage recording to queue for minimal response time.
     */
    public function dispatchEvent(UsageEventDTO $dto): void
    {
        $this->action->dispatch($dto);
    }
}
