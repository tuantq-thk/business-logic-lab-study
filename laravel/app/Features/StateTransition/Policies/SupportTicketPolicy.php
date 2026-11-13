<?php

namespace App\Features\StateTransition\Policies;

use App\Features\StateTransition\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->isAdmin()
            || $ticket->created_by === $user->id
            || $ticket->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function changeStatus(User $user, SupportTicket $ticket): bool
    {
        return $user->isAdmin() || $ticket->created_by === $user->id;
    }
}
