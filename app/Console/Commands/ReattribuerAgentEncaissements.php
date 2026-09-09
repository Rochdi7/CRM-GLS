<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Encaissement;
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
        {--tous-les-importes : Repointer TOUT l\'historique importé vers --vers (ignore --de)}
        {--de= : Employé actuellement enregistré comme agent (id)}
        {--vers= : Employé qui doit le remplacer (id)}
        {--importes-seulement : Ne toucher que les lignes issues de l\'import}
        {--dry-run : Afficher sans modifier}';

    protected $description = "Réattribue l'agent des encaissements d'un employé vers un autre (réparation du mapping d'import).";

    /**
     * Le marqueur que pose l'import (EncaissementImporter::LEGACY_SOURCE).
     *
     * ⚠ C'est LUI qui dit « cette ligne vient de l'ancien CRM », jamais
     * `legacy_ref` : une part secondaire d'un paiement éclaté sur plusieurs
     * frais garde la source mais pas la référence, qui reste unique par
     * centre. Vérifié le 09/09/2026 : 23 833 lignes portent la source, 23 779
     * une référence — 54 lignes seraient oubliées par l'autre filtre, et
     * AUCUNE ligne n'a une référence sans la source.
     */
    private const string SOURCE_IMPORT = 'ancien-crm';

    public function handle(): int
    {
        if ($this->option('auditer')) {
            return $this->auditer();
        }

        $tousLesImportes = (bool) $this->option('tous-les-importes');
        $deId = (int) $this->option('de');
        $versId = (int) $this->option('vers');

        if ($versId === 0) {
            $this->error('--vers est obligatoire (id de l\'employé destinataire).');

            return self::FAILURE;
        }

        if (! $tousLesImportes && $deId === 0) {
            $this->error('--de est obligatoire, sauf avec --tous-les-importes.');

            return self::FAILURE;
        }

        if ($deId !== 0 && $deId === $versId) {
            $this->error('--de et --vers doivent différer.');

            return self::FAILURE;
        }

        $de = $deId !== 0 ? Employee::withoutGlobalScopes()->find($deId) : null;
        $vers = Employee::withoutGlobalScopes()->find($versId);

        if ($vers === null || ($deId !== 0 && $de === null)) {
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

        $query = Encaissement::query();

        if ($tousLesImportes) {
            // TOUT l'historique importé, quel que soit l'agent actuel.
            // `legacy_source`, jamais `legacy_ref` : une part secondaire de
            // paiement éclaté sur plusieurs frais garde la source mais PAS la
            // référence (unique par centre) — 54 lignes en production le
            // 09/09/2026. Filtrer sur legacy_ref les laisserait derrière.
            $query->where('legacy_source', self::SOURCE_IMPORT)
                ->where('agent_id', '!=', $versId);
        } else {
            $query->where('agent_id', $deId);

            if ($this->option('importes-seulement')) {
                $query->where('legacy_source', self::SOURCE_IMPORT);
            }
        }

        $lignes = $query->orderBy('id')->get();

        if ($lignes->isEmpty()) {
            $this->info('Aucun encaissement à réattribuer.');

            return self::SUCCESS;
        }

        $importes = $lignes->where('legacy_source', self::SOURCE_IMPORT);
        $saisis = $lignes->where('legacy_source', '!=', self::SOURCE_IMPORT);

        $this->line('');
        $this->info(sprintf(
            '%s%s  ->  %s %s (%s)',
            $dry ? '[DRY-RUN] ' : '',
            $tousLesImportes
                ? 'TOUT l\'historique importé'
                : sprintf('%s %s (%s)', $de->prenom, $de->nom, $de->categorie),
            $vers->prenom, $vers->nom, $vers->categorie
        ));
        $this->line('');

        if ($tousLesImportes) {
            $this->line('  Agents actuellement enregistrés sur ces lignes :');

            foreach ($lignes->groupBy('agent_id') as $agentId => $groupe) {
                $agent = Employee::withoutGlobalScopes()->find($agentId);

                $this->line(sprintf(
                    '    %-26s %6d | %14s DH',
                    mb_substr(($agent->prenom ?? '?').' '.($agent->nom ?? ''), 0, 26),
                    $groupe->count(),
                    number_format((float) $groupe->sum('montant'), 2, '.', ' ')
                ));
            }

            $this->line('');
        }
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

        if ($tousLesImportes) {
            $restant = Encaissement::query()
                ->where('legacy_source', self::SOURCE_IMPORT)
                ->where('agent_id', '!=', $versId)
                ->count();

            $this->line(sprintf('  Reste %d ligne(s) importée(s) hors de %s %s.', $restant, $vers->prenom, $vers->nom));
        } else {
            $restant = Encaissement::where('agent_id', $deId)->count();
            $this->line(sprintf('  Reste %d encaissement(s) sur %s %s.', $restant, $de->prenom, $de->nom));
        }

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
            ->selectRaw('agent_id')
            ->selectRaw('count(*) filter (where legacy_source = ?) as importes', [self::SOURCE_IMPORT])
            ->selectRaw('sum(montant) filter (where legacy_source = ?) as montant_importe', [self::SOURCE_IMPORT])
            ->selectRaw('count(*) filter (where legacy_source is distinct from ?) as saisis', [self::SOURCE_IMPORT])
            ->groupBy('agent_id')
            ->havingRaw('count(*) filter (where legacy_source = ?) > 0', [self::SOURCE_IMPORT])
            ->orderByRaw('sum(montant) filter (where legacy_source = ?) desc', [self::SOURCE_IMPORT])
            ->get();

        if ($agents->isEmpty()) {
            $this->info('Aucun encaissement importé.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->info("Agents enregistrés sur l'historique IMPORTÉ (ancien CRM)");
        $this->line('');

        $lignes = [];
        $totalImporte = 0.0;

        foreach ($agents as $agregat) {
            $employe = Employee::withoutGlobalScopes()->find($agregat->agent_id);

            if ($employe === null) {
                continue;
            }

            $nonEncaisseur = in_array($employe->categorie, Employee::CATEGORIES_NON_ENCAISSEUSES, true);

            $lignes[] = [
                $employe->id,
                mb_substr($employe->prenom.' '.$employe->nom, 0, 24),
                $employe->categorie.($nonEncaisseur ? ' (!)' : ''),
                (int) $agregat->importes,
                number_format((float) $agregat->montant_importe, 2, '.', ' '),
                (int) $agregat->saisis,
            ];

            $totalImporte += (float) $agregat->montant_importe;
        }

        $this->table(
            ['id', 'employé', 'catégorie', 'importés', 'montant importé', 'saisies CRM'],
            $lignes
        );

        $this->line('');
        $this->info(sprintf("%d agent(s) — %s DH d'historique importé.", count($lignes), number_format($totalImporte, 2, '.', ' ')));
        $this->line('');
        $this->comment("  (!) = poste qui n'encaisse pas — n'aurait jamais dû être proposé à l'import.");
        $this->comment('  « saisies CRM » = paiements que cette personne a réellement enregistrés');
        $this->comment('  dans le CRM. Ces lignes-là ne sont JAMAIS réattribuées : agent_id y est');
        $this->comment("  une trace d'audit, pas un libellé (§11).");
        $this->line('');
        $this->comment("  Tout l'importé vers Rafik : --tous-les-importes --vers=1 --dry-run");
        $this->comment('  Un seul cas              : --de=<id> --vers=1 --importes-seulement --dry-run');

        return self::SUCCESS;
    }
}
