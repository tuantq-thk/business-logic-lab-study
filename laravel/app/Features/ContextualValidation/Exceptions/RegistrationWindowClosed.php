<?php

namespace App\Features\ContextualValidation\Exceptions;

use RuntimeException;

final class RegistrationWindowClosed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Registration is not open for this event.');
    }
}
