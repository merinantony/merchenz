<?php

use App\Jobs\AggregateUsageJob;
use App\Jobs\GenerateInvoiceJob;
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
| AggregateUsageJob runs nightly at 1 AM to aggregate yesterday's usage events
| into daily summaries. GenerateInvoiceJob runs at 2 AM to create invoices for
| any subscriptions whose billing cycle ended.
|
| The 1-hour gap ensures aggregation completes before invoice generation starts.
|
*/

Schedule::job(new AggregateUsageJob())->dailyAt('01:00');
Schedule::job(new GenerateInvoiceJob())->dailyAt('02:00');
