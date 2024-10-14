<?php

namespace App\Support\Enums;

/**
 * Purposefully using integer-baked enum to save space in DB and
 * have more efficient lookups.
 */
enum AppointmentStatus: int
{
    case SCHEDULED      = 0;
    case NOTIFIED       = 1;
    case VACCINATED     = 2;

    public function label(): string
    {
        return match ($this) {
            self::NOTIFIED       => "Notified",
            self::SCHEDULED      => "Scheduled",
            self::VACCINATED     => "Vaccinated",
        };
    }
}
