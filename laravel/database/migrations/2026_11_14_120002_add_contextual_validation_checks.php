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

        DB::statement('ALTER TABLE contextual_validation_events ADD CONSTRAINT chk_contextual_validation_event_capacity CHECK (capacity > 0)');
        DB::statement('ALTER TABLE contextual_validation_events ADD CONSTRAINT chk_contextual_validation_event_window CHECK (registration_closes_at_utc > registration_opens_at_utc)');
        DB::statement("ALTER TABLE contextual_validation_events ADD CONSTRAINT chk_contextual_validation_event_status CHECK (status IN ('draft', 'open', 'closed'))");
        DB::statement("ALTER TABLE contextual_validation_registrations ADD CONSTRAINT chk_contextual_validation_registration_ticket CHECK (ticket_type IN ('standard', 'invite'))");
        DB::statement("ALTER TABLE contextual_validation_registrations ADD CONSTRAINT chk_contextual_validation_registration_status CHECK (status IN ('confirmed', 'cancelled'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE contextual_validation_registrations DROP CHECK chk_contextual_validation_registration_status');
        DB::statement('ALTER TABLE contextual_validation_registrations DROP CHECK chk_contextual_validation_registration_ticket');
        DB::statement('ALTER TABLE contextual_validation_events DROP CHECK chk_contextual_validation_event_status');
        DB::statement('ALTER TABLE contextual_validation_events DROP CHECK chk_contextual_validation_event_window');
        DB::statement('ALTER TABLE contextual_validation_events DROP CHECK chk_contextual_validation_event_capacity');
    }
};
