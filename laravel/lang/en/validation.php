<?php

return [
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute field must be a string.',
    'max' => [
        'string' => 'The :attribute field may not be greater than :max characters.',
        'array' => 'The :attribute field may not have more than :max items.',
        'numeric' => 'The :attribute field may not be greater than :max.',
    ],
    'array' => 'The :attribute field must be an array.',
    'size' => [
        'array' => 'The :attribute field must contain exactly :size items.',
    ],
    'integer' => 'The :attribute field must be an integer.',
    'min' => [
        'numeric' => 'The :attribute field must be at least :min.',
        'string' => 'The :attribute field must be at least :min characters.',
    ],
    'between' => [
        'numeric' => 'The :attribute field must be between :min and :max.',
    ],
    'not_in' => 'The selected :attribute is invalid.',
    'boolean' => 'The :attribute field must be true or false.',
    'in' => 'The selected :attribute is invalid.',
    'attributes' => [
        'subject' => 'ticket subject',
        'to_status' => 'new status',
        'version' => 'version',
        'reason' => 'reason',
        'items.*.order_ref' => 'order reference',
        'items.*.quantity' => 'quantity',
        'items.*.ttl_minutes' => 'hold minutes',
        'delta' => 'stock delta',
        'reference' => 'reference',
    ],
];
