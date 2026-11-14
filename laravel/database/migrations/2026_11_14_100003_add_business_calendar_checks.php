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

        DB::statement('ALTER TABLE business_calendar_rooms ADD CONSTRAINT chk_business_calendar_timezone CHECK (timezone <> \'\')');
        DB::statement('ALTER TABLE business_calendar_weekly_hours ADD CONSTRAINT chk_business_calendar_weekday CHECK (weekday BETWEEN 0 AND 6)');
        DB::statement('ALTER TABLE business_calendar_weekly_hours ADD CONSTRAINT chk_business_calendar_weekly_window CHECK (opens_at_local < closes_at_local)');
        DB::statement("ALTER TABLE business_calendar_overrides ADD CONSTRAINT chk_business_calendar_override_kind CHECK (kind IN ('closed', 'open'))");
        DB::statement("ALTER TABLE business_calendar_overrides ADD CONSTRAINT chk_business_calendar_override_window CHECK ((kind = 'closed' AND opens_at_local IS NULL AND closes_at_local IS NULL) OR (kind = 'open' AND opens_at_local IS NOT NULL AND closes_at_local IS NOT NULL AND opens_at_local < closes_at_local))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE business_calendar_overrides DROP CHECK chk_business_calendar_override_window');
        DB::statement('ALTER TABLE business_calendar_overrides DROP CHECK chk_business_calendar_override_kind');
        DB::statement('ALTER TABLE business_calendar_weekly_hours DROP CHECK chk_business_calendar_weekly_window');
        DB::statement('ALTER TABLE business_calendar_weekly_hours DROP CHECK chk_business_calendar_weekday');
        DB::statement('ALTER TABLE business_calendar_rooms DROP CHECK chk_business_calendar_timezone');
    }
};
