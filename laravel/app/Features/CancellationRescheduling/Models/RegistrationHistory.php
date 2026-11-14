<?php

namespace App\Features\CancellationRescheduling\Models;

use App\Features\CancellationRescheduling\Enums\RegistrationHistoryAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['registration_id', 'action', 'from_course_id', 'to_course_id', 'from_starts_at_utc', 'to_starts_at_utc', 'actor_id', 'reason', 'policy_version'])]
class RegistrationHistory extends Model
{
    public const POLICY_VERSION = '2026-11-14.v1';

    public $timestamps = false;

    protected $table = 'cancellation_registration_histories';

    public function registration(): BelongsTo
    {
        return $this->belongsTo(CourseRegistration::class, 'registration_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'action' => RegistrationHistoryAction::class,
            'from_starts_at_utc' => 'immutable_datetime',
            'to_starts_at_utc' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
