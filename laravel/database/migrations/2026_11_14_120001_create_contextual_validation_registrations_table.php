<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contextual_validation_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('contextual_validation_events')->restrictOnDelete();
            $table->foreignId('attendee_id')->constrained('users')->restrictOnDelete();
            $table->string('ticket_type', 16);
            $table->string('status', 16)->default('confirmed');
            $table->timestamps();
            $table->unique(['event_id', 'attendee_id'], 'contextual_validation_registration_unique');
            $table->index(['event_id', 'status'], 'contextual_validation_registration_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contextual_validation_registrations');
    }
};
