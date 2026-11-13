<?php

return [
    'page' => [
        'title' => 'BL-01 · Ticket lifecycle',
        'eyebrow' => 'BL-01 / state transition',
        'heading' => 'Track support ticket progress',
        'description' => 'Every ticket follows a controlled flow. The backend checks the actor, valid transition, current version and records history in the same transaction.',
        'back' => '← Back to catalog',
    ],
    'flow' => [
        'aria_label' => 'Ticket processing flow',
        'request' => [
            'label' => '01 · Request',
            'title' => 'Actor submits a request',
            'description' => 'An admin or ticket owner selects a new status and submits the version they can see.',
        ],
        'decision' => [
            'label' => '02 · Decision',
            'title' => 'The backend checks the rules',
            'description' => 'The policy, transition table and stale version are checked against the latest state.',
        ],
        'commit' => [
            'label' => '03 · Commit',
            'title' => 'State and history commit together',
            'description' => 'The ticket update and history insert share one transaction; failures roll back both.',
        ],
    ],
    'create' => [
        'eyebrow' => 'Scenario setup',
        'title' => 'Create a new ticket',
        'description' => 'A new ticket always starts in Open.',
        'subject_label' => 'Ticket subject',
        'subject_placeholder' => 'Example: Confirmation email was not received',
        'submit' => 'Create ticket',
    ],
    'tickets' => [
        'eyebrow' => 'Progress and history',
        'title' => 'Tickets',
        'count' => '{0} No tickets|{1} :count ticket|[2,*] :count tickets',
        'empty' => 'No tickets yet. Create the first ticket to start the demo.',
        'meta' => 'ticket #:id · version :version',
        'created_by' => 'Created by :name',
        'assigned_to' => ' · assigned to :name',
    ],
    'progress' => [
        'label' => 'Progress',
        'step' => 'Step :current/:total',
        'aria_label' => 'Ticket progress',
    ],
    'history' => [
        'title' => 'Transition history',
        'created' => 'Created',
    ],
    'action' => [
        'eyebrow' => 'Allowed action',
        'status_label' => 'Change status',
        'reason_label' => 'Reason',
        'reason_required' => '(required when reopening)',
        'reason_placeholder' => 'Why should this ticket change status?',
        'helper' => 'The server will re-check the actor, valid transition and version before saving.',
        'submit' => 'Save change',
    ],
    'status' => [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],
    'flash' => [
        'ticket_created' => 'The ticket was created in Open status.',
        'status_changed' => 'The ticket status was updated.',
    ],
    'errors' => [
        'invalid_transition' => 'The ticket cannot move from [:from] to [:to].',
        'stale_ticket' => 'The ticket changed before the update was saved. Reload the page and try again.',
        'reason_required' => 'Please enter a reason when reopening a closed ticket.',
    ],
];
