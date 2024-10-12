<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\VaccineCenter;
use Illuminate\Support\Carbon;
use App\Models\VaccineAppointment;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Models\VaccineCenterDailyUsage;
use App\Support\Enums\AppointmentStatus;
use App\Support\Enums\VaccinationStatus;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class BatchScheduleVaccineAppointmentJob implements ShouldQueue
{
    use Queueable;

    protected UserRepository $userRepository;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $batchSize = 100,
    ) {
        $this->userRepository = new UserRepository();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $users = $this->userRepository->unappointed($this->batchSize);

        // A unique batch identifier
        $uniqueId = Str::orderedUuid();

        foreach ($users as $user) {
            $appointmentDate = $this->findAvailableDate($user->vaccineCenter,  $uniqueId);

            $appointment = VaccineAppointment::create([
                'date'              => $appointmentDate,
                'user_id'           => $user->id,
                'vaccine_center_id' => $user->vaccine_center_id,
                'status'            => AppointmentStatus::SCHEDULED,
            ]);

            $this->userRepository->updateStatus(
                user: $user->nid,
                status: VaccinationStatus::SCHEDULED,
                appointmentId: $appointment->id,
            );

            // Increment the vaccine center usage counter in Redis for efficiency
            $this->incrementDailyUsageCounter(
                date: $appointment->date,
                uniqueId: $uniqueId,
                vaccineCenterId: $user->vaccine_center_id,
            );
        }

        // After processing all users, batch update the VaccineCenterDailyUsage table
        $this->updateDailyUsageFromRedis($uniqueId);
        // Clear the Redis cache after the job finishes
        $this->clearRedisBatchData($uniqueId);
    }

    protected function findAvailableDate(
        VaccineCenter $vaccineCenter,
        string $uniqueId
    ): Carbon {
        // Get the current date and set up a date object to check the next available appointment date
        $nextDate = Carbon::now();

        /**
         * Skip next day if user registered or, the job is running after 8:55 PM.
         * This will ensure that everyone gets notified at 9 PM before the scheduled date
         */
        if (Carbon::now()->gt(Carbon::today()->setTime(20, 55))) {
            $nextDate->addDay();
        }

        // Loop to find the next available date
        do {
            // Skip weekends
            if ($this->isWeekend($nextDate)) {
                $nextDate->addDay();
                continue;
            }

            $totalAppointmentsForDate = $this->getDailyTotalUsage(
                date: $nextDate,
                uniqueId: $uniqueId,
                vaccineCenterId: $vaccineCenter->id,
            );

            // If the combined count is less than the daily capacity, return this date
            if ($totalAppointmentsForDate < (int) $vaccineCenter->daily_capacity) {
                return $nextDate;
            }

            // Otherwise, move to the next day and continue the loop
            $nextDate->addDay();
        } while (true);
    }

    protected function getDailyTotalUsage(
        string $uniqueId,
        int $vaccineCenterId,
        Carbon $date,
    ): int {
        // Use Redis to get the current appointment count for this center and date (job-specific count)
        $redisKey = $this->getDailyUsageKey($uniqueId, $vaccineCenterId, $date);
        $redisAppointmentsForDate = Redis::get($redisKey) ?? 0;

        // Retrieve and cache the count from DB to improve performance
        $dbUsage = Cache::remember("db:$redisKey", now()->addDay(), function () use ($vaccineCenterId, $date) {
            return VaccineCenterDailyUsage::where('vaccine_center_id', $vaccineCenterId)
                ->whereDate('date', $date->startOfDay()->toDateString())
                ->first();
        });

        $dbAppointmentsForDate = $dbUsage?->usage_counter ?? 0;

        // Combine both Redis counter and DB count
        return (int) $redisAppointmentsForDate + (int) $dbAppointmentsForDate;
    }

    protected function getDailyUsageKey(
        string $uniqueId,
        int $vaccineCenterId,
        Carbon $date,
    ): string {
        $date = $date->startOfDay()->toDateString();
        return "$uniqueId:$vaccineCenterId:$date";
    }

    protected function incrementDailyUsageCounter(
        string $uniqueId,
        int $vaccineCenterId,
        Carbon $date,
        int $amount = 1
    ): void {
        $key = $this->getDailyUsageKey($uniqueId, $vaccineCenterId, $date);

        Redis::incr($key, $amount);
    }

    protected function updateDailyUsageFromRedis(string $uniqueId): void
    {
        // Get all vaccine center keys for this batch from Redis
        $keys = Redis::keys("$uniqueId:*");

        foreach ($keys as $key) {
            // Extract vaccine center ID and date from the Redis key
            [$uniqueId, $vaccineCenterId, $date] = explode(':', $key);

            // Get the usage count from Redis
            $usageCount = Redis::get($key);

            // Update the VaccineCenterDailyUsage table in the database
            VaccineCenterDailyUsage::incrementUsage(
                date: Carbon::parse($date),
                amount: (int) $usageCount,
                vaccineCenter: (int) $vaccineCenterId,
            );
        }
    }

    protected function clearRedisBatchData(string $uniqueId): void
    {
        // Get all vaccine center keys for this batch from Redis
        $keys = Redis::keys("$uniqueId:*");

        // Delete all the Redis keys for this batch
        foreach ($keys as $key) {
            Redis::del($key);
        }
    }

    // Helper function to check if a date is a weekend
    protected function isWeekend(Carbon $date): bool
    {
        return in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);
    }
}


// Bulk insert appointment data
// // Calculate usage counts for each vaccine center
// $groupedUsage = $usageCounter->groupBy(['vaccine_center_id', 'date'])->map(function ($group) {
//     return [
//         'date'  => $group->date,
//         'count' => $group->count(),
//     ];
// });

// // Update vaccine center usage for the scheduled dates
// foreach ($groupedUsage as $centerId => $item) {
//     VaccineCenterDailyUsage::incrementUsage(
//         date: Carbon::parse($item['date']),
//         amount: $item['count'],
//         vaccineCenter: $centerId,
//     );
// }

// $usageCounter = collect([]);
// $usageCounter->push([
//     'date'              => $appointmentDate,
//     'vaccine_center_id' => $user->vaccine_center_id,
// ]);
