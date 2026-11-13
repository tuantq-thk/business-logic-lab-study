<?php

namespace Database\Factories\PricingDiscounts;

use App\Features\PricingDiscounts\Models\PricingProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingProduct>
 */
class PricingProductFactory extends Factory
{
    protected $model = PricingProduct::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sku = fake()->unique()->bothify('book-####');

        return [
            'sku' => $sku,
            'name_key' => 'pricing_discounts.demo.products.php',
            'unit_price_minor' => fake()->numberBetween(500, 10000),
            'currency' => 'USD',
            'active' => true,
        ];
    }
}
