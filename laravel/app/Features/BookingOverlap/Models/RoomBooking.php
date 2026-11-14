<?php

namespace App\Features\BookingOverlap\Models;

use App\Features\BookingOverlap\Enums\BookingStatus;
use App\Features\BusinessCalendarSlots\Models\Room;
use App\Models\User;
use DatabaseFactories\BookingOverlap\RoomBookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['room_id', 'user_id', 'starts_at_utc', 'ends_at_utc', 'status', 'idempotency_key'])]
class RoomBooking extends Model
{
    /** @use HasFactory<RoomBookingFactory> */
    use HasFactory;

    protected $table = 'business_calendar_bookings';

    protected static function newFactory(): RoomBookingFactory
    {
        return RoomBookingFactory::new();
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'starts_at_utc' => 'immutable_datetime',
            'ends_at_utc' => 'immutable_datetime',
            'status' => BookingStatus::class,
        ];
    }
}
