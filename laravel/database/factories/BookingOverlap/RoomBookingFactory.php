<?php

namespace DatabaseFactories\BookingOverlap;

use App\Features\BookingOverlap\Enums\BookingStatus;
use App\Features\BookingOverlap\Models\RoomBooking;
use App\Features\BusinessCalendarSlots\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RoomBooking> */
class RoomBookingFactory extends Factory
{
    protected $model = RoomBooking::class;

    public function definition(): array
    {
        $starts = CarbonImmutable::parse('2030-01-07 09:00:00', 'UTC');

        return [
            'room_id' => Room::factory(),
            'user_id' => User::factory(),
            'starts_at_utc' => $starts,
            'ends_at_utc' => $starts->addHour(),
            'status' => BookingStatus::Confirmed,
            'idempotency_key' => fake()->uuid(),
        ];
    }

    public function cancelled(): static
    {
        return $this->state(['status' => BookingStatus::Cancelled]);
    }
}
