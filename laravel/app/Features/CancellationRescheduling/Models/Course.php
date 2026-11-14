<?php

namespace App\Features\CancellationRescheduling\Models;

use App\Features\CancellationRescheduling\Enums\CourseStatus;
use Database\Factories\CancellationRescheduling\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'starts_at_utc', 'cancel_cutoff_at_utc', 'capacity', 'price_minor', 'currency', 'status'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $table = 'cancellation_courses';

    protected static function newFactory(): CourseFactory
    {
        return CourseFactory::new();
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CourseRegistration::class);
    }

    public function confirmedRegistrations(): HasMany
    {
        return $this->registrations()->where('status', 'confirmed');
    }

    protected function casts(): array
    {
        return [
            'starts_at_utc' => 'immutable_datetime',
            'cancel_cutoff_at_utc' => 'immutable_datetime',
            'capacity' => 'integer',
            'price_minor' => 'integer',
            'status' => CourseStatus::class,
        ];
    }
}
