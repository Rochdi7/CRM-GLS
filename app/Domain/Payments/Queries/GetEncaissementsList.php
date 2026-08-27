<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Models\Caisse;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Student;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-model for the Encaissements list — extracted verbatim from
 * EncaissementsIndex::render(). The `$accessibleCaisseIds` set used for the
 * list scope is deliberately NOT restricted to Active tills (a comment in
 * the original component explains why: a payment recorded while its till
 * was active must remain visible after the till is later deactivated) —
 * preserved here exactly via `caisseOptions(activesOnly: false)`.
 */
final class GetEncaissementsList
{
    public const DEFAULT_PER_PAGE = 10;

    /** SQL for an avance's remaining balance: montant − applied to fees − refunded. */
    private const AVANCE_RESTANT_SQL = '(encaissements.montant'
        .' - coalesce((select sum(a.montant) from encaissements a'
        .' where a.applied_from_encaissement_id = encaissements.id), 0)'
        .' - coalesce((select sum(r.montant) from remboursements r'
        .' where r.encaissement_id = encaissements.id), 0))';

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @return array{data: LengthAwarePaginator, montantTotal: string}
     */
    public function __invoke(
        User $user,
        string $search = '',
        string $caisseFilter = '',
        string $methodeFilter = '',
        string $dateFrom = '',
        string $dateTo = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        string $view = '',
        string $referenceFilter = '',
        string $studentFilter = '',
        string $numeroChequeFilter = '',
        string $banqueFilter = '',
        string $soldeFilter = '',
        string $groupFilter = '',
    ): array {
        $base = Encaissement::query()
            // What has been given back on this payment (Remboursement.
            // encaissement_id). A FULLY refunded payment is money that is no
            // longer there, so it leaves the Paiements / Chèques tabs
            // (24/08/2026) — the row itself is never deleted, it stays on the
            // student page, the caisse journal and the audit trail. A partial
            // refund keeps the row, with the refunded part shown.
            ->withSum('remboursements as remboursements_total', 'montant')
            ->when($view !== 'avance', fn ($q) => $q->whereRaw(
                'coalesce((select sum(r.montant) from remboursements r where r.encaissement_id = encaissements.id), 0) < encaissements.montant',
            ))
            // Centre of a payment = centre of the STUDENT it is for, never
            // the centre of the till it landed in. `encaissements` has no
            // etablissement_id precisely because "the centre is reached via
            // student / inscription" (create_encaissements_table migration).
            //
            // Scoping by the till instead made every payment collected by an
            // operator whose till lives in another centre invisible: the
            // legacy import books each row into the mapped opérateur's own
            // till (CaisseProvisioner puts it in that employee's PRIMARY
            // centre), so an Agadir import done by a Marrakech-based
            // operator produced an empty Agadir Encaissements page while the
            // same rows showed on the student's inscription.
            ->whereHas('student', fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            // Active centre: the student's centre, OR the centre of the
            // inscription the fee belongs to, OR a centre-less (global)
            // student — such a student is visible in every centre
            // (CenterAccessService treats NULL as global), so hiding their
            // payments behind the switcher left the page empty for money
            // that was just collected here (24/08/2026).
            ->when($this->context->etablissementId(), fn ($q, $centreId) => $q->where(function ($w) use ($centreId): void {
                $w->whereHas('student', fn ($s) => $s->where('etablissement_id', $centreId)->orWhereNull('etablissement_id'))
                    ->orWhereHas('fee.inscription', fn ($i) => $i->where('etablissement_id', $centreId));
            }))
            // Year of a payment = the academic year of the inscription its
            // fee belongs to (like the Inscriptions list). An avance has no
            // fee — and therefore no inscription — so it is matched by its
            // payment date falling inside the selected year instead.
            //
            // ⚠ An EXPLICIT date filter overrides that year window, the same
            // way GetChequesList treats date_echeance. Without this the
            // window was unconditional, so an avance dated outside the year
            // could not be reached at all: clearing both date fields left it
            // hidden with no way to ask for it (two RAZANE ZOUINE avances of
            // 11/07/2025, invisible under 2025/2026 which opens on 01/09,
            // 26/08/2026).
            //
            // The Avances tab is exempt entirely: an avance is money received
            // and NOT yet allocated, so it stays outstanding until someone
            // applies it — hiding one because it arrived before the current
            // year opened makes real, unspent money unreachable. It is listed
            // in full, like the transfer-validation inbox (CLAUDE.md §11
            // "Deliberate exceptions").
            ->when(
                $view !== 'avance'
                    && $this->context->anneeScolaire()
                    && ! self::isIsoDate($dateFrom)
                    && ! self::isIsoDate($dateTo),
                function ($q): void {
                    $annee = $this->context->anneeScolaire();

                    $q->where(function ($sub) use ($annee): void {
                        $sub->whereHas('fee.inscription', fn ($i) => $i->where('annee_scolaire_id', $annee->id))
                            ->orWhere(fn ($w) => $w
                                ->whereNull('inscription_fee_id')
                                ->whereBetween('date_paiement', [
                                    $annee->date_debut->toDateString(),
                                    $annee->date_fin->toDateString(),
                                ]));
                    });
                }
            )
            // Page view tabs (wimschool-style, read-only filters): "cheque" =
            // cheque payments; "avance" = unallocated advances — payments
            // with NO fee attached (Encaissement::isAvance()): fresh avances
            // AND rows later detached from their fee by
            // ConvertirEncaissementsEnAvance / ChangerGroupeInscription.
            ->when($view === 'cheque', fn ($q) => $q->where('methode', Encaissement::METHODE_CHEQUE))
            ->when($view === 'avance', fn ($q) => $q->whereNull('inscription_fee_id'))
            // Avances tab « Solde » filter: 'restant' = money still left
            // (montant − applied − refunded > 0; the default: what a cashier
            // has to allocate), 'epuise' = fully used/refunded (history).
            // Same arithmetic as the "Montant restant" column and
            // sumAvancesRestantes(), evaluated in SQL — never a per-row
            // accessor in a loop.
            ->when($view === 'avance' && in_array($soldeFilter, ['restant', 'epuise'], true), fn ($q) => $q->whereRaw(
                self::AVANCE_RESTANT_SQL.($soldeFilter === 'restant' ? ' > 0' : ' <= 0'),
            ))
            // Default "Paiements" tab: only rows allocated to a fee. An avance
            // is the PARENT of the "apply" rows that later credit each fee
            // (applied_from_encaissement_id) — listing it alongside them would
            // show the same money twice (1300 avance + 300 + 1000 applied).
            // Avances live under their own tab; the Chèques tab keeps them
            // because it tracks every cheque's échéance, allocated or not.
            ->when($view === '', fn ($q) => $q->whereNotNull('inscription_fee_id'))
            ->when($caisseFilter !== '', fn ($q) => $q->where('caisse_id', (int) $caisseFilter))
            ->when($methodeFilter !== '', fn ($q) => $q->where('methode', $methodeFilter))
            // `date_paiement` is a DATE column: a plain comparison keeps the
            // index usable, whereas whereDate() wraps the column in a cast.
            // Only well-formed Y-m-d values are applied (no PG type error on
            // a tampered query string).
            ->when(self::isIsoDate($dateFrom), fn ($q) => $q->where('date_paiement', '>=', $dateFrom))
            ->when(self::isIsoDate($dateTo), fn ($q) => $q->where('date_paiement', '<=', $dateTo))
            ->when($referenceFilter !== '', fn ($q) => $q->where('reference', 'ilike', "%{$referenceFilter}%"))
            ->when($studentFilter !== '', fn ($q) => $q->where('student_id', (int) $studentFilter))
            // Groupe: a fee-allocated payment belongs to the group of the
            // inscription its fee is on; an avance has no fee, so it belongs
            // to the groups its student is enrolled in (that is where the
            // money will end up being applied).
            ->when($groupFilter !== '', fn ($q) => $q->where(function ($w) use ($groupFilter): void {
                $groupId = (int) $groupFilter;
                $w->whereHas('fee.inscription', fn ($i) => $i->where('group_id', $groupId))
                    ->orWhere(fn ($a) => $a
                        ->whereNull('inscription_fee_id')
                        ->whereHas('student.inscriptions', fn ($i) => $i->where('group_id', $groupId)));
            }))
            ->when($numeroChequeFilter !== '', fn ($q) => $q->where('numero_cheque', 'ilike', "%{$numeroChequeFilter}%"))
            ->when($banqueFilter !== '', fn ($q) => $q->where('banque', 'ilike', "%{$banqueFilter}%"))
            ->when($search !== '', function ($q) use ($search): void {
                $term = "%{$search}%";
                $q->where(function ($sub) use ($term): void {
                    $sub->where('reference', 'ilike', $term)
                        ->orWhereHas('student', fn ($s) => $s
                            ->where('nom', 'ilike', $term)
                            ->orWhere('prenom', 'ilike', $term)
                            ->orWhere('reference', 'ilike', $term));
                });
            })
            // Latest recorded first — the row just saved is always on top,
            // whatever payment date was typed.
            ->orderByDesc('id');

        // Sum over every row matching the current filters/tab (not just the
        // page shown) — mirrors GetDepensesList's montantTotal so every
        // finance list states the total it represents, not just the visible
        // page's subtotal.
        //
        // The Avances tab totals what is still AVAILABLE, not what was once
        // received: an avance's remaining is montant − what has been applied
        // to fees (applications) − what was refunded, the same arithmetic the
        // per-row "Montant restant" column shows. Summing `montant` there
        // announced money that is already spent (26/08/2026). Computed as ONE
        // aggregate over the filtered set — never a per-row accessor in a
        // loop (CLAUDE.md §17 "read models").
        $montantTotal = $view === 'avance'
            ? $this->sumAvancesRestantes(clone $base)
            : (clone $base)->sum('montant');

        $encaissements = (clone $base)
            ->with(['student', 'fee.inscription', 'caisse', 'agent'])
            // Per-fee paid total, computed by the DB (no N+1): feeds the
            // edit modal's read-only "Reste à payer" figure.
            ->with(['fee' => fn ($q) => $q->withSum('encaissements', 'montant')])
            ->when($view === 'avance', fn ($q) => $q->withSum('applications', 'montant'))
            ->paginate($perPage)
            ->withQueryString();

        $encaissements->through(function (Encaissement $e) use ($view): array {
            $utilise = $view === 'avance' ? (float) ($e->applications_sum_montant ?? 0) : null;
            $feeTotal = $e->fee !== null ? (float) $e->fee->montant : null;
            $feePaye = $e->fee !== null ? (float) ($e->fee->encaissements_sum_montant ?? 0) : null;

            return [
                'id' => $e->id,
                'reference' => $e->reference,
                'student' => $e->student?->nomComplet(),
                'studentRef' => $e->student?->reference,
                'studentId' => $e->student_id,
                'inscriptionId' => $e->fee?->inscription_id,
                'feeNom' => $e->fee?->nom,
                'feeMontantTotal' => $feeTotal !== null ? number_format($feeTotal, 2, '.', '') : null,
                'feeReste' => $feeTotal !== null ? number_format(max(0.0, $feeTotal - $feePaye), 2, '.', '') : null,
                'caisse' => $e->caisse?->nom,
                'caisseId' => $e->caisse_id,
                'montant' => number_format((float) $e->montant, 2, '.', ''),
                'montantRembourse' => number_format((float) ($e->remboursements_total ?? 0), 2, '.', ''),
                'methode' => $e->methode,
                'datePaiement' => $e->date_paiement?->toDateString(),
                'numeroCheque' => $e->numero_cheque,
                'banque' => $e->banque,
                'dateEcheanceCheque' => $e->date_echeance_cheque?->toDateString(),
                'note' => $e->note,
                'agent' => $e->agent?->nomComplet(),
                'montantUtilise' => $utilise !== null ? number_format($utilise, 2, '.', '') : null,
                'montantRestant' => $utilise !== null ? number_format(max(0.0, (float) $e->montant - $utilise), 2, '.', '') : null,
                'studentEmail' => $e->student?->email,
                'showUrl' => route('backoffice.encaissements.show', $e),
                'recuUrl' => route('backoffice.encaissements.recu', $e),
                'recuEmailUrl' => route('backoffice.encaissements.recu.email', $e),
            ];
        });

        return [
            'data' => $encaissements,
            'montantTotal' => number_format((float) $montantTotal, 2, '.', ''),
        ];
    }

    /**
     * Total STILL AVAILABLE across the filtered avances: montant minus what
     * has been applied to fees minus what was refunded — the per-row
     * "Montant restant" column, summed by the database in one aggregate
     * (never a per-row accessor in a loop, CLAUDE.md §17 "read models").
     *
     * The inherited select AND order are dropped first: an aggregate query
     * cannot also carry `encaissements.*` with its correlated `withSum`
     * subquery, nor `order by id` — PostgreSQL rejects both with "column
     * encaissements.id must appear in the GROUP BY clause". A refund on an
     * avance never exceeds what is left, so the per-row result cannot go
     * negative.
     *
     * @param  Builder<Encaissement>  $query  the filtered avances
     */
    private function sumAvancesRestantes(Builder $query): float
    {
        $query->getQuery()->columns = null;
        $query->getQuery()->orders = null;

        return (float) $query
            ->selectRaw('coalesce(sum'.self::AVANCE_RESTANT_SQL.', 0) as total')
            ->value('total');
    }

    /**
     * Every accessible till — NOT restricted to Active status (matches the
     * original component's deliberate choice, see class docblock).
     *
     * @return Collection<int, array{id:int, nom:string}>
     */
    public function caisseOptions(User $user): Collection
    {
        return Caisse::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->where(function ($q): void {
                $this->scopeToActiveCenter($q);

                // The list is scoped by the student's centre, so it can show
                // rows sitting in a till of another centre. Offering those
                // tills here keeps the filter able to reach every displayed
                // row instead of silently emptying the page.
                $q->orWhereHas('encaissements.student', fn ($s) => $this->scopeStudentsToActiveCenter($s));
            })
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Caisse $c): array => ['id' => $c->id, 'nom' => $c->nom]);
    }

    private static function isIsoDate(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 && strtotime($value) !== false;
    }

    /**
     * @return Collection<int, array{id:int, nom:string}>
     */
    /**
     * Groups of the active centre + année, for the « Groupe » filter.
     *
     * @return Collection<int, array{id:int, nom:string}>
     */
    public function groupOptions(User $user): Collection
    {
        return Group::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Group $g): array => ['id' => $g->id, 'nom' => $g->nom]);
    }

    public function studentOptions(User $user): Collection
    {
        return Student::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->orderBy('prenom')
            // Only the three columns the option needs — this list is every
            // student of the centre, so hydrating full rows (photo, phones,
            // parent fields…) was pure waste.
            ->get(['id', 'nom', 'prenom'])
            ->map(fn (Student $s): array => ['id' => $s->id, 'nom' => $s->nomComplet()]);
    }

    /**
     * A student's registrations in the active academic year — mirrors
     * EncaissementsIndex::render()'s `inscriptions` cascade data exactly.
     *
     * @return Collection<int, array{id:int, label:string}>
     */
    public function studentInscriptions(int $studentId): Collection
    {
        return Inscription::query()
            ->with('group')
            ->where('student_id', $studentId)
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->get()
            ->map(fn (Inscription $i): array => [
                'id' => $i->id,
                'label' => $i->reference.' — '.($i->group?->nom ?? '—'),
            ]);
    }

    /** Active-centre scope on a `students` query (students always carry a centre). */
    private function scopeStudentsToActiveCenter($query): void
    {
        $id = $this->context->etablissementId();

        if ($id === null) {
            return;
        }

        $query->where('etablissement_id', $id);
    }

    private function scopeToActiveCenter($query): void
    {
        $id = $this->context->etablissementId();

        if ($id === null) {
            return;
        }

        $query->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
    }
}
