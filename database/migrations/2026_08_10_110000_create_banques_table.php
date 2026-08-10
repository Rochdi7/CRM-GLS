<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banques', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 150);
            $table->string('statut', 20)->default('Actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banques');
    }
};
