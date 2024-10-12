<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\VaccineCenter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Models\VaccineCenterDailyUsage;

class BookingService
{
    public string $uniqueId;

    public function __construct()
    {
        $this->uniqueId = Str::orderedUuid();
    }

    public function findDate(
        int|VaccineCenter $vaccineCenter,
    ): Carbon {
        $vaccineCenter = is_int($vaccineCenter)
            ? VaccineCenter::find($vaccineCenter)
            : $vaccineCenter;

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

            $totalAppointmentsForDate = $this->getDailyUsageTotal(
                date: $nextDate,
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

    // Increment the vaccine center usage counter in Redis for efficiency
    public function useDate(
        Carbon $date,
        int $vaccineCenterId,
    ): void {
        $key = $this->getDailyUsageKey($date, $vaccineCenterId);

        Redis::incr($key, 1);
    }

    // After processing all users, batch update the VaccineCenterDailyUsage table
    public function flush(): void
    {
        // Get all vaccine center keys for this batch from Redis
        $keys = Redis::keys("$this->uniqueId:*");
        $prefix = config('database.redis.options.prefix');

        foreach ($keys as $key) {
            // Extract vaccine center ID and date from the Redis key
            $key = str_replace($prefix, '', $key);
            [$uniqueId, $vaccineCenterId, $date] = explode(':', $key);

            // Get the usage count from Redis
            $usageCount = Redis::get($key) ?? 0;

            // Update the VaccineCenterDailyUsage table in the database
            VaccineCenterDailyUsage::incrementUsage(
                date: Carbon::parse($date),
                amount: (int) $usageCount,
                vaccineCenter: (int) $vaccineCenterId,
            );

            // Delete the redis data as we won't need it anymore
            Redis::del($key);
        }
    }

    public function getDailyUsageKey(
        Carbon $date,
        int $vaccineCenterId,
    ): string {
        $date = $date->startOfDay()->toDateString();
        return "$this->uniqueId:$vaccineCenterId:$date";
    }

    public function getDailyUsageTotal(
        Carbon $date,
        int $vaccineCenterId,
    ): int {
        // dd($vaccineCenterId);
        // Use Redis to get the current appointment count for this center and date (job-specific count)
        $redisKey = $this->getDailyUsageKey($date, $vaccineCenterId);
        $redisAppointmentsForDate = Redis::get($redisKey) ?? 0;
        // dd($redisAppointmentsForDate);
        // Retrieve and cache the count from DB to improve performance
        // $dbUsage = Cache::remember("db:$redisKey", now()->addDay(), function () use ($vaccineCenterId, $date) {
        //     return VaccineCenterDailyUsage::where('vaccine_center_id', $vaccineCenterId)
        //         ->whereDate('date', $date->startOfDay()->toDateString())
        //         ->first();
        // });

        // Using uncached version for debugging
        $dbUsage = VaccineCenterDailyUsage::where('vaccine_center_id', $vaccineCenterId)
            ->whereDate('date', $date->startOfDay()->toDateString())
            ->first();

        $dbAppointmentsForDate = $dbUsage?->usage_count ?? 0;
        $total = (int) $redisAppointmentsForDate + (int) $dbAppointmentsForDate;
        // \Log::info("$vaccineCenterId : {$date->startOfDay()->toDateString()} : redis $redisAppointmentsForDate : db: $dbAppointmentsForDate : total $x");
        // Combine both Redis counter and DB count
        return $total;
    }

    // Helper function to check if a date is a weekend
    public function isWeekend(Carbon $date): bool
    {
        return in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);
    }
}
