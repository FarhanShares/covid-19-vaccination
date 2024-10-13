<?php

namespace App\Support\Enums;

/**
 * Purposefully using integer-baked enum to save space in DB and
 * have more efficient lookups.
 */
enum VaccinationStatus: int
{
    case NOT_REGISTERED = 0;
    case NOT_SCHEDULED  = 1;
    case SCHEDULED      = 2;
    case VACCINATED     = 3;


    public function label(): string
    {
        return match ($this) {
            self::NOT_REGISTERED => "Not Registered",
            self::NOT_SCHEDULED  => "Not Scheduled",
            self::SCHEDULED      => "Scheduled",
            self::VACCINATED     => "Vaccinated",
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::NOT_REGISTERED => 'danger',
            self::NOT_SCHEDULED  => 'info',
            self::SCHEDULED      => 'warning',
            self::VACCINATED     => 'success',
        };
    }
}
