<?php

namespace App\Features\PricingDiscounts\Actions;

use App\Features\PricingDiscounts\Models\PricingPromotion;
use Illuminate\Support\Facades\DB;

final class DeletePricingPromotion
{
    public function handle(PricingPromotion $promotion): void
    {
        DB::transaction(function () use ($promotion): void {
            $promotion->deleteOrFail();
        });
    }
}
