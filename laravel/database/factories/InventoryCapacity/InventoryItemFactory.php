<?php

namespace Database\Factories\InventoryCapacity;

use App\Features\InventoryCapacity\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'name_key' => 'inventory_capacity.demo.products.default',
            'on_hand' => 100,
            'reserved_quantity' => 0,
            'version' => 0,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(['on_hand' => 5]);
    }

    public function outOfStock(): static
    {
        return $this->state(['on_hand' => 0]);
    }
}
