<?php

namespace Database\Factories\BusinessCalendarSlots;

use App\Features\BusinessCalendarSlots\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Room> */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('ROOM-###')),
            'name_key' => 'business_calendar_slots.demo.rooms.default',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'active' => true,
        ];
    }
}
