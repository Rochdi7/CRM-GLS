<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Queries;

use App\Models\InscriptionCorbeille;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use App\Support\Access\HiddenAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Corbeille des inscriptions (19/10/2026) — les dossiers SUPPRIMÉS, tels
 * que `SupprimerInscription` les a photographiés, et QUI les a supprimés.
 *
 * Portée : la même que la liste Inscriptions — centres affectés, centre
 * actif, année active (la ligne porte l'`annee_scolaire_id` du dossier
 * effacé) — pour que « Inscriptions » et « Corbeille » parlent du même
 * périmètre. Le compteur par auteur (`parAuteur`) et le total sont
 * calculés sur TOUT l'ensemble filtré en SQL (jamais sur la page).
 */
final class GetInscriptionsCorbeille
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
        string $auteurFilter = '',
        string $dateFrom = '',
        string $dateTo = '',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): LengthAwarePaginator {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        return $this->filtered($user, $search, $auteurFilter, $dateFrom, $dateTo)
            ->with(['supprimePar:id,name,username,email'])
            ->orderByDesc('supprime_le')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (InscriptionCorbeille $row): array => [
                'id' => $row->id,
                'reference' => $row->reference,
                'student' => [
                    'id' => $row->student_id,
                    'reference' => $row->student_reference,
                    'nomComplet' => $row->student_nom ?? '—',
                ],
                'groupe' => $row->groupe_nom,
                'anneeScolaire' => $row->annee_nom,
                'etablissement' => $row->etablissement?->nom_centre,
                'statut' => $row->statut,
                'dateInscription' => $row->date_inscription?->format('d/m/Y'),
                'montantTotal' => $row->montant_total !== null
                    ? number_format((float) $row->montant_total, 2, '.', '')
                    : null,
                'paiementsDetaches' => collect($row->paiements_detaches ?? [])->map(fn (array $d): array => [
                    'reference' => $d['reference'] ?? null,
                    'montant' => $d['montant'] ?? '0.00',
                    'frais' => $d['frais'] ?? null,
                    'rembourse' => $d['rembourse'] ?? null,
                ])->values()->all(),
                // Le libellé FIGÉ à la suppression prime : le compte a pu être
                // renommé ou supprimé depuis.
                'supprimePar' => $row->supprime_par_label ?? $row->supprimePar?->name,
                'supprimeLe' => $row->supprime_le?->format('d/m/Y H:i'),
            ]);
    }

    /**
     * Combien de dossiers chaque personne a supprimés dans le périmètre
     * filtré — UNE requête GROUP BY, du plus actif au moins actif.
     *
     * @return list<array{userId: ?int, label: string, count: int}>
     */
    public function parAuteur(User $user, string $search = '', string $auteurFilter = '', string $dateFrom = '', string $dateTo = ''): array
    {
        return $this->filtered($user, $search, $auteurFilter, $dateFrom, $dateTo)
            ->toBase()
            ->selectRaw('supprime_par_user_id, MAX(supprime_par_label) AS label, COUNT(*) AS total')
            ->groupBy('supprime_par_user_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r): array => [
                'userId' => $r->supprime_par_user_id !== null ? (int) $r->supprime_par_user_id : null,
                'label' => (string) ($r->label ?? __('Unknown account')),
                'count' => (int) $r->total,
            ])
            ->values()
            ->all();
    }

    /** Nombre total de dossiers supprimés dans le périmètre filtré. */
    public function total(User $user, string $search = '', string $auteurFilter = '', string $dateFrom = '', string $dateTo = ''): int
    {
        return $this->filtered($user, $search, $auteurFilter, $dateFrom, $dateTo)->count();
    }

    /**
     * Options du filtre « Supprimé par » — les auteurs présents dans la
     * portée (centre + année), hors filtres, pour que le menu offre toujours
     * de quoi revenir en arrière.
     *
     * @return list<array{id: int, label: string}>
     */
    public function auteurOptions(User $user): array
    {
        return $this->scoped($user)
            ->toBase()
            ->whereNotNull('supprime_par_user_id')
            ->selectRaw('supprime_par_user_id, MAX(supprime_par_label) AS label')
            ->groupBy('supprime_par_user_id')
            ->orderBy('label')
            ->get()
            ->map(fn ($r): array => ['id' => (int) $r->supprime_par_user_id, 'label' => (string) $r->label])
            ->values()
            ->all();
    }

    /** Badge de l'onglet « Corbeille » : le total du périmètre, sans filtre. */
    public function tabCount(User $user): int
    {
        return $this->scoped($user)->count();
    }

    /**
     * @return Builder<InscriptionCorbeille>
     */
    private function filtered(User $user, string $search, string $auteurFilter, string $dateFrom, string $dateTo): Builder
    {
        return $this->scoped($user)
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('reference', 'ilike', "%{$search}%")
                        ->orWhere('student_nom', 'ilike', "%{$search}%")
                        ->orWhere('student_reference', 'ilike', "%{$search}%")
                        ->orWhere('groupe_nom', 'ilike', "%{$search}%");
                });
            })
            ->when($auteurFilter !== '', fn ($q) => $q->where('supprime_par_user_id', (int) $auteurFilter))
            ->when($dateFrom !== '', fn ($q) => $q->where('supprime_le', '>=', $dateFrom.' 00:00:00'))
            ->when($dateTo !== '', fn ($q) => $q->where('supprime_le', '<=', $dateTo.' 23:59:59'));
    }

    /**
     * @return Builder<InscriptionCorbeille>
     */
    private function scoped(User $user): Builder
    {
        return InscriptionCorbeille::query()
            ->with('etablissement:id,nom_centre')
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(function ($q): void {
                if (! $this->context->isAllCenters()) {
                    $q->where(fn ($sub) => $sub->whereNull('etablissement_id')->orWhere('etablissement_id', $this->context->etablissementId()));
                }
            })
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            // Le compte de maintenance n'apparaît jamais comme auteur (§11) —
            // résolu par `HiddenAccount::emails()`, jamais une adresse en dur.
            ->when(HiddenAccount::hides(), fn ($q) => $q->where(fn ($w) => $w
                ->whereNull('supprime_par_user_id')
                ->orWhereNotIn('supprime_par_user_id', User::query()->whereIn('email', HiddenAccount::emails())->select('id'))));
    }
}
