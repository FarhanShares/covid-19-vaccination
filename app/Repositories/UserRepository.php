<?php

namespace App\Repositories;

use App\Models\User;
use App\Jobs\StoreUserJob;
use Illuminate\Support\Carbon;
use App\Models\VaccineAppointment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Auth\Events\Registered;
use App\Support\Enums\AppointmentStatus;
use App\Support\Enums\VaccinationStatus;

/**
 * The class makes use of Cache for better efficiency, a performant in-memory cache storage
 * i.e. Redis will significantly improve the performance. Hence, ensure to configure it.
 */
class UserRepository
{
    /**
     * A session (KEY:REGISTRATION_COMPLETED_SESSION) should be flashed containing User NID
     * once the registration has been completed successfully. This is primarily used
     * for displaying a welcome screen / notification to the new user.
     *
     * @var string the session key name for successful registration
     */
    public const REGISTRATION_COMPLETED_SESSION = 'REGISTRATION_COMPLETED';


    /**
     * Find a User by his or her NID
     *
     * It first checks in pool, then in db.
     * If checked in db, if not pooled, pools.
     *
     * @param int $nid
     * @return mixed
     */
    public function findByNid(int $nid): ?User
    {
        $cacheKey = self::getCacheKey($nid);
        if ($cachedUser = Cache::get($cacheKey)) {
            return $cachedUser;
        }

        $user = User::where("nid", $nid)->first();

        if (!$user) {
            return null;
        }

        $this->pool($user);

        return $user;
    }

    /**
     * Check the user's existence in the Pool or DB.
     *
     * @param int|\App\Models\User $user
     * @return bool
     */
    public function exists(int|User $user): bool
    {
        $nid = $user instanceof User ? $user->id : $user;

        return !!$this->findByNid($nid);
    }

    /**
     * Check the user's existence in DB
     *
     * @param int|\App\Models\User $user
     * @param bool $useCache
     * @return bool
     */
    public function existsInDB(int|User $user, bool $useCache = false): bool
    {
        $nid  = $user instanceof User ? $user->id : $user;
        $user = User::query()
            ->select($useCache ? ['id', 'nid'] : '*')
            ->where('nid', $nid)
            ->first();

        if ($user) {
            if ($useCache) {
                $this->pool($user);
            }

            return true;
        }

        return false;
    }

    /**
     * Check the user's existence in pool
     *
     * @param int|\App\Models\User $user
     * @param bool $useCache
     * @return bool
     */
    public function existsInPool(int|User $user, bool $useCache = false): bool
    {
        $cacheKey = self::getCacheKey($user);

        if ($cachedUser = Cache::get($cacheKey)) {
            if ($useCache) {
                $this->pool($cachedUser);
            }

            return true;
        }

        return true;
    }

    /**
     * Save a user upon registering
     *
     * @param \App\Models\User $user
     * @return void
     */
    public function save(User $user)
    {
        if ($this->existsInDB($user)) {
            $this->pool($user);
            return;
        }

        // Save to cache storage
        $this->pool($user);

        // Push to queue to be saved in the database asynchronously
        dispatch(new StoreUserJob($user->nid));
    }

    /**
     * Update the user status both in the DB and the Cache storage.
     *
     * @param int|\App\Models\User $user Integer NID or User Model
     * @param \App\Support\Enums\VaccinationStatus $status
     * @return void
     */
    public function updateStatus(
        int|User $user,
        VaccinationStatus $status,
    ): void {
        if (is_int($user)) {
            $user = $this->findByNid($user);
        }

        if ($user) {
            $user->status = $status->value;
            $user->save(); // Save to DB

            // Refresh the cache with the updated status
            $this->pool($user);
        }
    }

    /**
     * Update users' status in batch, both in the DB and the Cache storage.
     *
     * @param int|\App\Models\User $user Integer NID or User Model
     * @param \App\Support\Enums\VaccinationStatus $status
     * @return void
     */
    public function updateManyStatus(
        array $ids,
        VaccinationStatus $status,
    ): void {
        // Update the users' status
        User::whereIn('id', $ids)->update(['status' => $status->value]);

        // Refresh the cache pool
        $users = User::whereIn('id', $ids)->get();

        $users->each(function (User $user) use ($status) {
            $this->pool($user);
        });
    }

