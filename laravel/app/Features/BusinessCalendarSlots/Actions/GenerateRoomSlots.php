<?php

namespace App\Features\BusinessCalendarSlots\Actions;

use App\Features\BusinessCalendarSlots\Enums\CalendarOverrideKind;
use App\Features\BusinessCalendarSlots\Models\Room;
use App\Features\BusinessCalendarSlots\Services\SlotGenerator;
use App\Features\BusinessCalendarSlots\ValueObjects\Slot;
use Carbon\CarbonImmutable;

final class GenerateRoomSlots
{
    public function __construct(private readonly SlotGenerator $generator) {}

    /**
     * @return list<Slot>
     */
    public function handle(
        Room $room,
        string $localDate,
        int $durationMinutes,
        int $stepMinutes,
        CarbonImmutable $now,
    ): array {
        $override = $room->calendarOverrides()->where('local_date', $localDate)->first();

        if ($override?->kind === CalendarOverrideKind::Closed) {
            return [];
        }

        $windows = $override !== null
            ? [['opens_at_local' => substr((string) $override->opens_at_local, 0, 5), 'closes_at_local' => substr((string) $override->closes_at_local, 0, 5)]]
            : $room->weeklyOpeningHours()
                ->where('weekday', CarbonImmutable::createFromFormat('!Y-m-d', $localDate)->dayOfWeek)
                ->get()
                ->map(fn ($window): array => [
                    'opens_at_local' => substr((string) $window->opens_at_local, 0, 5),
                    'closes_at_local' => substr((string) $window->closes_at_local, 0, 5),
                ])->all();

        return $this->generator->generate(
            timezone: $room->timezone,
            localDate: $localDate,
            windows: $windows,
            durationMinutes: $durationMinutes,
            stepMinutes: $stepMinutes,
            now: $now,
        );
    }
}
