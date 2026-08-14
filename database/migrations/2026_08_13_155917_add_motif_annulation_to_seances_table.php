<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seances', function (Blueprint $table): void {
            $table->text('motif_annulation')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('seances', function (Blueprint $table): void {
            $table->dropColumn('motif_annulation');
        });
    }
};
