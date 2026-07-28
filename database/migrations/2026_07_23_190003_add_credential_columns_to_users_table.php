<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * gls-crm-laravel-structure.md §5 — auto-generated employee credentials.
 * `username` is the future login identifier (employees may have no email);
 * `must_change_password` forces a password reset on first login once the
 * ForcePasswordChange middleware is built.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->unique()->nullable()->after('email');
            $table->boolean('must_change_password')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['username', 'must_change_password']);
        });
    }
};
