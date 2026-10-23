<?php

declare(strict_types=1);

namespace App\Domain\Reports\Queries;

use App\Domain\Finance\Support\VentilationCentre;
use App\Domain\Payroll\Queries\GetPaiementProfCalcul;
use App\Domain\Payroll\Support\MoisDeGroupe;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Group;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * « Calcul paiement prof » — rapport imprimable (PDF / Excel) des paiements
 * prof ENREGISTRÉS (dépenses « Paiement prof »), refondu le 23/10/2026.
 *
 * Avant, le rapport refaisait un calcul libre pour un groupe + un enseignant
 * + une fenêtre de dates — l'utilisateur devait retaper la période d'un
 * paiement déjà saisi. Désormais trois filtres, tous optionnels, sans fenêtre
 * de dates :
 *  - **Groupe** : vide = tous les groupes du centre/année actifs ;
 *  - **Enseignant** : vide = tous ; seul = tous ses paiements, tous groupes ;
 *  - **Paiement** : la liste des paiements prof qui passent les deux filtres
 *    précédents. Vide = le document LISTE ces paiements (une ligne par
 *    paiement, total approuvé) ; choisi = le document DÉTAILLE ce paiement
 *    (résumé + une ligne par étudiant), tel que l'écran l'a calculé.
 *
 * ⚠ Le détail ne CALCULE rien de neuf : il appelle `GetPaiementProfCalcul`,
 * la seule définition de la paie, sur la période, l'enseignant et les
 * ajustements STOCKÉS sur la ligne — le même aperçu que la fiche de la
 * dépense (`DepenseController::calculApercu`). Le montant imprimé en pied est
 * celui de la ligne (le NET versé, après CNSS), jamais une valeur recalculée.
 *
 * Portée : la MÊME règle de ventilation que la liste Dépenses
 * (`VentilationCentre::scopeDepensesAuCentre`, centres affectés + centre
 * actif) et l'ANNÉE active via le groupe du paiement — un paiement prof porte
 * toujours un groupe. Un id de groupe, d'enseignant ou de paiement hors de
 * cette portée ne sort aucune ligne. Rien n'est écrit, aucune caisse n'est
 * touchée.
 */
final class GetPaiementProfReport
{
    public const KEY = 'calcul-paiement-prof';

    /** Statuts listés — les paiements VIVANTS, comme DoublonPaiementProf. */
    private const STATUTS = [Depense::STATUT_EN_ATTENTE, Depense::STATUT_APPROUVEE];

    public function __construct(
        private readonly GetPaiementProfCalcul $calcul,
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
        private readonly VentilationCentre $ventilation,
    ) {}

    /**
     * Tout ce que l'aperçu, le PDF et le classeur lisent, résolu UNE fois :
     * le mode (liste ou détail), les lignes, les options des deux dropdowns,
     * le résumé, le total et la raison d'un document vide.
     *
     * @return array{detail: bool, lignes: Collection<int, array<string, mixed>>, enseignantOptions: list<array{value: string, label: string}>, paiementOptions: list<array{value: string, label: string}>, resume: array<string, string>, total: string, avertissement: string}
     */
    public function resoudre(User $user, string $groupFilter, string $enseignantFilter, string $paiementFilter): array
    {
        $base = [
            'enseignantOptions' => $this->enseignantOptions($user, $groupFilter),
            'paiementOptions' => $this->paiementOptions($user, $groupFilter, $enseignantFilter),
        ];

        $paiement = $paiementFilter !== ''
            ? $this->baseQuery($user, $groupFilter, $enseignantFilter)->whereKey((int) $paiementFilter)
                ->with(['group.enseignant', 'enseignant'])->first()
            : null;

        if ($paiement !== null) {
            return [...$base, ...$this->detail($paiement)];
        }

        $lignes = $this->liste($user, $groupFilter, $enseignantFilter);

        return [
            ...$base,
            'detail' => false,
            'lignes' => $lignes,
            'resume' => [],
            // ⚠ « Argent sorti » = Approuvée seulement (§11) : les lignes En
            // attente sont IMPRIMÉES mais pas totalisées, et le libellé le dit.
            'total' => self::dh((float) $this->baseQuery($user, $groupFilter, $enseignantFilter)
                ->where('statut', Depense::STATUT_APPROUVEE)->sum('montant')),
            'avertissement' => $lignes->isEmpty() ? __('No teacher payment matches these filters.') : '',
        ];
    }

