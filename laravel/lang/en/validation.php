<?php

return [
    'required' => 'The :attribute field is required.',
    'string' => 'The :attribute field must be a string.',
    'max' => [
        'string' => 'The :attribute field may not be greater than :max characters.',
    ],
    'integer' => 'The :attribute field must be an integer.',
    'min' => [
        'numeric' => 'The :attribute field must be at least :min.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'in' => 'The selected :attribute is invalid.',
    'attributes' => [
        'subject' => 'ticket subject',
        'to_status' => 'new status',
        'version' => 'version',
        'reason' => 'reason',
    ],
];
