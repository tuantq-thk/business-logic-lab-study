<?php

namespace App\Features\CancellationRescheduling\Http\Controllers;

use App\Features\CancellationRescheduling\Actions\CancelCourseRegistration;
use App\Features\CancellationRescheduling\Actions\RescheduleCourseRegistration;
use App\Features\CancellationRescheduling\Enums\CourseStatus;
use App\Features\CancellationRescheduling\Exceptions\RegistrationMutationRejected;
use App\Features\CancellationRescheduling\Http\Requests\MutateCourseRegistrationRequest;
use App\Features\CancellationRescheduling\Models\Course;
use App\Features\CancellationRescheduling\Models\CourseRegistration;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CancellationReschedulingController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 401);
        $now = CarbonImmutable::now('UTC');

        $courses = Course::query()
            ->withCount(['confirmedRegistrations'])
            ->where('status', CourseStatus::Open->value)
            ->where('cancel_cutoff_at_utc', '>', $now)
            ->where('starts_at_utc', '>', $now)
            ->orderBy('starts_at_utc')
            ->get();

        $registrations = CourseRegistration::query()
            ->when(! $user->isAdmin(), fn ($query) => $query->where('attendee_id', $user->id))
            ->with(['course', 'histories', 'refundIntent'])
            ->when($user->isAdmin(), fn ($query) => $query->with('attendee:id,name'))
            ->latest()
            ->get();

        return view('labs.cancellation-rescheduling.index', compact('courses', 'registrations'));
    }

    public function mutate(
        MutateCourseRegistrationRequest $request,
        CancelCourseRegistration $cancel,
        RescheduleCourseRegistration $reschedule,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        $registration = CourseRegistration::query()
            ->when(! $actor->isAdmin(), fn ($query) => $query->where('attendee_id', $actor->getKey()))
            ->findOrFail($request->integer('registration_id'));
        $reason = $request->string('reason')->toString() ?: null;
        $mutation = $request->string('action')->toString();

        try {
            if ($mutation === 'cancel') {
                $cancel->handle($registration, $actor, $reason);
            } else {
                $reschedule->handle(
                    registration: $registration,
                    target: Course::query()->findOrFail($request->integer('target_course_id')),
                    actor: $actor,
                    reason: $reason,
                );
            }
        } catch (RegistrationMutationRejected $exception) {
            return back()
                ->withErrors(['registration' => __('cancellation_rescheduling.errors.'.$exception->reason)])
                ->withInput()
                ->withFragment('cancellation-rescheduling-practice');
        }

        return to_route('labs.cancellation-rescheduling.index')
            ->with('status', __('cancellation_rescheduling.flash.'.$mutation.'_success'))
            ->withFragment('cancellation-rescheduling-practice');
    }
}
