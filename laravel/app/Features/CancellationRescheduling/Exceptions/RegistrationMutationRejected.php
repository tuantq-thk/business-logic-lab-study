<?php

namespace App\Features\CancellationRescheduling\Exceptions;

use RuntimeException;

class RegistrationMutationRejected extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
