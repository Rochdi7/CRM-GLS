<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Employees: postal address.
 *
 * The employee PHOTO is deliberately NOT a column — it goes through
 * spatie/laravel-medialibrary like Student::photo does (single-file "photo"
 * collection, served from /media/<uuid8>/…, see CLAUDE.md § File uploads).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('adresse', 255)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn('adresse');
        });
    }
};
