<?php

declare(strict_types=1);

namespace App\Domain\Students\Queries;

use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\Activity;
use App\Models\Presence;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Extracted from resources/views/backoffice/students/show.blade.php +
 * StudentController::show()'s eager loads — same fields, same relations,
 * same unpaginated related-record lists (preserved exactly, not newly
 * paginated). Read-only, no request/React dependency.
 *
 * Two presentation rules (24/08/2026, after the legacy import brought
 * several years of history per student):
 *  - inscriptions are grouped by année scolaire (newest year first);
 *  - the Paiements list shows the payments of ONE inscription statut only:
 *    the Active inscription(s) when the student has one, otherwise the
 *    Annulée one(s), otherwise Changement — the priority the Encaissements
 *    import also uses. Unallocated avances (no fee) always show.
 */
final class GetStudentDetails
{
    /** Statut priority for the Paiements list. */
    private const array PAIEMENTS_PRIORITE = [
        Inscription::STATUT_ACTIVE,
        Inscription::STATUT_ANNULEE,
        Inscription::STATUT_CHANGEMENT,
        Inscription::STATUT_TRANSFEREE,
    ];

    /**
     * @return array<string, mixed>
     */
    public function __invoke(Student $student): array
    {
        $student->loadMissing([
            'etablissement',
            'transfereVers.etablissement',
            'transfereDepuis.etablissement',
            'inscriptions.group',
            'inscriptions.fees',
            'inscriptions.anneeScolaire',
            'encaissements.caisse',
            'encaissements.fee',
        ]);

        $inscriptionRows = $student->inscriptions->map(fn (Inscription $inscription): array => [
            'reference' => $inscription->reference,
            'groupe' => $inscription->group?->nom,
            'date' => $inscription->date_inscription?->format('d/m/Y'),
            'total' => $inscription->montant_total !== null ? number_format((float) $inscription->montant_total, 2, '.', '') : null,
            'statut' => $inscription->statut,
            'anneeScolaire' => $inscription->anneeScolaire?->nom,
        ]);

        [$paiementsScope, $paiements] = $this->paiementsAffiches($student);

        return [
            'id' => $student->id,
            'reference' => $student->reference,
            'nomComplet' => $student->nomComplet(),
            'prenom' => $student->prenom,
            'niveau' => $student->niveau,
            'orientation' => $student->niveau ? $student->orientation() : null,
            'sexe' => $student->sexe,
            'dateNaissance' => $student->date_naissance?->format('d/m/Y'),
            'cin' => $student->cin,
            'telephone' => $student->telephone,
            'whatsapp' => $student->whatsapp,
            'email' => $student->email,
            'adresse' => $student->adresse,
            'centre' => $student->etablissement?->nom_centre,
            // Transfert entre centres (25/09/2026) : chaque fiche renvoie vers
            // l'autre — l'original garde présences et historique, la copie
            // porte le dossier vivant et l'argent.
            'statut' => $student->statut,
            'transfereVers' => $student->transfereVers === null ? null : [
                'id' => $student->transfereVers->id,
                'reference' => $student->transfereVers->reference,
                'centre' => $student->transfereVers->etablissement?->nom_centre,
            ],
            'transfereDepuis' => $student->transfereDepuis === null ? null : [
                'id' => $student->transfereDepuis->id,
                'reference' => $student->transfereDepuis->reference,
                'centre' => $student->transfereDepuis->etablissement?->nom_centre,
            ],
            // L'historique des centres précédents, AFFICHÉ sur la fiche
            // d'arrivée (25/09/2026) : le centre qui reçoit l'étudiant ne
            // peut en général pas ouvrir la fiche d'origine (autre centre),
            // et c'est là que vivent ses présences et ses anciens dossiers.
            'historiqueTransfert' => $this->historiqueTransfert($student),
            'paiementsTransferes' => $this->paiementsTransferes($student),
            'photoUrl' => $student->avatarUrl(),
            'parent' => ($student->parent_nom || $student->parent_telephone || $student->parent_relation || $student->parent_cin)
                ? [
                    'relation' => $student->parent_relation,
                    'nom' => $student->parent_nom,
                    'sexe' => $student->parent_sexe,
                    'cin' => $student->parent_cin,
                    'telephone' => $student->parent_telephone,
                ]
                : null,
            'inscriptions' => $inscriptionRows->values()->all(),
            'inscriptionsParAnnee' => $this->groupByAnnee($student->inscriptions, $inscriptionRows),
            'paiementsScope' => $paiementsScope,
            'paiementsTotal' => number_format((float) $paiements->sum('montant'), 2, '.', ''),
            'paiements' => $paiements->map(fn (Encaissement $encaissement): array => [
                'reference' => $encaissement->reference,
                'montant' => number_format((float) $encaissement->montant, 2, '.', ''),
                'methode' => $encaissement->methode,
                'date' => $encaissement->date_paiement?->format('d/m/Y'),
                'caisse' => $encaissement->caisse?->nom,
            ])->values()->all(),
        ];
    }

