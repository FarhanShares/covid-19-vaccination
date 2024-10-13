<?php

namespace App\Models;

use App\Support\Enums\VaccinationStatus;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nid',
        'dob',
        'name',
        'email',
        'status',
        'vaccine_center_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VaccinationStatus::class,
        ];
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    public function routeNotificationForTwilio(): string
    {
        // Currently none of these exists
        return (string) $this->country_code . $this->phone_number;
    }

    public function vaccineCenter(): BelongsTo
    {
        return $this->belongsTo(VaccineCenter::class);
    }

    public function vaccineAppointment(): HasOne
    {
        return $this->hasOne(VaccineAppointment::class);
    }
}
