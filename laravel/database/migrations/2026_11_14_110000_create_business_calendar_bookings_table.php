<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_calendar_bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->constrained('business_calendar_rooms')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('starts_at_utc');
            $table->timestamp('ends_at_utc');
            $table->string('status', 16)->default('confirmed');
            $table->uuid('idempotency_key')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key'], 'business_calendar_booking_idempotency_unique');
            $table->index(['room_id', 'status', 'starts_at_utc', 'ends_at_utc'], 'business_calendar_booking_overlap_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_calendar_bookings');
    }
};
