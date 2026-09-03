<?php

declare(strict_types=1);

namespace App\Domain\Reports\Queries;

use App\Models\Group;
use App\Models\Inscription;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * « Liste des inscriptions » — le premier rapport de Gestion des rapports.
 *
 * Read-model UNIQUE des trois sorties (aperçu écran, PDF, Excel) : les trois
 * appellent ce même invocable, donc le document imprimé ne peut jamais
 * différer de ce que l'utilisateur a vu à l'écran avant de cliquer
 * « Télécharger » — la règle que suit déjà ExporterMatriceAbsences.
 *
 * Portée (CLAUDE.md §11 « Context scoping is MANDATORY ») : centres
 * accessibles via CenterAccessService, puis le CENTRE actif du sélecteur, puis
 * l'ANNÉE active. Un rapport n'a aucun privilège de lecture : il n'imprime que
 * des lignes que le titulaire pouvait déjà ouvrir dans la liste Inscriptions.
 *
 * Le filtre GROUPE est OPTIONNEL (demande métier) : vide, le rapport sort
 * toutes les inscriptions de la fenêtre de dates ; renseigné, il se limite à
 * ce groupe. La fenêtre de dates porte sur `date_inscription` — la date à
 * laquelle le dossier a été ouvert, qui est la colonne « Date d'inscription »
 * du document.
 *
 * ⚠ Pas de pagination : un rapport est un document complet. C'est voulu, et
 * c'est aussi pourquoi la fenêtre de dates est OBLIGATOIRE côté requête (le
 * contrôleur la remplit toujours) — elle borne le volume.
 */
final class GetInscriptionsReport
{
    /** Clé du rapport dans le sélecteur « Rapport » de l'onglet Inscriptions. */
    public const KEY = 'liste-inscriptions';

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * Les lignes du document, dans l'ordre du rapport de référence :
     * par date d'inscription croissante, puis par référence.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function __invoke(
        User $user,
        string $dateFrom,
        string $dateTo,
        string $groupFilter = '',
        string $statutFilter = '',
    ): Collection {
        $rows = $this->baseQuery($user, $dateFrom, $dateTo, $groupFilter, $statutFilter)
            ->with(['student:id,nom,prenom,telephone', 'group:id,nom'])
            // Numérotation et lecture du document : le plus ancien dossier en
            // premier, comme le rapport de référence (N° 1 = 04/08/2026).
            ->orderBy('date_inscription')
            ->orderBy('reference')
            ->get([
                'id', 'reference', 'student_id', 'group_id', 'statut',
                'date_inscription', 'date_debut', 'date_fin',
            ]);

        return $rows->values()->map(fn (Inscription $inscription, int $index): array => [
            // Le N° est le rang DANS LE DOCUMENT (1..n), pas un id : c'est ce
            // que la colonne « N° » du rapport de référence affiche.
            'numero' => $index + 1,
            'reference' => (string) $inscription->reference,
            'etudiant' => $inscription->student?->nomComplet() ?? '',
            'telephone' => (string) ($inscription->student?->telephone ?? ''),
            'groupe' => (string) ($inscription->group?->nom ?? ''),
            'statut' => (string) $inscription->statut,
            'dateInscription' => $this->formatDate($inscription->date_inscription),
            'dateDebut' => $this->formatDate($inscription->date_debut),
            'dateFin' => $this->formatDate($inscription->date_fin),
        ]);
    }

    /**
     * Nombre de lignes que le document contiendra — sert à l'aperçu de la page
     * (« n inscriptions ») sans rapatrier les lignes elles-mêmes.
     */
    public function count(
        User $user,
        string $dateFrom,
        string $dateTo,
        string $groupFilter = '',
        string $statutFilter = '',
    ): int {
        return $this->baseQuery($user, $dateFrom, $dateTo, $groupFilter, $statutFilter)->count();
    }

    /**
     * Les groupes proposés par le sélecteur « Groupe » — mêmes centres et même
     * année que la requête du rapport, donc l'option choisie ne peut jamais
     * viser un groupe hors contexte.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function groupOptions(User $user): array
    {
        return Group::query()
            ->tap(fn (Builder $q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->when($this->context->anneeScolaireId(), fn (Builder $q, $y) => $q->where('annee_scolaire_id', $y))
            ->tap(fn (Builder $q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Group $g): array => ['value' => (string) $g->id, 'label' => (string) $g->nom])
            ->all();
    }

    /** @return Builder<Inscription> */
    private function baseQuery(
        User $user,
        string $dateFrom,
        string $dateTo,
        string $groupFilter,
        string $statutFilter,
    ): Builder {
        return Inscription::query()
            ->tap(fn (Builder $q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn (Builder $q) => $this->scopeToActiveCenter($q))
            ->when($this->context->anneeScolaireId(), fn (Builder $q, $y) => $q->where('annee_scolaire_id', $y))
            // Plage sargable sur une colonne date indexée (CLAUDE.md §17
            // « Date-query rules ») — jamais whereDate()/DATE_FORMAT.
            ->when($dateFrom !== '', fn (Builder $q) => $q->where('date_inscription', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $q) => $q->where('date_inscription', '<=', $dateTo))
            ->when($groupFilter !== '', fn (Builder $q) => $q->where('group_id', (int) $groupFilter))
            ->when($statutFilter !== '', fn (Builder $q) => $q->where('statut', $statutFilter));
    }

    /**
     * Le centre du sélecteur du haut. Une inscription sans centre (ligne
     * héritée) reste visible, comme dans GetInscriptionsList — le rapport ne
     * cache pas ce que la liste montre.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    private function scopeToActiveCenter(Builder $query): void
    {
        if ($this->context->isAllCenters()) {
            return;
        }

        $query->where(fn ($sub) => $sub
            ->whereNull('etablissement_id')
            ->orWhere('etablissement_id', $this->context->etablissementId()));
    }

    private function formatDate(mixed $date): string
    {
        return $date instanceof \DateTimeInterface ? $date->format('d/m/Y') : '';
    }
}
