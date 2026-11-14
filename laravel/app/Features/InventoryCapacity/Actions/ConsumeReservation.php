<?php

namespace App\Features\InventoryCapacity\Actions;

use App\Features\InventoryCapacity\Enums\InventoryReservationStatus;
use App\Features\InventoryCapacity\Exceptions\InventoryInvariantViolation;
use App\Features\InventoryCapacity\Exceptions\ReservationExpired;
use App\Features\InventoryCapacity\Exceptions\ReservationTerminal;
use App\Features\InventoryCapacity\Models\InventoryItem;
use App\Features\InventoryCapacity\Models\InventoryMovement;
use App\Features\InventoryCapacity\Models\InventoryReservation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ConsumeReservation
{
    public function handle(InventoryReservation $reservation, User $actor, CarbonImmutable $now): InventoryReservation
    {
        Gate::forUser($actor)->authorize('manage', $reservation);

        $result = DB::transaction(function () use ($reservation, $actor, $now): InventoryReservation {
            $current = InventoryReservation::query()->whereKey($reservation->getKey())->firstOrFail();
            $item = InventoryItem::query()->whereKey($current->inventory_item_id)->lockForUpdate()->firstOrFail();
            $current = InventoryReservation::query()->whereKey($current->getKey())->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('manage', $current);

            if ($current->status === InventoryReservationStatus::Consumed) {
                return $current;
            }

            if ($current->status !== InventoryReservationStatus::Active) {
                throw new ReservationTerminal;
            }

            if ($current->expires_at <= $now) {
                $this->assertCapacity($item, $current->quantity);
                $this->expire($item, $current, $now);

                return $current->refresh();
            }

            $this->assertCapacity($item, $current->quantity);

            $item->forceFill([
                'on_hand' => $item->on_hand - $current->quantity,
                'reserved_quantity' => $item->reserved_quantity - $current->quantity,
                'version' => $item->version + 1,
            ])->saveOrFail();

            $current->forceFill([
                'status' => InventoryReservationStatus::Consumed,
                'consumed_at' => $now,
            ])->saveOrFail();

            InventoryMovement::query()->create([
                'inventory_item_id' => $item->id,
                'delta' => -$current->quantity,
                'reason' => 'reservation_consumed',
                'reference' => $current->order_ref,
                'actor_id' => $actor->id,
            ]);

            return $current->refresh();
        }, attempts: 3);

        if ($result->status === InventoryReservationStatus::Expired) {
            throw new ReservationExpired;
        }

        return $result;
    }

    private function expire(InventoryItem $item, InventoryReservation $reservation, CarbonImmutable $now): void
    {
        $item->forceFill([
            'reserved_quantity' => $item->reserved_quantity - $reservation->quantity,
            'version' => $item->version + 1,
        ])->saveOrFail();

        $reservation->forceFill([
            'status' => InventoryReservationStatus::Expired,
            'released_at' => $now,
        ])->saveOrFail();
    }

    private function assertCapacity(InventoryItem $item, int $quantity): void
    {
        if ($item->reserved_quantity < $quantity || $item->on_hand < $quantity) {
            throw new InventoryInvariantViolation;
        }
    }
}
