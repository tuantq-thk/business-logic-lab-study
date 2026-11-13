<?php

namespace Database\Seeders\StateTransition;

use App\Features\StateTransition\Enums\SupportTicketStatus;
use App\Features\StateTransition\Models\SupportTicket;
use App\Features\StateTransition\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class StateTransitionSeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::query()->first();

        if ($actor === null) {
            $this->command?->warn('State transition demo skipped: create a user first.');

            return;
        }

        $scenarios = [
            ['subject' => 'Demo: ticket mới', 'statuses' => [SupportTicketStatus::Open]],
            ['subject' => 'Demo: ticket đang xử lý', 'statuses' => [SupportTicketStatus::Open, SupportTicketStatus::InProgress]],
            ['subject' => 'Demo: ticket đã xử lý', 'statuses' => [SupportTicketStatus::Open, SupportTicketStatus::InProgress, SupportTicketStatus::Resolved]],
            ['subject' => 'Demo: ticket đã đóng', 'statuses' => [SupportTicketStatus::Open, SupportTicketStatus::InProgress, SupportTicketStatus::Resolved, SupportTicketStatus::Closed]],
        ];

        foreach ($scenarios as $scenario) {
            $statuses = $scenario['statuses'];
            $currentStatus = end($statuses);
            $ticket = SupportTicket::query()->firstOrNew(['subject' => $scenario['subject']]);

            if ($ticket->exists) {
                continue;
            }

            $ticket->forceFill([
                'status' => $currentStatus,
                'assigned_to' => count($statuses) > 1 ? $actor->id : null,
                'created_by' => $actor->id,
                'resolved_at' => in_array($currentStatus, [SupportTicketStatus::Resolved, SupportTicketStatus::Closed], true) ? now() : null,
                'version' => count($statuses) - 1,
            ])->save();

            $previous = null;

            foreach ($statuses as $status) {
                TicketStatusHistory::query()->create([
                    'ticket_id' => $ticket->id,
                    'from_status' => $previous,
                    'to_status' => $status,
                    'actor_id' => $actor->id,
                ]);

                $previous = $status;
            }
        }
    }
}
