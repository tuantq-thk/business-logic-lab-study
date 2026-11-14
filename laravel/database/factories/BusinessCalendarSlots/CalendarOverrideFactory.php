<?php

namespace Database\Factories\BusinessCalendarSlots;

use App\Features\BusinessCalendarSlots\Enums\CalendarOverrideKind;
use App\Features\BusinessCalendarSlots\Models\CalendarOverride;
use App\Features\BusinessCalendarSlots\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CalendarOverride> */
class CalendarOverrideFactory extends Factory
{
    protected $model = CalendarOverride::class;

    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'local_date' => now()->addDay()->toDateString(),
            'kind' => CalendarOverrideKind::Closed,
            'opens_at_local' => null,
            'closes_at_local' => null,
            'reason' => 'Public holiday',
        ];
    }

    public function open(): static
    {
        return $this->state([
            'kind' => CalendarOverrideKind::Open,
            'opens_at_local' => '10:00',
            'closes_at_local' => '14:00',
        ]);
    }
}
