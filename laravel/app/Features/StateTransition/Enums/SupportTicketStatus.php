<?php

namespace App\Features\StateTransition\Enums;

enum SupportTicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return __("state_transition.status.{$this->value}");
    }
}
