<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Recover from MySQL DDL that completed before a later migration statement failed. */
    public function up(): void
    {
        if (! Schema::hasTable('cancellation_courses')) {
            Schema::create('cancellation_courses', function (Blueprint $table): void {
                $table->id();
                $table->string('title', 160);
                $table->timestamp('starts_at_utc');
                $table->timestamp('cancel_cutoff_at_utc');
                $table->unsignedInteger('capacity');
                $table->unsignedBigInteger('price_minor');
                $table->string('currency', 3)->default('USD');
                $table->string('status', 16)->default('open');
                $table->timestamps();
                $table->index(['status', 'starts_at_utc']);
            });
        }

        if (! Schema::hasTable('cancellation_course_registrations')) {
            Schema::create('cancellation_course_registrations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('course_id')->constrained('cancellation_courses')->restrictOnDelete();
                $table->foreignId('attendee_id')->constrained('users')->restrictOnDelete();
                $table->string('status', 16)->default('confirmed');
                $table->unsignedBigInteger('paid_amount_minor');
                $table->string('currency', 3)->default('USD');
                $table->timestamps();
                $table->unique(['course_id', 'attendee_id'], 'cancellation_registration_course_attendee_unique');
                $table->index(['course_id', 'status'], 'cancellation_registration_capacity_index');
                $table->index(['attendee_id', 'status']);
            });
        }

        if (! Schema::hasTable('cancellation_registration_histories')) {
            Schema::create('cancellation_registration_histories', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('registration_id')->constrained('cancellation_course_registrations')->restrictOnDelete();
                $table->string('action', 16);
                $table->foreignId('from_course_id')->nullable()->constrained('cancellation_courses')->restrictOnDelete();
                $table->foreignId('to_course_id')->nullable()->constrained('cancellation_courses')->restrictOnDelete();
                $table->timestamp('from_starts_at_utc')->nullable();
                $table->timestamp('to_starts_at_utc')->nullable();
                $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
                $table->string('reason', 500)->nullable();
                $table->string('policy_version', 32);
                $table->timestamp('created_at')->useCurrent();
                $table->index(['registration_id', 'created_at'], 'crh_registration_created_idx');
            });
        }

        if (! Schema::hasTable('cancellation_refund_intents')) {
            Schema::create('cancellation_refund_intents', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('registration_id')->constrained('cancellation_course_registrations')->restrictOnDelete();
                $table->unsignedBigInteger('amount_minor');
                $table->string('currency', 3)->default('USD');
                $table->string('status', 16)->default('pending');
                $table->string('idempotency_key', 100)->unique();
                $table->timestamps();
                $table->unique('registration_id', 'cancellation_refund_registration_unique');
            });
        }

        if (! Schema::hasIndex('cancellation_registration_histories', ['registration_id', 'created_at'])) {
            Schema::table('cancellation_registration_histories', function (Blueprint $table): void {
                $table->index(['registration_id', 'created_at'], 'crh_registration_created_idx');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            $this->addCheckConstraint('cancellation_courses', 'chk_cancellation_course_capacity', 'capacity > 0');
            $this->addCheckConstraint('cancellation_courses', 'chk_cancellation_course_cutoff', 'cancel_cutoff_at_utc < starts_at_utc');
            $this->addCheckConstraint('cancellation_courses', 'chk_cancellation_course_status', "status IN ('open', 'closed')");
            $this->addCheckConstraint('cancellation_course_registrations', 'chk_cancellation_registration_status', "status IN ('confirmed', 'cancelled')");
            $this->addCheckConstraint('cancellation_registration_histories', 'chk_cancellation_history_action', "action IN ('cancelled', 'rescheduled')");
            $this->addCheckConstraint('cancellation_refund_intents', 'chk_cancellation_refund_amount', 'amount_minor > 0');
            $this->addCheckConstraint('cancellation_refund_intents', 'chk_cancellation_refund_status', "status IN ('pending', 'completed', 'failed')");
        }
    }

    private function addCheckConstraint(string $table, string $name, string $expression): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->exists();

        if (! $exists) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_refund_intents');
        Schema::dropIfExists('cancellation_registration_histories');
        Schema::dropIfExists('cancellation_course_registrations');
        Schema::dropIfExists('cancellation_courses');
    }
};