    /**
     * Le mode LISTE : une ligne par paiement, du plus récent au plus ancien.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function liste(User $user, string $groupFilter, string $enseignantFilter): Collection
    {
        return $this->baseQuery($user, $groupFilter, $enseignantFilter)
            ->with(['group:id,nom,enseignant_id', 'group.enseignant:id,nom,prenom', 'enseignant:id,nom,prenom'])
            ->orderByDesc('periode_debut')->orderByDesc('id')
            ->get()
            ->values()
            ->map(fn (Depense $d, int $i): array => [
                'numero' => $i + 1,
                'reference' => (string) $d->reference,
                'groupe' => (string) ($d->group?->nom ?? '-'),
                'enseignant' => (string) (($d->enseignant ?? $d->group?->enseignant)?->nomComplet() ?? '-'),
                'periode' => $d->periode_debut?->format('d/m/Y').' → '.$d->periode_fin?->format('d/m/Y'),
                'montant' => self::dh((float) $d->montant),
                'cnss' => $d->cnss_montant === null ? '-' : self::dh((float) $d->cnss_montant),
                'statut' => (string) $d->statut,
                'date' => $d->date_depense?->format('d/m/Y') ?? '-',
            ]);
    }

    /**
     * Le mode DÉTAIL : le calcul du paiement choisi, refait depuis les appels
     * sur la période STOCKÉE, avec les ajustements stockés.
     *
     * @return array{detail: bool, lignes: Collection<int, array<string, mixed>>, resume: array<string, string>, total: string, avertissement: string}
     */
    private function detail(Depense $paiement): array
    {
        $group = $paiement->group;
        $enseignant = $paiement->enseignant ?? $group?->enseignant;
        $vide = ['detail' => true, 'lignes' => collect(), 'resume' => $this->resumePaiement($paiement), 'total' => self::dh((float) $paiement->montant)];

        if ($group === null || $enseignant === null || $paiement->periode_debut === null || $paiement->periode_fin === null) {
            return [...$vide, 'avertissement' => __('This payment carries no group, teacher or period: nothing to detail.')];
        }

        $periode = MoisDeGroupe::resoudre($group, null, $paiement->periode_debut->toDateString(), $paiement->periode_fin->toDateString());

        if ($periode === null) {
            return [...$vide, 'avertissement' => __('Choose a start date and an end date, :days days at most.', [
                'days' => MoisDeGroupe::DUREE_MAX_JOURS,
            ])];
        }

        $calcul = ($this->calcul)(
            group: $group,
            enseignant: $enseignant,
            mois: $periode,
            ajustements: Depense::normaliserAjustements($paiement->calcul_ajustements) ?? [],
        );

        $avertissement = match (true) {
            $calcul['enseignant']['probleme'] !== null => (string) $calcul['enseignant']['probleme'],
            (int) $calcul['nombreSeances'] === 0 => __('No completed session for this teacher over this period.'),
            default => '',
        };

        return [
            'detail' => true,
            'lignes' => $this->lignesCalcul($calcul),
            'resume' => [...$this->resumePaiement($paiement), ...$this->resumeCalcul($calcul)],
            'total' => self::dh((float) $paiement->montant),
            'avertissement' => $avertissement,
        ];
    }

