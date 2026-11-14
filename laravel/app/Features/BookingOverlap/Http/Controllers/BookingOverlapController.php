<?php

namespace App\Features\BookingOverlap\Http\Controllers;

use App\Features\BookingOverlap\Actions\CancelRoomBooking;
use App\Features\BookingOverlap\Actions\CreateRoomBooking;
use App\Features\BookingOverlap\Exceptions\BookingConflict;
use App\Features\BookingOverlap\Exceptions\BookingIdempotencyConflict;
use App\Features\BookingOverlap\Exceptions\BookingOutsideAvailability;
use App\Features\BookingOverlap\Http\Requests\BookingAvailabilityRequest;
use App\Features\BookingOverlap\Http\Requests\CancelRoomBookingRequest;
use App\Features\BookingOverlap\Http\Requests\StoreRoomBookingRequest;
use App\Features\BookingOverlap\Models\RoomBooking;
use App\Features\BookingOverlap\ValueObjects\BookingInterval;
use App\Features\BusinessCalendarSlots\Actions\GenerateRoomSlots;
use App\Features\BusinessCalendarSlots\Models\Room;
use App\Features\BusinessCalendarSlots\Services\NextAvailableDateFinder;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookingOverlapController extends Controller
{
    public function index(BookingAvailabilityRequest $request, NextAvailableDateFinder $dateFinder, GenerateRoomSlots $generate): View
    {
        $rooms = Room::query()->where('active', true)->orderBy('code')->get();
        $data = $request->validated();
        $room = $rooms->firstWhere('id', (int) ($data['room_id'] ?? 0)) ?? $rooms->first();
        $date = $data['date'] ?? ($room === null ? CarbonImmutable::now()->toDateString() : $dateFinder->find($room));
        $duration = 60;
        $slots = $room === null ? [] : $generate->handle($room, $date, $duration, 30, CarbonImmutable::now());
        $allBookings = $room === null ? collect() : RoomBooking::query()
            ->with('user')
            ->where('room_id', $room->id)
            ->whereIn('status', ['held', 'confirmed'])
            ->where('starts_at_utc', '<', $this->localDayEnd($room, $date))
            ->where('ends_at_utc', '>', $this->localDayStart($room, $date))
            ->orderBy('starts_at_utc')
            ->get();
        $slots = array_values(array_filter($slots, fn ($slot): bool => $allBookings->every(
            fn ($booking): bool => $booking->starts_at_utc >= $slot->endsAt->utc() || $booking->ends_at_utc <= $slot->startsAt->utc(),
        )));
        $bookings = request()->user()->isAdmin()
            ? $allBookings
            : $allBookings->where('user_id', request()->user()->id)->values();

        return view('labs.booking-overlap.index', compact('rooms', 'room', 'date', 'duration', 'slots', 'bookings'));
    }

    private function localDayStart(Room $room, string $date): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d H:i', "{$date} 00:00", new DateTimeZone($room->timezone))->utc();
    }

    private function localDayEnd(Room $room, string $date): CarbonImmutable
    {
        return $this->localDayStart($room, $date)->addDay();
    }

    public function store(StoreRoomBookingRequest $request, CreateRoomBooking $create, GenerateRoomSlots $generate): RedirectResponse
    {
        $data = $request->validated();
        $room = Room::query()->whereKey($data['room_id'])->where('active', true)->firstOrFail();

        try {
            $interval = BookingInterval::fromLocal($data['date'], $data['start'], (int) $data['duration'], $room->timezone);
            $available = $generate->handle($room, $data['date'], (int) $data['duration'], 30, CarbonImmutable::now());
            $matchesSlot = collect($available)->contains(fn ($slot): bool => $slot->startsAt->equalTo($interval->startsAtUtc->setTimezone(new DateTimeZone($room->timezone))) && $slot->endsAt->equalTo($interval->endsAtUtc->setTimezone(new DateTimeZone($room->timezone))));

            if (! $matchesSlot) {
                throw new BookingOutsideAvailability;
            }

            $create->handle($room, $request->user(), $interval, $data['idempotency_key'] ?? null);
        } catch (BookingConflict) {
            return back()->withErrors(['booking' => __('booking_overlap.errors.conflict')])->withInput();
        } catch (BookingIdempotencyConflict) {
            return back()->withErrors(['booking' => __('booking_overlap.errors.idempotency_conflict')])->withInput();
        } catch (BookingOutsideAvailability) {
            return back()->withErrors(['booking' => __('booking_overlap.errors.outside_availability')])->withInput();
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['booking' => __($exception->getMessage())])->withInput();
        }

        return to_route('labs.booking-overlap.index')->with('status', __('booking_overlap.flash.created'));
    }

    public function cancel(CancelRoomBookingRequest $request, RoomBooking $booking, CancelRoomBooking $cancel): RedirectResponse
    {
        $cancel->handle($booking, $request->user());

        return to_route('labs.booking-overlap.index')->with('status', __('booking_overlap.flash.cancelled'));
    }
}
