<?php

namespace App\Features\PricingDiscounts\Actions;

use App\Features\PricingDiscounts\Models\PricingPromotion;
use Illuminate\Support\Facades\DB;

final class SavePricingPromotion
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?PricingPromotion $promotion = null): PricingPromotion
    {
        return DB::transaction(function () use ($data, $promotion): PricingPromotion {
            $promotion ??= new PricingPromotion;
            $promotion->fill($data);
            $promotion->saveOrFail();

            return $promotion->refresh();
        });
    }
}
