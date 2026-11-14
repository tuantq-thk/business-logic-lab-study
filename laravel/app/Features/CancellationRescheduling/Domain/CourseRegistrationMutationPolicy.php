<?php

namespace App\Features\CancellationRescheduling\Domain;

use App\Features\CancellationRescheduling\Enums\CourseRegistrationStatus;
use App\Features\CancellationRescheduling\Exceptions\RegistrationMutationRejected;
use App\Features\CancellationRescheduling\Models\Course;
use App\Features\CancellationRescheduling\Models\CourseRegistration;
use App\Models\User;
use Carbon\CarbonImmutable;

final class CourseRegistrationMutationPolicy
{
    public function assertCanCancel(CourseRegistration $registration, User $actor, Course $course, CarbonImmutable $now, ?string $reason): void
    {
        $this->assertConfirmed($registration);

        $isOwner = (int) $actor->getKey() === (int) $registration->attendee_id;

        if (! $isOwner && ! $actor->isAdmin()) {
            throw new RegistrationMutationRejected('forbidden');
        }

        if ($now->lessThan($course->cancel_cutoff_at_utc)) {
            return;
        }

        if (! $actor->isAdmin() || $isOwner) {
            throw new RegistrationMutationRejected('cutoff_passed');
        }

        if (trim((string) $reason) === '') {
            throw new RegistrationMutationRejected('override_reason_required');
        }
    }

    public function assertCanReschedule(CourseRegistration $registration, User $actor, Course $source, CarbonImmutable $now): void
    {
        $this->assertConfirmed($registration);

        if ((int) $actor->getKey() !== (int) $registration->attendee_id) {
            throw new RegistrationMutationRejected('forbidden');
        }

        if ($now->greaterThanOrEqualTo($source->cancel_cutoff_at_utc)) {
            throw new RegistrationMutationRejected('cutoff_passed');
        }
    }

    private function assertConfirmed(CourseRegistration $registration): void
    {
        if ($registration->status !== CourseRegistrationStatus::Confirmed) {
            throw new RegistrationMutationRejected('not_confirmed');
        }
    }
}
