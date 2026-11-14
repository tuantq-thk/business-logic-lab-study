<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table): void {
            $table->index(
                ['inventory_item_id', 'status', 'quantity'],
                'inventory_reservation_reconciliation_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table): void {
            $table->dropIndex('inventory_reservation_reconciliation_index');
        });
    }
};
