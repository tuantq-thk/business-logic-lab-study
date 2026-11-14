<?php

namespace App\Providers;

use App\Features\InventoryCapacity\Models\InventoryItem;
use App\Features\InventoryCapacity\Models\InventoryReservation;
use App\Features\InventoryCapacity\Policies\InventoryItemPolicy;
use App\Features\InventoryCapacity\Policies\InventoryReservationPolicy;
use App\Features\StateTransition\Models\SupportTicket;
use App\Features\StateTransition\Policies\SupportTicketPolicy;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-catalog', fn (User $user): bool => $user->isAdmin());
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);
        Gate::policy(InventoryItem::class, InventoryItemPolicy::class);
        Gate::policy(InventoryReservation::class, InventoryReservationPolicy::class);
        Gate::policy(RoomBooking::class, RoomBookingPolicy::class);

        RateLimiter::for('inventory-mutations', function (Request $request): Limit {
            return Limit::perMinute(30)
                ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });

        RateLimiter::for('booking-mutations', function (Request $request): Limit {
            return Limit::perMinute(30)
                ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });

        RateLimiter::for('contextual-validation-mutations', function (Request $request): Limit {
            return Limit::perMinute(30)
                ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        });
    }
}
use App\Features\BookingOverlap\Models\RoomBooking;
use App\Features\BookingOverlap\Policies\RoomBookingPolicy;
