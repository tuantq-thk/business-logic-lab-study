<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->bigInteger('delta');
            $table->string('reason', 64);
            $table->string('reference', 64)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['inventory_item_id', 'created_at', 'id'], 'inventory_movement_item_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
