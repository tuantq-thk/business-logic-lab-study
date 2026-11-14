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
];
