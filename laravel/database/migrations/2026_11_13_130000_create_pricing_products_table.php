<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name_key');
            $table->unsignedBigInteger('unit_price_minor');
            $table->char('currency', 3);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_products');
    }
};
