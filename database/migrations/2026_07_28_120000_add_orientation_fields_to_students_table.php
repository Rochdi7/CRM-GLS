<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Students: German-track orientation + the student's own CIN.
 *
 * `niveau` now also carries the non-CEFR tracks (Arbeit / Studium /
 * Ausbildung), so the old VARCHAR(10) is widened to 20 ("Ausbildung" alone
 * is 10 chars — no headroom left).
 *
 * The two orientation columns are mutually exclusive by usage and both
 * nullable (a plain CEFR level uses neither):
 *  - `domaine`      → Arbeit / Ausbildung   (Student::DOMAINES)
 *  - `examen_type`  → Studium               (Student::EXAMEN_TYPES: STK / DSH)
 *
 * Plain VARCHARs validated against model constants — no lookup tables
 * (schema §5 "Deliberate Simplifications").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('niveau', 20)->nullable()->change();
            $table->string('cin', 30)->nullable()->after('date_naissance');
            $table->string('domaine', 60)->nullable()->after('niveau');
            $table->string('examen_type', 10)->nullable()->after('domaine');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn(['cin', 'domaine', 'examen_type']);
            $table->string('niveau', 10)->nullable()->change();
        });
    }
};
