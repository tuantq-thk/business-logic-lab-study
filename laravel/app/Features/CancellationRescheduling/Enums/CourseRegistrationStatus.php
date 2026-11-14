<?php

namespace App\Features\CancellationRescheduling\Enums;

enum CourseRegistrationStatus: string
{
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
