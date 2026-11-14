<?php

namespace App\Features\CancellationRescheduling\Enums;

enum RegistrationHistoryAction: string
{
    case Cancelled = 'cancelled';
    case Rescheduled = 'rescheduled';
}
