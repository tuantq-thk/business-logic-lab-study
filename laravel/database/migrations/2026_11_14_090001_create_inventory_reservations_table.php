<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('order_ref', 64);
            $table->unsignedBigInteger('quantity');
            $table->string('status', 16)->default('active');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->unique(['owner_id', 'order_ref', 'inventory_item_id'], 'inventory_reservation_business_key');
            $table->index(['status', 'expires_at', 'id'], 'inventory_reservation_expiry_index');
            $table->index(['owner_id', 'created_at', 'id'], 'inventory_reservation_owner_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
