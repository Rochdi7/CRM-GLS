<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Domain\Payments\Support\CibleTransfertFrais;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Presence;
use App\Models\Seance;

/**
 * Le DIAGNOSTIC que « Déplacer un paiement » affiche avant de rien écrire.
 *
 * C'est la version écran des trois scripts `diag*.php` passés en PuTTY :
 * la ligne source (montant, date, agent, caisse — ce qui ne bougera PAS),
 * son dossier et ses appels, le dossier cible et ses frais, puis ce qui
 * bloque. Un débutant doit pouvoir lire la page et savoir POURQUOI le
 * bouton est grisé — jamais un simple « transfert impossible ».
 *
 * ⚠ Lecture seule et SANS autorité : chaque refus listé ici est recopié
 * de l'action qui le porte (`TransfererFraisVersAutreEtudiant`,
 * `AffecterAvanceVersAutreEtudiant`, `PurgerPresencesFantomes`), pas
 * redéfini. Le frais cible d'une ligne à frais passe par la MÊME
 * `CibleTransfertFrais::resoudre()` que l'action, sans verrou. Si les deux
 * divergent un jour, c'est l'action qui a raison et l'écran qui ment.
 *
 * Le périmètre des appels est exactement celui de la garde :
 * étudiant × séances du groupe de l'inscription source.
 *
 * @phpstan-type FraisCible array{id:int, nom:string, montant:string, paye:string, reste:string, statut:string, masque:bool}
 */
