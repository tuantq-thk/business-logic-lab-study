<?php

namespace App\Features\ContextualValidation\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
}
