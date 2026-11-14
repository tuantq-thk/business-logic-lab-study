<?php

namespace Database\Seeders;

use App\Features\InventoryCapacity\Models\InventoryItem;
use Illuminate\Database\Seeder;

class InventoryCapacitySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['sku' => 'NOTEBOOK-BLUE', 'name_key' => 'inventory_capacity.demo.products.notebook', 'on_hand' => 24],
            ['sku' => 'PEN-BLACK', 'name_key' => 'inventory_capacity.demo.products.pen', 'on_hand' => 3],
            ['sku' => 'MUG-LAB', 'name_key' => 'inventory_capacity.demo.products.mug', 'on_hand' => 0],
        ];

        foreach ($items as $item) {
            InventoryItem::query()->firstOrCreate(
                ['sku' => $item['sku']],
                ['name_key' => $item['name_key'], 'on_hand' => $item['on_hand']],
            );
        }
    }
}
