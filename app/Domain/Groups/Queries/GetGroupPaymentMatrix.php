<?php

declare(strict_types=1);

namespace App\Domain\Groups\Queries;

use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\MotifAnnulation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * "Détails paiement" — the group's payment matrix (Statistique de groupe).
 *
 * One ROW per inscription of the group, one COLUMN per fee assigned to the
 * group (ordered by due date, earliest first), one CELL per
 * inscription × fee holding what the student actually paid on that line.
 *
 * Row colour = the inscription's own statut:
 *   Active     → white   (étudiant actif)
 *   Changement → grey    (parti vers un autre groupe)
 *   Annulée    → red     (inscription annulée)
 *
 * Cell state (mirrors the legacy CRM's Excel export exactly):
 *   'absent'  → grey    the fee line is NOT on this student's inscription
 *                       (never added, or removed on a group change) — nothing
 *                       is owed, nothing is displayed.
 *   'paye'    → green   fully settled (reste <= 0).
 *   'partiel' → orange  something was paid but a remainder is still due.
 *   'impaye'  → red     the line is assigned and 0 DH has been paid — this is
 *                       what "encore en recouvrement" means.
 *
 * Masked lines (inscription_fees.masque_le) are excluded like everywhere else,
 * so a hidden fee reads as 'absent' rather than as an unpaid debt.
 *
 * Performance: two queries total whatever the row count — the fee lines are
 * loaded in ONE withSum('encaissements','montant') pass (never the per-row
 * InscriptionFee::montantPaye() accessor, see CLAUDE.md §17 "Read models
 * never call a per-row money accessor in a loop").
 */
final class GetGroupPaymentMatrix
{
    public const SORT_NOM = 'nom';

    public const SORT_DATE = 'date';

    public const SORT_NOM_DESC = 'nom_desc';

    public const SORTS = [self::SORT_NOM, self::SORT_DATE, self::SORT_NOM_DESC];

    /**
     * @return array{
     *     columns: list<array{key: string, nom: string, dateEcheance: ?string,
     *                         dateEcheanceIso: ?string, classification: ?string,
     *                         montant: string, total: string}>,
     *     rows: list<array<string, mixed>>,
     *     totals: array{parColonne: array<string, string>, general: string},
     *     sort: string,
     * }
     */
    public function __invoke(Group $group, string $sort = self::SORT_NOM): array
    {
        if (! in_array($sort, self::SORTS, true)) {
            $sort = self::SORT_NOM;
        }

        $columns = $this->columns($group);
        $columnKeys = array_column($columns, 'key');

        $inscriptions = Inscription::query()
            ->with('student')
            ->where('group_id', $group->id)
            ->get();

        $feesByInscription = InscriptionFee::query()
            ->whereIn('inscription_id', $inscriptions->pluck('id'))
            ->whereNull('masque_le')
            ->withSum('encaissements', 'montant')
            ->get()
            ->groupBy('inscription_id');

        $rows = $this->rows($inscriptions, $feesByInscription, $columnKeys);
        $rows = $this->sortRows($rows, $sort);

        // Renumber AFTER sorting so #1 is always the first visible line.
        foreach ($rows as $index => $row) {
            $rows[$index]['numero'] = '#'.($index + 1);
        }

        [$parColonne, $general] = $this->totals($rows, $columnKeys);

        foreach ($columns as $index => $column) {
            $columns[$index]['total'] = $parColonne[$column['key']];
        }

        return [
            'columns' => $columns,
            'rows' => array_values($rows),
            'totals' => ['parColonne' => $parColonne, 'general' => $general],
            'sort' => $sort,
        ];
    }

    /**
     * The group's fee columns in the order the legacy « Statistique de
     * groupe » screen used, which is the one the cashiers read by:
     * the one-off charges a student settles UP FRONT first — inscription,
     * exam, annuel: the fees carrying no due date — then the monthly
     * instalments in school-year order, earliest échéance first
     * (Septembre → Août, not Janvier → Décembre; the year each month falls
     * in comes from FraisEcheanceResolver).
     *
     * @return list<array{key: string, nom: string, dateEcheance: ?string,
     *                    dateEcheanceIso: ?string, classification: ?string,
     *                    montant: string, total: string}>
     */
    private function columns(Group $group): array
    {
        $columns = $group->frais
            ->map(fn ($fee): array => [
                'key' => (string) $fee->id,
                'nom' => $fee->nom,
                'dateEcheance' => $fee->pivot->date_echeance
                    ? Carbon::parse($fee->pivot->date_echeance)->format('d/m/Y')
                    : null,
                'dateEcheanceIso' => $fee->pivot->date_echeance
                    ? Carbon::parse($fee->pivot->date_echeance)->format('Y-m-d')
                    : null,
                'classification' => $fee->pivot->classification,
                'montant' => $this->money((float) $fee->pivot->montant),
                'total' => '0.00',
            ])
            ->values()
            ->all();

        // Dateless fees FIRST (rank 0), then the dated ones by échéance.
        // Sorting on the raw value instead would work by accident — PHP puts
        // null before any string — but says nothing about the intent, and a
        // dateless fee is deliberately at the top, not merely unsorted.
        usort($columns, fn (array $a, array $b): int => [
            $a['dateEcheanceIso'] === null ? 0 : 1, $a['dateEcheanceIso'] ?? '', $a['nom'],
        ] <=> [
            $b['dateEcheanceIso'] === null ? 0 : 1, $b['dateEcheanceIso'] ?? '', $b['nom'],
        ]);

        return $columns;
    }

    /**
     * @param  Collection<int, Inscription>  $inscriptions
     * @param  Collection<int, Collection<int, InscriptionFee>>  $feesByInscription
     * @param  list<string>  $columnKeys
     * @return list<array<string, mixed>>
     */
    private function rows(Collection $inscriptions, Collection $feesByInscription, array $columnKeys): array
    {
        return $inscriptions
            ->map(function (Inscription $inscription) use ($feesByInscription, $columnKeys): array {
                $cells = [];
                $total = 0.0;
                $reste = 0.0;

                /** @var Collection<int, InscriptionFee> $fees */
                $fees = $feesByInscription->get($inscription->id, collect());

                foreach ($fees as $fee) {
                    $du = (float) $fee->montant;
                    $paye = (float) ($fee->encaissements_sum_montant ?? 0);

                    $total += $paye;
                    $reste += max(0.0, $du - $paye);

                    // A line whose frais_id is not one of the group's columns
                    // (carried over from another group, or a hand-added line)
                    // has nowhere to render — it still counts in the row total.
                    $key = $fee->frais_id !== null ? (string) $fee->frais_id : null;

                    if ($key === null || ! in_array($key, $columnKeys, true)) {
                        continue;
                    }

                    // Two lines for the same fee on one inscription should not
                    // happen, but if they do, merge rather than drop.
                    if (isset($cells[$key])) {
                        $du += (float) $cells[$key]['du'];
                        $paye += (float) $cells[$key]['montant'];
                    }

                    $restant = max(0.0, $du - $paye);

                    $cells[$key] = [
                        'state' => $this->state($paye, $restant),
                        'montant' => $this->money($paye),
                        'du' => $this->money($du),
                        'reste' => $this->money($restant),
                    ];
                }

                return [
                    'key' => (string) $inscription->id,
                    'numero' => '',
                    'student' => $inscription->student?->nomComplet(),
                    'studentShowUrl' => $inscription->student
                        ? route('backoffice.students.show', $inscription->student)
                        : null,
                    'reference' => $inscription->reference,
                    'statut' => $inscription->statut,
                    // Why the enrollment ended, so the row's own tooltip
                    // answers it: a red or grey line raises the question
                    // « annulée / partie pourquoi ? » and the answer was only
                    // reachable by leaving the matrix for the inscription
                    // page. NULL on an Active row (nothing to explain).
                    //
                    // A « Changement » carries no stored motif — the statut IS
                    // the reason, and ChangerGroupeInscription never writes
                    // one — so it falls back to the catalogue's own name for
                    // that move rather than leaving the line unexplained.
                    'motifAnnulation' => $inscription->statut === Inscription::STATUT_CHANGEMENT
                        ? ($inscription->motif_annulation ?? MotifAnnulation::MOTIF_CHANGEMENT_GROUPE)
                        : $inscription->motif_annulation,
                    'dateFin' => $inscription->date_fin?->format('d/m/Y'),
                    // The note the cancellation appended to the enrollment.
                    // AnnulerInscription APPENDS it to whatever note the row
                    // already had, so this is the enrollment note as a whole —
                    // shown as-is, never parsed apart.
                    'note' => $inscription->note,
                    'dateInscription' => $inscription->date_inscription?->format('d/m/Y'),
                    'dateInscriptionIso' => $inscription->date_inscription?->format('Y-m-d'),
                    'total' => $this->money($total),
                    'reste' => $this->money($reste),
                    'cells' => $cells,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Green when nothing is left to pay, orange when part of it is, red when
     * the line is assigned and untouched. A 0 DH fee counts as settled.
     */
    private function state(float $paye, float $restant): string
    {
        if ($restant <= 0.004) {
            return 'paye';
        }

        return $paye > 0.004 ? 'partiel' : 'impaye';
    }

    /**
     * Reading order of the status blocks. Rows are GROUPED by statut first and
     * only sorted within their block, so the list always reads: every active
     * student, then the archived ones, then the cancelled ones. A cashier
     * scanning for who still owes money should never have to step over a
     * cancelled inscription to reach the next active one — and, sorting by
     * date or by reste, a cancelled row would otherwise land anywhere.
     *
     * Anything not listed here sorts after the known blocks, in the order the
     * statut names collate, so a new Inscription statut can never silently
     * interleave with the active students.
     */
    private const STATUT_ORDRE = [
        Inscription::STATUT_ACTIVE => 0,
        Inscription::STATUT_CHANGEMENT => 1,
        Inscription::STATUT_EXPIREE => 2,
        Inscription::STATUT_ARCHIVEE => 3,
        Inscription::STATUT_ANNULEE => 4,
    ];

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sortRows(array $rows, string $sort): array
    {
        usort($rows, function (array $a, array $b) use ($sort): int {
            // The status block always wins — see STATUT_ORDRE. The chosen
            // sort only orders rows INSIDE a block.
            $bloc = $this->rangStatut((string) $a['statut']) <=> $this->rangStatut((string) $b['statut']);

            if ($bloc !== 0) {
                return $bloc;
            }

            $nom = strcoll((string) $a['student'], (string) $b['student']);

            return match ($sort) {
                self::SORT_NOM_DESC => -$nom,
                self::SORT_DATE => (($a['dateInscriptionIso'] ?? '9999-12-31')
                    <=> ($b['dateInscriptionIso'] ?? '9999-12-31')) ?: $nom,
                default => $nom,
            };
        });

        return $rows;
    }

    /**
     * Position of a statut's block, with unknown values pushed past every
     * known one (never interleaved with the active students).
     */
    private function rangStatut(string $statut): int
    {
        return self::STATUT_ORDRE[$statut] ?? count(self::STATUT_ORDRE);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $columnKeys
     * @return array{0: array<string, string>, 1: string}
     */
    private function totals(array $rows, array $columnKeys): array
    {
        $parColonne = array_fill_keys($columnKeys, 0.0);
        $general = 0.0;

        foreach ($rows as $row) {
            foreach ($row['cells'] as $key => $cell) {
                $parColonne[$key] = ($parColonne[$key] ?? 0.0) + (float) $cell['montant'];
            }

            $general += (float) $row['total'];
        }

        return [array_map(fn (float $v): string => $this->money($v), $parColonne), $this->money($general)];
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
