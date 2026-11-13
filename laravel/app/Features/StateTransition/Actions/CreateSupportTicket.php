<?php

namespace App\Features\StateTransition\Actions;

use App\Features\StateTransition\Enums\SupportTicketStatus;
use App\Features\StateTransition\Models\SupportTicket;
use App\Features\StateTransition\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class CreateSupportTicket
{
    public function handle(User $actor, string $subject): SupportTicket
    {
        Gate::forUser($actor)->authorize('create', SupportTicket::class);

        return DB::transaction(function () use ($actor, $subject): SupportTicket {
            $ticket = new SupportTicket;
            $ticket->forceFill([
                'subject' => $subject,
                'status' => SupportTicketStatus::Open,
                'created_by' => $actor->id,
                'version' => 0,
            ])->saveOrFail();

            TicketStatusHistory::query()->create([
                'ticket_id' => $ticket->id,
                'from_status' => null,
                'to_status' => SupportTicketStatus::Open,
                'actor_id' => $actor->id,
            ]);

            return $ticket->refresh();
        });
    }
}
