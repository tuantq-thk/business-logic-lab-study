<?php

namespace App\Features\InventoryCapacity\Actions;

use App\Features\InventoryCapacity\Exceptions\InsufficientInventory;
use App\Features\InventoryCapacity\Exceptions\InvalidReservationInput;
use App\Features\InventoryCapacity\Exceptions\ReservationKeyConflict;
use App\Features\InventoryCapacity\Models\InventoryItem;
use App\Features\InventoryCapacity\Models\InventoryReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ReserveStock
{
    public function handle(
        InventoryItem $item,
        User $owner,
        string $orderRef,
        int $quantity,
        CarbonImmutable $expiresAt,
    ): InventoryReservation {
        Gate::forUser($owner)->authorize('create', InventoryReservation::class);

        $orderRef = strtoupper(trim($orderRef));

        if ($quantity < 1 || $orderRef === '' || $expiresAt <= CarbonImmutable::now()) {
            throw new InvalidReservationInput;
        }

        return DB::transaction(function () use ($item, $owner, $orderRef, $quantity, $expiresAt): InventoryReservation {
            $currentItem = InventoryItem::query()->whereKey($item->getKey())->lockForUpdate()->firstOrFail();
            $existing = InventoryReservation::query()
                ->where('inventory_item_id', $currentItem->id)
                ->where('owner_id', $owner->id)
                ->where('order_ref', $orderRef)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if ($existing->quantity !== $quantity) {
                    throw new ReservationKeyConflict;
                }

                return $existing;
            }

            if ($currentItem->availableQuantity() < $quantity) {
                throw new InsufficientInventory;
            }

            $currentItem->forceFill([
                'reserved_quantity' => $currentItem->reserved_quantity + $quantity,
                'version' => $currentItem->version + 1,
            ])->saveOrFail();

            return InventoryReservation::query()->create([
                'inventory_item_id' => $currentItem->id,
                'owner_id' => $owner->id,
                'order_ref' => $orderRef,
                'quantity' => $quantity,
                'expires_at' => $expiresAt,
            ]);
        }, attempts: 3);
    }
}
