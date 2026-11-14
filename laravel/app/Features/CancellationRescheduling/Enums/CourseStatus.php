<?php

namespace App\Features\CancellationRescheduling\Enums;

enum CourseStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
