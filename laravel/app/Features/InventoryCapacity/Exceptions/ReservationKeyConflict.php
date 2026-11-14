<?php

namespace App\Features\InventoryCapacity\Exceptions;

use RuntimeException;

final class ReservationKeyConflict extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('inventory_capacity.errors.key_conflict');
    }
}
