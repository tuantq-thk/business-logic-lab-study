<?php

return [
    'brand' => 'Business Logic Lab',
    'nav' => [
        'greeting' => 'Hello, :name',
        'dashboard' => 'Open learning shell',
        'continue' => 'Continue learning',
        'logout' => 'Log out',
        'login' => 'Log in',
        'register' => 'Create account',
    ],
    'hero' => [
        'eyebrow' => 'technical notebook / learning shell',
        'title' => 'Do not just learn code. Learn how to decide.',
        'description' => 'Business Logic Lab helps you see the rules behind a reliable system — from business questions to evidence in code and tests.',
        'principles' => 'View principles',
    ],
    'lab_index' => [
        'label' => 'lab index',
        'status' => 'building',
        'question_label' => '01 / question',
        'scenario_label' => '02 / scenario',
        'evidence_label' => '03 / evidence',
        'question' => 'What rule truly protects the invariant?',
        'scenario' => 'What happens at the boundary and during a conflict?',
        'evidence' => 'Tests, migrations and source links — no invented evidence.',
    ],
    'principles' => [
        'question' => ['title' => 'Question first', 'text' => 'Start from the problem, not from a domain menu.'],
        'state' => ['title' => 'Clear states', 'text' => 'Planned, implemented and evidence-backed are not the same thing.'],
        'server' => ['title' => 'The server is the source of truth', 'text' => 'The UI explains the logic; the backend still validates every mutation.'],
    ],
];