final class DiagnostiquerDeplacementPaiement
{
    public function __construct(private readonly CibleTransfertFrais $cible) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $referenceEncaissement, string $referenceInscription): array
    {
        $enc = Encaissement::query()
            ->with(['student', 'fee.inscription.student', 'fee.inscription.group', 'caisse', 'agent'])
            ->where('reference', $referenceEncaissement)
            ->first();

        $ins = Inscription::query()
            ->with(['student', 'group', 'fees' => fn ($q) => $q->orderBy('date_echeance')->orderBy('id')])
            ->where('reference', $referenceInscription)
            ->first();

        if ($enc === null || $ins === null) {
            return [
                'erreur' => $enc === null
                    ? __('No payment carries the reference « :ref ».', ['ref' => $referenceEncaissement])
                    : __('No registration carries the reference « :ref ».', ['ref' => $referenceInscription]),
            ];
        }

        $inscriptionSource = $enc->fee?->inscription;
        $presences = $inscriptionSource !== null ? $this->presences($inscriptionSource) : [];
        $reelles = array_values(array_filter($presences, fn (array $p): bool => $p['statut'] !== Presence::STATUT_ABSENT));

        $mode = $enc->isAvance() ? 'avance' : 'frais';

        // Ce que le transfert refusera QUOI QU'ON FASSE — recopié des actions.
        $blocages = [];

        if ($enc->applied_from_encaissement_id !== null) {
            $blocages[] = __('This row is an advance allocation: its money belongs to the parent advance. Detach it first, then re-apply it.');
        }
        if ($enc->applications()->exists()) {
            $blocages[] = $mode === 'avance'
                ? __('This advance has already been applied to fees: detach its allocations before moving it.')
                : __('This payment has already funded advance allocations and cannot be transferred.');
        }
        if ($enc->remboursements()->exists()) {
            $blocages[] = __('A refunded payment cannot be transferred.');
        }
        if ($enc->cheque_id !== null) {
            $blocages[] = __('This payment is backed by a tracked cheque, which belongs to its own owner: it cannot be transferred to another student.');
        }
        if ($ins->student_id === $enc->student_id) {
            $blocages[] = __('This registration belongs to the same student. Use the ordinary payment-move tool instead.');
        }
        if ($mode === 'frais' && $inscriptionSource === null) {
            $blocages[] = __('This payment is not attached to a registration fee: there is nothing to transfer.');
        }
        if ($mode === 'frais' && $inscriptionSource !== null && $inscriptionSource->group_id === null) {
            $blocages[] = __('This registration is no longer attached to a group: its attendance can no longer be verified, so the transfer cannot be allowed.');
        }
        $centreSource = $mode === 'frais' ? $inscriptionSource?->etablissement_id : $enc->etablissement_id;
        if ($centreSource !== null && $centreSource !== $ins->etablissement_id) {
            $blocages[] = __('The target registration belongs to another centre. A fee can only be transferred within the same centre.');
        }

        // Le frais cible d'une ligne à frais : DÉTECTÉ, par la même règle
        // que l'action (sans verrou ici).
        $fraisDetecte = null;
        $fraisDetecteRefus = null;

        if ($mode === 'frais' && $enc->fee !== null) {
            $resolution = $this->cible->resoudre($enc->fee, $ins, (float) $enc->montant, verrouiller: false);

            if ($resolution['frais'] !== null) {
                $fraisDetecte = $this->frais($resolution['frais']);
            } else {
                $fraisDetecteRefus = $this->cible->message($resolution['raison'], $enc->fee, (float) $enc->montant, $resolution['reste']);
            }
        }

        return [
            'erreur' => null,
            'mode' => $mode,
            'source' => [
                'id' => $enc->id,
                'reference' => $enc->reference,
                'montant' => (string) $enc->montant,
                'methode' => $enc->methode,
                'date' => $enc->date_paiement?->format('Y-m-d'),
                'agent' => $enc->agent?->nomComplet(),
                'caisse' => $enc->caisse?->nom,
                'centreId' => $centreSource,
                'etudiant' => $enc->student?->nomComplet(),
                'etudiantId' => $enc->student_id,
                'frais' => $enc->fee?->nom,
                'inscription' => $inscriptionSource?->reference,
                'inscriptionStatut' => $inscriptionSource?->statut,
                'groupe' => $inscriptionSource?->group?->nom,
            ],
            'cible' => [
                'id' => $ins->id,
                'reference' => $ins->reference,
                'statut' => $ins->statut,
                'centreId' => $ins->etablissement_id,
                'etudiant' => $ins->student?->nomComplet(),
                'etudiantId' => $ins->student_id,
                'groupe' => $ins->group?->nom,
                'presences' => $this->compterPresences($ins),
                // Ce qui peut encore RECEVOIR l'argent d'abord (reste dû),
                // puis les lignes soldées, puis les masquées — l'opérateur
                // cherche une cible, pas un relevé ; l'échéance départage.
                'frais' => $ins->fees
                    ->map(fn (InscriptionFee $f): array => $this->frais($f))
                    ->sortBy(fn (array $f, int $i): int => (match (true) {
                        $f['masque'] => 2,
                        (float) $f['reste'] > 0 => 0,
                        default => 1,
                    }) * 1000 + $i)
                    ->values()
                    ->all(),
            ],
            'presences' => $presences,
            // La purge ne sait effacer QUE des « Absent » — même règle que
            // PurgerPresencesFantomes, qui la revérifie sous verrou.
            'purgeable' => $presences !== [] && $reelles === [] && ($inscriptionSource?->group_id !== null),
            'purgeRefus' => $reelles === [] ? null : __(
                ':count attendance line(s) are not « Absent » (:dates): this student was actually called in class, so these lines are real and cannot be erased.',
                [
                    'count' => count($reelles),
                    'dates' => implode(', ', array_map(fn (array $p): string => $p['date'].' '.$p['statut'], $reelles)),
                ],
            ),
            'fraisDetecte' => $fraisDetecte,
            'fraisDetecteRefus' => $fraisDetecteRefus,
            'blocages' => $blocages,
        ];
    }

    /**
     * @return list<array{id:int, date:string, statut:string}>
     */
    private function presences(Inscription $inscription): array
    {
        if ($inscription->group_id === null) {
            return [];
        }

        return Presence::query()
            ->join('seances', 'seances.id', '=', 'presences.seance_id')
            ->where('presences.student_id', $inscription->student_id)
            ->where('seances.group_id', $inscription->group_id)
            ->orderBy('seances.date_seance')
            ->get(['presences.id', 'seances.date_seance', 'presences.statut'])
            ->map(fn ($p): array => [
                'id' => (int) $p->id,
                'date' => (string) $p->date_seance,
                'statut' => (string) $p->statut,
            ])
            ->all();
    }

    private function compterPresences(Inscription $inscription): int
    {
        if ($inscription->group_id === null) {
            return 0;
        }

        return Presence::query()
            ->where('student_id', $inscription->student_id)
            ->whereIn('seance_id', Seance::query()->select('id')->where('group_id', $inscription->group_id))
            ->count();
    }

    /**
     * @return array{id:int, nom:string, montant:string, paye:string, reste:string, statut:string, masque:bool}
     */
    private function frais(InscriptionFee $f): array
    {
        $paye = $f->montantPaye();

        return [
            'id' => $f->id,
            'nom' => $f->nom,
            'montant' => (string) $f->montant,
            'paye' => number_format($paye, 2, '.', ''),
            'reste' => number_format(max(0.0, round((float) $f->montant - $paye, 2)), 2, '.', ''),
            'statut' => $f->statut,
            'masque' => $f->estMasque(),
        ];
    }
}
