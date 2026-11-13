<?php

namespace App\Features\StateTransition\Http\Controllers;

use App\Features\StateTransition\Actions\ChangeSupportTicketStatus;
use App\Features\StateTransition\Actions\CreateSupportTicket;
use App\Features\StateTransition\Enums\SupportTicketStatus;
use App\Features\StateTransition\Exceptions\InvalidTicketTransition;
use App\Features\StateTransition\Exceptions\StaleTicket;
use App\Features\StateTransition\Exceptions\TicketTransitionReasonRequired;
use App\Features\StateTransition\Http\Requests\ChangeSupportTicketStatusRequest;
use App\Features\StateTransition\Http\Requests\CreateSupportTicketRequest;
use App\Features\StateTransition\Models\SupportTicket;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StateTransitionController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 401);

        $tickets = SupportTicket::query()
            ->with(['createdBy', 'assignedTo', 'histories.actor'])
            ->visibleTo($user)
            ->latest()
            ->get();
        $statuses = SupportTicketStatus::cases();

        return view('labs.state-transition.index', compact('tickets', 'statuses'));
    }

    public function store(CreateSupportTicketRequest $request, CreateSupportTicket $create): RedirectResponse
    {
        $create->handle(
            actor: $request->user(),
            subject: $request->string('subject')->trim()->toString(),
        );

        return to_route('labs.state-transition.index')->with('status', __('state_transition.flash.ticket_created'));
    }

    public function changeStatus(
        ChangeSupportTicketStatusRequest $request,
        SupportTicket $ticket,
        ChangeSupportTicketStatus $changeStatus,
    ): RedirectResponse|JsonResponse {
        try {
            $changeStatus->handle(
                ticket: $ticket,
                actor: $request->user(),
                toStatus: SupportTicketStatus::from($request->string('to_status')->toString()),
                reason: $request->string('reason')->trim()->toString() ?: null,
                expectedVersion: $request->integer('version'),
            );
        } catch (StaleTicket $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => [
                        'code' => 'ticket_stale',
                        'message' => $exception->getMessage(),
                    ],
                ], 409);
            }

            return back()
                ->withErrors(['transition' => $exception->getMessage()])
                ->with('error_ticket_id', $ticket->id)
                ->setStatusCode(409);
        } catch (TicketTransitionReasonRequired|InvalidTicketTransition $exception) {
            $field = $exception instanceof TicketTransitionReasonRequired ? 'reason' : 'transition';

            return back()
                ->withErrors([$field => $exception->getMessage()])
                ->with('error_ticket_id', $ticket->id);
        }

        return to_route('labs.state-transition.index')->with('status', __('state_transition.flash.status_changed'));
    }
}
