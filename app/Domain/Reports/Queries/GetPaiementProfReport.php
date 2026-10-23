<?php

declare(strict_types=1);

namespace App\Domain\Reports\Queries;

use App\Domain\Payroll\Queries\GetPaiementProfCalcul;
use App\Domain\Payroll\Support\MoisDeGroupe;
use App\Models\Employee;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * « Calcul paiement prof » — rapport imprimable (PDF / Excel) du calcul que
 * propose l'écran « Calcul paiement prof », pour UN groupe, UN enseignant et
 * UNE période (la fenêtre de dates du rapport).
 *
 * ⚠ Ce rapport ne CALCULE rien lui-même : il appelle `GetPaiementProfCalcul`,
 * la seule définition de la paie (paliers de présences, mode et taux lus sur
 * la fiche de l'employé, séances de CET enseignant seulement). Recopier la
 * règle ici ferait imprimer un montant que l'écran ne propose pas.
 *
 * ⚠ Rien n'est écrit, aucune caisse n'est touchée : un calcul n'est pas un
 * paiement (le paiement reste une dépense « Paiement prof »).
 *
 * Trois bornes :
 *  - le groupe doit être un de ceux que le rapport propose (centres affectés
 *    + contexte actif, MÊME liste que le filtre « Groupe ») — un id forgé ne
 *    sort aucune ligne ;
 *  - l'enseignant est choisi parmi ceux du groupe ; vide ou étranger au
 *    groupe, c'est celui qui a donné le plus de séances sur la période
 *    (même pré-sélection que le modal de l'écran) ;
 *  - une période illisible ou de plus de `MoisDeGroupe::DUREE_MAX_JOURS`
 *    jours ne calcule rien, et le rapport DIT pourquoi (`avertissement`)
 *    plutôt que d'imprimer un document vide sans explication.
 */
final class GetPaiementProfReport
{
    public const KEY = 'calcul-paiement-prof';

    public function __construct(
        private readonly GetPaiementProfCalcul $calcul,
        private readonly GetInscriptionsReport $inscriptions,
    ) {}

    /**
     * Le calcul complet (tel que le rend GetPaiementProfCalcul), plus ce que
     * le document imprime autour : l'enseignant retenu, les enseignants
     * proposés et la raison pour laquelle rien n'est calculé.
     *
     * @return array{calcul: ?array<string, mixed>, enseignantId: string, enseignantOptions: list<array{value: string, label: string}>, avertissement: string}
     */
    public function resoudre(User $user, string $groupFilter, string $enseignantFilter, string $dateFrom, string $dateTo): array
    {
        $vide = ['calcul' => null, 'enseignantId' => '', 'enseignantOptions' => [], 'avertissement' => ''];

        if ($groupFilter === '') {
            return [...$vide, 'avertissement' => __('Choose a group to compute the teacher payment.')];
        }

        // La MÊME liste que le filtre « Groupe » : centres affectés + centre
        // et année actifs. Un groupe hors de cette liste n'est pas imprimé.
        $proposes = array_column($this->inscriptions->groupOptions($user), 'value');
        $group = in_array($groupFilter, $proposes, true)
            ? Group::query()->find((int) $groupFilter)
            : null;

        if ($group === null) {
            return [...$vide, 'avertissement' => __('Choose a group to compute the teacher payment.')];
        }

        $periode = MoisDeGroupe::periode($group, $dateFrom, $dateTo);
        $options = $this->calcul->optionsPourGroupe($group, null, $dateFrom ?: null, $dateTo ?: null);

        $enseignantOptions = array_map(
            static fn (array $e): array => ['value' => (string) $e['value'], 'label' => (string) $e['label']],
            $options['enseignants'],
        );
        $ids = array_column($enseignantOptions, 'value');

        $enseignantId = in_array($enseignantFilter, $ids, true)
            ? $enseignantFilter
            : (string) ($options['enseignantParDefaut'] ?? '');

        $base = ['calcul' => null, 'enseignantId' => $enseignantId, 'enseignantOptions' => $enseignantOptions];

        if ($periode === null) {
            return [...$base, 'avertissement' => __('Choose a start date and an end date, :days days at most.', [
                'days' => MoisDeGroupe::DUREE_MAX_JOURS,
            ])];
        }

        $enseignant = $enseignantId !== '' ? Employee::query()->find((int) $enseignantId) : null;

        if ($enseignant === null) {
            return [...$base, 'avertissement' => __('No teacher is assigned to this group.')];
        }

        $calcul = ($this->calcul)(group: $group, enseignant: $enseignant, mois: $periode);

        $avertissement = match (true) {
            $calcul['enseignant']['probleme'] !== null => (string) $calcul['enseignant']['probleme'],
            (int) $calcul['nombreSeances'] === 0 => __('No completed session for this teacher over this period.'),
            default => '',
        };

        return [...$base, 'calcul' => $calcul, 'avertissement' => $avertissement];
    }

    /**
     * Les lignes du document — une par étudiant, dans l'ordre du calcul
     * (dossiers actifs d'abord). Vide en mode horaire : la paie y est
     * `taux × heures`, sans détail par étudiant.
     *
     * @param  array<string, mixed>|null  $calcul
     * @return Collection<int, array<string, mixed>>
     */
    public function lignes(?array $calcul): Collection
    {
        if ($calcul === null || $calcul['enseignant']['mode'] === Employee::MODE_PAIEMENT_HORAIRE) {
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

    /**
     * Le résumé du calcul imprimé en tête du document (et du classeur) :
     * libellé => valeur, dans l'ordre de lecture.
     *
     * @param  array<string, mixed>|null  $calcul
     * @return array<string, string>
     */
    public function resume(?array $calcul): array
    {
        if ($calcul === null) {
            return [];
        }

        $mode = (string) $calcul['enseignant']['mode'];
        $horaire = $mode === Employee::MODE_PAIEMENT_HORAIRE;

        // Le groupe n'y figure pas : il est déjà rappelé par les filtres
        // appliqués (RapportController::filtresAppliques()).
        $resume = [
            'Enseignant' => (string) $calcul['enseignant']['nom'],
            'Mode de paie' => self::mode($mode),
            'Période de paie' => (string) $calcul['periode']['libelle'],
            'Séances effectuées' => (string) $calcul['nombreSeances'],
        ];

        if ($horaire) {
            $resume['Taux horaire'] = self::dh((float) $calcul['enseignant']['taux']);
            $resume['Heures retenues'] = number_format((float) ($calcul['heuresSaisies'] ?? 0), 2, ',', ' ').' h';
        } else {
            $resume['Montant par étudiant'] = self::dh((float) $calcul['montantParEtudiant']);
            $resume['Étudiants rémunérateurs'] = (string) $calcul['etudiantsRemunerateurs'];
        }

        if ((int) $calcul['seancesSansEnseignant'] > 0) {
            $resume['Séances sans enseignant (non comptées)'] = (string) $calcul['seancesSansEnseignant'];
        }

        return $resume;
    }

    /** Le total proposé, formaté comme les autres rapports. Vide sans calcul. */
    public function total(?array $calcul): string
    {
        return $calcul === null ? '' : self::dh((float) $calcul['total']);
    }

    /** Même lecture que l'écran : 4 semaines = le mois complet. */
    private static function palier(int $semaines): string
    {
        return match ($semaines) {
            0 => '-',
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
