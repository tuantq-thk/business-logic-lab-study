<?php

namespace App\Features\PricingDiscounts\ValueObjects;

use App\Features\PricingDiscounts\Models\PricingProduct;
use App\Features\PricingDiscounts\Models\PricingPromotion;

/**
 * @param  array<string, PricingProduct>  $products
 * @param  array<string, PricingPromotion>  $promotions
 */
final readonly class PricingQuoteDraft
{
    public function __construct(
        public PriceQuote $quote,
        public array $products,
        public array $promotions,
    ) {}
}
