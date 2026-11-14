<?php

namespace App\Features\BusinessCalendarSlots\Services;

use App\Features\BusinessCalendarSlots\ValueObjects\Slot;
use Carbon\CarbonImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class SlotGenerator
{
    /**
     * @param  list<array{opens_at_local: string, closes_at_local: string}>  $windows
     * @return list<Slot>
     */
    public function generate(
        string $timezone,
        string $localDate,
        array $windows,
        int $durationMinutes,
        int $stepMinutes,
        CarbonImmutable $now,
    ): array {
        if (! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidArgumentException('business_calendar_slots.errors.invalid_timezone');
        }

        if ($durationMinutes < 1 || $stepMinutes < 1) {
            throw new InvalidArgumentException('business_calendar_slots.errors.invalid_slot_parameters');
        }

        $zone = new DateTimeZone($timezone);
        $nowInRoom = $now->setTimezone($zone);
        $slots = [];

        foreach ($windows as $window) {
            $opensAt = $this->localTime($localDate, $window['opens_at_local'], $zone);
            $closesAt = $this->localTime($localDate, $window['closes_at_local'], $zone);

            if ($opensAt >= $closesAt) {
                throw new InvalidArgumentException('business_calendar_slots.errors.invalid_window');
            }

            for ($startsAt = $opensAt; $startsAt->addMinutes($durationMinutes) <= $closesAt; $startsAt = $startsAt->addMinutes($stepMinutes)) {
                $endsAt = $startsAt->addMinutes($durationMinutes);

                if ($startsAt >= $nowInRoom) {
                    $slots[] = new Slot($startsAt, $endsAt);
                }
            }
        }

        return $slots;
    }

    private function localTime(string $localDate, string $localTime, DateTimeZone $zone): CarbonImmutable
    {
        $value = CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$localDate} {$localTime}", $zone);

        if ($value === false || $value->format('Y-m-d H:i') !== "{$localDate} {$localTime}") {
            throw new InvalidArgumentException('business_calendar_slots.errors.invalid_local_time');
        }

        return $value;
    }
}
