<?php

return [
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
        'inventory-capacity' => [
            'key' => 'inventory-capacity',
            'label_key' => 'labs.inventory_capacity.label',
            'description_key' => 'labs.inventory_capacity.description',
            'group_key' => 'labs.inventory_capacity.group',
            'question_key' => 'labs.inventory_capacity.question',
            'implemented' => false,
            'default_enabled' => false,
            'toggleable' => true,
            'route_names' => ['labs.show'],
        ],
    ],
];
