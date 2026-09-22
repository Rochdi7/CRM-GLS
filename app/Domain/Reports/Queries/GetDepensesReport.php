<?php

declare(strict_types=1);

namespace App\Domain\Reports\Queries;

use App\Domain\Finance\Support\VentilationCentre;
use App\Models\Depense;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * « Liste des dépenses » — le rapport de l'onglet Dépenses.
 *
 * Read-model UNIQUE des trois sorties (compteur écran, PDF, Excel), comme
 * GetEncaissementsReport : le document imprimé ne peut pas différer de ce que
 * l'utilisateur vient de voir.
 *
 * ⚠ Ce rapport est le PENDANT de la liste « Gestion des dépenses », et il en
 * reprend les règles au lieu de les redériver (CLAUDE.md §5) :
 *
 *  - **le centre d'une dépense est celui où elle a été SAISIE**, résolu par
 *    `VentilationCentre::scopeDepensesAuCentre()` (groupe si « Paiement
 *    prof », sinon `depenses.etablissement_id`, sinon la caisse pour les
 *    lignes antérieures à la colonne — 18/09/2026). Jamais
 *    `caisses.etablissement_id` seul : une employée n'a qu'UNE caisse,
 *    rattachée à son centre principal, donc un rapport filtré par la caisse
 *    listerait sous ce centre-là une dépense saisie ailleurs — et
 *    contredirait la liste qui, elle, l'a bien classée ;
 *  - **« argent sorti » a UNE seule définition, `statut = Approuvée »** : le
 *    TOTAL du document ne compte que les dépenses approuvées. Les lignes
 *    « En attente » (l'argent est encore dans le tiroir), « Refusée » (il n'a
 *    jamais bougé) et « Annulée » (il est revenu par écriture compensatoire)
 *    restent IMPRIMÉES — une dépense refusée ou annulée fait partie de ce
 *    qu'un contrôleur vient vérifier, et l'omettre ferait un document qui ne
 *    permet pas d'expliquer pourquoi une somme demandée n'est pas sortie —
 *    mais la colonne « Statut » les nomme et le total les écarte. Sans cette
 *    borne, le relevé annoncerait une sortie de caisse supérieure à ce que
 *    les tiroirs ont réellement payé.
 *
 * Portée (CLAUDE.md §11) : centres accessibles via CenterAccessService, puis
 * le CENTRE actif — les deux par la règle de ventilation ci-dessus. L'ANNÉE
 * active n'est pas appliquée : une dépense ne porte aucune FK d'année, sa
 * fenêtre est une fenêtre de DATES, et le rapport en impose déjà une (le
 * contrôleur la remplit toujours). Un rapport n'a aucun privilège de lecture.
 *
 * ⚠ Pas de pagination : un rapport est un document complet, borné par la
 * fenêtre de dates.
 */
final class GetDepensesReport
{
    /** Clé du rapport dans le sélecteur « Rapport ». */
    public const KEY = 'liste-depenses';

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
        private readonly VentilationCentre $ventilation,
    ) {}

    /**
     * Les lignes du document, par date de dépense croissante puis référence —
     * le N° 1 est la plus ancienne dépense de la période.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function __invoke(
        User $user,
        string $dateFrom,
        string $dateTo,
        string $typeFilter = '',
        string $statutFilter = '',
    ): Collection {
        $rows = $this->baseQuery($user, $dateFrom, $dateTo, $typeFilter, $statutFilter)
            ->with([
                'typeDepense:id,nom',
                'caisse:id,nom',
                'agent:id,nom,prenom',
                // Les trois chaînes de `centreNom()` : sans elles la colonne
                // Centre déclencherait une requête PAR LIGNE (§17 perf).
                'group:id,nom,etablissement_id',
                'group.etablissement:id,nom_centre',
                'etablissement:id,nom_centre',
                'caisse.etablissement:id,nom_centre',
            ])
            ->orderBy('date_depense')
            ->orderBy('reference')
            ->get();

        return $rows->values()->map(function (Depense $d, int $index): array {
            return [
                // Le N° est le rang DANS LE DOCUMENT (1..n), pas un id.
                'numero' => $index + 1,
                'reference' => (string) $d->reference,
                'type' => (string) ($d->typeDepense?->nom ?? ''),
                'description' => (string) $d->description,
                // Formaté ici, une seule fois, pour que le PDF et le classeur
                // impriment le même montant (le gabarit ne recalcule rien).
                'montant' => number_format((float) $d->montant, 2, ',', ' ').' DH',
                // Le montant NUMÉRIQUE : le gabarit ne ré-analyse jamais la
                // chaîne formatée ci-dessus.
                'montantBrut' => (float) $d->montant,
                'methode' => (string) $d->methode_paiement,
                // La colonne qui explique si l'argent est SORTI : sans elle,
                // une ligne « En attente » ou « Annulée » se lirait comme une
                // dépense payée, et le total du bas paraîtrait faux.
                'statut' => (string) $d->statut,
                'caisse' => (string) ($d->caisse?->nom ?? ''),
                'centre' => (string) (self::centreNom($d) ?? ''),
                'groupe' => (string) ($d->group?->nom ?? ''),
                'date' => $this->formatDate($d->date_depense),
                'operateur' => $d->agent?->nomComplet() ?? '',
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
        string $typeFilter = '',
        string $statutFilter = '',
    ): int {
        return $this->baseQuery($user, $dateFrom, $dateTo, $typeFilter, $statutFilter)->count();
    }

    /**
     * Le total APPROUVÉ sur la période filtrée, formaté — l'argent réellement
     * sorti des caisses.
     *
     * Calculé en SQL sur tout l'ensemble filtré, jamais en additionnant les
     * lignes rendues (CLAUDE.md : « a header Total comes from the server over
     * the filtered set »). ⚠ Il n'est PAS égal à la somme de la colonne
     * « Montant » du document : les lignes En attente / Refusée / Annulée y
     * figurent mais n'ont rien fait sortir. C'est la MÊME définition que
     * `montantTotal` de la liste Dépenses et que toutes les lectures de
     * caisse — un écran de plus qui sommerait sans ce filtre contredirait les
     * autres.
     */
    public function total(
        User $user,
        string $dateFrom,
        string $dateTo,
        string $typeFilter = '',
        string $statutFilter = '',
    ): string {
        $montant = (float) $this->baseQuery($user, $dateFrom, $dateTo, $typeFilter, $statutFilter)
            ->where('statut', Depense::STATUT_APPROUVEE)
            ->sum('montant');

        return number_format($montant, 2, ',', ' ').' DH';
    }

    /**
     * Les types proposés par le filtre « Type » — le catalogue ACTIF, comme
     * le modal de saisie. « Paiement prof » y figure : c'est une dépense
     * comme une autre dans ce document (même table, même caisse, mêmes
     * invariants), seule la liste à l'écran la range dans un onglet à part
     * pour rester lisible.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function typeOptions(): array
    {
        return TypeDepense::query()
            ->where('statut', TypeDepense::STATUT_ACTIF)
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (TypeDepense $t): array => ['value' => (string) $t->id, 'label' => (string) $t->nom])
            ->all();
    }

    /** @return Builder<Depense> */
    private function baseQuery(
        User $user,
        string $dateFrom,
        string $dateTo,
        string $typeFilter,
        string $statutFilter,
    ): Builder {
        return Depense::query()
            // Portée par la MÊME règle de ventilation que la liste Dépenses
            // (voir l'entête de classe) — jamais par la caisse seule.
            ->tap(fn (Builder $q) => $this->scopeToReachableCenters($q, $user))
            ->when(
                $this->context->etablissementId() !== null,
                fn (Builder $q) => $this->ventilation->scopeDepensesAuCentre(
                    $q,
                    (int) $this->context->etablissementId(),
                    true,
                ),
            )
            // Plage sargable sur une colonne DATE indexée (CLAUDE.md §17) —
            // jamais whereDate(), qui enveloppe la colonne dans un cast.
            ->when($dateFrom !== '', fn (Builder $q) => $q->where('date_depense', '>=', $dateFrom))
            ->when($dateTo !== '', fn (Builder $q) => $q->where('date_depense', '<=', $dateTo))
            ->when($typeFilter !== '', fn (Builder $q) => $q->where('type_depense_id', (int) $typeFilter))
            // Le filtre « Statut » porte sur la MÊME colonne que la colonne
            // imprimée : filtrer « Approuvée » sort exactement les lignes que
            // le document marque « Approuvée ».
            ->when($statutFilter !== '', fn (Builder $q) => $q->where('statut', $statutFilter));
    }

    /**
     * Le NOM du centre auquel la dépense est imputée — `Depense::centreId()`,
     * forme PHP de la règle qui a filtré le rapport. Les trois relations sont
     * eager-loadées par `__invoke()`, donc aucune requête par ligne.
     */
    private static function centreNom(Depense $depense): ?string
    {
        if ($depense->centreId() === null) {
            return null;
        }

        return $depense->group?->etablissement?->nom_centre
            ?? $depense->etablissement?->nom_centre
            ?? $depense->caisse?->etablissement?->nom_centre;
    }

    /**
     * Centre REACH (« Centres affectés », §16) — super-admins voient tous les
     * centres, les autres ceux auxquels ils sont affectés, résolus par la
     * même règle de ventilation que le filtre de centre actif.
     */
    private function scopeToReachableCenters(Builder $query, User $user): void
    {
        if ($this->centerAccess->hasGlobalAccess($user)) {
            return;
        }

        $this->ventilation->scopeDepensesAuxCentres(
            $query,
            $this->centerAccess->accessibleCenterIds($user),
            true,
        );
    }

    private function formatDate(mixed $date): string
    {
        return $date instanceof \DateTimeInterface ? $date->format('d/m/Y') : '';
    }
}
