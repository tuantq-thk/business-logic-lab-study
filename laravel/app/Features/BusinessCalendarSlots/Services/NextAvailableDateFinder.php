<?php

namespace App\Features\BusinessCalendarSlots\Services;

use App\Features\BusinessCalendarSlots\Enums\CalendarOverrideKind;
use App\Features\BusinessCalendarSlots\Models\Room;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Exception;

final class NextAvailableDateFinder
{
    public function find(Room $room): string
    {
        try {
            $today = CarbonImmutable::now(new DateTimeZone($room->timezone))->startOfDay();
        } catch (Exception) {
            return CarbonImmutable::now()->toDateString();
        }

        $weeklyDays = $room->weeklyOpeningHours()
            ->pluck('weekday')
            ->map(static fn ($weekday): int => (int) $weekday)
            ->unique();
        $overrides = $room->calendarOverrides()
            ->whereBetween('local_date', [$today->toDateString(), $today->addDays(6)->toDateString()])
            ->get()
            ->keyBy(static fn ($override): string => $override->local_date->toDateString());

        for ($offset = 0; $offset < 7; $offset++) {
            $date = $today->addDays($offset);
            $override = $overrides->get($date->toDateString());

            if ($override?->kind === CalendarOverrideKind::Closed) {
                continue;
            }

            if ($override?->kind === CalendarOverrideKind::Open || $weeklyDays->contains($date->dayOfWeek)) {
                return $date->toDateString();
            }
        }

        return $today->toDateString();
    }
}
