<?php

namespace App\Features\InventoryCapacity\Models;

use Database\Factories\InventoryCapacity\InventoryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sku', 'name_key', 'on_hand'])]
class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory;

    protected static function newFactory(): InventoryItemFactory
    {
        return InventoryItemFactory::new();
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function availableQuantity(): int
    {
        return (int) $this->on_hand - (int) $this->reserved_quantity;
    }

    protected function casts(): array
    {
        return [
            'on_hand' => 'integer',
            'reserved_quantity' => 'integer',
            'version' => 'integer',
        ];
    }
}
