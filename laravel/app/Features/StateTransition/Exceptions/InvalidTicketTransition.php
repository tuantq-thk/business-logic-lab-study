<?php

namespace App\Features\StateTransition\Exceptions;

use App\Features\StateTransition\Enums\SupportTicketStatus;
use RuntimeException;

class InvalidTicketTransition extends RuntimeException
{
    public function __construct(SupportTicketStatus $from, SupportTicketStatus $to)
    {
        parent::__construct(__('state_transition.errors.invalid_transition', [
            'from' => $from->label(),
            'to' => $to->label(),
        ]));
    }
}
