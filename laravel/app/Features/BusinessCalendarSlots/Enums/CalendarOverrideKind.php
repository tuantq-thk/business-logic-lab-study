<?php

namespace App\Features\BusinessCalendarSlots\Enums;

enum CalendarOverrideKind: string
{
    case Closed = 'closed';
    case Open = 'open';
}
