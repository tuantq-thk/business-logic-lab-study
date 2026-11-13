<?php

namespace App\Features\PricingDiscounts\Actions;

use App\Features\PricingDiscounts\Catalog\PricingCatalog;
use App\Features\PricingDiscounts\Exceptions\PricingSelectionUnavailable;
use App\Features\PricingDiscounts\Models\PricingProduct;
use App\Features\PricingDiscounts\Models\PricingPromotion;
use App\Features\PricingDiscounts\ValueObjects\CartLine;
use App\Features\PricingDiscounts\ValueObjects\DiscountRule;
use App\Features\PricingDiscounts\ValueObjects\PricingQuoteDraft;

final class BuildPricingQuote
{
    public function __construct(
        private readonly PricingCatalog $catalog,
        private readonly CalculateCartQuote $calculate,
    ) {}

    /**
     * @param  array<string, int>  $quantities
     * @param  list<string>  $selectedRuleCodes
     */
    public function handle(array $quantities, array $selectedRuleCodes): PricingQuoteDraft
    {
        $products = $this->catalog->productsBySku(array_keys($quantities));

        if ($products->count() !== count($quantities)) {
            throw new PricingSelectionUnavailable('One or more selected products are unavailable.');
        }

        $promotions = $this->catalog->promotionsByCode($selectedRuleCodes);
        if ($promotions->count() !== count(array_unique($selectedRuleCodes))) {
            throw new PricingSelectionUnavailable('One or more selected promotions are unavailable.');
        }

        $lines = array_map(
            static fn (PricingProduct $product): CartLine => $product->toCartLine($quantities[$product->sku]),
            $products->values()->all(),
        );
        $rules = array_map(
            static fn (PricingPromotion $promotion): DiscountRule => $promotion->toDiscountRule(),
            $promotions->values()->all(),
        );

        return new PricingQuoteDraft(
            quote: $this->calculate->handle($lines, $rules),
            products: $products->all(),
            promotions: $promotions->all(),
        );
    }
}
