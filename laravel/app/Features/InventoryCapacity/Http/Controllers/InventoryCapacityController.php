<?php

namespace App\Features\InventoryCapacity\Http\Controllers;

use App\Features\InventoryCapacity\Actions\AdjustStock;
use App\Features\InventoryCapacity\Actions\ConsumeReservation;
use App\Features\InventoryCapacity\Actions\ReleaseReservation;
use App\Features\InventoryCapacity\Actions\ReserveStock;
use App\Features\InventoryCapacity\Exceptions\InsufficientInventory;
use App\Features\InventoryCapacity\Exceptions\InvalidReservationInput;
use App\Features\InventoryCapacity\Exceptions\InvalidStockAdjustment;
use App\Features\InventoryCapacity\Exceptions\InventoryInvariantViolation;
use App\Features\InventoryCapacity\Exceptions\ReservationExpired;
use App\Features\InventoryCapacity\Exceptions\ReservationKeyConflict;
use App\Features\InventoryCapacity\Exceptions\ReservationTerminal;
use App\Features\InventoryCapacity\Http\Requests\AdjustInventoryRequest;
use App\Features\InventoryCapacity\Http\Requests\ReserveInventoryRequest;
use App\Features\InventoryCapacity\Models\InventoryItem;
use App\Features\InventoryCapacity\Models\InventoryReservation;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryCapacityController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 401);

        $items = InventoryItem::query()
            ->orderBy('sku')
            ->limit(config('labs.inventory_capacity.max_items', 100))
            ->get();
        $reservations = InventoryReservation::query()
            ->with('inventoryItem')
            ->when(! $user->isAdmin(), fn ($query) => $query->where('owner_id', $user->id))
            ->latest('id')
            ->limit(20)
            ->get();

        return view('labs.inventory-capacity.index', compact('items', 'reservations'));
    }

    public function reserve(ReserveInventoryRequest $request, InventoryItem $item, ReserveStock $reserve): RedirectResponse
    {
        try {
            $data = $request->itemData($item);
            $reserve->handle(
                item: $item,
                owner: $request->user(),
                orderRef: $data['order_ref'],
                quantity: $data['quantity'],
                expiresAt: CarbonImmutable::now()->addMinutes($data['ttl_minutes']),
            );
        } catch (InsufficientInventory) {
            return back()->withErrors(['inventory' => __('inventory_capacity.errors.insufficient')]);
        } catch (InvalidReservationInput) {
            return back()->withErrors(['inventory' => __('inventory_capacity.errors.invalid_input')]);
        } catch (ReservationKeyConflict) {
            return back()->withErrors(['order_ref' => __('inventory_capacity.errors.key_conflict')]);
        } catch (InventoryInvariantViolation) {
            return back()->withErrors(['inventory' => __('inventory_capacity.errors.invariant')]);
        }

        return to_route('labs.inventory-capacity.index')->with('status', __('inventory_capacity.flash.reserved'));
    }

    public function consume(InventoryReservation $reservation, ConsumeReservation $consume): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage', $reservation), 404);

        try {
            $consume->handle($reservation, auth()->user(), CarbonImmutable::now());
        } catch (ReservationExpired) {
            return back()->withErrors(['reservation' => __('inventory_capacity.errors.expired')]);
        } catch (ReservationTerminal) {
            return back()->withErrors(['reservation' => __('inventory_capacity.errors.terminal')]);
        } catch (InventoryInvariantViolation) {
            return back()->withErrors(['reservation' => __('inventory_capacity.errors.invariant')]);
        }

        return to_route('labs.inventory-capacity.index')->with('status', __('inventory_capacity.flash.consumed'));
    }

    public function release(InventoryReservation $reservation, ReleaseReservation $release): RedirectResponse
    {
        abort_unless(auth()->user()?->can('manage', $reservation), 404);

        try {
            $release->handle($reservation, auth()->user(), CarbonImmutable::now());
        } catch (InventoryInvariantViolation) {
            return back()->withErrors(['reservation' => __('inventory_capacity.errors.invariant')]);
        }

        return to_route('labs.inventory-capacity.index')->with('status', __('inventory_capacity.flash.released'));
    }

    public function adjust(AdjustInventoryRequest $request, InventoryItem $item, AdjustStock $adjust): RedirectResponse
    {
        try {
            $data = $request->validated();
            $adjust->handle(
                item: $item,
                actor: $request->user(),
                delta: (int) $data['delta'],
                reason: trim((string) $data['reason']),
                reference: filled($data['reference'] ?? null) ? trim((string) $data['reference']) : null,
            );
        } catch (InvalidStockAdjustment) {
            return back()->withErrors(['delta' => __('inventory_capacity.errors.adjustment')]);
        } catch (\InvalidArgumentException) {
            return back()->withErrors(['delta' => __('inventory_capacity.errors.invalid_adjustment')]);
        } catch (InventoryInvariantViolation) {
            return back()->withErrors(['delta' => __('inventory_capacity.errors.invariant')]);
        }

        return to_route('labs.inventory-capacity.index')->with('status', __('inventory_capacity.flash.adjusted'));
    }
}
