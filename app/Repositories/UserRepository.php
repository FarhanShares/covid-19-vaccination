<?php

namespace App\Repositories;

use App\Models\User;
use App\Jobs\StoreUserJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Auth\Events\Registered;
use App\Support\Enums\VaccinationStatus;

/**
 * The class makes use of Cache for better efficiency, a performant in-memory cache storage
 * i.e. Redis will significantly improve the performance. Hence, ensure to configure it.
 */
class UserRepository
{
    /**
     * A session (key: REGISTRATION_COMPLETED_SESSION) should be flashed containing User NID
     * once the registration has been completed successfully. This is primarily used
     * for displaying a welcome screen / notification to the new user.
     *
     * @var string the session key name for successful registration
     */
    public const REGISTRATION_COMPLETED_SESSION = 'REGISTRATION_COMPLETED';

    // first check in cache storage, the check in db
    // when checked in db, if not cached, cache it
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

    public function exists(int|User $user): bool
    {
        $nid = $user instanceof User ? $user->id : $user;

        return !!$this->findByNid($nid);
    }

    public function existsInDB(int|User $user, bool $useCache = false): bool
    {
        $nid  = $user instanceof User ? $user->id : $user;
        $user = User::where('nid', $nid)->first();

        if ($user) {
            if ($useCache) {
                $this->pool($user);
            }

            return true;
        }

        return false;
    }

    public function existsInCache(int|User $user, bool $useCache = false): bool
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

    public function save(User $user)
    {
        // todo: check if user exists already

        // Save to cache storage
        $this->pool($user);

        // Push to queue to be saved in the database asynchronously
        dispatch(new StoreUserJob($user));
    }

    /**
     * Update the user status both in the DB and the Cache storage
     *
     * @param int|\App\Models\User $user Integer NID or User Model
     * @param \App\Support\Enums\VaccinationStatus $status
     * @param int $appointmentId
     * @return void
     */
    public function updateStatus(int|User $user, VaccinationStatus $status, int $appointmentId): void
    {
        if (is_int($user)) {
            $user = $this->findByNid($user);
        }

        if ($user) {
            $user->status = $status->value;
            $user->vaccine_appointment_id = $appointmentId;
            $user->save(); // Save to DB

            // Refresh the cache with the updated status
            $this->pool($user);
        }
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
    protected function store(User $user)
    {
        if (!$this->existsInDB($user)) {
            $user->save();
            $this->pool($user);
            event(new Registered($user));
        }
    }

    // Cache pool, all caching of User model should be through this method
    // if the status is VaccinationStatus::vaccinated, limit the cache ttl to 7 days, otherwise forever
    // for data integrity and faster reads. A user is not likely to be checking his status after getting vaccinated
    protected function pool(User $user): string
    {
        $cacheKey = self::getCacheKey($user);

        if ($user->status === VaccinationStatus::VACCINATED) {
            Cache::put($cacheKey, $user, now()->addDays(7));
        } else {
            Cache::rememberForever($cacheKey, fn() => $user);
        }

        return $cacheKey;
    }
}
