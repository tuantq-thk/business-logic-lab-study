<?php

namespace App\Features\PricingDiscounts\ValueObjects;

use App\Features\PricingDiscounts\Enums\DiscountKind;
use InvalidArgumentException;

final readonly class DiscountRule
{
    public function __construct(
        public string $code,
        public DiscountKind $kind,
        public int $value,
        public int $minimumSubtotalMinor = 0,
        public int $priority = 0,
        public bool $stackable = false,
        public bool $active = true,
    ) {
        if ($code === '') {
            throw new InvalidArgumentException('A discount rule requires a code.');
        }

        if ($value < 0 || $minimumSubtotalMinor < 0 || $priority < 0) {
            throw new InvalidArgumentException('Discount values cannot be negative.');
        }

        if ($kind === DiscountKind::Percentage && $value > 100) {
            throw new InvalidArgumentException('Percentage discount cannot exceed 100.');
        }
    }
}
