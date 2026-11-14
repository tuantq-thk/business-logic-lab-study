<?php

namespace App\Features\ContextualValidation\Domain;

use App\Features\ContextualValidation\Enums\EventStatus;
use App\Features\ContextualValidation\Enums\TicketType;
use App\Features\ContextualValidation\Exceptions\RegistrationCapacityReached;
use App\Features\ContextualValidation\Exceptions\RegistrationEligibilityFailed;
use App\Features\ContextualValidation\Exceptions\RegistrationWindowClosed;
use App\Features\ContextualValidation\Models\WorkshopEvent;
use Carbon\CarbonImmutable;

final class RegistrationPolicy
{
    public function assertEligible(
        WorkshopEvent $event,
        TicketType $ticketType,
        CarbonImmutable $now,
        int $confirmedRegistrations,
        bool $inviteCodeMatches,
    ): void {
        if ($event->status !== EventStatus::Open
            || $now->lt($event->registration_opens_at_utc)
            || ! $now->lt($event->registration_closes_at_utc)) {
            throw new RegistrationWindowClosed;
        }

        if ($confirmedRegistrations >= $event->capacity) {
            throw new RegistrationCapacityReached;
        }

        $eventRequiresInvite = $event->invite_code_hash !== null;

        if (($eventRequiresInvite && $ticketType !== TicketType::Invite)
            || ($ticketType === TicketType::Invite && ! $inviteCodeMatches)) {
            throw new RegistrationEligibilityFailed;
        }
    }
}