    /**
     * Sur une fiche « Transféré » : les paiements partis avec l'étudiant
     * (25/09/2026). La fiche d'origine n'en porte plus aucun — l'argent suit
     * la personne sur sa copie — et afficher « Aucun paiement · 0,00 MAD »
     * se lisait comme de l'argent perdu. La liste vient de l'entrée de
     * journal du transfert (`encaissements_deplaces`), append-only : c'est
     * EXACTEMENT ce qui a été déplacé, jamais une re-dérivation. Lecture
     * seule ; montant, date, méthode et caisse sont ceux du jour de
     * l'encaissement. Le total exclut les lignes d'application d'avance,
     * comme `montant_transfere` (sinon le même dirham compterait deux fois).
     *
     * @return array{vers: ?string, centre: ?string, total: string, lignes: list<array<string, mixed>>}|null
     */
    private function paiementsTransferes(Student $student): ?array
    {
        if (! $student->estTransfere()) {
            return null;
        }

        $ids = Activity::query()
            ->where('subject_type', Student::class)
            ->where('subject_id', $student->id)
            ->where('event', 'student_transferred')
            ->get()
            ->flatMap(fn (Activity $a): array => (array) ($a->properties['encaissements_deplaces'] ?? []))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $rows = Encaissement::query()
            ->with('caisse:id,nom')
            ->whereIn('id', $ids)
            ->orderBy('date_paiement')
            ->get();

        return [
            'vers' => $student->transfereVers?->reference,
            'centre' => $student->transfereVers?->etablissement?->nom_centre,
            'total' => number_format((float) $rows->whereNull('applied_from_encaissement_id')->sum('montant'), 2, '.', ''),
            'lignes' => $rows->map(fn (Encaissement $e): array => [
                'reference' => $e->reference,
                'montant' => number_format((float) $e->montant, 2, '.', ''),
                'methode' => $e->methode,
                'date' => $e->date_paiement?->format('d/m/Y'),
                'caisse' => $e->caisse?->nom,
                'type' => $e->applied_from_encaissement_id !== null
                    ? 'Application d\'avance'
                    : ($e->inscription_fee_id === null ? 'Avance' : 'Paiement'),
            ])->values()->all(),
        ];
    }

