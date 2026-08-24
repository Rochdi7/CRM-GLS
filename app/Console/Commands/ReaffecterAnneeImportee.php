<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\GroupHistorique;
use App\Models\ImportBatch;
use App\Models\Inscription;
use App\Models\Seance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-homes one centre's records from one année scolaire to another —
 * the repair for data imported (or created) under the wrong top-bar year
 * before the strict context rule of 24/08/2026.
 *
 * Moves every table carrying annee_scolaire_id: groups, inscriptions,
 * seances, groups_historique, plus the import_batches rows so the
 * « Imports récents » screen reflects where the data now lives.
 * Encaissements need no touch (they follow fee.inscription) and students
 * carry no year. Money amounts, tills and references are never modified.
 *
 * Rows are saved one by one through Eloquent — NOT a mass update — so the
 * Auditable trait journals every change (avant/après) like any other edit.
 *
 * Usage:
 *   php artisan annee:reaffecter --centre=Marrakech --de=2026/2027 --vers=2025/2026 --dry-run
 *   php artisan annee:reaffecter --centre=Marrakech --de=2026/2027 --vers=2025/2026
 */
final class ReaffecterAnneeImportee extends Command
{
    protected $signature = 'annee:reaffecter
        {--centre= : Centre (id ou partie du nom, ex. "Marrakech")}
        {--de= : Année scolaire source (nom exact, ex. "2026/2027")}
        {--vers= : Année scolaire cible (nom exact, ex. "2025/2026")}
        {--dry-run : Compter sans rien modifier}';

    protected $description = "Réaffecte les groupes/inscriptions/séances d'un centre d'une année scolaire vers une autre (réparation d'un import fait sous la mauvaise année).";

    public function handle(): int
    {
        $centre = $this->resolveCentre((string) $this->option('centre'));
        $de = $this->resolveAnnee((string) $this->option('de'), 'de');
        $vers = $this->resolveAnnee((string) $this->option('vers'), 'vers');

        if ($centre === null || $de === null || $vers === null) {
            return self::FAILURE;
        }

        if ($de->id === $vers->id) {
            $this->error("L'année source et l'année cible sont identiques.");

            return self::FAILURE;
        }

        $scopes = [
            'Groupes' => Group::query()->where('etablissement_id', $centre->id)->where('annee_scolaire_id', $de->id),
            'Inscriptions' => Inscription::query()->where('etablissement_id', $centre->id)->where('annee_scolaire_id', $de->id),
            'Séances' => Seance::query()->where('etablissement_id', $centre->id)->where('annee_scolaire_id', $de->id),
            'Groupes archivés' => GroupHistorique::query()->where('etablissement_id', $centre->id)->where('annee_scolaire_id', $de->id),
            'Lots d\'import' => ImportBatch::query()->where('etablissement_id', $centre->id)->where('annee_scolaire_id', $de->id),
        ];

        $this->info(sprintf(
            '%s : %s -> %s%s',
            $centre->nom_centre,
            $de->nom,
            $vers->nom,
            $this->option('dry-run') ? '  (simulation — rien ne sera modifié)' : '',
        ));

        foreach ($scopes as $label => $query) {
            $this->line(sprintf('  %-18s %d ligne(s)', $label.' :', (clone $query)->count()));
        }

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        if (! $this->confirm('Confirmer la réaffectation ?')) {
            $this->warn('Abandonné — rien n\'a été modifié.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($scopes, $vers): void {
            foreach ($scopes as $label => $query) {
                $moved = 0;

                // Model-by-model save (not a mass update) so Auditable
                // journals each change — an unjournalled rewrite of business
                // rows is exactly what the audit log exists to prevent.
                foreach ((clone $query)->cursor() as $model) {
                    $model->annee_scolaire_id = $vers->id;
                    $model->save();
                    $moved++;
                }

                $this->line(sprintf('  %-18s %d réaffectée(s)', $label.' :', $moved));
            }
        });

        $this->info('Terminé. Les encaissements suivent automatiquement leurs inscriptions (aucun montant modifié).');

        return self::SUCCESS;
    }

    private function resolveCentre(string $input): ?Etablissement
    {
        if ($input === '') {
            $this->error('--centre est obligatoire (id ou partie du nom).');

            return null;
        }

        $query = Etablissement::query();

        $matches = ctype_digit($input)
            ? $query->whereKey((int) $input)->get()
            : $query->where('nom_centre', 'ilike', "%{$input}%")->get();

        if ($matches->count() === 1) {
            return $matches->first();
        }

        $this->error($matches->isEmpty()
            ? "Aucun centre ne correspond à « {$input} »."
            : "Plusieurs centres correspondent à « {$input} » : ".$matches->pluck('nom_centre')->implode(', ').' — précisez.');

        return null;
    }

    private function resolveAnnee(string $nom, string $option): ?AnneeScolaire
    {
        if ($nom === '') {
            $this->error("--{$option} est obligatoire (nom exact de l'année, ex. 2025/2026).");

            return null;
        }

        $annee = AnneeScolaire::query()->where('nom', $nom)->first();

        if ($annee === null) {
            $this->error("Année scolaire « {$nom} » introuvable. Années existantes : "
                .AnneeScolaire::query()->orderByDesc('date_debut')->pluck('nom')->implode(', '));
        }

        return $annee;
    }
}
