<?php

namespace Database\Factories\ContextualValidation;

use App\Features\ContextualValidation\Enums\RegistrationStatus;
use App\Features\ContextualValidation\Enums\TicketType;
use App\Features\ContextualValidation\Models\EventRegistration;
use App\Features\ContextualValidation\Models\WorkshopEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EventRegistration> */
class EventRegistrationFactory extends Factory
{
    protected $model = EventRegistration::class;

    public function definition(): array
    {
        return [
            'event_id' => WorkshopEvent::factory(),
            'attendee_id' => User::factory(),
            'ticket_type' => TicketType::Standard,
            'status' => RegistrationStatus::Confirmed,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(['status' => RegistrationStatus::Cancelled]);
    }
}
