<?php

return [
    'state_transition' => [
        'label' => 'State transition & invariant',
        'description' => 'Learn how to protect valid state changes in a workflow.',
        'group' => 'Workflow',
        'question' => 'What rule prevents a ticket from moving to an invalid state?',
    ],
    'inventory_capacity' => [
        'label' => 'Inventory, reservation & capacity',
        'description' => 'Keep inventory counters and reservation lifecycles correct under concurrent requests.',
        'group' => 'Capacity',
        'question' => 'How do we prevent overselling when requests run concurrently?',
    ],
    'pricing_discounts' => [
        'label' => 'Pricing, discounts & order of application',
        'description' => 'Learn how deterministic money rules choose, round and apply discounts.',
        'group' => 'Pricing',
        'question' => 'How can a quote remain correct when several discount rules apply?',
    ],
    'business_calendar_slots' => [
        'label' => 'Business calendar & slots',
        'description' => 'Turn weekly hours and date overrides into timezone-safe selectable slots.',
        'group' => 'Calendar',
        'question' => 'Which room slots are actually available on a local date?',
    ],
    'booking_overlap' => [
        'label' => 'Booking overlap',
        'description' => 'Book a room safely when requests compete for the same interval.',
        'group' => 'Booking / concurrency',
        'question' => 'Can concurrent requests create overlapping bookings?',
    ],
    'contextual_validation' => [
        'label' => 'Contextual validation',
        'description' => 'Separate malformed input from valid input that fails the current business policy.',
        'group' => 'Validation and eligibility',
        'question' => 'When does valid input still fail the current event policy?',
    ],
    'cancellation_rescheduling' => ['label' => 'Cancellation and rescheduling', 'description' => 'Apply cutoff, capacity, history and compensation rules to course changes.', 'group' => 'Workflow and compensation', 'question' => 'How can a course registration change without releasing a seat twice?'],
];
