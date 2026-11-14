<?php

namespace App\Features\CancellationRescheduling\Actions;

use App\Features\CancellationRescheduling\Domain\CourseRegistrationMutationPolicy;
use App\Features\CancellationRescheduling\Enums\CourseStatus;
use App\Features\CancellationRescheduling\Enums\RegistrationHistoryAction;
use App\Features\CancellationRescheduling\Exceptions\RegistrationMutationRejected;
use App\Features\CancellationRescheduling\Models\Course;
use App\Features\CancellationRescheduling\Models\CourseRegistration;
use App\Features\CancellationRescheduling\Models\RegistrationHistory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class RescheduleCourseRegistration
{
    public function __construct(private readonly CourseRegistrationMutationPolicy $policy) {}

    public function handle(CourseRegistration $registration, Course $target, User $actor, ?string $reason): void
    {
        $expectedSourceId = (int) $registration->course_id;
        $courseIds = [$expectedSourceId, (int) $target->getKey()];
        sort($courseIds, SORT_NUMERIC);

        DB::transaction(function () use ($registration, $target, $actor, $reason, $expectedSourceId, $courseIds): void {
            $lockedCourses = Course::query()
                ->whereIn('id', $courseIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $source = $lockedCourses->get($expectedSourceId);
            $destination = $lockedCourses->get((int) $target->getKey());

            if ($source === null || $destination === null) {
                throw new RegistrationMutationRejected('target_unavailable');
            }

            $current = CourseRegistration::query()->whereKey($registration->getKey())->lockForUpdate()->firstOrFail();

            if ((int) $current->course_id !== $expectedSourceId) {
                throw new RegistrationMutationRejected('stale_registration');
            }

            $now = CarbonImmutable::now('UTC');
            $this->policy->assertCanReschedule($current, $actor, $source, $now);

            if ($source->is($destination)) {
                throw new RegistrationMutationRejected('same_course');
            }

            if (
                $destination->status !== CourseStatus::Open
                || $destination->starts_at_utc->lessThanOrEqualTo($now)
                || $destination->cancel_cutoff_at_utc->lessThanOrEqualTo($now)
            ) {
                throw new RegistrationMutationRejected('target_unavailable');
            }

            if ($destination->confirmedRegistrations()->count() >= $destination->capacity) {
                throw new RegistrationMutationRejected('target_full');
            }

            if ((int) $destination->price_minor !== $current->paid_amount_minor || $destination->currency !== $current->currency) {
                throw new RegistrationMutationRejected('price_mismatch');
            }

            if (CourseRegistration::query()->where('course_id', $destination->id)->where('attendee_id', $current->attendee_id)->exists()) {
                throw new RegistrationMutationRejected('already_registered');
            }

            $current->forceFill(['course_id' => $destination->id])->saveOrFail();
            RegistrationHistory::query()->create([
                'registration_id' => $current->id,
                'action' => RegistrationHistoryAction::Rescheduled,
                'from_course_id' => $source->id,
                'to_course_id' => $destination->id,
                'from_starts_at_utc' => $source->starts_at_utc,
                'to_starts_at_utc' => $destination->starts_at_utc,
                'actor_id' => $actor->id,
                'reason' => $reason,
                'policy_version' => RegistrationHistory::POLICY_VERSION,
            ]);

        }, 3);
    }
}
