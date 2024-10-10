<?php

namespace App\Support\Enums;

/**
 * Purposefully using integer-baked enum to save space in DB and
 * have more efficient lookups.
 */
enum AppointmentStatus: int
{
    case SCHEDULED      = 0;
    case VACCINATED     = 1;


    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED      => "Scheduled",
            self::VACCINATED     => "Vaccinated",
        };
    }
}
