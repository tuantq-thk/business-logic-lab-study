<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE inventory_items ADD CONSTRAINT chk_inventory_reserved_available CHECK (reserved_quantity <= on_hand)');
        DB::statement('ALTER TABLE inventory_reservations ADD CONSTRAINT chk_inventory_reservation_quantity CHECK (quantity > 0)');
        DB::statement("ALTER TABLE inventory_reservations ADD CONSTRAINT chk_inventory_reservation_status CHECK (status IN ('active', 'consumed', 'released', 'expired'))");
        DB::statement('ALTER TABLE inventory_movements ADD CONSTRAINT chk_inventory_movement_delta CHECK (delta <> 0)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE inventory_movements DROP CHECK chk_inventory_movement_delta');
        DB::statement('ALTER TABLE inventory_reservations DROP CHECK chk_inventory_reservation_status');
        DB::statement('ALTER TABLE inventory_reservations DROP CHECK chk_inventory_reservation_quantity');
        DB::statement('ALTER TABLE inventory_items DROP CHECK chk_inventory_reserved_available');
    }
};
