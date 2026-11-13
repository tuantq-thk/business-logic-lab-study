<?php

namespace App\Features\StateTransition\Actions;

use App\Features\StateTransition\Enums\SupportTicketStatus;
use App\Features\StateTransition\Exceptions\InvalidTicketTransition;
use App\Features\StateTransition\Exceptions\StaleTicket;
use App\Features\StateTransition\Exceptions\TicketTransitionReasonRequired;
use App\Features\StateTransition\Models\SupportTicket;
use App\Features\StateTransition\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ChangeSupportTicketStatus
{
    public function handle(
        SupportTicket $ticket,
        User $actor,
        SupportTicketStatus $toStatus,
        ?string $reason,
        int $expectedVersion,
    ): SupportTicket {
        Gate::forUser($actor)->authorize('changeStatus', $ticket);

        return DB::transaction(function () use ($ticket, $actor, $toStatus, $reason, $expectedVersion): SupportTicket {
            $current = SupportTicket::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $current->status;

            // Re-check after locking so a concurrent ownership change cannot bypass policy.
            Gate::forUser($actor)->authorize('changeStatus', $current);

            if ($current->version !== $expectedVersion) {
                throw new StaleTicket;
            }

            $this->assertAllowed($fromStatus, $toStatus, $reason);

            $current->forceFill([
                'status' => $toStatus,
                'assigned_to' => $toStatus === SupportTicketStatus::InProgress ? $actor->id : $current->assigned_to,
                'resolved_at' => $toStatus === SupportTicketStatus::Resolved ? now() : ($toStatus === SupportTicketStatus::Open ? null : $current->resolved_at),
                'version' => $current->version + 1,
            ])->saveOrFail();

            TicketStatusHistory::query()->create([
                'ticket_id' => $current->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_id' => $actor->id,
                'reason' => $reason,
            ]);

            return $current->refresh();
        });
    }

    private function assertAllowed(SupportTicketStatus $fromStatus, SupportTicketStatus $toStatus, ?string $reason): void
    {
        $allowed = match ($fromStatus) {
            SupportTicketStatus::Open => [SupportTicketStatus::InProgress],
            SupportTicketStatus::InProgress => [SupportTicketStatus::Resolved],
            SupportTicketStatus::Resolved => [SupportTicketStatus::Closed],
            SupportTicketStatus::Closed => [SupportTicketStatus::Open],
        };

        if (! in_array($toStatus, $allowed, true)) {
            throw new InvalidTicketTransition($fromStatus, $toStatus);
        }

        if ($fromStatus === SupportTicketStatus::Closed && blank($reason)) {
            throw new TicketTransitionReasonRequired;
        }
    }
}
