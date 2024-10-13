<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Jobs\BatchSendAppointmentNotifications;
use App\Jobs\BatchScheduleVaccineAppointmentJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/**
 * <<Notes by Farhan Israq>>
 * The notification job needs to be set at 9 PM each day as per the task.
 * The Schedule appointment job can be run in 15 mins interval or as needed.
 *
 * Increase the batch size as needed or, as the server can handle
 * This determines the no of users to be appointed a vaccination schedule in a batch
 * Also, we may adjust the cron timer as needed.
 */

Schedule::job(new BatchScheduleVaccineAppointmentJob(batchSize: 250))
    ->everyTenSeconds();

Schedule::job(new BatchSendAppointmentNotifications(batchSize: 250))
    ->everyTwoSeconds();