    /**
     * Les lignes du détail — une par étudiant, dans l'ordre du calcul. Vide
     * en mode horaire : la paie y est `taux × heures`, sans détail.
     *
     * @param  array<string, mixed>  $calcul
     * @return Collection<int, array<string, mixed>>
     */
    private function lignesCalcul(array $calcul): Collection
    {
        if ($calcul['enseignant']['mode'] === Employee::MODE_PAIEMENT_HORAIRE) {
            return collect();
        }

        return collect($calcul['lignes'])->values()->map(static fn (array $l, int $i): array => [
            'numero' => $i + 1,
            'etudiant' => (string) $l['nom'],
            // Appelé dans le groupe sans y être inscrit : ses présences sont
            // réelles, la ligne le DIT au lieu de laisser une case vide.
            'statut' => $l['inscriptionStatut'] ?? 'Non inscrit',
            'presences' => (int) $l['joursRetenus'],
            'absences' => (int) $l['joursAbsents'],
            'palier' => self::palier((int) $l['semainesPayees']),
            'montant' => self::dh((float) $l['montantEffectif']),
        ]);
    }

    /** Ce que la LIGNE stocke : référence, période, net versé, CNSS, statut. */
    private function resumePaiement(Depense $paiement): array
    {
        $resume = [
            'Paiement' => (string) $paiement->reference,
            'Enseignant' => (string) (($paiement->enseignant ?? $paiement->group?->enseignant)?->nomComplet() ?? '-'),
            'Période payée' => $paiement->periode_debut?->format('d/m/Y').' → '.$paiement->periode_fin?->format('d/m/Y'),
            'Statut' => (string) $paiement->statut,
        ];

        if ($paiement->cnss_montant !== null) {
            $resume['Cotisation CNSS retenue'] = self::dh((float) $paiement->cnss_montant);
            $resume['Montant brut'] = self::dh((float) $paiement->montant + (float) $paiement->cnss_montant);
        }

        return $resume;
    }

    /**
     * Ce que le CALCUL dit : mode, taux, séances — ce qui permet de refaire
     * le total à la main.
     *
     * @param  array<string, mixed>  $calcul
     * @return array<string, string>
     */
    private function resumeCalcul(array $calcul): array
    {
        $mode = (string) $calcul['enseignant']['mode'];
        $resume = [
            'Mode de paie' => self::mode($mode),
            'Séances effectuées' => (string) $calcul['nombreSeances'],
        ];

        if ($mode === Employee::MODE_PAIEMENT_HORAIRE) {
            $resume['Taux horaire'] = self::dh((float) $calcul['enseignant']['taux']);
            $resume['Heures retenues'] = number_format((float) ($calcul['heuresSaisies'] ?? 0), 2, ',', ' ').' h';
        } else {
            $resume['Montant par étudiant'] = self::dh((float) $calcul['montantParEtudiant']);
            $resume['Étudiants rémunérateurs'] = (string) $calcul['etudiantsRemunerateurs'];
        }

        $resume['Total calculé'] = self::dh((float) $calcul['total']);

        if ((int) $calcul['seancesSansEnseignant'] > 0) {
            $resume['Séances sans enseignant (non comptées)'] = (string) $calcul['seancesSansEnseignant'];
        }

        return $resume;
    }

    /**
     * Les enseignants qui ont au moins un paiement dans la portée (et dans le
     * groupe choisi, s'il y en a un) — jamais ceux d'un autre centre.
     *
     * @return list<array{value: string, label: string}>
     */
    private function enseignantOptions(User $user, string $groupFilter): array
    {
        $ids = $this->baseQuery($user, $groupFilter, '')
            ->with(['group:id,enseignant_id'])
            ->get(['id', 'enseignant_id', 'group_id'])
            ->map(fn (Depense $d) => $d->enseignant_id ?? $d->group?->enseignant_id)
            ->filter()->unique()->values()->all();

        return Employee::query()
            ->whereIn('id', $ids)
            ->orderBy('nom')->orderBy('prenom')
            ->get(['id', 'nom', 'prenom'])
            ->map(fn (Employee $e): array => ['value' => (string) $e->id, 'label' => $e->nomComplet()])
            ->all();
    }

