<?php

namespace Database\Factories\StateTransition;

use App\Features\StateTransition\Enums\SupportTicketStatus;
use App\Features\StateTransition\Models\SupportTicket;
use App\Features\StateTransition\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    protected $model = SupportTicket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(5),
            'status' => SupportTicketStatus::Open,
            'assigned_to' => null,
            'created_by' => User::factory(),
            'resolved_at' => null,
            'version' => 0,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'status' => SupportTicketStatus::InProgress,
            'assigned_to' => User::factory(),
            'version' => 1,
        ]);
    }

    public function assigned(): static
    {
        return $this->inProgress();
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => SupportTicketStatus::Resolved,
            'assigned_to' => User::factory(),
            'resolved_at' => now(),
            'version' => 2,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => SupportTicketStatus::Closed,
            'assigned_to' => User::factory(),
            'resolved_at' => now(),
            'version' => 3,
        ]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (SupportTicket $ticket): void {
            $statuses = match ($ticket->status) {
                SupportTicketStatus::Open => [SupportTicketStatus::Open],
                SupportTicketStatus::InProgress => [SupportTicketStatus::Open, SupportTicketStatus::InProgress],
                SupportTicketStatus::Resolved => [SupportTicketStatus::Open, SupportTicketStatus::InProgress, SupportTicketStatus::Resolved],
                SupportTicketStatus::Closed => [SupportTicketStatus::Open, SupportTicketStatus::InProgress, SupportTicketStatus::Resolved, SupportTicketStatus::Closed],
            };

            $previous = null;

            foreach ($statuses as $status) {
                TicketStatusHistory::query()->create([
                    'ticket_id' => $ticket->id,
                    'from_status' => $previous,
                    'to_status' => $status,
                    'actor_id' => $ticket->assigned_to ?? $ticket->created_by,
                ]);

                $previous = $status;
            }
        });
    }
}
