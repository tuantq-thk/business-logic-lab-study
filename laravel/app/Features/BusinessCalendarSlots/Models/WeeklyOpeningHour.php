<?php

namespace App\Features\BusinessCalendarSlots\Models;

use Database\Factories\BusinessCalendarSlots\WeeklyOpeningHourFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['room_id', 'weekday', 'opens_at_local', 'closes_at_local'])]
class WeeklyOpeningHour extends Model
{
    protected $table = 'business_calendar_weekly_hours';

    /** @use HasFactory<WeeklyOpeningHourFactory> */
    use HasFactory;

    public $timestamps = false;

    protected static function newFactory(): WeeklyOpeningHourFactory
    {
        return WeeklyOpeningHourFactory::new();
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    protected function casts(): array
    {
        return ['weekday' => 'integer'];
    }
}