    /**
     * Les paiements qui passent les filtres Groupe / Enseignant — ce que le
     * dropdown « Paiement » propose, du plus récent au plus ancien.
     *
     * @return list<array{value: string, label: string}>
     */
    private function paiementOptions(User $user, string $groupFilter, string $enseignantFilter): array
    {
        return $this->baseQuery($user, $groupFilter, $enseignantFilter)
            ->with(['group:id,nom'])
            ->orderByDesc('periode_debut')->orderByDesc('id')
            ->get(['id', 'reference', 'group_id', 'periode_debut', 'periode_fin', 'montant', 'statut'])
            ->map(fn (Depense $d): array => [
                'value' => (string) $d->id,
                'label' => sprintf(
                    '%s · %s · %s → %s · %s',
                    $d->reference,
                    $d->group?->nom ?? '-',
                    $d->periode_debut?->format('d/m/Y'),
                    $d->periode_fin?->format('d/m/Y'),
                    self::dh((float) $d->montant),
                ),
            ])
            ->all();
    }

    /**
     * Les paiements prof de la portée : centres affectés + centre actif (par
     * ventilation), année active via le groupe. Pas de filtre HiddenAccount :
     * une dépense est un enregistrement de GLS, comme sur la liste Dépenses.
     *
     * @return Builder<Depense>
     */
    private function baseQuery(User $user, string $groupFilter, string $enseignantFilter): Builder
    {
        return Depense::query()
            ->whereHas('typeDepense', fn (Builder $q) => $q->where('nom', TypeDepense::SYSTEM_PAIEMENT_PROF))
            ->whereIn('statut', self::STATUTS)
            ->whereNotNull('group_id')
            ->tap(function (Builder $q) use ($user): void {
                if (! $this->centerAccess->hasGlobalAccess($user)) {
                    $this->ventilation->scopeDepensesAuxCentres($q, $this->centerAccess->accessibleCenterIds($user), true);
                }
            })
            ->when(
                $this->context->etablissementId() !== null,
                fn (Builder $q) => $this->ventilation->scopeDepensesAuCentre($q, (int) $this->context->etablissementId(), true),
            )
            ->when(
                $this->context->anneeScolaireId(),
                fn (Builder $q, $y) => $q->whereHas('group', fn (Builder $g) => $g->where('annee_scolaire_id', $y)),
            )
            ->when($groupFilter !== '', fn (Builder $q) => $q->where('group_id', (int) $groupFilter))
            // L'enseignant PAYÉ : celui de la ligne, sinon le prof du groupe
            // (lignes antérieures à la colonne) — même repli que la liste.
            ->when($enseignantFilter !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('enseignant_id', (int) $enseignantFilter)
                ->orWhere(fn (Builder $x) => $x
                    ->whereNull('enseignant_id')
                    ->whereHas('group', fn (Builder $g) => $g->where('enseignant_id', (int) $enseignantFilter)))));
    }

    /**
     * Même lecture que l'écran : 4 semaines = le mois complet. Rien payé
     * s'imprime « 0 », jamais « - » qu'on lirait comme une donnée manquante
     * (23/10/2026), dans le PDF comme dans le classeur.
     */
    private static function palier(int $semaines): string
    {
        return match ($semaines) {
            0 => '0',
            1 => '1 semaine',
            4 => 'Mois complet',
            default => "{$semaines} semaines",
        };
    }

    private static function mode(string $mode): string
    {
        return match ($mode) {
            // Mêmes libellés que l'écran (CalculPaiementProf::libelleMode).
            Employee::MODE_PAIEMENT_HORAIRE => __('Per hour'),
            Employee::MODE_PAIEMENT_GLS => __('GLS system'),
            Employee::MODE_PAIEMENT_WIN_WIN => __('Win-win system'),
            default => '-',
        };
    }

    private static function dh(float $montant): string
    {
        return number_format($montant, 2, ',', ' ').' DH';
    }
}
