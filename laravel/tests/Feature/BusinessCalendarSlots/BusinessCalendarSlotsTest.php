<?php

use App\Features\BusinessCalendarSlots\Models\Room;
use App\Features\BusinessCalendarSlots\Models\WeeklyOpeningHour;
use App\Models\CatalogSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\AdminCatalogSeeder;

test('calendar UI generates slots from a room weekly schedule', function () {
    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);

    $room = Room::factory()->create();
    WeeklyOpeningHour::factory()->create([
        'room_id' => $room->id,
        'weekday' => 1,
        'opens_at_local' => '09:00',
        'closes_at_local' => '12:00',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('labs.business-calendar-slots.index', [
            'room_id' => $room->id,
            'date' => '2030-01-07',
            'duration' => 60,
            'step' => 60,
        ]))
        ->assertOk()
        ->assertSee('09:00')
        ->assertSee('11:00')
        ->assertDontSee('12:00 – 13:00');
});

test('calendar UI defaults to the next available local date', function () {
    CarbonImmutable::setTestNow('2030-01-12 08:00:00');
    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);

    $room = Room::factory()->create();
    WeeklyOpeningHour::factory()->create([
        'room_id' => $room->id,
        'weekday' => 1,
        'opens_at_local' => '09:00',
        'closes_at_local' => '12:00',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('labs.business-calendar-slots.index', ['room_id' => $room->id]))
        ->assertOk()
        ->assertSee('value="2030-01-14"', false);

    CarbonImmutable::setTestNow();
});

test('invalid availability parameters return localized validation messages', function () {
    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);

    $this->actingAs(User::factory()->create())
        ->get(route('labs.business-calendar-slots.index', ['duration' => 1, 'step' => 1]))
        ->assertRedirect()
        ->assertSessionHasErrors([
            'duration' => __('business_calendar_slots.validation.duration_range'),
            'step' => __('business_calendar_slots.validation.step_range'),
        ]);
});

test('an invalid room timezone returns a clear generation error', function () {
    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);

    $room = Room::factory()->create(['timezone' => 'Invalid/Timezone']);

    $this->actingAs(User::factory()->create())
        ->get(route('labs.business-calendar-slots.index', [
            'room_id' => $room->id,
            'date' => '2030-01-07',
        ]))
        ->assertOk()
        ->assertSee(__('business_calendar_slots.errors.invalid_timezone'));
});

test('a closed date override removes weekly slots', function () {
    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);

    $room = Room::factory()->create();
    WeeklyOpeningHour::factory()->create([
        'room_id' => $room->id,
        'weekday' => 1,
        'opens_at_local' => '09:00',
        'closes_at_local' => '12:00',
    ]);
    $room->calendarOverrides()->create([
        'local_date' => '2030-01-07',
        'kind' => 'closed',
        'reason' => 'Maintenance',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('labs.business-calendar-slots.index', ['room_id' => $room->id, 'date' => '2030-01-07']))
        ->assertOk()
        ->assertSee(__('business_calendar_slots.slots.empty'));
});

test('an open date override replaces the weekly schedule', function () {
    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);

    $room = Room::factory()->create();
    WeeklyOpeningHour::factory()->create([
        'room_id' => $room->id,
        'weekday' => 1,
        'opens_at_local' => '09:00',
        'closes_at_local' => '12:00',
    ]);
    $room->calendarOverrides()->create([
        'local_date' => '2030-01-07',
        'kind' => 'open',
        'opens_at_local' => '14:00',
        'closes_at_local' => '16:00',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('labs.business-calendar-slots.index', [
            'room_id' => $room->id,
            'date' => '2030-01-07',
            'duration' => 60,
            'step' => 60,
        ]))
        ->assertOk()
        ->assertSee('14:00')
        ->assertDontSee('09:00');
});
