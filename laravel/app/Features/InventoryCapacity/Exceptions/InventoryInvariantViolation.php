<?php

namespace App\Features\InventoryCapacity\Exceptions;

use RuntimeException;

final class InventoryInvariantViolation extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('inventory_capacity.errors.invariant');
    }
}
