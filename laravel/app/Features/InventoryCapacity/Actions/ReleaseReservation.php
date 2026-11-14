<?php

namespace App\Features\InventoryCapacity\Actions;

use App\Features\InventoryCapacity\Enums\InventoryReservationStatus;
use App\Features\InventoryCapacity\Exceptions\InventoryInvariantViolation;
use App\Features\InventoryCapacity\Models\InventoryItem;
use App\Features\InventoryCapacity\Models\InventoryReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ReleaseReservation
{
    public function handle(InventoryReservation $reservation, User $actor, CarbonImmutable $now): InventoryReservation
    {
        Gate::forUser($actor)->authorize('manage', $reservation);

        return DB::transaction(function () use ($reservation, $actor, $now): InventoryReservation {
            $current = InventoryReservation::query()->whereKey($reservation->getKey())->firstOrFail();
            $item = InventoryItem::query()->whereKey($current->inventory_item_id)->lockForUpdate()->firstOrFail();
            $current = InventoryReservation::query()->whereKey($current->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manage', $current);

            if ($current->status !== InventoryReservationStatus::Active) {
                return $current;
            }

            if ($item->reserved_quantity < $current->quantity) {
                throw new InventoryInvariantViolation;
            }

            $current->forceFill([
                'status' => $current->expires_at <= $now ? InventoryReservationStatus::Expired : InventoryReservationStatus::Released,
                'released_at' => $now,
            ])->saveOrFail();

            $item->forceFill([
                'reserved_quantity' => $item->reserved_quantity - $current->quantity,
                'version' => $item->version + 1,
            ])->saveOrFail();

            return $current->refresh();
        }, attempts: 3);
    }
}
