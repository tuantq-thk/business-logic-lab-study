<?php

namespace App\Features\ContextualValidation\Enums;

enum TicketType: string
{
    case Standard = 'standard';
    case Invite = 'invite';
}
