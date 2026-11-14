<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_calendar_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->constrained('business_calendar_rooms')->restrictOnDelete();
            $table->date('local_date');
            $table->string('kind', 16);
            $table->time('opens_at_local')->nullable();
            $table->time('closes_at_local')->nullable();
            $table->string('reason', 255)->nullable();
            $table->unique(['room_id', 'local_date'], 'business_calendar_override_unique');
            $table->index(['room_id', 'local_date'], 'business_calendar_override_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_calendar_overrides');
    }
};
