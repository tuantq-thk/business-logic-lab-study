<?php

namespace App\Features\InventoryCapacity\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['inventory_item_id', 'delta', 'reason', 'reference', 'actor_id'])]
class InventoryMovement extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new \LogicException(__('inventory_capacity.exceptions.append_only'));
        });

        static::deleting(function (): never {
            throw new \LogicException(__('inventory_capacity.exceptions.append_only'));
        });
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'delta' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }
}
