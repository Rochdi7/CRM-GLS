<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Application-wide switches a super-admin flips from Paramètres → Système.
 * Deliberately a tiny key/value table (not one column per feature): every
 * value is stored as text and cast by App\Support\Settings\AppSettings,
 * so a new switch is one registry line, never a migration.
 *
 * `valeur` is the scalar a switch is read as (a bool "1"/"0", a string, a
 * number). `options` is the jsonb companion for settings needing more than
 * one scalar: a list, a per-center override map, a threshold set, arbitrary
 * future config — so a future setting stays a registry line + an AppSettings
 * accessor rather than another migration. jsonb (not json) per CLAUDE.md §17;
 * no GIN index, because nothing filters inside it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('cle', 100)->unique();
            $table->text('valeur')->nullable();
            $table->jsonb('options')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
