<?php

namespace App\Features\CancellationRescheduling\Actions;

use App\Features\CancellationRescheduling\Domain\CourseRegistrationMutationPolicy;
use App\Features\CancellationRescheduling\Enums\CourseRegistrationStatus;
use App\Features\CancellationRescheduling\Enums\RefundIntentStatus;
use App\Features\CancellationRescheduling\Enums\RegistrationHistoryAction;
use App\Features\CancellationRescheduling\Exceptions\RegistrationMutationRejected;
use App\Features\CancellationRescheduling\Models\Course;
use App\Features\CancellationRescheduling\Models\CourseRegistration;
use App\Features\CancellationRescheduling\Models\RefundIntent;
use App\Features\CancellationRescheduling\Models\RegistrationHistory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class CancelCourseRegistration
{
    public function __construct(private readonly CourseRegistrationMutationPolicy $policy) {}

    public function handle(CourseRegistration $registration, User $actor, ?string $reason): void
    {
        $expectedCourseId = (int) $registration->course_id;

        DB::transaction(function () use ($registration, $actor, $reason, $expectedCourseId): void {
            $course = Course::query()->whereKey($expectedCourseId)->lockForUpdate()->firstOrFail();
            $current = CourseRegistration::query()->whereKey($registration->getKey())->lockForUpdate()->firstOrFail();

            if ((int) $current->course_id !== $expectedCourseId) {
                throw new RegistrationMutationRejected('stale_registration');
            }

            $this->policy->assertCanCancel($current, $actor, $course, CarbonImmutable::now('UTC'), $reason);

            $current->forceFill(['status' => CourseRegistrationStatus::Cancelled])->saveOrFail();
            RegistrationHistory::query()->create([
                'registration_id' => $current->id,
                'action' => RegistrationHistoryAction::Cancelled,
                'from_course_id' => $course->id,
                'from_starts_at_utc' => $course->starts_at_utc,
                'actor_id' => $actor->id,
                'reason' => $reason,
                'policy_version' => RegistrationHistory::POLICY_VERSION,
            ]);

            if ($current->paid_amount_minor > 0) {
                RefundIntent::query()->create([
                    'registration_id' => $current->id,
                    'amount_minor' => $current->paid_amount_minor,
                    'currency' => $current->currency,
                    'status' => RefundIntentStatus::Pending,
                    'idempotency_key' => 'course-registration-'.$current->id.'-refund-v1',
                ]);
            }

        }, 3);
    }
}
