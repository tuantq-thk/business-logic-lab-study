<?php

namespace Database\Factories\PricingDiscounts;

use App\Features\PricingDiscounts\Enums\DiscountKind;
use App\Features\PricingDiscounts\Models\PricingPromotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingPromotion>
 */
class PricingPromotionFactory extends Factory
{
    protected $model = PricingPromotion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('PROMO-####'),
            'kind' => DiscountKind::Percentage->value,
            'value' => 10,
            'minimum_subtotal_minor' => 0,
            'priority' => 10,
            'stackable' => true,
            'active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }
}
