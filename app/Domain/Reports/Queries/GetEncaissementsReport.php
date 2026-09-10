<?php

declare(strict_types=1);

namespace App\Domain\Reports\Queries;

use App\Models\Caisse;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use App\Support\Access\HiddenAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * « Relevé des encaissements » — le rapport de l'onglet Finance & Paiements.
 *
 * Read-model UNIQUE des trois sorties (compteur écran, PDF, Excel), comme
 * GetInscriptionsReport : le document imprimé ne peut pas différer de ce que
 * l'utilisateur vient de voir.
 *
 * ⚠ LES AVANCES SONT IMPRIMÉES, et se lisent comme telles (demande du
 * 10/09/2026 : « if there is avances show avance »). Une avance est de
 * l'argent REÇU — la caisse a bougé — simplement pas encore affecté à un
 * frais. L'exclure d'un relevé d'encaissements ferait un document dont le
 * total est inférieur à ce que la caisse a effectivement encaissé, signé et
 * tamponné comme s'il était complet. Trois conséquences, portées par la
 * colonne « Type » :
 *
 *  - `Avance` — `inscription_fee_id` NULL : argent reçu, pas encore affecté.
 *    La colonne « Frais » ne peut rien nommer, elle porte « Avance » plutôt
 *    qu'un tiret, exactement comme la liste Encaissements (29/08/2026) ;
 *  - `Règlement` — la ligne ordinaire, posée sur un frais ;
 *  - une ligne d'APPLICATION d'avance (`applied_from_encaissement_id` non
 *    NULL) n'est PAS listée : elle ne fait que reposer sur un frais l'argent
 *    d'une avance déjà comptée, la caisse n'a jamais bougé pour elle.
 *    L'imprimer compterait le même dirham deux fois (1 300 d'avance + 300 +
 *    1 000 appliqués = 2 600 au bas d'un relevé qui n'a encaissé que 1 300).
 *    C'est la MÊME règle que l'onglet « Encaissements » de la liste, et c'est
 *    ce qui rend le total du document égal à l'argent réellement entré.
 *
 * Portée (CLAUDE.md §11) : centres accessibles via CenterAccessService, puis
 * le CENTRE actif, puis l'ANNÉE active — le centre d'un paiement est celui de
 * son ÉTUDIANT (ou de l'inscription de son frais), jamais celui de la caisse
 * où il a atterri : c'est déjà la règle de GetEncaissementsList, et la
 * contredire ici viderait le relevé d'un centre dont l'opérateur est rattaché
 * ailleurs. Un rapport n'a aucun privilège de lecture.
 *
 * ⚠ Pas de pagination : un rapport est un document complet, borné par la
 * fenêtre de dates (que le contrôleur remplit toujours).
 */
final class GetEncaissementsReport
{
    /** Clé du rapport dans le sélecteur « Rapport ». */
    public const KEY = 'releve-encaissements';

    /** Les types imprimés dans la colonne « Type » — et les valeurs du filtre. */
    public const TYPE_REGLEMENT = 'reglement';

    public const TYPE_AVANCE = 'avance';

    /** @var list<string> */
    public const TYPES = [self::TYPE_REGLEMENT, self::TYPE_AVANCE];

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * Les lignes du document, dans l'ordre du relevé de référence : par date
     * de paiement croissante, puis par référence — le N° 1 est le plus ancien
     * encaissement de la période.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function __invoke(
        User $user,
        string $dateFrom,
        string $dateTo,
        string $methodeFilter = '',
        string $caisseFilter = '',
        string $typeFilter = '',
    ): Collection {
        $rows = $this->baseQuery($user, $dateFrom, $dateTo, $methodeFilter, $caisseFilter, $typeFilter)
            ->with([
                'student:id,nom,prenom',
                // Le frais réglé et son inscription : le nom du frais est ce
                // que la colonne « Frais » imprime, le groupe sert au libellé
                // des lignes de règlement.
                'fee:id,inscription_id,nom',
                'fee.inscription:id,group_id',
                'fee.inscription.group:id,nom',
                'agent:id,nom,prenom',
            ])
            ->orderBy('date_paiement')
            ->orderBy('reference')
            ->get([
                'id', 'reference', 'student_id', 'inscription_fee_id',
                'montant', 'methode', 'date_paiement', 'agent_id',
            ]);

        return $rows->values()->map(function (Encaissement $e, int $index): array {
            $estAvance = $e->inscription_fee_id === null;

            return [
                // Le N° est le rang DANS LE DOCUMENT (1..n), pas un id —
                // comme la colonne « N° d'ordre » du relevé de référence.
                'numero' => $index + 1,
                'reference' => (string) $e->reference,
                'etudiant' => $e->student?->nomComplet() ?? '',
                // La colonne qui porte la demande : une avance se DIT avance,
                // partout où elle apparaît dans le document.
                'type' => $estAvance ? 'Avance' : 'Règlement',
                // Formaté ici, une seule fois, pour que le PDF et le classeur
                // impriment le même montant (le gabarit ne recalcule rien).
                'montant' => number_format((float) $e->montant, 2, ',', ' ').' DH',
                // Le montant NUMÉRIQUE, pour le total du document : le
                // gabarit ne ré-analyse jamais la chaîne formatée ci-dessus.
                'montantBrut' => (float) $e->montant,
                'methode' => (string) $e->methode,
                // Une avance n'est sur aucun frais : la colonne le dit au lieu
                // de laisser un blanc qu'on lirait comme une donnée manquante.
                'frais' => $estAvance ? 'Avance' : (string) ($e->fee?->nom ?? ''),
                'groupe' => (string) ($e->fee?->inscription?->group?->nom ?? ''),
                'date' => $this->formatDate($e->date_paiement),
                'operateur' => $e->agent?->nomComplet() ?? '',
            ];
        });
    }

    /**
     * Nombre de lignes du document — l'aperçu de la page, sans rapatrier les
     * lignes elles-mêmes.
     */
    public function count(
        User $user,
        string $dateFrom,
        string $dateTo,
        string $methodeFilter = '',
        string $caisseFilter = '',
        string $typeFilter = '',
    ): int {
        return $this->baseQuery($user, $dateFrom, $dateTo, $methodeFilter, $caisseFilter, $typeFilter)->count();
    }

    /**
     * Le total encaissé sur la période filtrée, formaté.
     *
     * Calculé en SQL sur TOUT l'ensemble filtré, jamais en additionnant les
     * lignes rendues (CLAUDE.md : « a header Total comes from the server over
     * the filtered set »). Il est égal à la somme de la colonne « Montant »
     * du document parce que les lignes d'application d'avance sont exclues de
     * la requête — sans cette exclusion, le total dépasserait l'argent
     * réellement entré en caisse.
     */
    public function total(
        User $user,
        string $dateFrom,
        string $dateTo,
        string $methodeFilter = '',
        string $caisseFilter = '',
        string $typeFilter = '',
    ): string {
        $montant = (float) $this->baseQuery($user, $dateFrom, $dateTo, $methodeFilter, $caisseFilter, $typeFilter)
            ->sum('encaissements.montant');

        return number_format($montant, 2, ',', ' ').' DH';
    }

    /**
     * Les caisses proposées par le filtre « Caisse » — mêmes centres que le
     * rapport, et passées par HiddenAccount : le compte de maintenance ne
     * s'offre pas dans un sélecteur (CLAUDE.md §11, « EVERY list, dropdown,
     * lookup and total »).
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function caisseOptions(User $user): array
    {
        return Caisse::query()
            ->tap(fn (Builder $q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn (Builder $q) => HiddenAccount::hideCaisses($q))
            ->when(
                $this->context->etablissementId(),
                fn (Builder $q, $centreId) => $q->where(fn ($w) => $w
                    ->whereNull('etablissement_id')
                    ->orWhere('etablissement_id', $centreId)),
            )
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Caisse $c): array => ['value' => (string) $c->id, 'label' => (string) $c->nom])
            ->all();
    }

    /** @return Builder<Encaissement> */
    private function baseQuery(
        User $user,
        string $dateFrom,
        string $dateTo,
        string $methodeFilter,
        string $caisseFilter,
        string $typeFilter,
    ): Builder {
        return Encaissement::query()
            // ⚠ Les lignes d'APPLICATION d'avance sont exclues — voir l'entête
            // de classe : elles reposent l'argent d'une avance déjà comptée
            // sur un frais, la caisse n'a pas bougé pour elles. Sans ce
            // filtre, le total du relevé dépasse l'argent réellement encaissé.
            ->whereNull('applied_from_encaissement_id')
            // Le centre d'un paiement est celui de son ÉTUDIANT, jamais celui
            // de la caisse — même règle que GetEncaissementsList.
            ->whereHas('student', fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->when($this->context->etablissementId(), fn ($q, $centreId) => $q->where(function ($w) use ($centreId): void {
                $w->whereHas('student', fn ($s) => $s->where('etablissement_id', $centreId)->orWhereNull('etablissement_id'))
                    ->orWhereHas('fee.inscription', fn ($i) => $i->where('etablissement_id', $centreId));
            }))
            // L'année d'un paiement est celle de l'inscription de son frais.
            // Une AVANCE n'a pas de frais, donc pas d'année : elle reste
            // listée quelle que soit la sienne, comme dans la liste
            // Encaissements — c'est de l'argent reçu et non affecté, et la
            // fenêtre de dates du rapport la borne déjà. Une fenêtre d'année
            // qui la masquerait ferait disparaître du relevé de l'argent bien
            // réel (CLAUDE.md §11, « Deliberate exceptions »).
            ->when($this->context->anneeScolaireId(), fn ($q, $anneeId) => $q->where(function ($w) use ($anneeId): void {
                $w->whereHas('fee.inscription', fn ($i) => $i->where('annee_scolaire_id', $anneeId))
                    ->orWhereNull('inscription_fee_id');
            }))
            // Plage sargable sur une colonne DATE indexée (CLAUDE.md §17) —
            // jamais whereDate(), qui enveloppe la colonne dans un cast.
            ->when($dateFrom !== '', fn (Builder $q) => $q->where('date_paiement', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $q) => $q->where('date_paiement', '<=', $dateTo))
            ->when($methodeFilter !== '', fn (Builder $q) => $q->where('methode', $methodeFilter))
            ->when($caisseFilter !== '', fn (Builder $q) => $q->where('caisse_id', (int) $caisseFilter))
            // Le filtre « Type » repose sur la MÊME colonne que la colonne
            // imprimée (inscription_fee_id), donc filtrer « Avance » sort
            // exactement les lignes que le document marque « Avance ».
            ->when($typeFilter === self::TYPE_AVANCE, fn (Builder $q) => $q->whereNull('inscription_fee_id'))
            ->when($typeFilter === self::TYPE_REGLEMENT, fn (Builder $q) => $q->whereNotNull('inscription_fee_id'));
    }

    private function formatDate(mixed $date): string
    {
        return $date instanceof \DateTimeInterface ? $date->format('d/m/Y') : '';
    }
}
