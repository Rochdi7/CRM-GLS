<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_rows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->unsignedInteger('source_row_number');
            $table->jsonb('raw');
            $table->string('status', 20);
            $table->jsonb('errors')->nullable();
            $table->jsonb('resolution')->nullable();
            $table->string('legacy_ref', 50)->nullable();
            $table->string('created_model_type', 40)->nullable();
            $table->unsignedBigInteger('created_model_id')->nullable();
            $table->boolean('selected')->default(true);
            $table->timestamps();

            $table->index(['import_batch_id', 'status'], 'import_rows_batch_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
