<?php

namespace App\Features\CancellationRescheduling\Enums;

enum RefundIntentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
