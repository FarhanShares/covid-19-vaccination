<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Support\Carbon;
use App\Models\VaccineAppointment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VaccineCenter extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'daily_capacity',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(VaccineAppointment::class);
    }

    public function dailyUsage(Carbon $date): HasOne
    {
        return $this->hasOne(VaccineCenterDailyUsage::class)
            ->where('date', $date);
    }

    public function dailyUsages(): HasMany
    {
        return $this->hasMany(VaccineCenterDailyUsage::class);
    }
}
