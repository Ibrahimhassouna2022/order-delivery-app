<?php

use App\Jobs\PurgeAccountJob;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('delivery:expire-stale-offers')->everyMinute();

// Dispatch one purge job per account whose retention window has elapsed
// (FR-035/SC-011); each job is idempotent and re-checks the window.
Schedule::call(function () {
    $purgeDays = (int) config('delivery.retention.personal_data_purge_days');

    User::query()
        ->whereNotNull('deletion_requested_at')
        ->whereNull('anonymized_at')
        ->where('deletion_requested_at', '<=', now()->subDays($purgeDays))
        ->pluck('id')
        ->each(fn (int $userId) => PurgeAccountJob::dispatch($userId));
})->daily();
