<?php

namespace App\Features\InventoryCapacity\Actions;

use App\Features\InventoryCapacity\Enums\InventoryReservationStatus;
use App\Features\InventoryCapacity\Exceptions\InventoryInvariantViolation;
use App\Features\InventoryCapacity\Models\InventoryItem;
use App\Features\InventoryCapacity\Models\InventoryReservation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ExpireReservation
{
    public function handle(InventoryReservation $reservation, CarbonImmutable $now): ?InventoryReservation
    {
        return DB::transaction(function () use ($reservation, $now): ?InventoryReservation {
            $current = InventoryReservation::query()->whereKey($reservation->getKey())->first();

            if ($current === null || $current->status !== InventoryReservationStatus::Active || $current->expires_at > $now) {
                return $current;
            }

            $item = InventoryItem::query()->whereKey($current->inventory_item_id)->lockForUpdate()->firstOrFail();
            $current = InventoryReservation::query()->whereKey($current->getKey())->lockForUpdate()->firstOrFail();

            if ($current->status !== InventoryReservationStatus::Active || $current->expires_at > $now) {
                return $current;
            }

            if ($item->reserved_quantity < $current->quantity) {
                throw new InventoryInvariantViolation;
            }

            $item->forceFill([
                'reserved_quantity' => $item->reserved_quantity - $current->quantity,
                'version' => $item->version + 1,
            ])->saveOrFail();

            $current->forceFill([
                'status' => InventoryReservationStatus::Expired,
                'released_at' => $now,
            ])->saveOrFail();

            return $current->refresh();
        }, attempts: 3);
    }
}
