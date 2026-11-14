<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_calendar_weekly_hours', function (Blueprint $table): void {
            $table->dropIndex('business_calendar_weekly_lookup');
        });

        Schema::table('business_calendar_overrides', function (Blueprint $table): void {
            $table->dropIndex('business_calendar_override_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('business_calendar_weekly_hours', function (Blueprint $table): void {
            $table->index(['room_id', 'weekday', 'opens_at_local'], 'business_calendar_weekly_lookup');
        });

        Schema::table('business_calendar_overrides', function (Blueprint $table): void {
            $table->index(['room_id', 'local_date'], 'business_calendar_override_lookup');
        });
    }
};
