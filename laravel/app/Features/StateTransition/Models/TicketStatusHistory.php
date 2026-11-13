<?php

namespace App\Features\StateTransition\Models;

use App\Features\StateTransition\Enums\SupportTicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['ticket_id', 'from_status', 'to_status', 'actor_id', 'reason'])]
class TicketStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Ticket status history is append-only.');
        });

        static::deleting(function (): never {
            throw new LogicException('Ticket status history cannot be deleted.');
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'from_status' => SupportTicketStatus::class,
            'to_status' => SupportTicketStatus::class,
            'created_at' => 'datetime',
        ];
    }
}
