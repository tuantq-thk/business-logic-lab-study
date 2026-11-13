<?php

namespace Database\Seeders;

use App\Features\PricingDiscounts\Enums\DiscountKind;
use App\Features\PricingDiscounts\Models\PricingProduct;
use App\Features\PricingDiscounts\Models\PricingPromotion;
use Illuminate\Database\Seeder;

class PricingDiscountsSeeder extends Seeder
{
    public function run(): void
    {
        PricingProduct::query()->updateOrCreate(
            ['sku' => 'book-php'],
            [
                'name_key' => 'pricing_discounts.demo.products.php',
                'unit_price_minor' => 1500,
                'currency' => 'USD',
                'active' => true,
            ],
        );
        PricingProduct::query()->updateOrCreate(
            ['sku' => 'book-sql'],
            [
                'name_key' => 'pricing_discounts.demo.products.sql',
                'unit_price_minor' => 2000,
                'currency' => 'USD',
                'active' => true,
            ],
        );

        $promotions = [
            [
                'code' => 'WELCOME10',
                'kind' => DiscountKind::Percentage->value,
                'value' => 10,
                'minimum_subtotal_minor' => 0,
                'priority' => 10,
                'stackable' => true,
            ],
            [
                'code' => 'STACK5',
                'kind' => DiscountKind::Percentage->value,
                'value' => 5,
                'minimum_subtotal_minor' => 4500,
                'priority' => 20,
                'stackable' => true,
            ],
            [
                'code' => 'VIP700',
                'kind' => DiscountKind::Fixed->value,
                'value' => 700,
                'minimum_subtotal_minor' => 0,
                'priority' => 5,
                'stackable' => false,
            ],
            [
                'code' => 'SAVE300',
                'kind' => DiscountKind::Fixed->value,
                'value' => 300,
                'minimum_subtotal_minor' => 3000,
                'priority' => 15,
                'stackable' => false,
            ],
            [
                'code' => 'BULK15',
                'kind' => DiscountKind::Percentage->value,
                'value' => 15,
                'minimum_subtotal_minor' => 7000,
                'priority' => 30,
                'stackable' => true,
            ],
        ];

        foreach ($promotions as $promotion) {
            PricingPromotion::query()->updateOrCreate(
                ['code' => $promotion['code']],
                $promotion + ['active' => true, 'starts_at' => null, 'ends_at' => null],
            );
        }
    }
}
