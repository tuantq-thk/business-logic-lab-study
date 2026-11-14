<?php

namespace App\Features\InventoryCapacity\Policies;

use App\Features\InventoryCapacity\Models\InventoryReservation;
use App\Models\User;

final class InventoryReservationPolicy
{
    public function create(User $user): bool
    {
        return $user->exists && $user->can('viewAny', InventoryReservation::class);
    }

    public function viewAny(User $user): bool
    {
        return $user->exists;
    }

    public function view(User $user, InventoryReservation $reservation): bool
    {
        return $user->isAdmin() || $reservation->owner_id === $user->id;
    }

    public function manage(User $user, InventoryReservation $reservation): bool
    {
        return $this->view($user, $reservation);
    }

    public function adjust(User $user): bool
    {
        return $user->isAdmin();
    }
}
