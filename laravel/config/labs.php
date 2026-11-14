<?php

return [
    'inventory_capacity' => [
        'max_items' => 100,
    ],
    'registry' => [
        'state-transition' => [
            'key' => 'state-transition',
            'label_key' => 'labs.state_transition.label',
            'description_key' => 'labs.state_transition.description',
            'group_key' => 'labs.state_transition.group',
            'question_key' => 'labs.state_transition.question',
            'implemented' => true,
            'default_enabled' => true,
            'toggleable' => true,
            'route_names' => ['labs.state-transition.index'],
        ],
        'pricing-discounts' => [
            'key' => 'pricing-discounts',
            'label_key' => 'labs.pricing_discounts.label',
            'description_key' => 'labs.pricing_discounts.description',
            'group_key' => 'labs.pricing_discounts.group',
            'question_key' => 'labs.pricing_discounts.question',
            'implemented' => true,
            'default_enabled' => true,
            'toggleable' => true,
            'route_names' => ['labs.pricing-discounts.index'],
        ],
        'inventory-capacity' => [
            'key' => 'inventory-capacity',
            'label_key' => 'labs.inventory_capacity.label',
            'description_key' => 'labs.inventory_capacity.description',
            'group_key' => 'labs.inventory_capacity.group',
            'question_key' => 'labs.inventory_capacity.question',
            'implemented' => true,
            'default_enabled' => true,
            'toggleable' => true,
            'route_names' => ['labs.inventory-capacity.index'],
        ],
    ],
];
