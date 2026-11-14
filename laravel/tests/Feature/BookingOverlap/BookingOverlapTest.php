<?php

use App\Features\BookingOverlap\Actions\CancelRoomBooking;
use App\Features\BookingOverlap\Actions\CreateRoomBooking;
use App\Features\BookingOverlap\Enums\BookingStatus;
use App\Features\BookingOverlap\Exceptions\BookingConflict;
use App\Features\BookingOverlap\Exceptions\BookingIdempotencyConflict;
use App\Features\BookingOverlap\ValueObjects\BookingInterval;
use App\Features\BusinessCalendarSlots\Models\Room;
use App\Features\BusinessCalendarSlots\Models\WeeklyOpeningHour;
use App\Models\CatalogSetting;
use App\Models\User;
use Database\Seeders\AdminCatalogSeeder;
use Illuminate\Support\Str;

function bookingInterval(string $start = '09:00', int $duration = 60): BookingInterval
{
    return BookingInterval::fromLocal('2030-01-07', $start, $duration, 'Asia/Ho_Chi_Minh');
}

function bookingRoom(): Room
{
    $room = Room::factory()->create();
    WeeklyOpeningHour::factory()->create([
        'room_id' => $room->id,
        'weekday' => 1,
        'opens_at_local' => '09:00',
        'closes_at_local' => '17:00',
    ]);

    return $room;
}

test('booking overlap uses half-open boundaries', function () {
    $room = bookingRoom();
    $user = User::factory()->create();
    $create = app(CreateRoomBooking::class);

    $create->handle($room, $user, bookingInterval('09:00', 60));
    $adjacent = $create->handle($room, User::factory()->create(), bookingInterval('10:00', 60));

    expect($adjacent->status)->toBe(BookingStatus::Confirmed);
});

test('booking overlap rejects partial and containing intervals', function () {
    $room = bookingRoom();
    $create = app(CreateRoomBooking::class);
    $create->handle($room, User::factory()->create(), bookingInterval('10:00', 120));

    expect(fn () => $create->handle($room, User::factory()->create(), bookingInterval('11:00', 60)))
        ->toThrow(BookingConflict::class);
    expect(fn () => $create->handle($room, User::factory()->create(), bookingInterval('09:00', 240)))
        ->toThrow(BookingConflict::class);
});

test('cancelled booking does not block a new booking', function () {
    $room = bookingRoom();
    $firstUser = User::factory()->create();
    $create = app(CreateRoomBooking::class);
    $booking = $create->handle($room, $firstUser, bookingInterval());

    app(CancelRoomBooking::class)->handle($booking, $firstUser);
    $replacement = $create->handle($room, User::factory()->create(), bookingInterval());

    expect($replacement->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->refresh()->status)->toBe(BookingStatus::Cancelled);
});

test('same idempotency key returns the same booking and rejects different interval', function () {
    $room = bookingRoom();
    $create = app(CreateRoomBooking::class);
    $key = (string) Str::uuid();
    $user = User::factory()->create();
    $first = $create->handle($room, $user, bookingInterval(), $key);
    $retry = $create->handle($room, $user, bookingInterval(), $key);

    expect($retry->is($first))->toBeTrue();
    expect(fn () => $create->handle($room, $user, bookingInterval('10:00'), $key))
        ->toThrow(BookingIdempotencyConflict::class);
});

test('booking lab route is reachable and catalog enabled', function () {
    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);
    $room = bookingRoom();
    $user = User::factory()->create();
    app(CreateRoomBooking::class)->handle($room, $user, bookingInterval());

    $response = $this->actingAs($user)
        ->get(route('labs.booking-overlap.index', ['room_id' => $room->id, 'date' => '2030-01-07']))
        ->assertOk()
        ->assertSee(__('booking_overlap.slots.title'))
        ->assertSee(__('booking_overlap.bookings.title'))
        ->assertSee(__('booking_overlap.learning.examples_title'))
        ->assertSee(__('lab_learning.layers.title'))
        ->assertSee(__('lab_learning.layers.items.0.title'))
        ->assertSee(__('lab_advanced.booking-overlap.data_title'))
        ->assertSee(__('lab_advanced.booking-overlap.test_title'))
        ->assertSee(__('lab_advanced.booking-overlap.observability_title'))
        ->assertSee('href="#booking-overlap-invariant"', false)
        ->assertSee('href="#booking-overlap-test-evidence"', false)
        ->assertSee(__('lab_advanced.evidence_status.not_covered'))
        ->assertSee('assertNoActiveOverlap')
        ->assertSee('09:00');

    expect(substr_count($response->getContent(), 'id="booking-overlap-data-contract"'))->toBe(1)
        ->and(substr_count($response->getContent(), 'id="booking-overlap-test-evidence"'))->toBe(1)
        ->and(substr_count($response->getContent(), 'id="booking-overlap-observability"'))->toBe(1);

    preg_match_all('/href="#([^"]+)"/', $response->getContent(), $links);
    preg_match_all('/id="([^"]+)"/', $response->getContent(), $ids);

    expect(array_values(array_diff(array_unique($links[1]), array_unique($ids[1]))))->toBe([]);
});

test('booked slots are hidden from other learners while their booking data stays private', function () {
    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);
    $room = bookingRoom();
    $owner = User::factory()->create(['name' => 'Private Owner']);
    app(CreateRoomBooking::class)->handle($room, $owner, bookingInterval());

    $this->actingAs(User::factory()->create())
        ->get(route('labs.booking-overlap.index', ['room_id' => $room->id, 'date' => '2030-01-07']))
        ->assertOk()
        ->assertDontSee('09:00')
        ->assertDontSee('Private Owner');
});
