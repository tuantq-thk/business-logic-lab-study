<?php

namespace App\Features\InventoryCapacity\Exceptions;

use RuntimeException;

final class InsufficientInventory extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('inventory_capacity.errors.insufficient');
    }
}
