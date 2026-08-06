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
    ): LengthAwarePaginator {
        $accessibleCaisseIds = $this->caisseOptions($user)->pluck('id')->all();

        $encaissements = Encaissement::query()
            ->with(['student', 'fee.inscription', 'caisse', 'agent'])
            ->whereIn('caisse_id', $accessibleCaisseIds)
            // Page view tabs (wimschool-style, read-only filters):
            // "cheque" = cheque payments; "avance" = payments whose fee is
            // still only partially settled (InscriptionFee's own statut).
            ->when($view === 'cheque', fn ($q) => $q->where('methode', Encaissement::METHODE_CHEQUE))
            ->when($view === 'avance', fn ($q) => $q->whereHas(
                'fee',
                fn ($f) => $f->where('statut', \App\Models\InscriptionFee::STATUT_PAYE_PARTIELLEMENT),
            ))
            ->when($caisseFilter !== '', fn ($q) => $q->where('caisse_id', (int) $caisseFilter))
            ->when($methodeFilter !== '', fn ($q) => $q->where('methode', $methodeFilter))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_paiement', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('date_paiement', '<=', $dateTo))
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
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $encaissements->through(fn (Encaissement $e): array => [
            'id' => $e->id,
            'reference' => $e->reference,
            'student' => $e->student?->nomComplet(),
            'studentId' => $e->student_id,
            'inscriptionId' => $e->fee?->inscription_id,
            'feeNom' => $e->fee?->nom,
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
            'showUrl' => route('backoffice.encaissements.show', $e),
        ]);

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
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
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

    private function scopeToActiveCenter($query): void
    {
        $id = $this->context->etablissementId();

        if ($id === null) {
            return;
        }

        $query->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
    }
}
