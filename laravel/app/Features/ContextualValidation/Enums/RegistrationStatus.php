<?php

namespace App\Features\ContextualValidation\Enums;

enum RegistrationStatus: string
{
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
