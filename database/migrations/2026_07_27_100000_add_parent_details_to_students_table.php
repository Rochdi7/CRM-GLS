<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Parent/guardian stays inline on students (schema §5). Relation ("Categorie"),
// sexe and CIN are plain VARCHARs validated against model constants — no lookup table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('parent_relation', 50)->nullable()->after('parent_nom');
            $table->string('parent_sexe', 10)->nullable()->after('parent_relation');
            $table->string('parent_cin', 30)->nullable()->after('parent_sexe');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn(['parent_relation', 'parent_sexe', 'parent_cin']);
        });
    }
};
