<?php

namespace App\Providers;

use App\Features\StateTransition\Models\SupportTicket;
use App\Features\StateTransition\Policies\SupportTicketPolicy;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
    }
}
