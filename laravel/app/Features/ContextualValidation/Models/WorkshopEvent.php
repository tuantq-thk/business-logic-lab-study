<?php

namespace App\Features\ContextualValidation\Models;

use App\Features\ContextualValidation\Enums\EventStatus;
use App\Features\ContextualValidation\Enums\RegistrationStatus;
use Database\Factories\ContextualValidation\WorkshopEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'starts_at_utc', 'registration_opens_at_utc', 'registration_closes_at_utc', 'capacity'])]
class WorkshopEvent extends Model
{
    /** @use HasFactory<WorkshopEventFactory> */
    use HasFactory;

    protected $table = 'contextual_validation_events';

    protected static function newFactory(): WorkshopEventFactory
    {
        return WorkshopEventFactory::new();
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class, 'event_id');
    }

    public function confirmedRegistrations(): HasMany
    {
        return $this->registrations()->where('status', RegistrationStatus::Confirmed->value);
    }

    protected function casts(): array
    {
        return [
            'starts_at_utc' => 'immutable_datetime',
            'registration_opens_at_utc' => 'immutable_datetime',
            'registration_closes_at_utc' => 'immutable_datetime',
            'capacity' => 'integer',
            'status' => EventStatus::class,
        ];
    }
}
