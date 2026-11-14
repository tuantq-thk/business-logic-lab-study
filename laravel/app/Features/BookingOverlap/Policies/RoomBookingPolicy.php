<?php

namespace App\Features\BookingOverlap\Policies;

use App\Features\BookingOverlap\Models\RoomBooking;
use App\Models\User;

class RoomBookingPolicy
{
    public function cancel(User $user, RoomBooking $booking): bool
    {
        return $user->isAdmin() || $booking->user_id === $user->id;
    }
}
