<?php

namespace App\Features\BusinessCalendarSlots\ValueObjects;

use Carbon\CarbonImmutable;

final readonly class Slot
{
    public function __construct(
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
    ) {}
}
