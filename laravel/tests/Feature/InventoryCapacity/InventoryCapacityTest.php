<?php

use App\Features\InventoryCapacity\Actions\AdjustStock;
use App\Features\InventoryCapacity\Actions\ConsumeReservation;
use App\Features\InventoryCapacity\Actions\ReleaseReservation;
use App\Features\InventoryCapacity\Actions\ReserveStock;
use App\Features\InventoryCapacity\Enums\InventoryReservationStatus;
use App\Features\InventoryCapacity\Exceptions\InsufficientInventory;
use App\Features\InventoryCapacity\Exceptions\InvalidStockAdjustment;
use App\Features\InventoryCapacity\Exceptions\InventoryInvariantViolation;
use App\Features\InventoryCapacity\Exceptions\ReservationExpired;
use App\Features\InventoryCapacity\Exceptions\ReservationKeyConflict;
use App\Features\InventoryCapacity\Models\InventoryItem;
use App\Features\InventoryCapacity\Models\InventoryMovement;
use App\Features\InventoryCapacity\Models\InventoryReservation;
use App\Models\CatalogSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\AdminCatalogSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Lang;

function inventoryUser(): User
{
    return User::factory()->create();
}

test('seeded inventory product names resolve in every supported locale', function () {
    foreach (['notebook', 'mug', 'pen'] as $product) {
        $key = "inventory_capacity.demo.products.{$product}";

        expect(Lang::get($key, [], 'en'))->not->toBe($key)
            ->and(Lang::get($key, [], 'vi'))->not->toBe($key);
    }
});

test('a reservation atomically consumes available capacity and retries by business key', function () {
    $owner = inventoryUser();
    $item = InventoryItem::factory()->create(['on_hand' => 5]);
    $reserve = app(ReserveStock::class);
    $expiresAt = CarbonImmutable::now()->addMinutes(15);

    $first = $reserve->handle($item, $owner, 'ORDER-001', 3, $expiresAt);
    $retry = $reserve->handle($item->refresh(), $owner, 'ORDER-001', 3, $expiresAt);

    expect($retry->id)->toBe($first->id)
        ->and(InventoryReservation::query()->count())->toBe(1);
    expect($item->refresh()->reserved_quantity)->toBe(3)
        ->and($item->availableQuantity())->toBe(2)
        ->and($item->version)->toBe(1);
});

test('a same-key retry with a different quantity is rejected without changing the counter', function () {
    $owner = inventoryUser();
    $item = InventoryItem::factory()->create(['on_hand' => 5]);
    $reserve = app(ReserveStock::class);

    $reserve->handle($item, $owner, 'ORDER-002', 2, CarbonImmutable::now()->addMinutes(15));

    expect(fn () => $reserve->handle($item->refresh(), $owner, 'ORDER-002', 3, CarbonImmutable::now()->addMinutes(15)))
        ->toThrow(ReservationKeyConflict::class);
    expect($item->refresh()->reserved_quantity)->toBe(2);
});

test('a reservation cannot exceed available inventory', function () {
    $owner = inventoryUser();
    $item = InventoryItem::factory()->create(['on_hand' => 2]);

    expect(fn () => app(ReserveStock::class)->handle($item, $owner, 'ORDER-003', 3, CarbonImmutable::now()->addMinutes(15)))
        ->toThrow(InsufficientInventory::class);
    expect($item->refresh()->reserved_quantity)->toBe(0)
        ->and(InventoryReservation::query()->count())->toBe(0);
});

test('consuming a reservation decrements on hand and writes an immutable movement', function () {
    $owner = inventoryUser();
    $item = InventoryItem::factory()->create(['on_hand' => 5]);
    $reservation = app(ReserveStock::class)->handle($item, $owner, 'ORDER-004', 2, CarbonImmutable::now()->addMinutes(15));

    $result = app(ConsumeReservation::class)->handle($reservation, $owner, CarbonImmutable::now());

    expect($result->status)->toBe(InventoryReservationStatus::Consumed);
    expect($item->refresh()->on_hand)->toBe(3)
        ->and($item->reserved_quantity)->toBe(0)
        ->and(InventoryMovement::query()->sole()->delta)->toBe(-2);

    $retry = app(ConsumeReservation::class)->handle($result, $owner, CarbonImmutable::now());
    expect($retry->id)->toBe($result->id)
        ->and(InventoryMovement::query()->count())->toBe(1);
});

