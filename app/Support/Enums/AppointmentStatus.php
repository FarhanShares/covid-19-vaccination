<?php

namespace App\Support\Enums;

/**
 * Purposefully using integer-baked enum to save space in DB and
 * have more efficient lookups.
 */
enum AppointmentStatus: int
{
    case NOTIFIED       = 1;
    case SCHEDULED      = 2;
    case VACCINATED     = 3;

    public function label(): string
    {
        return match ($this) {
            self::NOTIFIED       => "Notified",
            self::SCHEDULED      => "Scheduled",
            self::VACCINATED     => "Vaccinated",
        };
    }
}
