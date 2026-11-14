<?php

namespace Database\Factories\BusinessCalendarSlots;

use App\Features\BusinessCalendarSlots\Models\Room;
use App\Features\BusinessCalendarSlots\Models\WeeklyOpeningHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WeeklyOpeningHour> */
class WeeklyOpeningHourFactory extends Factory
{
    protected $model = WeeklyOpeningHour::class;

    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'weekday' => 1,
            'opens_at_local' => '09:00',
            'closes_at_local' => '17:00',
        ];
    }
}
