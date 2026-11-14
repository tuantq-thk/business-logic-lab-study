<?php

namespace App\Features\ContextualValidation\Models;

use App\Features\ContextualValidation\Enums\RegistrationStatus;
use App\Features\ContextualValidation\Enums\TicketType;
use App\Models\User;
use Database\Factories\ContextualValidation\EventRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'attendee_id', 'ticket_type'])]
class EventRegistration extends Model
{
    /** @use HasFactory<EventRegistrationFactory> */
    use HasFactory;

    protected $table = 'contextual_validation_registrations';

    protected static function newFactory(): EventRegistrationFactory
    {
        return EventRegistrationFactory::new();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(WorkshopEvent::class, 'event_id');
    }

    public function attendee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendee_id');
    }

    protected function casts(): array
    {
        return [
            'ticket_type' => TicketType::class,
            'status' => RegistrationStatus::class,
        ];
    }
}
