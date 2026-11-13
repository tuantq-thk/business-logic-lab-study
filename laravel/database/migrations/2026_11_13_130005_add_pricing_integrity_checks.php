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

        DB::statement("ALTER TABLE pricing_promotions ADD CONSTRAINT chk_pricing_promotions_kind_value CHECK (kind <> 'percentage' OR value <= 100)");
        DB::statement('ALTER TABLE pricing_promotions ADD CONSTRAINT chk_pricing_promotions_window CHECK (ends_at IS NULL OR starts_at IS NULL OR ends_at >= starts_at)');
        DB::statement('ALTER TABLE pricing_quotes ADD CONSTRAINT chk_pricing_quotes_discount CHECK (discount_minor <= subtotal_minor)');
        DB::statement('ALTER TABLE pricing_quotes ADD CONSTRAINT chk_pricing_quotes_total CHECK (total_minor + discount_minor = subtotal_minor)');
        DB::statement('ALTER TABLE pricing_quote_lines ADD CONSTRAINT chk_pricing_quote_lines_quantity CHECK (quantity > 0)');
        DB::statement('ALTER TABLE pricing_quote_lines ADD CONSTRAINT chk_pricing_quote_lines_total CHECK (line_total_minor = quantity * unit_price_minor)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE pricing_quote_lines DROP CHECK chk_pricing_quote_lines_total');
        DB::statement('ALTER TABLE pricing_quote_lines DROP CHECK chk_pricing_quote_lines_quantity');
        DB::statement('ALTER TABLE pricing_quotes DROP CHECK chk_pricing_quotes_total');
        DB::statement('ALTER TABLE pricing_quotes DROP CHECK chk_pricing_quotes_discount');
        DB::statement('ALTER TABLE pricing_promotions DROP CHECK chk_pricing_promotions_window');
        DB::statement('ALTER TABLE pricing_promotions DROP CHECK chk_pricing_promotions_kind_value');
    }
};
