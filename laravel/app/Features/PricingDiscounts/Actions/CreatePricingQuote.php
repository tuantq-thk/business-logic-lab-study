<?php

namespace App\Features\PricingDiscounts\Actions;

use App\Features\PricingDiscounts\Models\PricingQuote;
use App\Features\PricingDiscounts\Models\PricingQuoteDiscount;
use App\Features\PricingDiscounts\Models\PricingQuoteLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreatePricingQuote
{
    public function __construct(private readonly BuildPricingQuote $build) {}

    /**
     * @param  array<string, int>  $quantities
     * @param  list<string>  $selectedRuleCodes
     */
    public function handle(User $actor, array $quantities, array $selectedRuleCodes): PricingQuote
    {
        $draft = $this->build->handle($quantities, $selectedRuleCodes);

        return DB::transaction(function () use ($actor, $draft): PricingQuote {
            $quote = PricingQuote::query()->create([
                'created_by' => $actor->id,
                'currency' => $draft->quote->currency,
                'subtotal_minor' => $draft->quote->subtotalMinor,
                'discount_minor' => $draft->quote->discountMinor,
                'total_minor' => $draft->quote->totalMinor,
            ]);

            foreach ($draft->quote->lines as $line) {
                $product = $draft->products[$line->sku];

                PricingQuoteLine::query()->create([
                    'pricing_quote_id' => $quote->id,
                    'pricing_product_id' => $product->id,
                    'sku_snapshot' => $line->sku,
                    'name_snapshot' => $line->name,
                    'quantity' => $line->quantity,
                    'unit_price_minor' => $line->unitPrice->amountMinor,
                    'line_total_minor' => $line->subtotalMinor(),
                ]);
            }

            foreach ($draft->quote->appliedDiscounts as $discount) {
                $promotion = $draft->promotions[$discount['code']] ?? null;

                PricingQuoteDiscount::query()->create([
                    'pricing_quote_id' => $quote->id,
                    'pricing_promotion_id' => $promotion?->id,
                    'code_snapshot' => $discount['code'],
                    'discount_minor' => $discount['amountMinor'],
                ]);
            }

            return $quote->load(['lines', 'discounts']);
        });
    }
}
