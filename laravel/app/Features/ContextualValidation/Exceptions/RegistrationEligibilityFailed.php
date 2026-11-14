<?php

namespace App\Features\ContextualValidation\Exceptions;

use RuntimeException;

final class RegistrationEligibilityFailed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The selected ticket is not available for this attendee.');
    }
}
