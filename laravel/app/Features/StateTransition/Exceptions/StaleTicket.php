<?php

namespace App\Features\StateTransition\Exceptions;

use RuntimeException;

class StaleTicket extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('state_transition.errors.stale_ticket'));
    }
}
