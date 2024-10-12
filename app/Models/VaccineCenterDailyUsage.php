<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VaccineCenterDailyUsage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'vaccine_center_id',
        'usage_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usage_count' => 'int',
        ];
    }

    public function getUsage(
        int|VaccineCenter $vaccineCenter,
        ?Carbon $date = null,
    ) {
        $vid  = $vaccineCenter instanceof VaccineCenter ? $vaccineCenter->id : $vaccineCenter;
        $date = Carbon::parse($date ?? now())->startOfDay()->toDateString();

        return self::where([
            'date' => $date,
            'vaccine_center_id' => $vid,
        ])->first();
    }

    public function getRemainingSlots(
        int|VaccineCenter $vaccineCenter,
        ?Carbon $date = null,
    ) {
        if (is_int($vaccineCenter)) {
            $vaccineCenter = VaccineCenter::find($vaccineCenter);
        }

        if (!$vaccineCenter) {
            throw new \Exception('Vaccine center not found.');
        }

        $capacity = $vaccineCenter->daily_capacity;

        if (!$capacity) {
            return 0;
        }

        $date ??= Carbon::now();
        $dailyUsage = $this->getUsage($vaccineCenter, $date);
        if (is_null($dailyUsage)) {
            return $capacity;
        }

        return (int) $capacity - $dailyUsage->usage_count;
    }


    public function incrementUsage(
        int|VaccineCenter $vaccineCenter,
        ?Carbon $date = null,
        int $amount = 1
    ) {
        $vid  = $vaccineCenter instanceof VaccineCenter ? $vaccineCenter->id : $vaccineCenter;
        $date = Carbon::parse($date ?? now())->startOfDay()->toDateString();

        $counter = self::firstOrCreate([
            'date' => $date,
            'vaccine_center_id' => $vid,
        ]);

        $counter->increment('usage_count', $amount);

        return $counter;
    }

    public function vaccineCenter(): BelongsTo
    {
        return $this->belongsTo(VaccineCenter::class);
    }
}
