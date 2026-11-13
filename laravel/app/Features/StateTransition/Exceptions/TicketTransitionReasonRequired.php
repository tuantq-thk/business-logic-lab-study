<?php

namespace App\Features\StateTransition\Exceptions;

use RuntimeException;

class TicketTransitionReasonRequired extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('state_transition.errors.reason_required'));
    }
}
