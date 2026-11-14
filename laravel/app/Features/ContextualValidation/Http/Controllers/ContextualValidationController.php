<?php

namespace App\Features\ContextualValidation\Http\Controllers;

use App\Features\ContextualValidation\Actions\RegisterForEvent;
use App\Features\ContextualValidation\Enums\EventStatus;
use App\Features\ContextualValidation\Enums\TicketType;
use App\Features\ContextualValidation\Exceptions\RegistrationAlreadyExists;
use App\Features\ContextualValidation\Exceptions\RegistrationCapacityReached;
use App\Features\ContextualValidation\Exceptions\RegistrationEligibilityFailed;
use App\Features\ContextualValidation\Exceptions\RegistrationWindowClosed;
use App\Features\ContextualValidation\Http\Requests\StoreEventRegistrationRequest;
use App\Features\ContextualValidation\Models\EventRegistration;
use App\Features\ContextualValidation\Models\WorkshopEvent;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContextualValidationController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user !== null, 401);

        $events = WorkshopEvent::query()
            ->withCount(['confirmedRegistrations'])
            ->whereIn('status', [EventStatus::Open->value, EventStatus::Closed->value])
            ->orderBy('starts_at_utc')
            ->get();
        $registrations = EventRegistration::query()
            ->where('attendee_id', $user->id)
            ->with('event')
            ->latest()
            ->get();

        return view('labs.contextual-validation.index', compact('events', 'registrations'));
    }

    public function store(StoreEventRegistrationRequest $request, RegisterForEvent $register): RedirectResponse
    {
        try {
            $register->handle(
                event: WorkshopEvent::query()->findOrFail($request->integer('event_id')),
                attendee: $request->user(),
                ticketType: TicketType::from($request->string('ticket_type')->toString()),
                inviteCode: $request->string('invite_code')->toString() ?: null,
                now: CarbonImmutable::now('UTC'),
            );
        } catch (RegistrationAlreadyExists) {
            return $this->registrationError('registration', 'contextual_validation.errors.duplicate');
        } catch (RegistrationCapacityReached) {
            return $this->registrationError('registration', 'contextual_validation.errors.capacity');
        } catch (RegistrationEligibilityFailed) {
            return $this->registrationError('invite_code', 'contextual_validation.errors.ineligible');
        } catch (RegistrationWindowClosed) {
            return $this->registrationError('registration', 'contextual_validation.errors.window_closed');
        }

        return to_route('labs.contextual-validation.index')
            ->with('status', __('contextual_validation.flash.registered'))
            ->withFragment('contextual-validation-practice');
    }

    private function registrationError(string $field, string $messageKey): RedirectResponse
    {
        return back()
            ->withErrors([$field => __($messageKey)])
            ->withInput()
            ->withFragment('contextual-validation-practice');
    }
}
