<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Cheque;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Remboursement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * READ-ONLY financial consistency check (24/08/2026 audit, CLAUDE.md §11).
 *
 * Prints every record that breaks one of the "one dirham = one `caisses`
 * row" invariants, so an operator can see the state of a database before /
 * after `caisse:recalculer-soldes`, after a restore, or on a schedule.
 * Nothing is ever changed here — repairs are separate, reviewed actions.
 *
 * Anomalies (exit code 1):
 *  A. an encaissement whose account disagrees with its `methode`
 *     (Espèces in a TPE account, TPE in a physical till…);
 *  B. a dépense sitting in a method account; a remboursement sitting in a
 *     method account that is not the reversal of a REJECTED cheque payment;
 *  C. a centre missing one of its three method accounts, or a method
 *     account without centre / with a responsable (the CHECK should make
 *     this impossible — reported in case the constraint was dropped);
 *  D. an employee with no physical till, or with more than one;
 *  E. a validated transfer with a non-cash end.
 *
 * Warnings (exit code 0 unless --strict):
 *  F. a negative balance (allowed by the business rules today — see the
 *     audit report's « Business decisions required »);
 *  G. a balance that does not equal the sum of its journaled movements, or
 *     a journal whose `solde_avant` does not chain from the previous
 *     `solde_apres`. Accounts that moved BEFORE CaisseLedger existed
 *     (raw increment() era) legitimately show a gap — the offset is
 *     printed so a genuine break can be told from history.
 */
final class VerifierCoherenceCaisses extends Command
{
    protected $signature = 'caisse:verifier-coherence
        {--strict : Also fail (exit 1) on warnings (negative balances, ledger gaps)}';

    protected $description = 'Audit (read-only) the caisses / encaissements / dépenses / remboursements / transfers for broken money invariants';

    public function handle(): int
    {
        $anomalies = 0;
        $warnings = 0;

        $anomalies += $this->section('A. Encaissements dont le compte ne correspond pas à la méthode', $this->encaissementsMalRoutes());
        $anomalies += $this->section('B. Dépenses / remboursements logés dans un compte de méthode', $this->sortiesDansCompteMethode());
        $anomalies += $this->section('C. Comptes de méthode manquants ou mal formés', $this->comptesMethodeIncoherents());
        $anomalies += $this->section('D. Employés sans caisse physique ou avec plusieurs', $this->caissesEmployesIncoherentes());
        $anomalies += $this->section('E. Transferts validés avec une extrémité non-espèces', $this->transfertsNonEspeces());

        $warnings += $this->section('F. Soldes négatifs (avertissement)', $this->soldesNegatifs());
        $warnings += $this->section('G. Écarts entre le solde et le journal des mouvements (avertissement)', $this->ecartsLedger());

        $this->newLine();

        if ($anomalies === 0 && $warnings === 0) {
            $this->info('Aucune anomalie : tous les invariants de caisse sont respectés.');

            return self::SUCCESS;
        }

        $this->line(sprintf('%d anomalie(s), %d avertissement(s).', $anomalies, $warnings));

        if ($anomalies > 0 || ($warnings > 0 && $this->option('strict'))) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return int number of rows reported
     */
    private function section(string $title, array $rows): int
    {
        $this->newLine();
        $this->line("<options=bold>{$title}</> — ".count($rows));

        if ($rows !== []) {
            $this->table(array_keys($rows[0]), $rows);
        }

        return count($rows);
    }

    /** @return list<array<string, string>> */
    private function encaissementsMalRoutes(): array
    {
        return Encaissement::query()
            ->with(['caisse', 'student'])
            ->whereHas('caisse', function ($q): void {
                $q->where(function ($w): void {
                    // Cash payment outside a cash account…
                    $w->where(fn ($c) => $c
                        ->whereColumn('encaissements.methode', '=', DB::raw("'".Encaissement::METHODE_ESPECES."'"))
                        ->whereNotIn('caisses.type', Caisse::TYPES_ESPECES))
                        // …or a non-cash payment not in the account of ITS method.
                        ->orWhere(fn ($c) => $c
                            ->where('encaissements.methode', '!=', Encaissement::METHODE_ESPECES)
                            ->whereColumn('caisses.type', '!=', 'encaissements.methode'));
                });
            })
            ->orderBy('id')
            ->get()
            ->map(fn (Encaissement $e): array => [
                'Référence' => $e->reference,
                'Méthode' => $e->methode,
                'Montant' => $this->money($e->montant),
                'Caisse' => (string) ($e->caisse?->nom ?? '—'),
                'Type caisse' => (string) ($e->caisse?->type ?? '—'),
                'Centre étudiant' => (string) ($e->student?->etablissement?->nom_centre ?? '—'),
                'Ligne d\'application' => $e->applied_from_encaissement_id !== null ? 'oui' : '',
            ])
            ->all();
    }

    /** @return list<array<string, string>> */
    private function sortiesDansCompteMethode(): array
    {
        $rows = [];

        Depense::query()
            ->with('caisse')
            ->whereHas('caisse', fn ($q) => $q->whereIn('type', Caisse::TYPES_METHODE))
            ->orderBy('id')
            ->get()
            ->each(function (Depense $d) use (&$rows): void {
                $rows[] = [
                    'Table' => 'depenses',
                    'Référence' => $d->reference,
                    'Montant' => $this->money($d->montant),
                    'Statut' => (string) $d->statut,
                    'Caisse' => (string) ($d->caisse?->nom ?? '—'),
                    'Raison' => 'Une dépense se règle toujours depuis la caisse physique.',
                ];
            });

        Remboursement::query()
            ->with(['caisse', 'encaissement.cheque'])
            ->whereHas('caisse', fn ($q) => $q->whereIn('type', Caisse::TYPES_METHODE))
            ->orderBy('id')
            ->get()
            ->each(function (Remboursement $r) use (&$rows): void {
                $legit = $r->encaissement !== null
                    && $r->encaissement->cheque !== null
                    && $r->encaissement->cheque->statut === Cheque::STATUT_REJETE
                    && (int) $r->encaissement->caisse_id === (int) $r->caisse_id;

                if ($legit) {
                    return; // CaisseResolver::forRemboursement()'s one exception.
                }

                $rows[] = [
                    'Table' => 'remboursements',
                    'Référence' => $r->reference,
                    'Montant' => $this->money($r->montant),
                    'Statut' => '—',
                    'Caisse' => (string) ($r->caisse?->nom ?? '—'),
                    'Raison' => 'Un remboursement se règle depuis la caisse physique (sauf reprise d\'un chèque rejeté).',
                ];
            });

        return $rows;
    }

    /** @return list<array<string, string>> */
    private function comptesMethodeIncoherents(): array
    {
        $rows = [];

        foreach (Etablissement::query()->orderBy('id')->get() as $centre) {
            foreach (Caisse::TYPES_METHODE as $methode) {
                $n = Caisse::query()->where('etablissement_id', $centre->id)->where('type', $methode)->count();

                if ($n !== 1) {
                    $rows[] = [
                        'Centre' => $centre->nom_centre,
                        'Type' => $methode,
                        'Problème' => $n === 0 ? 'compte manquant' : "{$n} comptes (doublon)",
                    ];
                }
            }
        }

        Caisse::query()
            ->whereIn('type', Caisse::TYPES_METHODE)
            ->where(fn ($q) => $q->whereNull('etablissement_id')->orWhereNotNull('responsable_employee_id'))
            ->orderBy('id')
            ->get()
            ->each(function (Caisse $c) use (&$rows): void {
                $rows[] = [
                    'Centre' => '—',
                    'Type' => $c->type,
                    'Problème' => "compte #{$c->id} « {$c->nom} » : ".($c->etablissement_id === null ? 'sans centre' : 'avec un responsable'),
                ];
            });

        return $rows;
    }

    /** @return list<array<string, string>> */
    private function caissesEmployesIncoherentes(): array
    {
        return Employee::query()
            ->withoutGlobalScopes()
            ->withCount(['caisses as tills_count' => fn ($q) => $q->where('type', Caisse::TYPE_CAISSIERE)])
            // PostgreSQL cannot HAVING on a select alias without GROUP BY —
            // the filter is applied on the hydrated collection instead
            // (staff table: a few dozen rows).
            ->orderBy('id')
            ->get()
            ->filter(fn (Employee $e): bool => (int) $e->tills_count !== 1)
            ->values()
            ->map(fn (Employee $e): array => [
                'Employé' => "#{$e->id} ".$e->nomComplet(),
                'Caisses physiques' => (string) $e->tills_count,
                'Problème' => (int) $e->tills_count === 0
                    ? 'aucune caisse (caisses:provision la créera)'
                    : 'plusieurs caisses — à fusionner à la main',
            ])
            ->all();
    }

    /** @return list<array<string, string>> */
    private function transfertsNonEspeces(): array
    {
        return CaisseTransfer::query()
            ->with(['caisseSource', 'caisseDestination'])
            ->where('statut', CaisseTransfer::STATUT_VALIDE)
            ->where(fn ($q) => $q
                ->whereHas('caisseSource', fn ($c) => $c->whereNotIn('type', Caisse::TYPES_ESPECES))
                ->orWhereHas('caisseDestination', fn ($c) => $c->whereNotIn('type', Caisse::TYPES_ESPECES)))
            ->orderBy('id')
            ->get()
            ->map(fn (CaisseTransfer $t): array => [
                'Référence' => $t->reference,
                'Montant' => $this->money($t->montant),
                'Source' => (string) ($t->caisseSource?->nom ?? '—').' ('.($t->caisseSource?->type ?? '—').')',
                'Destination' => (string) ($t->caisseDestination?->nom ?? '—').' ('.($t->caisseDestination?->type ?? '—').')',
            ])
            ->all();
    }

    /** @return list<array<string, string>> */
    private function soldesNegatifs(): array
    {
        return Caisse::query()
            ->with('etablissement')
            ->where('solde', '<', 0)
            ->orderBy('solde')
            ->get()
            ->map(fn (Caisse $c): array => [
                'Caisse' => $c->nom,
                'Type' => $c->type,
                'Centre' => (string) ($c->etablissement?->nom_centre ?? '—'),
                'Solde' => $this->money($c->solde),
            ])
            ->all();
    }

    /**
     * Rebuilds every balance from its `solde_movement` journal entries
     * (CaisseLedger writes them with solde_avant / montant / solde_apres) and
     * compares with the stored solde; also checks that entries chain.
     *
     * @return list<array<string, string>>
     */
    private function ecartsLedger(): array
    {
        $rows = [];

        foreach (Caisse::query()->orderBy('id')->get() as $caisse) {
            $entries = Activity::query()
                ->where('log_name', 'caisse')
                ->where('event', 'solde_movement')
                ->where('subject_id', $caisse->id)
                ->orderBy('id')
                ->get();

            $net = 0.0;
            $chainBreaks = 0;
            $expectedAvant = null;

            foreach ($entries as $entry) {
                $p = $entry->properties;
                $montant = (float) ($p['montant'] ?? 0);
                $net += ($p['sens'] ?? '') === 'Entrée' ? $montant : -$montant;

                $avant = (float) ($p['solde_avant'] ?? 0);
                $apres = (float) ($p['solde_apres'] ?? 0);

                if ($expectedAvant !== null && abs($avant - $expectedAvant) > 0.005) {
                    $chainBreaks++;
                }

                $expectedAvant = $apres;
            }

            $net = round($net, 2);
            $solde = round((float) $caisse->solde, 2);
            $ecart = round($solde - $net, 2);

            if (abs($ecart) > 0.005 || $chainBreaks > 0) {
                $rows[] = [
                    'Caisse' => $caisse->nom,
                    'Type' => $caisse->type,
                    'Solde stocké' => $this->money($solde),
                    'Σ journal' => $this->money($net),
                    'Écart' => $this->money($ecart),
                    'Mouvements' => (string) $entries->count(),
                    'Ruptures de chaîne' => (string) $chainBreaks,
                ];
            }
        }

        return $rows;
    }

    private function money(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
