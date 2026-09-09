<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-points the AGENT of payments filed under someone who never cashes.
 *
 * The legacy Import's « Opérateur → Employé » mapping offered every employee
 * of the centre, so a teaching job title could be chosen as the agent of a
 * payment. Audit 09/09/2026 found 3 166 imported payments (3 100 540 DH)
 * signed by an « Enseignant » or an « Autre » — one of them (Aya Figar,
 * EMP-072) has NO login and has never signed in, so she cannot have taken a
 * dirham from anyone.
 *
 * ⚠ `agent_id` is an AUDIT TRAIL, not a label. Rewriting it on a payment
 * somebody really recorded would erase who did it, so this command is
 * deliberately opt-in per employee (`--de=`) rather than a sweeping
 * "everyone who is not a cashier" pass: each case has to be judged. The
 * change itself is journaled (Encaissement is Auditable, and each row is
 * saved through Eloquent, never a mass update, so « avant → après » is
 * recorded).
 *
 * What it does NOT touch: `montant`, `date_paiement`, `methode`,
 * `caisse_id`, `caisses.solde`, `inscription_fee_id`. The money stays
 * exactly where it is — only the name of the recorded agent changes.
 *
 * Usage:
 *   php artisan encaissements:reattribuer-agent --de=72 --vers=1 --dry-run
 *   php artisan encaissements:reattribuer-agent --de=72 --vers=1
 *   php artisan encaissements:reattribuer-agent --de=72 --vers=1 --importes-seulement
 */
final class ReattribuerAgentEncaissements extends Command
{
    protected $signature = 'encaissements:reattribuer-agent
        {--auditer : Lister TOUS les agents non-encaisseurs, sans rien modifier}
        {--de= : Employé actuellement enregistré comme agent (id)}
        {--vers= : Employé qui doit le remplacer (id)}
        {--importes-seulement : Ne toucher que les lignes portant un legacy_ref}
        {--dry-run : Afficher sans modifier}';

    protected $description = "Réattribue l'agent des encaissements d'un employé vers un autre (réparation du mapping d'import).";

