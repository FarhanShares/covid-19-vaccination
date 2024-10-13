<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Jobs\BatchScheduleVaccineAppointmentJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Increase the batch size as needed or, as the server can handle
// This determines the no of users to be appointed a vaccination schedule in a batch
// Also, we may adjust the cron timer as needed.
Schedule::job(new BatchScheduleVaccineAppointmentJob(batchSize: 250))
    ->everyTwoSeconds();
