<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Models\Activity;
use App\Models\Caisse;
use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use App\Support\Access\HiddenAccount;
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
            // ⚠ AVANCES ARE EXEMPT FROM THE YEAR WINDOW — on EVERY tab, not
            // just the Avances one. An avance is money received and NOT yet
            // allocated, so it stays outstanding until someone applies it;
            // hiding one because it arrived before the current year opened
            // makes real, unspent money unreachable (CLAUDE.md §11
            // "Deliberate exceptions", like the transfer-validation inbox).
            //
            // Keying that exemption on the TAB ($view !== 'avance') instead
            // of on the ROW was a bug (30/08/2026): on the Encaissements tab
            // an avance was still date-windowed, so clearing « Date de fin »
            // — which must only ever WIDEN a result set — emptied the list.
            // A student filtered to 5 200 MAD of payments showed 0.00 MAD the
            // moment the date came off, because every remaining row was
            // either an avance dated outside the active year or a fee of the
            // previous one. A cleared filter must never REMOVE rows.
            ->when(
                $this->context->anneeScolaire()
                    && ! self::isIsoDate($dateFrom)
                    && ! self::isIsoDate($dateTo),
                function ($q): void {
                    $annee = $this->context->anneeScolaire();

                    $q->where(function ($sub) use ($annee): void {
                        $sub->whereHas('fee.inscription', fn ($i) => $i->where('annee_scolaire_id', $annee->id))
                            // No fee = an avance: always listed, whatever its date.
                            ->orWhereNull('inscription_fee_id');
                    });
                }
            )
            // Page view tabs (wimschool-style, read-only filters): "cheque" =
            // cheque payments; "avance" = unallocated advances — payments
            // with NO fee attached (Encaissement::isAvance()): fresh avances
            // AND rows later detached from their fee by
            // ConvertirEncaissementsEnAvance / ChangerGroupeInscription.
            ->when($view === 'cheque', fn ($q) => $q->where('methode', Encaissement::METHODE_CHEQUE))
            // A detached application row (applied_from set, fee NULL) IS
            // listed: its parent counts it as used, so it is the only place
            // that money is still available (28/08/2026).
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
            // Default "Encaissements" tab = money RECEIVED: every original
            // row, whether allocated to a fee or still an avance (the Frais
            // column then reads « Avance » so a cashier sees at a glance that
            // this money is not on any fee, 29/08/2026). What is excluded is
            // the "apply" rows (applied_from_encaissement_id set): they only
            // re-allocate their parent avance's money to a fee, the till never
            // moved for them — listing parent AND applications would show the
            // same money twice (1300 avance + 300 + 1000 applied). Their
            // allocation is visible on the Avances tab (montant utilisé) and
            // on the inscription's fee lines. The Chèques tab keeps every
            // cheque row because it tracks each échéance, allocated or not.
            ->when($view === '', fn ($q) => $q->whereNull('applied_from_encaissement_id'))
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
            // `cheque` feeds the per-row `applicable` flag below — a bounced
            // cheque's money cannot be applied, and the UI must know that
            // without asking the DB once per row.
            ->with(['student', 'fee.inscription', 'caisse', 'agent', 'cheque:id,statut'])
            // Per-fee paid total, computed by the DB (no N+1): feeds the
            // edit modal's read-only "Reste à payer" figure.
            ->with(['fee' => fn ($q) => $q->withSum('encaissements', 'montant')])
            // One correlated SUM, never a per-row accessor: feeds "Montant
            // utilisé / restant" on the Avances tab and the « Avance » cell of
            // the Encaissements tab (an avance there shows what is applied).
            ->when($view !== 'cheque', fn ($q) => $q->withSum('applications', 'montant'))
            ->paginate($perPage)
            ->withQueryString();

        $anciensFrais = $view === 'avance'
            ? $this->anciensFrais($encaissements->getCollection()->pluck('id')->all())
            : [];

        // Applied-to detail for every avance on the page, on BOTH tabs: an
        // avance is listed on the Encaissements tab too (it is money
        // received), and the question "applied to what?" is the same there.
        $fraisAppliques = $view !== 'cheque'
            ? $this->fraisAppliques($encaissements->getCollection()->pluck('id')->all())
            : [];

        $encaissements->through(function (Encaissement $e) use ($view, $anciensFrais, $fraisAppliques): array {
            $isAvance = $e->inscription_fee_id === null;
            $utilise = $isAvance && $view !== 'cheque' ? (float) ($e->applications_sum_montant ?? 0) : null;

            // ⚠ Having a remaining balance is NOT the same as being
            // applicable. AppliquerAvance refuses an avance funded by a
            // cheque the bank REJECTED (audit DB-05: the Chèque account was
            // reversed, so that money never existed) — but montantRestant()
            // knows nothing about the cheque, so the row still reported its
            // full amount and « Appliquer à un frais » was offered on money
            // that could only ever fail. The flag carries the ACTION's own
            // rule to the UI instead of letting it re-derive one from the
            // amount. Refunding such a row stays allowed — that reversal is
            // the intended remedy, and it debits the Chèque account, not the
            // till (CaisseResolver::forRemboursement).
            $chequeRejete = $e->cheque_id !== null
                && $e->cheque?->statut === Cheque::STATUT_REJETE;
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
                'isAvance' => $isAvance,
                // Avances tab only: the fee this money sat on before it was
                // detached (changement de groupe, annulation, conversion) —
                // null for an avance that was received as such.
                'ancienFrais' => $anciensFrais[$e->id]['frais'] ?? null,
                'ancienFraisGroupe' => $anciensFrais[$e->id]['groupe'] ?? null,
                // The fee lines this avance was applied to (empty when none),
                // so the list can name them instead of only totalling them.
                'fraisAppliques' => $isAvance ? ($fraisAppliques[$e->id] ?? []) : [],
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
                // Whether AppliquerAvance would accept this row at all.
                'applicable' => $isAvance && ! $chequeRejete,
                'chequeRejete' => $chequeRejete,
                'studentEmail' => $e->student?->email,
                'showUrl' => route('backoffice.encaissements.show', $e),
                'recuUrl' => route('backoffice.encaissements.recu', $e),
                'recuEmailUrl' => route('backoffice.encaissements.recu.email', $e),
                'recuWhatsAppUrl' => route('backoffice.encaissements.recu.whatsapp', $e),
            ];
        });

        return [
            'data' => $encaissements,
            'montantTotal' => number_format((float) $montantTotal, 2, '.', ''),
        ];
    }

    /**
     * « Ancien frais » of detached avances: the fee each row was allocated to
     * before ConvertirEncaissementsEnAvance / ChangerGroupeInscription /
     * AnnulerInscription set inscription_fee_id to NULL. The row itself no
     * longer knows (money records are edited in place, never copied), but
     * every one of those writes goes through Eloquent `update()` on an
     * Auditable model, so the journal holds « inscription_fee_id : X → vide »
     * for it (spatie v5 `attribute_changes` column). The LATEST such entry per row wins (a payment can be detached
     * more than once across group changes).
     *
     * Two queries for the whole page, whatever its size — never one per row.
     *
     * @param  list<int>  $encaissementIds
     * @return array<int, array{frais: string, groupe: string|null}>
     */
    private function anciensFrais(array $encaissementIds): array
    {
        if ($encaissementIds === []) {
            return [];
        }

        $feeIdByEncaissement = Activity::query()
            ->where('subject_type', Encaissement::class)
            ->whereIn('subject_id', $encaissementIds)
            // spatie v5 keeps the model diff in `attribute_changes`
            // (`properties` is the free-form bag of custom events).
            ->whereRaw("attribute_changes->'old'->>'inscription_fee_id' is not null")
            ->whereRaw("attribute_changes->'attributes'->>'inscription_fee_id' is null")
            ->orderByDesc('id')
            ->get(['subject_id', 'attribute_changes'])
            // Latest entry first, so the first one seen per row is kept.
            ->reduce(function (array $carry, Activity $a): array {
                $carry[(int) $a->subject_id] ??= (int) $a->attribute_changes['old']['inscription_fee_id'];

                return $carry;
            }, []);

        if ($feeIdByEncaissement === []) {
            return [];
        }

        $fees = InscriptionFee::query()
            ->with('inscription.group')
            ->whereIn('id', array_unique(array_values($feeIdByEncaissement)))
            ->get()
            ->keyBy('id');

        $result = [];

        foreach ($feeIdByEncaissement as $encaissementId => $feeId) {
            $fee = $fees->get($feeId);

            if ($fee === null) {
                continue;
            }

            $result[$encaissementId] = [
                'frais' => $fee->nom,
                'groupe' => $fee->inscription?->group?->nom,
            ];
        }

        return $result;
    }

    /**
     * Per avance, the fee lines its money was actually applied to — what the
     * « Appliquée : X MAD » caption on the list summarises in one number.
     * Without it the cell says money left the avance but never where it went,
     * which is the whole question a cashier is asking.
     *
     * ONE query for the whole page (same batching discipline as
     * anciensFrais() above) — never a relation read per row, which is exactly
     * the N+1 the read-model rule forbids (CLAUDE.md §17).
     *
     * @param  list<int>  $encaissementIds
     * @return array<int, list<array{frais: string, groupe: ?string, montant: string, date: ?string}>>
     */
    private function fraisAppliques(array $encaissementIds): array
    {
        if ($encaissementIds === []) {
            return [];
        }

        $applications = Encaissement::query()
            ->whereIn('applied_from_encaissement_id', $encaissementIds)
            ->with(['fee.inscription.group'])
            ->orderBy('date_paiement')
            ->get();

        $result = [];

        foreach ($applications as $application) {
            $result[(int) $application->applied_from_encaissement_id][] = [
                'frais' => $application->fee?->nom ?? __('Unlinked fee'),
                'groupe' => $application->fee?->inscription?->group?->nom,
                'montant' => number_format((float) $application->montant, 2, '.', ''),
                'date' => $application->date_paiement?->toDateString(),
            ];
        }

        return $result;
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
     * ⚠ The « Caisse » filter of the Encaissements / Avances page lists only
     * the tills a cashier can actually collect money into (30/08/2026):
     *
     *  - PHYSICAL cash tills only (Caissière / Externe) — the centre-level
     *    TPE / Chèque / Virement accounts are an accounting destination, not
     *    a caisse a user picks between, so they are dropped from the list.
     *  - a teacher's own till is dropped too: an Enseignant never cashes a
     *    student in, so its till only bloated a dropdown of ~40 names.
     *
     * @return Collection<int, array{id:int, nom:string}>
     */
    public function caisseOptions(User $user): Collection
    {
        return Caisse::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->whereIn('type', Caisse::TYPES_ESPECES)
            // The maintainer's till is never an option (HiddenAccount).
            ->tap(fn ($q) => HiddenAccount::hideCaisses($q))
            ->whereDoesntHave('responsable', fn ($e) => $e->where('categorie', Employee::CATEGORIE_ENSEIGNANT))
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
            // Seule une inscription ACTIVE se paie. Une inscription annulée,
            // archivée, expirée ou remplacée par un changement de groupe n'a
            // plus de frais dus : l'argent reçu pour un tel dossier est une
            // avance (elle sera appliquée à une inscription active), jamais un
            // encaissement sur ses frais. Le garde serveur correspondant est
            // EncaissementController::assertInscriptionPayable().
            ->where('statut', Inscription::STATUT_ACTIVE)
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->get()
            ->map(fn (Inscription $i): array => [
                'id' => $i->id,
                'label' => $i->reference.' — '.($i->group?->nom ?? '—'),
            ]);
    }

    /**
     * A student's registrations for the "Convertir en avance" modal — the
     * ACTIVE-YEAR filter is kept (the switcher decides which year's dossiers
     * are on screen, so converting an old year's payments means switching to
     * that year first), but the statut filter is deliberately DROPPED:
     * converting frees the money of a CLOSED dossier (annulée, archivée,
     * expirée, changement de groupe) into reusable avances — the exact
     * opposite of studentInscriptions(), which lists only payable (Active)
     * dossiers. The statut is appended to the label so the cashier can tell
     * a closed dossier from the live one.
     *
     * @return Collection<int, array{id:int, label:string}>
     */
    public function studentInscriptionsForConversion(int $studentId): Collection
    {
        return Inscription::query()
            ->with('group')
            ->where('student_id', $studentId)
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->get()
            ->map(fn (Inscription $i): array => [
                'id' => $i->id,
                'label' => $i->reference.' — '.($i->group?->nom ?? '—')
                    .($i->statut === Inscription::STATUT_ACTIVE ? '' : ' ('.$i->statut.')'),
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
