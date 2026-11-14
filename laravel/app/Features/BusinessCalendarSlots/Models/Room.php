<?php

namespace App\Features\BusinessCalendarSlots\Models;

use Database\Factories\BusinessCalendarSlots\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name_key', 'timezone', 'active'])]
class Room extends Model
{
    protected $table = 'business_calendar_rooms';

    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected static function newFactory(): RoomFactory
    {
        return RoomFactory::new();
    }

    public function weeklyOpeningHours(): HasMany
    {
        return $this->hasMany(WeeklyOpeningHour::class)->orderBy('weekday')->orderBy('opens_at_local');
    }

    public function calendarOverrides(): HasMany
    {
        return $this->hasMany(CalendarOverride::class);
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
