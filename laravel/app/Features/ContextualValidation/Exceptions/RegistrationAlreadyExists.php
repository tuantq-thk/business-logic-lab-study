<?php

namespace App\Features\ContextualValidation\Exceptions;

use RuntimeException;

final class RegistrationAlreadyExists extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This attendee is already registered for the event.');
    }
}
