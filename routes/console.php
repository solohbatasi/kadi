<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('payments:expire-pending-transactions')->everyFiveMinutes();
Schedule::command('payments:cleanup-idempotency-keys')->dailyAt('02:15');
Schedule::command('payments:reconciliation-report')->dailyAt('02:30');
Schedule::command('payments:security-check')->dailyAt('03:00');
Schedule::command('operations:check-alerts')->everyFiveMinutes();
