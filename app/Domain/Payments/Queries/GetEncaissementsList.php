<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Models\Caisse;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\Student;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
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
    public const DEFAULT_PER_PAGE = 15;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

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
    ): LengthAwarePaginator {
        $encaissements = Encaissement::query()
            ->with(['student', 'fee.inscription', 'caisse', 'agent'])
            // Per-fee paid total, computed by the DB (no N+1): feeds the
            // edit modal's read-only "Reste à payer" figure.
            ->with(['fee' => fn ($q) => $q->withSum('encaissements', 'montant')])
            ->when($view === 'avance', fn ($q) => $q->withSum('applications', 'montant'))
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
            ->when(
                $this->context->etablissementId(),
                fn ($q, $centreId) => $q->whereHas('student', fn ($s) => $s->where('etablissement_id', $centreId)),
            )
            // Year of a payment = the academic year of the inscription its
            // fee belongs to (like the Inscriptions list). An avance has no
            // fee — and therefore no inscription — so it is matched by its
            // payment date falling inside the selected year instead.
            ->when($this->context->anneeScolaire(), function ($q, $annee): void {
                $q->where(function ($sub) use ($annee): void {
                    $sub->whereHas('fee.inscription', fn ($i) => $i->where('annee_scolaire_id', $annee->id))
                        ->orWhere(fn ($w) => $w
                            ->whereNull('inscription_fee_id')
                            ->whereBetween('date_paiement', [
                                $annee->date_debut->toDateString(),
                                $annee->date_fin->toDateString(),
                            ]));
                });
            })
            // Page view tabs (wimschool-style, read-only filters): "cheque" =
            // cheque payments; "avance" = unallocated advances — payments
            // with NO fee attached (Encaissement::isAvance()): fresh avances
            // AND rows later detached from their fee by
            // ConvertirEncaissementsEnAvance / ChangerGroupeInscription.
            ->when($view === 'cheque', fn ($q) => $q->where('methode', Encaissement::METHODE_CHEQUE))
            ->when($view === 'avance', fn ($q) => $q->whereNull('inscription_fee_id'))
            // Default "Paiements" tab: only rows allocated to a fee. An avance
            // is the PARENT of the "apply" rows that later credit each fee
            // (applied_from_encaissement_id) — listing it alongside them would
            // show the same money twice (1300 avance + 300 + 1000 applied).
            // Avances live under their own tab; the Chèques tab keeps them
            // because it tracks every cheque's échéance, allocated or not.
            ->when($view === '', fn ($q) => $q->whereNotNull('inscription_fee_id'))
            ->when($caisseFilter !== '', fn ($q) => $q->where('caisse_id', (int) $caisseFilter))
            ->when($methodeFilter !== '', fn ($q) => $q->where('methode', $methodeFilter))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_paiement', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('date_paiement', '<=', $dateTo))
            ->when($referenceFilter !== '', fn ($q) => $q->where('reference', 'ilike', "%{$referenceFilter}%"))
            ->when($studentFilter !== '', fn ($q) => $q->where('student_id', (int) $studentFilter))
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
            ->orderByDesc('date_paiement')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
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

        return $encaissements;
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
            ->get()
            ->map(fn (Caisse $c): array => ['id' => $c->id, 'nom' => $c->nom]);
    }

    /**
     * @return Collection<int, array{id:int, nom:string}>
     */
    public function studentOptions(User $user): Collection
    {
        return Student::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->get()
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
