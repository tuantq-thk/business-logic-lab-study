<?php

namespace App\Features\CancellationRescheduling\Models;

use App\Features\CancellationRescheduling\Enums\CourseRegistrationStatus;
use App\Models\User;
use Database\Factories\CancellationRescheduling\CourseRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['course_id', 'attendee_id', 'paid_amount_minor', 'currency'])]
class CourseRegistration extends Model
{
    /** @use HasFactory<CourseRegistrationFactory> */
    use HasFactory;

    protected $table = 'cancellation_course_registrations';

    protected static function newFactory(): CourseRegistrationFactory
    {
        return CourseRegistrationFactory::new();
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function attendee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendee_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RegistrationHistory::class, 'registration_id')->latest('id');
    }

    public function refundIntent(): HasOne
    {
        return $this->hasOne(RefundIntent::class, 'registration_id');
    }

    protected function casts(): array
    {
        return [
            'status' => CourseRegistrationStatus::class,
            'paid_amount_minor' => 'integer',
        ];
    }
}
