<?php

namespace App\Features\CancellationRescheduling\Models;

use App\Features\CancellationRescheduling\Enums\RefundIntentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['registration_id', 'amount_minor', 'currency', 'status', 'idempotency_key'])]
class RefundIntent extends Model
{
    protected $table = 'cancellation_refund_intents';

    public function registration(): BelongsTo
    {
        return $this->belongsTo(CourseRegistration::class, 'registration_id');
    }

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'status' => RefundIntentStatus::class,
        ];
    }
}
