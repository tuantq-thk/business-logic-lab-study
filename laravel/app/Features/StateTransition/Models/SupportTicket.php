<?php

namespace App\Features\StateTransition\Models;

use App\Features\StateTransition\Enums\SupportTicketStatus;
use App\Models\User;
use Database\Factories\StateTransition\SupportTicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subject', 'created_by'])]
class SupportTicket extends Model
{
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory;

    protected static function newFactory(): SupportTicketFactory
    {
        return SupportTicketFactory::new();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class, 'ticket_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->when(
            ! $user->isAdmin(),
            fn (Builder $query): Builder => $query->where(function (Builder $query) use ($user): void {
                $query->where('created_by', $user->id)
                    ->orWhere('assigned_to', $user->id);
            }),
        );
    }

    protected function casts(): array
    {
        return [
            'status' => SupportTicketStatus::class,
            'resolved_at' => 'datetime',
            'version' => 'integer',
        ];
    }
}
