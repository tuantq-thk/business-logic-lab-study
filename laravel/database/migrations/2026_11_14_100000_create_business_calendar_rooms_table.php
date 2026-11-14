<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_calendar_rooms', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name_key', 160);
            $table->string('timezone', 64);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_calendar_rooms');
    }
};
