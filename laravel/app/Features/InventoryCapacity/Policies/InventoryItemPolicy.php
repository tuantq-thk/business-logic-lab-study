<?php

namespace App\Features\InventoryCapacity\Policies;

use App\Models\User;

final class InventoryItemPolicy
{
    public function adjust(User $user): bool
    {
        return $user->isAdmin();
    }
}
