<?php

namespace Database\Factories\ContextualValidation;

use App\Features\ContextualValidation\Enums\EventStatus;
use App\Features\ContextualValidation\Models\WorkshopEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<WorkshopEvent> */
class WorkshopEventFactory extends Factory
{
    protected $model = WorkshopEvent::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'starts_at_utc' => CarbonImmutable::parse('2030-01-15 09:00:00', 'UTC'),
            'registration_opens_at_utc' => CarbonImmutable::parse('2030-01-01 00:00:00', 'UTC'),
            'registration_closes_at_utc' => CarbonImmutable::parse('2030-01-14 23:59:59', 'UTC'),
            'capacity' => 20,
            'status' => EventStatus::Open,
            'invite_code_hash' => null,
        ];
    }

    public function inviteOnly(string $code = 'LAB-INVITE-2026'): static
    {
        return $this->state(['invite_code_hash' => Hash::make($code)]);
    }

    public function closed(): static
    {
        return $this->state(['status' => EventStatus::Closed]);
    }

    public function draft(): static
    {
        return $this->state(['status' => EventStatus::Draft]);
    }
}
