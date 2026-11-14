<?php

namespace App\Features\BookingOverlap\ValueObjects;

use Carbon\CarbonImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class BookingInterval
{
    public function __construct(
        public CarbonImmutable $startsAtUtc,
        public CarbonImmutable $endsAtUtc,
    ) {
        if ($endsAtUtc <= $startsAtUtc) {
            throw new InvalidArgumentException('booking_overlap.errors.invalid_interval');
        }
    }

    public static function fromLocal(string $date, string $start, int $durationMinutes, string $timezone): self
    {
        if (! in_array($timezone, DateTimeZone::listIdentifiers(), true) || $durationMinutes < 1) {
            throw new InvalidArgumentException('booking_overlap.errors.invalid_interval');
        }

        $zone = new DateTimeZone($timezone);
        $local = CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$date} {$start}", $zone);

        if ($local === false || $local->format('Y-m-d H:i') !== "{$date} {$start}") {
            throw new InvalidArgumentException('booking_overlap.errors.invalid_local_time');
        }

        return new self($local->utc(), $local->addMinutes($durationMinutes)->utc());
    }
}
