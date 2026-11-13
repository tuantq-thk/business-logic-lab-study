<?php

namespace App\Features\PricingDiscounts\ValueObjects;

/**
 * @param  list<CartLine>  $lines
 * @param  list<string>  $appliedRuleCodes
 * @param  list<array{code: string, amountMinor: int}>  $appliedDiscounts
 */
final readonly class PriceQuote
{
    public function __construct(
        public array $lines,
        public string $currency,
        public int $subtotalMinor,
        public int $discountMinor,
        public int $totalMinor,
        public array $appliedRuleCodes,
        public array $appliedDiscounts,
    ) {}
}
