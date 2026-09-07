<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Models\Creneau;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Collection;

/**
 * Read-model for the "Emploi du temps" weekly grid — every créneau matching
 * the filters, scoped to the active center/year the same way Séances/Groups
 * are. The React page buckets rows by (jour_semaine, heure_debut) itself;
 * this query just returns the flat, already-authorized list.
 *
 * ⚠ Un créneau CLÔTURÉ (date_fin renseignée) ne génère plus aucune séance
 * (GenererSeancesDepuisCreneau s'arrête net dessus). La grille les affichait
 * pourtant à l'identique des créneaux vivants : l'écran montrait un emploi du
 * temps complet — cinq cases bien remplies — pendant que le groupe ne
 * produisait plus rien et que sa fiche affichait « plus d'emploi du temps
 * actif » (signalé le 03/09/2026 sur B2 Mehdi Kouay17h). Une case morte est
 * donc désormais RENVOYÉE AVEC SON ÉTAT (`clos`, `dateFin`) pour que la
 * grille la distingue, jamais masquée en silence : la faire disparaître
 * laisserait l'utilisateur devant un planning vide, sans rien à corriger ni
 * comprendre.
 */
final class GetCreneauxGrille
{
    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @param  array{groupFilter?: string, enseignantFilter?: string, salleFilter?: string, jourFilter?: string}  $filters
     * @return Collection<int, array{
     *     id: int, groupId: int, groupNom: string, groupNiveau: ?string,
     *     jourSemaine: int, heureDebut: string, heureFin: string,
     *     enseignant: ?string, enseignantId: ?int, salle: ?string, salleId: ?int,
     *     clos: bool, dateFin: ?string, motifCloture: ?string,
     * }>
     */
    public function __invoke(User $user, array $filters = []): Collection
    {
        $groupFilter = $filters['groupFilter'] ?? '';
        $enseignantFilter = $filters['enseignantFilter'] ?? '';
        $salleFilter = $filters['salleFilter'] ?? '';
        $jourFilter = $filters['jourFilter'] ?? '';

        return Creneau::query()
            ->with(['group:id,nom,niveau,etablissement_id,annee_scolaire_id,date_fin_formation', 'enseignant:id,nom,prenom', 'salle:id,nom'])
            ->whereHas('group', function ($q) use ($user): void {
                $this->centerAccess->scopeAccessibleCenters($q, $user);
                $this->scopeToActiveCenter($q);
                if ($this->context->anneeScolaireId()) {
                    $q->where('annee_scolaire_id', $this->context->anneeScolaireId());
                }
            })
            ->when($groupFilter !== '', fn ($q) => $q->where('group_id', (int) $groupFilter))
            ->when($enseignantFilter !== '', fn ($q) => $q->where('enseignant_id', (int) $enseignantFilter))
            ->when($salleFilter !== '', fn ($q) => $q->where('salle_id', (int) $salleFilter))
            ->when($jourFilter !== '', fn ($q) => $q->where('jour_semaine', (int) $jourFilter))
            ->orderBy('jour_semaine')
            ->orderBy('heure_debut')
            // Deux créneaux peuvent partager le MÊME jour et la MÊME heure —
            // c'est exactement ce que produit un changement d'enseignant :
            // l'ancien créneau clôturé et le nouveau se superposent dans la
            // case. Sans départage, PostgreSQL rend l'ordre qu'il veut et une
            // colonne du tableau s'affiche à l'envers (signalé le 07/09/2026 :
            // sur Yassine SEPT 10H, jeudi listait l'actif au-dessus du clos,
            // les quatre autres jours l'inverse). On range donc TOUJOURS le
            // vivant d'abord, puis l'id pour un ordre stable d'un rendu à
            // l'autre.
            ->orderByRaw('(date_fin is not null)')
            ->orderBy('id')
            ->get()
            ->map(fn (Creneau $creneau): array => [
                'id' => $creneau->id,
                'groupId' => $creneau->group_id,
                'groupNom' => $creneau->group?->nom ?? '—',
                'groupNiveau' => $creneau->group?->niveau,
                'jourSemaine' => $creneau->jour_semaine,
                'heureDebut' => substr((string) $creneau->heure_debut, 0, 5),
                'heureFin' => substr((string) $creneau->heure_fin, 0, 5),
                'enseignant' => $creneau->enseignant?->nomComplet(),
                'enseignantId' => $creneau->enseignant_id,
                'salle' => $creneau->salle?->nom,
                'salleId' => $creneau->salle_id,
                // Voir l'avertissement en tête de classe : une case clôturée
                // ne produit plus de séance et doit se voir comme telle.
                'clos' => $creneau->date_fin !== null,
                'dateFin' => $creneau->date_fin?->format('d/m/Y'),
                'motifCloture' => $this->motifCloture($creneau),
            ]);
    }

    /**
     * Pourquoi ce créneau est clos — la grille n'a pas à le redériver
     * (§5 : un read-model porte la règle, l'écran l'affiche).
     *
     * Deux causes très différentes finissent dans la MÊME colonne
     * `date_fin`, et les confondre alarmait pour rien (signalé le
     * 07/09/2026) :
     *
     *  - `termine` — la formation du groupe est arrivée à son terme. C'est
     *    la fin NORMALE d'un emploi du temps, l'équivalent d'un archivage :
     *    il n'y a rien à corriger.
     *  - `remplace` — le créneau a été fermé AVANT cette date, donc en cours
     *    de formation : c'est ChangerEnseignantGroupe qui a séparé l'emploi
     *    du temps du prof sortant pour la paie. Là encore rien d'anormal,
     *    mais l'enseignant a changé.
     *
     * Un groupe sans `date_fin_formation` ne permet pas de trancher : on
     * reste sur `remplace`, la cause de loin la plus fréquente.
     */
    private function motifCloture(Creneau $creneau): ?string
    {
        if ($creneau->date_fin === null) {
            return null;
        }

        $finFormation = $creneau->group?->date_fin_formation;

        if ($finFormation !== null && ! $creneau->date_fin->lt($finFormation)) {
            return 'termine';
        }

        return 'remplace';
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
