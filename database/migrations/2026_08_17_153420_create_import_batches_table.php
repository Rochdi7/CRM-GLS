<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('module', 20);
            $table->string('original_filename', 255);
            $table->foreignId('etablissement_id')->constrained('etablissements');
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires');
            $table->jsonb('context')->nullable();
            $table->string('status', 30)->default('analyzed');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('inserted_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->foreignId('created_by')->constrained('employees');
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamps();

            $table->index(['module', 'created_at'], 'import_batches_module_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
