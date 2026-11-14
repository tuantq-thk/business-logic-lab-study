<?php

namespace App\Features\InventoryCapacity\Exceptions;

use InvalidArgumentException;

final class InvalidReservationInput extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('inventory_capacity.errors.invalid_input');
    }
}
