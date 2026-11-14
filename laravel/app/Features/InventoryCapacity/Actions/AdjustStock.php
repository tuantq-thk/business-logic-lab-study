<?php

namespace App\Features\InventoryCapacity\Actions;

use App\Features\InventoryCapacity\Exceptions\InvalidStockAdjustment;
use App\Features\InventoryCapacity\Models\InventoryItem;
use App\Features\InventoryCapacity\Models\InventoryMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class AdjustStock
{
    public function handle(InventoryItem $item, User $actor, int $delta, string $reason, ?string $reference = null): InventoryItem
    {
        if ($delta === 0 || blank(trim($reason))) {
            throw new \InvalidArgumentException('inventory_capacity.errors.invalid_adjustment');
        }

        Gate::forUser($actor)->authorize('adjust', InventoryItem::class);

        return DB::transaction(function () use ($item, $actor, $delta, $reason, $reference): InventoryItem {
            $current = InventoryItem::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $newOnHand = $current->on_hand + $delta;

            if ($newOnHand < $current->reserved_quantity) {
                throw new InvalidStockAdjustment;
            }

            $current->forceFill([
                'on_hand' => $newOnHand,
                'version' => $current->version + 1,
            ])->saveOrFail();

            InventoryMovement::query()->create([
                'inventory_item_id' => $current->id,
                'delta' => $delta,
                'reason' => $reason,
                'reference' => $reference,
                'actor_id' => $actor->id,
            ]);

            return $current->refresh();
        }, attempts: 3);
    }
}
