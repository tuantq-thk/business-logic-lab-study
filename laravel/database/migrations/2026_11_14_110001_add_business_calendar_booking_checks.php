<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE business_calendar_bookings ADD CONSTRAINT chk_business_calendar_booking_interval CHECK (ends_at_utc > starts_at_utc)');
        DB::statement("ALTER TABLE business_calendar_bookings ADD CONSTRAINT chk_business_calendar_booking_status CHECK (status IN ('held', 'confirmed', 'cancelled'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE business_calendar_bookings DROP CHECK chk_business_calendar_booking_status');
        DB::statement('ALTER TABLE business_calendar_bookings DROP CHECK chk_business_calendar_booking_interval');
    }
};
