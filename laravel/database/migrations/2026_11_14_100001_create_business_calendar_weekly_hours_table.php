<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_calendar_weekly_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->constrained('business_calendar_rooms')->restrictOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('opens_at_local');
            $table->time('closes_at_local');
            $table->unique(['room_id', 'weekday', 'opens_at_local'], 'business_calendar_weekly_unique');
            $table->index(['room_id', 'weekday', 'opens_at_local'], 'business_calendar_weekly_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_calendar_weekly_hours');
    }
};
