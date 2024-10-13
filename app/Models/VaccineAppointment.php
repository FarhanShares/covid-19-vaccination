<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VaccineAppointment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'user_id',
        'vaccine_center_id',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date'   => 'date',
            'status' => AppointmentStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vaccineCenter(): BelongsTo
    {
        return $this->belongsTo(VaccineCenter::class);
    }
}
