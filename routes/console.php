<?php

use App\Jobs\AggregateDailyUsageJob;
use App\Jobs\GenerateCycleInvoicesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Jobs
|--------------------------------------------------------------------------
|
| AggregateDailyUsageJob runs nightly at 1 AM to aggregate yesterday's usage
| events into daily_usage_aggregations. GenerateCycleInvoicesJob runs at 2 AM
| to create invoices for any subscriptions whose billing cycle has ended.
|
| The 1-hour gap ensures aggregation completes before invoice generation starts.
|
*/

Schedule::job(new AggregateDailyUsageJob())->dailyAt('01:00');
Schedule::job(new GenerateCycleInvoicesJob())->dailyAt('02:00');
