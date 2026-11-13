<?php

namespace App\Features\PricingDiscounts\Catalog;

use App\Features\PricingDiscounts\Models\PricingProduct;
use App\Features\PricingDiscounts\Models\PricingPromotion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

final class PricingCatalog
{
    /**
     * @return Collection<int, PricingProduct>
     */
    public function products(): Collection
    {
        return PricingProduct::query()
            ->available()
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, PricingPromotion>
     */
    public function promotions(?Carbon $at = null): Collection
    {
        return PricingPromotion::query()
            ->currentlyActive($at ?? now())
            ->orderBy('priority')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<string>  $skus
     * @return Collection<string, PricingProduct>
     */
    public function productsBySku(array $skus): Collection
    {
        return PricingProduct::query()
            ->available()
            ->whereIn('sku', $skus)
            ->get()
            ->keyBy('sku');
    }

    /**
     * @param  list<string>  $codes
     * @return Collection<string, PricingPromotion>
     */
    public function promotionsByCode(array $codes, ?Carbon $at = null): Collection
    {
        return PricingPromotion::query()
            ->currentlyActive($at ?? now())
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');
    }
}
