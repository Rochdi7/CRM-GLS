<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Extra HR fields on employees. Only `sexe` is required at the app layer
 * (see StoreEmployeeRequest / EmployeesIndex); the rest are optional.
 * `email` already exists on the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('sexe', 10)->nullable()->after('prenom');
            $table->date('date_naissance')->nullable()->after('sexe');
            $table->date('date_embauche')->nullable()->after('date_naissance');
            $table->decimal('salaire', 10, 2)->nullable()->after('date_embauche');
            $table->text('note')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn(['sexe', 'date_naissance', 'date_embauche', 'salaire', 'note']);
        });
    }
};
