<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forensic context for the audit journal (CLAUDE.md §11 "Audit log").
 *
 * spatie/laravel-activitylog only records WHAT changed, WHO caused it and
 * WHEN. For the encaissement-fraud trail the journal must also answer FROM
 * WHERE and THROUGH WHICH ENDPOINT, so an investigation can tie a suspicious
 * money row to a machine/session instead of just an employee name that may
 * have been shared or borrowed.
 *
 * These columns are written by App\Models\Activity::bootActivity() on insert,
 * so every entry gets them regardless of which model or Domain action logged
 * it — including entries caused by a super-admin.
 *
 * Indexes: the journal page filters by causer, by subject type, by log name
 * and by date range, and always sorts newest-first. PostgreSQL does not index
 * the referencing side of a relation automatically (CLAUDE.md §17), and the
 * base migration only indexed subject/causer morphs and log_name individually
 * — the composites below cover the page's real access paths.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->string('ip_address', 45)->nullable()->after('properties');
            $table->string('user_agent', 512)->nullable()->after('ip_address');
            $table->string('method', 10)->nullable()->after('user_agent');
            $table->string('url', 1024)->nullable()->after('method');
            $table->string('route_name')->nullable()->after('url');
            $table->string('causer_label')->nullable()->after('route_name');
        });

        Schema::table('activity_log', function (Blueprint $table): void {
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
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropIndex('activity_log_created_at_index');
            $table->dropIndex('activity_log_log_name_created_at_index');
            $table->dropIndex('activity_log_event_created_at_index');
            $table->dropIndex('activity_log_causer_created_at_index');
            $table->dropIndex('activity_log_subject_type_created_at_index');
            $table->dropIndex('activity_log_ip_address_index');
        });

        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropColumn([
                'ip_address', 'user_agent', 'method', 'url', 'route_name', 'causer_label',
            ]);
        });
    }
};
