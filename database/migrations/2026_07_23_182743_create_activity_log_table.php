<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Journal d'audit — spatie/laravel-activitylog v5 storage (CLAUDE.md §11).
 *
 * Beyond the package's own columns this table carries FORENSIC CONTEXT
 * (ip_address … causer_label), stamped on insert by
 * App\Models\Activity::bootActivity() so every entry has it regardless of
 * which model or Domain action logged it — including entries caused by a
 * super-admin. The package records WHAT changed, WHO and WHEN; for the
 * encaissement-fraud trail the journal must also answer FROM WHERE and
 * THROUGH WHICH ENDPOINT, so an investigation can tie a suspicious money
 * row to a machine/session instead of an employee name that may have been
 * shared or borrowed.
 *
 * The composite indexes cover the journal page's real access paths: it
 * filters by causer, subject type, log name and date range, and always
 * sorts newest-first. PostgreSQL does not index the referencing side of a
 * relation automatically (CLAUDE.md §17).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table): void {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->jsonb('attribute_changes')->nullable();
            $table->jsonb('properties')->nullable();

            // Forensic context (Activity::bootActivity()).
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('url', 1024)->nullable();
            $table->string('route_name')->nullable();
            $table->string('causer_label')->nullable();

            $table->timestamps();

            $table->index(['created_at'], 'activity_log_created_at_index');
            $table->index(['log_name', 'created_at'], 'activity_log_log_name_created_at_index');
            $table->index(['event', 'created_at'], 'activity_log_event_created_at_index');
            $table->index(['causer_type', 'causer_id', 'created_at'], 'activity_log_causer_created_at_index');
            $table->index(['subject_type', 'created_at'], 'activity_log_subject_type_created_at_index');
            $table->index(['ip_address'], 'activity_log_ip_address_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