    public function handle(): int
    {
        if ($this->option('auditer')) {
            return $this->auditer();
        }

        $deId = (int) $this->option('de');
        $versId = (int) $this->option('vers');

        if ($deId === 0 || $versId === 0) {
            $this->error('--de et --vers sont obligatoires (ids d\'employés).');

            return self::FAILURE;
        }

        if ($deId === $versId) {
            $this->error('--de et --vers doivent différer.');

            return self::FAILURE;
        }

        $de = Employee::withoutGlobalScopes()->find($deId);
        $vers = Employee::withoutGlobalScopes()->find($versId);

        if ($de === null || $vers === null) {
            $this->error('Employé introuvable.');

            return self::FAILURE;
        }

        // The destination must itself be a plausible cashier, or the repair
        // just moves the problem to another name.
        if (in_array($vers->categorie, Employee::CATEGORIES_NON_ENCAISSEUSES, true)) {
            $this->error(sprintf(
                '%s %s est « %s » — un poste qui n\'encaisse pas. Choisir un autre destinataire.',
                $vers->prenom, $vers->nom, $vers->categorie
            ));

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');

        $query = Encaissement::query()->where('agent_id', $deId);

        if ($this->option('importes-seulement')) {
            $query->whereNotNull('legacy_ref');
        }

        $lignes = $query->orderBy('id')->get();

        if ($lignes->isEmpty()) {
            $this->info('Aucun encaissement à réattribuer.');

            return self::SUCCESS;
        }

        $importes = $lignes->whereNotNull('legacy_ref');
        $saisis = $lignes->whereNull('legacy_ref');

        $this->line('');
        $this->info(sprintf(
            '%s%s %s (%s)  ->  %s %s (%s)',
            $dry ? '[DRY-RUN] ' : '',
            $de->prenom, $de->nom, $de->categorie,
            $vers->prenom, $vers->nom, $vers->categorie
        ));
        $this->line('');
        $this->line(sprintf('  %d ligne(s) importée(s)   %s DH', $importes->count(), number_format((float) $importes->sum('montant'), 2, '.', '')));
        $this->line(sprintf('  %d ligne(s) saisie(s)     %s DH', $saisis->count(), number_format((float) $saisis->sum('montant'), 2, '.', '')));
        $this->line(sprintf('  %d au total               %s DH', $lignes->count(), number_format((float) $lignes->sum('montant'), 2, '.', '')));

        if ($dry) {
            $this->line('');
            $this->comment('  DRY-RUN — rien écrit. Relancer sans --dry-run pour appliquer.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($lignes, $versId): void {
            foreach ($lignes as $ligne) {
                // save() ligne par ligne, jamais un update() de masse : sinon
                // Auditable ne journalise rien et la trace « avant → après »
                // de l'agent disparaît (CLAUDE.md §11).
                $ligne->agent_id = $versId;
                $ligne->save();
            }
        });

        $this->line('');
        $this->info(sprintf('%d encaissement(s) réattribué(s).', $lignes->count()));

        $restant = Encaissement::where('agent_id', $deId)->count();
        $this->line(sprintf('  Reste %d encaissement(s) sur %s %s.', $restant, $de->prenom, $de->nom));

        return self::SUCCESS;
    }

    /**
     * READ-ONLY: every employee holding payments whose job title never
     * cashes, with what it takes to decide what to do about each one.
     *
     * Two very different situations hide behind the same symptom, and the
     * columns are here to tell them apart:
     *   - NO LOGIN / never signed in  => the name was merely PICKED in the
     *     import mapping; that person cannot have cashed anything, so
     *     re-pointing is a plain correction;
     *   - a real, active login        => someone genuinely worked at the
     *     desk under a wrong job title. The fix is their EMPLOYEE RECORD
     *     (give them their real catégorie), not a rewrite of the money
     *     trail — `agent_id` is an audit trail, not a label (§11).
     */
    private function auditer(): int
    {
        $agents = Encaissement::query()
            ->whereHas('agent', fn ($q) => $q
                ->withoutGlobalScopes()
                ->whereIn('categorie', Employee::CATEGORIES_NON_ENCAISSEUSES))
            ->selectRaw('agent_id, count(*) as n, sum(montant) as total')
            ->selectRaw('count(legacy_ref) as importes')
            ->groupBy('agent_id')
            ->orderByDesc('total')
            ->get();

        if ($agents->isEmpty()) {
            $this->info('Aucun encaissement rattaché à un poste non-encaisseur.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->info('Encaissements dont l\'agent occupe un poste qui n\'encaisse pas');
        $this->line('  ('.implode(' / ', Employee::CATEGORIES_NON_ENCAISSEUSES).')');
        $this->line('');

        $lignes = [];
        $totalGeneral = 0.0;

        foreach ($agents as $agregat) {
            $employe = Employee::withoutGlobalScopes()->find($agregat->agent_id);

            if ($employe === null) {
                continue;
            }

            $user = $employe->user_id !== null ? User::find($employe->user_id) : null;
            $connecte = $user !== null
                ? Activity::query()
                    ->where('log_name', 'auth')
                    ->where('causer_id', $user->id)
                    ->exists()
                : false;

            $lignes[] = [
                $employe->id,
                mb_substr($employe->prenom.' '.$employe->nom, 0, 24),
                $employe->categorie,
                (int) $agregat->n,
                (int) $agregat->importes,
                (int) $agregat->n - (int) $agregat->importes,
                number_format((float) $agregat->total, 2, '.', ' '),
                $user === null ? 'AUCUN' : ($connecte ? 'oui' : 'jamais'),
            ];

            $totalGeneral += (float) $agregat->total;
        }

        $this->table(
            ['id', 'employé', 'catégorie', 'total', 'importés', 'saisis', 'montant', 'connecté'],
            $lignes
        );

        $this->line('');
        $this->info(sprintf('%d employé(s) — %s DH au total.', count($lignes), number_format($totalGeneral, 2, '.', ' ')));
        $this->line('');
        $this->comment('  « connecté = AUCUN/jamais » : le nom a seulement été CHOISI à l\'import,');
        $this->comment('  la personne n\'a rien pu encaisser — réattribution sûre.');
        $this->comment('  « connecté = oui » : quelqu\'un a réellement travaillé sous cette fiche.');
        $this->comment('  Corriger sa CATÉGORIE dans sa fiche employé plutôt que de réécrire');
        $this->comment('  l\'historique — agent_id est une trace d\'audit, pas un libellé.');
        $this->line('');
        $this->comment('  Réparer un cas : --de=<id> --vers=<id> --dry-run');

        return self::SUCCESS;
    }
}
