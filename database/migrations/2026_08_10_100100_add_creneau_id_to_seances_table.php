<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Links a séance back to the créneau (weekly recurring slot) it was
// generated from — nullable, since manually-created séances have none. Lets
// GenererSeancesDepuisCreneau tell "generated from this créneau, still
// Prévue, still untouched" future séances apart from everything else when a
// créneau is edited or deleted (only those get updated/removed).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seances', function (Blueprint $table): void {
            $table->foreignId('creneau_id')->nullable()->after('group_id')
                ->constrained('creneaux')->nullOnDelete();
            $table->index('creneau_id');
        });
    }

    public function down(): void
    {
        Schema::table('seances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('creneau_id');
        });
    }
};
