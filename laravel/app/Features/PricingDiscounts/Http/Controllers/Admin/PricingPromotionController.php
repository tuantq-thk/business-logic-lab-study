<?php

namespace App\Features\PricingDiscounts\Http\Controllers\Admin;

use App\Features\PricingDiscounts\Actions\DeletePricingPromotion;
use App\Features\PricingDiscounts\Actions\SavePricingPromotion;
use App\Features\PricingDiscounts\Http\Requests\SavePricingPromotionRequest;
use App\Features\PricingDiscounts\Models\PricingPromotion;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class PricingPromotionController extends Controller
{
    public function index(): RedirectResponse
    {
        return to_route('labs.pricing-discounts.index');
    }

    public function store(SavePricingPromotionRequest $request, SavePricingPromotion $save): RedirectResponse
    {
        $save->handle($request->promotionData());

        return to_route('labs.pricing-discounts.index')->with('status', __('pricing_discounts.admin.flash.created'));
    }

    public function update(SavePricingPromotionRequest $request, PricingPromotion $promotion, SavePricingPromotion $save): RedirectResponse
    {
        $save->handle($request->promotionData(), $promotion);

        return to_route('labs.pricing-discounts.index')->with('status', __('pricing_discounts.admin.flash.updated'));
    }

    public function destroy(PricingPromotion $promotion, DeletePricingPromotion $delete): RedirectResponse
    {
        $delete->handle($promotion);

        return to_route('labs.pricing-discounts.index')->with('status', __('pricing_discounts.admin.flash.deleted'));
    }
}
