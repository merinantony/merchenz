<?php

namespace App\Jobs;

/**
 * AggregateUsageJob
 *
 * Backward-compatible wrapper for AggregateDailyUsageJob.
 */
class AggregateUsageJob extends AggregateDailyUsageJob
{
    // Inherits all chunked aggregation logic and properties
}
