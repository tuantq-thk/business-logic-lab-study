<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_settings', function (Blueprint $table): void {
            $table->string('label')->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->string('group')->nullable()->change();
            $table->string('label_key')->nullable()->after('group');
            $table->string('description_key')->nullable()->after('label_key');
            $table->string('group_key')->nullable()->after('description_key');
        });

        DB::table('catalog_settings')->where('key', 'lab.catalog')->update([
            'label_key' => 'catalog.global.label',
            'description_key' => 'catalog.global.description',
            'group_key' => 'catalog.global.group',
        ]);

        DB::table('catalog_settings')->where('key', 'state-transition')->update([
            'label_key' => 'labs.state_transition.label',
            'description_key' => 'labs.state_transition.description',
            'group_key' => 'labs.state_transition.group',
        ]);

        DB::table('catalog_settings')->where('key', 'inventory-capacity')->update([
            'label_key' => 'labs.inventory_capacity.label',
            'description_key' => 'labs.inventory_capacity.description',
            'group_key' => 'labs.inventory_capacity.group',
        ]);
    }

    public function down(): void
    {
        DB::table('catalog_settings')->whereNull('label')->update([
            'label' => DB::raw('COALESCE(label_key, `key`)'),
        ]);
        DB::table('catalog_settings')->whereNull('description')->update([
            'description' => DB::raw('COALESCE(description_key, `key`)'),
        ]);
        DB::table('catalog_settings')->whereNull('group')->update([
            'group' => DB::raw('COALESCE(group_key, `key`)'),
        ]);

        Schema::table('catalog_settings', function (Blueprint $table): void {
            $table->string('label')->nullable(false)->change();
            $table->text('description')->nullable(false)->change();
            $table->string('group')->nullable(false)->change();
            $table->dropColumn(['label_key', 'description_key', 'group_key']);
        });
    }
};
