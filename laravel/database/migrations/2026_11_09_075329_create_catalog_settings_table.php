<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('catalog_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description');
            $table->string('group');
            $table->boolean('implemented')->default(false);
            $table->boolean('default_enabled')->default(false);
            $table->boolean('enabled')->nullable();
            $table->foreignId('last_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_changed_at')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamps();
        });

        DB::table('catalog_settings')->insert([
            'key' => 'lab.catalog',
            'label' => 'Lab catalog',
            'description' => 'Cho phép learner nhìn thấy catalog các business-logic lab.',
            'group' => 'Learning shell',
            'implemented' => true,
            'default_enabled' => false,
            'enabled' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalog_settings');
    }
};
