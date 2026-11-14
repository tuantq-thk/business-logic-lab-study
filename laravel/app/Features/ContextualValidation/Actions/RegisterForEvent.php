<?php

namespace App\Features\ContextualValidation\Actions;

use App\Features\ContextualValidation\Domain\RegistrationPolicy;
use App\Features\ContextualValidation\Enums\RegistrationStatus;
use App\Features\ContextualValidation\Enums\TicketType;
use App\Features\ContextualValidation\Exceptions\RegistrationAlreadyExists;
use App\Features\ContextualValidation\Models\EventRegistration;
use App\Features\ContextualValidation\Models\WorkshopEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class RegisterForEvent
{
    public function __construct(private readonly RegistrationPolicy $policy) {}

    public function handle(
        WorkshopEvent $event,
        User $attendee,
        TicketType $ticketType,
        ?string $inviteCode,
        CarbonImmutable $now,
    ): EventRegistration {
        return DB::transaction(function () use ($event, $attendee, $ticketType, $inviteCode, $now): EventRegistration {
            $lockedEvent = WorkshopEvent::query()->whereKey($event->getKey())->lockForUpdate()->firstOrFail();

            if (EventRegistration::query()->where('event_id', $lockedEvent->id)->where('attendee_id', $attendee->id)->exists()) {
                throw new RegistrationAlreadyExists;
            }

            $confirmedCount = $lockedEvent->confirmedRegistrations()->count();
            $inviteCodeMatches = $ticketType === TicketType::Invite
                && $lockedEvent->invite_code_hash !== null
                && $inviteCode !== null
                && Hash::check($inviteCode, $lockedEvent->invite_code_hash);

            $this->policy->assertEligible($lockedEvent, $ticketType, $now, $confirmedCount, $inviteCodeMatches);

            $registration = new EventRegistration;
            $registration->forceFill([
                'event_id' => $lockedEvent->id,
                'attendee_id' => $attendee->id,
                'ticket_type' => $ticketType,
                'status' => RegistrationStatus::Confirmed,
            ])->saveOrFail();

            return $registration;
        });
    }
}
