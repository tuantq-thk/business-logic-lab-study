<?php

namespace Database\Seeders;

use App\Features\BusinessCalendarSlots\Enums\CalendarOverrideKind;
use App\Features\BusinessCalendarSlots\Models\CalendarOverride;
use App\Features\BusinessCalendarSlots\Models\Room;
use App\Features\BusinessCalendarSlots\Models\WeeklyOpeningHour;
use Illuminate\Database\Seeder;

class BusinessCalendarSlotsSeeder extends Seeder
{
    public function run(): void
    {
        $room = Room::query()->updateOrCreate(
            ['code' => 'BOARDROOM-01'],
            [
                'name_key' => 'business_calendar_slots.demo.rooms.boardroom',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'active' => true,
            ],
        );

        foreach ([1, 2, 3, 4, 5] as $weekday) {
            foreach ([['09:00', '12:00'], ['13:00', '17:00']] as [$opensAt, $closesAt]) {
                WeeklyOpeningHour::query()->updateOrCreate(
                    ['room_id' => $room->id, 'weekday' => $weekday, 'opens_at_local' => $opensAt],
                    ['closes_at_local' => $closesAt],
                );
            }
        }

        CalendarOverride::query()->updateOrCreate(
            ['room_id' => $room->id, 'local_date' => '2026-11-20'],
            [
                'kind' => CalendarOverrideKind::Open,
                'opens_at_local' => '10:00',
                'closes_at_local' => '14:00',
                'reason' => 'Demo special opening',
            ],
        );
    }
}
