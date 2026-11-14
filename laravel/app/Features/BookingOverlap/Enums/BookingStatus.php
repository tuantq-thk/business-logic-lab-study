<?php

namespace App\Features\BookingOverlap\Enums;

enum BookingStatus: string
{
    case Held = 'held';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    /** @return list<string> */
    public static function blockingValues(): array
    {
        return [self::Held->value, self::Confirmed->value];
    }
}