test('release is idempotent and returns capacity without changing physical stock', function () {
    $owner = inventoryUser();
    $item = InventoryItem::factory()->create(['on_hand' => 5]);
    $reservation = app(ReserveStock::class)->handle($item, $owner, 'ORDER-005', 2, CarbonImmutable::now()->addMinutes(15));
    $release = app(ReleaseReservation::class);

    $first = $release->handle($reservation, $owner, CarbonImmutable::now());
    $second = $release->handle($first, $owner, CarbonImmutable::now());

    expect($second->status)->toBe(InventoryReservationStatus::Released);
    expect($item->refresh()->on_hand)->toBe(5)
        ->and($item->reserved_quantity)->toBe(0)
        ->and($item->version)->toBe(2);
});

test('an expired reservation is released before consume can succeed', function () {
    $owner = inventoryUser();
    $item = InventoryItem::factory()->create(['on_hand' => 5]);
    $reservation = app(ReserveStock::class)->handle($item, $owner, 'ORDER-006', 2, CarbonImmutable::now()->addMinute());
    $reservation->forceFill(['expires_at' => CarbonImmutable::now()->subMinute()])->saveOrFail();

    expect(fn () => app(ConsumeReservation::class)->handle($reservation, $owner, CarbonImmutable::now()))
        ->toThrow(ReservationExpired::class);
    expect($reservation->refresh()->status)->toBe(InventoryReservationStatus::Expired)
        ->and($item->refresh()->reserved_quantity)->toBe(0);
});

test('a counter drift fails closed instead of producing a negative balance', function () {
    $owner = inventoryUser();
    $item = InventoryItem::factory()->create(['on_hand' => 5]);
    $reservation = app(ReserveStock::class)->handle($item, $owner, 'ORDER-DRIFT', 2, CarbonImmutable::now()->addMinutes(15));
    $item->refresh()->forceFill(['reserved_quantity' => 0])->saveOrFail();

    expect(fn () => app(ConsumeReservation::class)->handle($reservation, $owner, CarbonImmutable::now()))
        ->toThrow(InventoryInvariantViolation::class);
    expect($reservation->refresh()->status)->toBe(InventoryReservationStatus::Active)
        ->and($item->refresh()->on_hand)->toBe(5);
});

test('only an admin can adjust stock and an adjustment cannot undercut active reservations', function () {
    $owner = inventoryUser();
    $item = InventoryItem::factory()->create(['on_hand' => 5]);
    app(ReserveStock::class)->handle($item, $owner, 'ORDER-007', 4, CarbonImmutable::now()->addMinutes(15));

    expect(fn () => app(AdjustStock::class)->handle($item->refresh(), $owner, -2, 'Damaged'))
        ->toThrow(AuthorizationException::class);

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    expect(fn () => app(AdjustStock::class)->handle($item->refresh(), $admin, -2, 'Damaged'))
        ->toThrow(InvalidStockAdjustment::class);

    $adjusted = app(AdjustStock::class)->handle($item->refresh(), $admin, 3, 'Cycle count', 'COUNT-001');
    expect($adjusted->on_hand)->toBe(8)
        ->and(InventoryMovement::query()->sole()->reason)->toBe('Cycle count');
});

test('the inventory demo protects routes and exposes reserve and admin adjustment flows', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $learner = inventoryUser();
    $item = InventoryItem::factory()->create(['sku' => 'UI-SKU', 'on_hand' => 4]);

    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);

    $this->actingAs($learner)
        ->post(route('labs.inventory-capacity.reserve', $item), ['items' => [$item->id => ['order_ref' => 'UI-001', 'quantity' => 2, 'ttl_minutes' => 15]]])
        ->assertRedirect(route('labs.inventory-capacity.index'));

    $reservation = InventoryReservation::query()->sole();
    $this->actingAs($learner)->post(route('labs.inventory-capacity.consume', $reservation))->assertRedirect();

    $this->actingAs($admin)
        ->patch(route('admin.inventory.items.adjust', $item), ['delta' => 1, 'reason' => 'Cycle count'])
        ->assertRedirect(route('labs.inventory-capacity.index'));

    expect($item->refresh()->on_hand)->toBe(3);
});

test('a user cannot use another users reservation endpoint or enumerate its existence', function () {
    $owner = inventoryUser();
    $other = inventoryUser();
    $item = InventoryItem::factory()->create(['on_hand' => 4]);
    $reservation = app(ReserveStock::class)->handle($item, $owner, 'ORDER-SCOPE', 1, CarbonImmutable::now()->addMinutes(15));
    $this->seed(AdminCatalogSeeder::class);
    CatalogSetting::query()->where('key', CatalogSetting::CATALOG_KEY)->update(['enabled' => true]);

    $this->actingAs($other)
        ->post(route('labs.inventory-capacity.consume', $reservation))
        ->assertNotFound();

    expect($reservation->refresh()->status)->toBe(InventoryReservationStatus::Active);
});
