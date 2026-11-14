<?php

namespace Database\Factories\InventoryCapacity;

use App\Features\InventoryCapacity\Enums\InventoryReservationStatus;
use App\Features\InventoryCapacity\Exceptions\InsufficientInventory;
use App\Features\InventoryCapacity\Models\InventoryItem;
use App\Features\InventoryCapacity\Models\InventoryReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<InventoryReservation>
 */
class InventoryReservationFactory extends Factory
{
    protected $model = InventoryReservation::class;

    public function configure(): static
    {
        return $this->afterCreating(function (InventoryReservation $reservation): void {
            if ($reservation->status !== InventoryReservationStatus::Active) {
                return;
            }

            DB::transaction(function () use ($reservation): void {
                $item = $reservation->inventoryItem()->lockForUpdate()->firstOrFail();

                if ($item->availableQuantity() < $reservation->quantity) {
                    throw new InsufficientInventory;
                }

                $item->forceFill([
                    'reserved_quantity' => $item->reserved_quantity + $reservation->quantity,
                    'version' => $item->version + 1,
                ])->saveOrFail();
            });
        });
    }

    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'owner_id' => User::factory(),
            'order_ref' => strtoupper(fake()->unique()->bothify('ORDER-####')),
            'quantity' => 1,
            'status' => InventoryReservationStatus::Active,
            'expires_at' => now()->addMinutes(15),
        ];
    }

    public function consumed(): static
    {
        return $this->state([
            'status' => InventoryReservationStatus::Consumed,
            'consumed_at' => now(),
        ]);
    }

    public function released(): static
    {
        return $this->state([
            'status' => InventoryReservationStatus::Released,
            'released_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'status' => InventoryReservationStatus::Expired,
            'expires_at' => now()->subMinute(),
            'released_at' => now(),
        ]);
    }
}
