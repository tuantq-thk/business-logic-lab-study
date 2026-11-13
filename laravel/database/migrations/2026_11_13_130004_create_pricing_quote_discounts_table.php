<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_quote_discounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pricing_quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pricing_promotion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code_snapshot');
            $table->unsignedBigInteger('discount_minor');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_quote_discounts');
    }
};
