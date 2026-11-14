<?php

namespace App\Features\BookingOverlap\Actions;

use App\Features\BookingOverlap\Enums\BookingStatus;
use App\Features\BookingOverlap\Exceptions\BookingConflict;
use App\Features\BookingOverlap\Exceptions\BookingIdempotencyConflict;
use App\Features\BookingOverlap\Models\RoomBooking;
use App\Features\BookingOverlap\ValueObjects\BookingInterval;
use App\Features\BusinessCalendarSlots\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateRoomBooking
{
    public function handle(Room $room, User $user, BookingInterval $interval, ?string $idempotencyKey = null): RoomBooking
    {
        return DB::transaction(function () use ($room, $user, $interval, $idempotencyKey): RoomBooking {
            $lockedRoom = Room::query()->whereKey($room->getKey())->where('active', true)->lockForUpdate()->firstOrFail();

            if ($idempotencyKey !== null) {
                $existing = RoomBooking::query()->where('user_id', $user->id)->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();

                if ($existing !== null) {
                    if ($existing->room_id !== $lockedRoom->id || $existing->starts_at_utc->ne($interval->startsAtUtc) || $existing->ends_at_utc->ne($interval->endsAtUtc)) {
                        throw new BookingIdempotencyConflict;
                    }

                    return $existing;
                }
            }

            $conflict = RoomBooking::query()
                ->where('room_id', $lockedRoom->id)
                ->whereIn('status', BookingStatus::blockingValues())
                ->where('starts_at_utc', '<', $interval->endsAtUtc)
                ->where('ends_at_utc', '>', $interval->startsAtUtc)
                ->exists();

            if ($conflict) {
                throw new BookingConflict;
            }

            return RoomBooking::query()->create([
                'room_id' => $lockedRoom->id,
                'user_id' => $user->id,
                'starts_at_utc' => $interval->startsAtUtc,
                'ends_at_utc' => $interval->endsAtUtc,
                'status' => BookingStatus::Confirmed,
                'idempotency_key' => $idempotencyKey,
            ]);
        }, attempts: 3);
    }
}