    /**
     * Les fiches d'ORIGINE de cet étudiant, de la plus récente à la plus
     * ancienne (un étudiant transféré deux fois garde ses deux centres).
     * Lecture seule : rien n'est copié ni déplacé, les présences restent
     * rattachées à la fiche d'origine (ValiderTransfertEtudiant) — on les
     * LIT ici. Une requête par fiche pour les dossiers et une pour les
     * appels, jamais une par ligne.
     *
     * @return list<array<string, mixed>>
     */
    private function historiqueTransfert(Student $student): array
    {
        $historique = [];
        $vus = [$student->id => true];
        $origine = $student->transfereDepuis;

        while ($origine !== null && ! isset($vus[$origine->id])) {
            $vus[$origine->id] = true;

            $inscriptions = Inscription::query()
                ->with(['group:id,nom', 'anneeScolaire:id,nom'])
                ->where('student_id', $origine->id)
                ->orderByDesc('date_inscription')
                ->get()
                ->map(fn (Inscription $i): array => [
                    'reference' => $i->reference,
                    'groupe' => $i->group?->nom,
                    'anneeScolaire' => $i->anneeScolaire?->nom,
                    'dateDebut' => $i->date_debut?->format('d/m/Y') ?? $i->date_inscription?->format('d/m/Y'),
                    'dateFin' => $i->date_fin?->format('d/m/Y'),
                    'statut' => $i->statut,
                ])
                ->all();

            $presences = DB::table('presences as p')
                ->join('seances as se', 'se.id', '=', 'p.seance_id')
                ->leftJoin('groups as g', 'g.id', '=', 'se.group_id')
                ->where('p.student_id', $origine->id)
                ->orderByDesc('se.date_seance')
                ->orderByDesc('se.heure_debut')
                ->get(['p.id', 'p.statut', 'p.note', 'se.date_seance', 'se.heure_debut', 'g.nom as groupe']);

            $compteurs = Presence::compteurs($presences);

            $historique[] = [
                'id' => $origine->id,
                'reference' => $origine->reference,
                'centre' => $origine->etablissement?->nom_centre,
                'inscriptions' => $inscriptions,
                'presencesTotal' => $presences->count(),
                'compteurs' => $compteurs,
                'presences' => $presences->map(fn ($r): array => [
                    'id' => (int) $r->id,
                    'date' => \Illuminate\Support\Carbon::parse($r->date_seance)->format('d/m/Y'),
                    'heure' => $r->heure_debut !== null ? substr((string) $r->heure_debut, 0, 5) : null,
                    'groupe' => $r->groupe,
                    'statut' => (string) $r->statut,
                    'note' => $r->note,
                ])->all(),
            ];

            $origine->loadMissing('transfereDepuis.etablissement');
            $origine = $origine->transfereDepuis;
        }

        return $historique;
    }

    /**
     * One group per année scolaire, newest year first; inscriptions with no
     * year (legacy edge case) come last under a null label.
     *
     * @param  Collection<int, Inscription>  $inscriptions
     * @param  Collection<int, array<string, mixed>>  $rows  same order as $inscriptions
     * @return list<array{annee: ?string, inscriptions: list<array<string, mixed>>}>
     */
    private function groupByAnnee(Collection $inscriptions, Collection $rows): array
    {
        $groups = [];

        foreach ($inscriptions as $index => $inscription) {
            $annee = $inscription->anneeScolaire;
            $key = $annee?->id ?? 0;

            $groups[$key] ??= [
                'annee' => $annee?->nom,
                'dateDebut' => $annee?->date_debut?->toDateString() ?? '',
                'inscriptions' => [],
            ];
            $groups[$key]['inscriptions'][] = $rows[$index];
        }

        usort($groups, fn (array $a, array $b): int => strcmp($b['dateDebut'], $a['dateDebut']));

        return array_map(fn (array $g): array => [
            'annee' => $g['annee'],
            'inscriptions' => $g['inscriptions'],
        ], $groups);
    }

    /**
     * Payments of the first statut (Active > Annulée > Changement) the
     * student actually holds, plus unallocated avances. With no inscription
     * at all, every payment shows.
     *
     * @return array{?string, Collection<int, Encaissement>}
     */
    private function paiementsAffiches(Student $student): array
    {
        foreach (self::PAIEMENTS_PRIORITE as $statut) {
            $ids = $student->inscriptions->where('statut', $statut)->pluck('id');

            if ($ids->isEmpty()) {
                continue;
            }

            return [$statut, $student->encaissements->filter(
                fn (Encaissement $e): bool => $e->fee === null || $ids->contains($e->fee->inscription_id),
            )->values()];
        }

        return [null, $student->encaissements->values()];
    }
}
