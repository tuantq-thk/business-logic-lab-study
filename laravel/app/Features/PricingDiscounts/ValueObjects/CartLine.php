<?php

namespace App\Features\PricingDiscounts\ValueObjects;

use InvalidArgumentException;

final readonly class CartLine
{
    public function __construct(
        public string $sku,
        public string $name,
        public int $quantity,
        public Money $unitPrice,
    ) {
        if ($sku === '' || $name === '') {
            throw new InvalidArgumentException('A cart line requires a SKU and name.');
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('Cart quantity must be positive.');
        }
    }

    public function subtotalMinor(): int
    {
        return $this->quantity * $this->unitPrice->amountMinor;
    }
}
