<?php

namespace App\Features\InventoryCapacity\Enums;

enum InventoryReservationStatus: string
{
    case Active = 'active';
    case Consumed = 'consumed';
    case Released = 'released';
    case Expired = 'expired';

    public function isTerminal(): bool
    {
        return $this !== self::Active;
    }
}
