<?php

namespace App\Features\InventoryCapacity\Models;

use App\Features\InventoryCapacity\Enums\InventoryReservationStatus;
use App\Models\User;
use Database\Factories\InventoryCapacity\InventoryReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['inventory_item_id', 'owner_id', 'order_ref', 'quantity', 'expires_at'])]
class InventoryReservation extends Model
{
    /** @use HasFactory<InventoryReservationFactory> */
    use HasFactory;

    protected static function newFactory(): InventoryReservationFactory
    {
        return InventoryReservationFactory::new();
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function isActive(): bool
    {
        return $this->status === InventoryReservationStatus::Active;
    }

    protected function casts(): array
    {
        return [
            'status' => InventoryReservationStatus::class,
            'quantity' => 'integer',
            'expires_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
            'released_at' => 'immutable_datetime',
        ];
    }
}
