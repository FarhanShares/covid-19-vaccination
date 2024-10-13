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
        $this->uniqueId = Str::orderedUuid()->toString();
    }

    public function findDate(
        int|VaccineCenter $vaccineCenter,
    ): Carbon {
        $vaccineCenter = is_int($vaccineCenter)
            ? VaccineCenter::find($vaccineCenter)
            : $vaccineCenter;

        // Get the current date and set up a date object to check the closest available appointment date
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

    /**
     * UseDate increases the "vaccine center usage count" which is stored as Redis data for efficiency.
     * Later in flush method, the count can be persisted in batch and, the Redis data will be flushed.
     *
     * @param \Illuminate\Support\Carbon $date
     * @param int $vaccineCenterId
     * @return void
     */
    public function useDate(
        Carbon $date,
        int $vaccineCenterId,
    ): void {
        $key = $this->getDailyUsageKey($date, $vaccineCenterId);

        /**
         * Though we do flush, but, we can even set a 3 days ttl for the key. The ttl value should be
         * based on server config, if we are confident that the server can process this job without
         * exceeding 3 days, that's fine. Otherwise increase it or, do not even set it.
         *
         * We are skipping it now and relying on flush method do it's job.
         */
        Redis::incr($key, 1);
    }


    /**
     * After processing all users in the batch, flush to persist all necessary data
     * from temporary storage to permanent storage and clear the temporary data too.
     *
     * @param bool $persist
     * @return void
     */
    public function flush(bool $persist = true): void
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

            if ($persist) {
                // Persist the changes (count) in the database
                VaccineCenterDailyUsage::incrementUsage(
                    date: Carbon::parse($date),
                    amount: (int) $usageCount,
                    vaccineCenter: (int) $vaccineCenterId,
                );
            }

            // Delete the redis and cached data as we won't need it anymore
            Redis::del($key);
            Cache::forget("db:$key");
        }
    }

    /**
     * Get the key in a definite format. Used as the in-memory storage key.
     *
     * @param \Illuminate\Support\Carbon $date
     * @param int $vaccineCenterId
     * @return string
     */
    public function getDailyUsageKey(
        Carbon $date,
        int $vaccineCenterId,
    ): string {
        $date = $date->startOfDay()->toDateString();
        return "$this->uniqueId:$vaccineCenterId:$date";
    }

    /**
     * Get the total used slots for a date in a particular vaccine center. This considers the
     * in-memory counts by the BookingService too.
     *
     * @param \Illuminate\Support\Carbon $date
     * @param int $vaccineCenterId
     * @return int
     */
    public function getDailyUsageTotal(
        Carbon $date,
        int $vaccineCenterId,
    ): int {
        // Use Redis to get the current appointment count for this center and date (job-specific count)
        $redisKey = $this->getDailyUsageKey($date, $vaccineCenterId);
        $redisAppointmentsForDate = Redis::get($redisKey) ?? 0;

        /**
         * Retrieve and cache the count from DB to improve performance, use an in-memory cache driver
         * i.e. Redis for better efficiency and scalability.
         *
         * The TTL value should be based on server config, if we are confident that the server can
         * process this job without exceeding the TTL value, we're good to go.
         *
         * Otherwise increase it or, do not even set it.
         */
        $dbUsage = Cache::remember(
            "db:$redisKey",
            now()->addDays(3),
            function () use ($vaccineCenterId, $date) {
                return VaccineCenterDailyUsage::where('vaccine_center_id', $vaccineCenterId)
                    ->whereDate('date', $date->startOfDay()->toDateString())
                    ->first();
            }
        );

        $dbAppointmentsForDate = $dbUsage?->usage_count ?? 0;

        return (int) $redisAppointmentsForDate + (int) $dbAppointmentsForDate;
    }

    /**
     * Helper function to check if a date is a weekend. This uses the UTC or the Global Carbon
     * Timezone settings or env vars in consideration.
     *
     * @param \Illuminate\Support\Carbon $date
     * @return bool
     */
    public function isWeekend(Carbon $date): bool
    {
        return in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);
    }
}