    /**
     * Get the users collection who haven't yet appointed a schedule for vaccination.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function unappointed(int $limit = 100)
    {
        return User::query()
            ->with('vaccineCenter:id,daily_capacity')
            ->where('status', VaccinationStatus::NOT_SCHEDULED->value)
            ->orderBy('created_at',  'asc') // First come, first served
            ->limit($limit)
            ->get();
    }

    /**
     * Get the appointment collection with users who hasn't yet received
     * a notification for vaccination and has the schedule tomorrow.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function notifiableToday(int $limit = 100)
    {
        return VaccineAppointment::query()
            ->with('user:id,name,email')
            ->where('status', AppointmentStatus::SCHEDULED->value)
            ->whereDate('date', '=', Carbon::tomorrow()->startOfDay()->toDateString())
            ->orderBy('created_at',  'asc') // First come, first served
            ->limit($limit)
            ->get();
    }

    /**
     * Get appointments that are past the vaccination date but are still in the NOTIFIED status.
     *
     * @param int $limit
     */
    public function pastAppointments(int $limit = 100)
    {
        return VaccineAppointment::query()
            ->select(['id', 'user_id', 'date', 'status'])
            ->where('status', AppointmentStatus::NOTIFIED->value) // Who has been notified before the scheduled date
            ->whereDate('date', '<', now()->startOfDay()->toDateString()) // Appointments before today
            ->orderBy('date', 'asc') // Oldest appointments first (btw, date column is indexed)
            ->limit($limit)
            ->get();
    }

    /**
     * Helper method for consistent cache key generation or retrieval
     *
     * @param int|User $user Integer NID or User model
     * @return string
     */
    public static function getCacheKey(int|User $user): string
    {
        $nid = $user instanceof User ? $user->nid : $user;

        return "user:$nid";
    }

    /**
     * Store in the DB and update cache. It's meant to be used by StoreUserJob only.
     *
     * @param \App\Models\User $user
     * @return void
     */
    public function store(int $nid)
    {
        if (!$this->existsInDB($nid)) {
            $cacheKey = $this->getCacheKey($nid);

            $user = Cache::get($cacheKey); // We may throw exception if not found
            $user->status = VaccinationStatus::NOT_SCHEDULED;

            $user->save();
            $this->pool($user);

            event(new Registered($user));
        }
    }

    /**
     * Efficiently pull metadata for a user
     *
     * @param \App\Models\User $user
     * @return mixed
     */
    public function withMetadata(User $user)
    {
        $cacheKey = self::getCacheKey($user) . ":metadata";

        // Metadata is only meaningful if it's currently on scheduled status
        if (!$user->status === VaccinationStatus::SCHEDULED) {
            return null;
        }

        // Return if we have the metadata cached already
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }

        // Otherwise load it and cache it
        $user->loadMissing([
            'vaccineCenter:id,name,address',
            'vaccineAppointment:id,date,user_id,status',
        ]);

        // Since we can assume that all user will be vaccinated on the scheduled date,
        // cache the data for that period of time plus a bit more.
        $ttl = $user->vaccineAppointment->date->addDays(2);

        Cache::remember($cacheKey, $ttl, fn() => $user);

        return $user;
    }

    /**
     * Cache pool, all caching of User model should be through this method. For the best outcome,
     * an in-memory cache driver i.e. Redis should be used.
     *
     * If the status is VaccinationStatus::vaccinated, limit the cache ttl to 3 days, otherwise longer
     * for data integrity and faster reads. A user is not likely to be checking his status
     * after getting vaccinated.
     *
     * @param \App\Models\User $user
     * @return string
     */
    protected function pool(User $user): string
    {
        $cacheKey = self::getCacheKey($user);
        $ttl = $user?->status === VaccinationStatus::VACCINATED
            ? now()->addDays(3)
            : now()->addDays(365); // The server should process the user within this moment for newly registered users

        Cache::put($cacheKey, $user, $ttl);

        return $cacheKey;
    }
}
