<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_quote_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pricing_quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pricing_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku_snapshot');
            $table->string('name_snapshot');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price_minor');
            $table->unsignedBigInteger('line_total_minor');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_quote_lines');
    }
};
