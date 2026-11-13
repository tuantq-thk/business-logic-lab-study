<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_promotions', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('kind', 20);
            $table->unsignedInteger('value');
            $table->unsignedBigInteger('minimum_subtotal_minor')->default(0);
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('stackable')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_promotions');
    }
};
