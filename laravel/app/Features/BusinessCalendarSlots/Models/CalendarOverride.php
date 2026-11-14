<?php

namespace App\Features\BusinessCalendarSlots\Models;

use App\Features\BusinessCalendarSlots\Enums\CalendarOverrideKind;
use Database\Factories\BusinessCalendarSlots\CalendarOverrideFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['room_id', 'local_date', 'kind', 'opens_at_local', 'closes_at_local', 'reason'])]
class CalendarOverride extends Model
{
    protected $table = 'business_calendar_overrides';

    /** @use HasFactory<CalendarOverrideFactory> */
    use HasFactory;

    public $timestamps = false;

    protected static function newFactory(): CalendarOverrideFactory
    {
        return CalendarOverrideFactory::new();
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    protected function casts(): array
    {
        return [
            'local_date' => 'date:Y-m-d',
            'kind' => CalendarOverrideKind::class,
        ];
    }
}
