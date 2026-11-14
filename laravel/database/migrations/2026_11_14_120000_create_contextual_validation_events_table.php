<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contextual_validation_events', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 160);
            $table->text('description');
            $table->timestamp('starts_at_utc');
            $table->timestamp('registration_opens_at_utc');
            $table->timestamp('registration_closes_at_utc');
            $table->unsignedInteger('capacity');
            $table->string('status', 16)->default('draft');
            $table->string('invite_code_hash', 255)->nullable();
            $table->timestamps();
            $table->index(['status', 'registration_opens_at_utc', 'registration_closes_at_utc'], 'contextual_validation_event_window_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contextual_validation_events');
    }
};
