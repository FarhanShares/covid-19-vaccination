<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Jobs\BatchUpdateToVaccinatedStatus;
use App\Jobs\BatchSendAppointmentNotifications;
use App\Jobs\BatchScheduleVaccineAppointmentJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/**
 * Increase the batch size as needed or, as the server can handle.
 * Batch size determines the no of users to be processed at a time.
 *
 * Also, we may adjust the cron timer as needed.
 */

Schedule::job(new BatchScheduleVaccineAppointmentJob(batchSize: 250))
    // ->everyTenSeconds();
    ->hourly();

Schedule::job(new BatchSendAppointmentNotifications(batchSize: 250))
    // ->everyTenSeconds();
    ->dailyAt('21:00');

Schedule::job(new BatchUpdateToVaccinatedStatus(batchSize: 250))
    // ->everyFifteenMinutes();
    ->dailyAt('00:00');
