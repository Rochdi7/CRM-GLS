<?php

declare(strict_types=1);

namespace App\Domain\Students\Queries;

use App\Models\StudentTransfer;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Liste des demandes de transfert d'étudiants (25/09/2026).
 *
 * Portée : les demandes dont le centre SOURCE ou le centre CIBLE est dans
 * les « Centres affectés » de l'utilisateur, puis — si le sélecteur est
 * sur un centre — celles qui touchent CE centre, dans un sens ou dans
 * l'autre. Aucune fenêtre d'année : une demande en attente ne doit jamais
 * se cacher derrière le sélecteur d'année (même exception assumée que la
 * boîte de validation des transferts de caisse, §11).
 */
final class GetStudentTransfersList
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 25;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    public function __invoke(
        User $user,
        string $search = '',
        string $statutFilter = '',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): LengthAwarePaginator {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $employeeId = $user->employee?->id;
        $peutDecider = $user->can('student-transfers.validate');

        $transfers = $this->scoped($user)
            ->with([
                'student:id,reference,nom,prenom',
                'nouveauStudent:id,reference,nom,prenom',
                'etablissementSource:id,nom_centre',
                'etablissementCible:id,nom_centre',
                'groupeCible:id,nom,niveau',
                'nouvelleInscription:id,reference',
                'requestedBy:id,nom,prenom',
                'decidedBy:id,name',
            ])
            ->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('reference', 'ilike', "%{$search}%")
                ->orWhereHas('student', fn ($s) => $s
                    ->where('nom', 'ilike', "%{$search}%")
                    ->orWhere('prenom', 'ilike', "%{$search}%")
                    ->orWhere('reference', 'ilike', "%{$search}%"))))
            ->orderByRaw("CASE WHEN statut = 'En attente' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $transfers->through(fn (StudentTransfer $t): array => [
            'id' => $t->id,
            'reference' => $t->reference,
            'statut' => $t->statut,
            'student' => [
                'id' => $t->student_id,
                'reference' => $t->student?->reference,
                'nomComplet' => $t->student?->nomComplet() ?? '—',
            ],
            'nouveauStudent' => $t->nouveauStudent === null ? null : [
                'id' => $t->nouveauStudent->id,
                'reference' => $t->nouveauStudent->reference,
            ],
            'nouvelleInscription' => $t->nouvelleInscription === null ? null : [
                'id' => $t->nouvelleInscription->id,
                'reference' => $t->nouvelleInscription->reference,
            ],
            'centreSource' => $t->etablissementSource?->nom_centre,
            'centreCible' => $t->etablissementCible?->nom_centre,
            'groupeCible' => $t->groupeCible === null ? null : [
                'id' => $t->groupeCible->id,
                'nom' => $t->groupeCible->nom,
                'niveau' => $t->groupeCible->niveau,
            ],
            'motif' => $t->motif,
            'motifDecision' => $t->motif_decision,
            'montantTransfere' => $t->montant_transfere === null ? null : number_format((float) $t->montant_transfere, 2, '.', ''),
            'demandePar' => $t->requestedBy?->nomComplet(),
            'demandeLe' => $t->created_at?->format('d/m/Y H:i'),
            'decidePar' => $t->decidedBy?->name,
            'decideLe' => $t->decided_at?->format('d/m/Y H:i'),
            // Confort d'UI (§5) : la policy et l'action Domain revérifient.
            'canDecide' => $peutDecider && $t->estEnAttente(),
            'canCancel' => $t->estEnAttente()
                && ($peutDecider || ($employeeId !== null && (int) $t->requested_by === (int) $employeeId)),
        ]);

        return $transfers;
    }

    /**
     * Nombre de demandes « En attente » dans la MÊME portée que la liste —
     * badge de l'onglet « Transferts d'étudiants ». Même périmètre, sinon le
     * badge promet des lignes que le tableau ne montre pas.
     */
    public function enAttenteCount(User $user): int
    {
        return $this->scoped($user)
            ->where('statut', StudentTransfer::STATUT_EN_ATTENTE)
            ->count();
    }

    /**
     * Badges de la barre d'onglets Étudiants, clés = href de l'onglet
     * (Config/pageTabs.ts). Vide pour qui ne voit pas les transferts.
     *
     * @return array<string, int>
     */
    public function tabCounts(User $user): array
    {
        if (! $user->can('student-transfers.view')) {
            return [];
        }

        return ['/backoffice/student-transfers' => $this->enAttenteCount($user)];
    }

    /** Portée centre (centres affectés + centre actif), partagée par la liste et le compteur. */
    private function scoped(User $user): Builder
    {
        return StudentTransfer::query()
            ->when(! $this->centerAccess->hasGlobalAccess($user), function ($q) use ($user): void {
                $ids = $this->centerAccess->accessibleCenterIds($user);
                $q->where(fn ($w) => $w
                    ->whereIn('etablissement_source_id', $ids)
                    ->orWhereIn('etablissement_cible_id', $ids));
            })
            ->when($this->context->etablissementId(), fn ($q, $centreId) => $q->where(fn ($w) => $w
                ->where('etablissement_source_id', $centreId)
                ->orWhere('etablissement_cible_id', $centreId)));
    }
}
