<?php

return [
    'page' => [
        'title' => 'BL-04 · Business calendar & slots',
        'back' => 'Back to lab library',
        'eyebrow' => 'BL-04 / business calendar',
        'heading' => 'Turn local opening rules into selectable slots',
        'description' => 'Weekly hours and date overrides are evaluated in the room timezone. Booking overlap belongs to BL-05.',
        'loading' => 'Calculating…',
    ],
    'form' => [
        'title' => 'Availability settings',
        'description' => 'Choose a room and local time window to calculate selectable slots.',
        'room' => 'Room',
        'date' => 'Local date',
        'duration' => 'Duration (minutes)',
        'step' => 'Step (minutes)',
        'submit' => 'Generate slots',
        'timezone' => 'Room timezone',
        'no_rooms' => 'No active rooms are configured yet.',
        'date_hint' => 'Use the room’s local calendar date.',
        'duration_hint' => 'Allowed range: 15–480 minutes.',
        'step_hint' => 'New start times are generated every 5–120 minutes.',
    ],
    'slots' => [
        'eyebrow' => 'Availability result',
        'title' => 'Selectable slots',
        'empty' => 'No slot matches this date, window, duration and current time.',
        'start' => 'Start',
        'end' => 'End',
        'count' => ':count slots',
        'local' => 'Local time',
    ],
    'rules' => [
        'title' => 'How it works',
        'weekly' => 'Weekly hours provide the default windows.',
        'override' => 'A date override replaces the weekly schedule for that room.',
        'containment' => 'A slot must fit completely inside one open window.',
        'past' => 'Slots that already started are excluded using the injected clock.',
    ],
    'errors' => [
        'form_heading' => 'Please review the highlighted fields.',
        'invalid_timezone' => 'The room timezone is invalid.',
        'invalid_slot_parameters' => 'Duration and step must be positive.',
        'invalid_window' => 'The calendar contains an invalid opening window.',
        'invalid_local_time' => 'The local time is invalid for this timezone.',
        'generation_failed' => 'The calendar could not be converted into selectable slots. Please check the room schedule.',
    ],
    'validation' => [
        'room_unavailable' => 'The selected room is unavailable.',
        'date_format' => 'The date must use the YYYY-MM-DD format.',
        'duration_integer' => 'Duration must be a whole number of minutes.',
        'duration_range' => 'Duration must be between 15 and 480 minutes.',
        'step_integer' => 'Step must be a whole number of minutes.',
        'step_range' => 'Step must be between 5 and 120 minutes.',
    ],
    'demo' => [
        'rooms' => [
            'boardroom' => 'Boardroom 01',
            'default' => 'Demo room',
        ],
    ],
];
