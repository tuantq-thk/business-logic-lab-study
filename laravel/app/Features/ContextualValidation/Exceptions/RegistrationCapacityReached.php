<?php

namespace App\Features\ContextualValidation\Exceptions;

use RuntimeException;

final class RegistrationCapacityReached extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The event has reached its registration capacity.');
    }
}
