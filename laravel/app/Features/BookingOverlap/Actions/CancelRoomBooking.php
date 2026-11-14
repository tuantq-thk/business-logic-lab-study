<?php

namespace App\Features\BookingOverlap\Actions;

use App\Features\BookingOverlap\Enums\BookingStatus;
use App\Features\BookingOverlap\Models\RoomBooking;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class CancelRoomBooking
{
    public function handle(RoomBooking $booking, User $user): RoomBooking
    {
        Gate::forUser($user)->authorize('cancel', $booking);

        return $booking->getConnection()->transaction(function () use ($booking): RoomBooking {
            $current = RoomBooking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();

            if ($current->status !== BookingStatus::Cancelled) {
                $current->forceFill(['status' => BookingStatus::Cancelled])->saveOrFail();
            }

            return $current->refresh();
        });
    }
}
