<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Support\CibleTransfertFrais;
use App\Domain\Registrations\Support\GardePresencesInscription;
use App\Models\Cheque;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * « Transfert de frais vers un autre étudiant » (10/09/2026).
 *
 * LE CAS RÉEL — un étudiant s'inscrit, paie ses frais, puis ne vient
 * jamais. Sa sœur (ou son frère) se présente : « je prends la place de ma
 * sœur, elle n'étudie pas ». Jusqu'ici l'arrangement se réglait à l'oral,
 * hors système : le CRM montrait l'argent au nom de quelqu'un qui n'a
 * jamais suivi le cours, et la personne réellement en classe apparaissait
 * comme n'ayant rien payé.
 *
 * CE QUE FAIT CETTE ACTION, ET RIEN DE PLUS : elle déplace UN encaissement
 * du frais d'une inscription vers le frais de l'inscription D'UN AUTRE
 * ÉTUDIANT. Elle ne crée aucune inscription, n'en clôture aucune et n'en
 * annule aucune :
 *
 * - la sœur est inscrite AVANT, par l'écran d'inscription habituel ; c'est
 *   son frais à elle que l'on vient solder ;
 * - le dossier du frère reste OUVERT et redevient simplement dû (son frais
 *   repasse « Non payé »). S'il ne vient pas, on l'annule ensuite avec son
 *   motif, par le geste prévu pour cela. Décider ici du sort de son dossier
 *   serait une décision qu'aucun opérateur n'a demandée.
 *
 * ⚠ C'EST L'EXCEPTION ASSUMÉE au seul garde-fou que
 * DeplacerEncaissementVersFrais ne relâche jamais : « l'argent d'un
 * étudiant ne peut jamais solder le frais d'un autre ». Ce garde-fou reste
 * la règle partout ailleurs — il empêche la fiche d'un tiers d'apparaître
 * payée avec l'argent de quelqu'un d'autre. Ici, franchir cette frontière
 * EST l'objet du geste, donc trois choses le bornent :
 *   1. ZÉRO présence sur l'inscription SOURCE (celle qui perd l'argent) ;
 *   2. les deux inscriptions dans le MÊME CENTRE ;
 *   3. un motif obligatoire, et une entrée de journal dédiée.
 * Ne jamais assouplir DeplacerEncaissementVersFrais « pour faire pareil » :
 * c'est cette action-ci, avec ses bornes, qui porte l'exception.
 *
 * ⚠ AUCUN ARGENT NE BOUGE PHYSIQUEMENT. `montant`, `methode`,
 * `date_paiement`, `caisse_id`, `agent_id` et `caisses.solde` sont
 * inchangés : l'argent est dans la caisse depuis le jour où il a été reçu,
 * seule son AFFECTATION change. Re-dater le paiement le rejetterait dans un
 * autre mois du journal de caisse, peut-être déjà rapproché.
 *
 * `student_id` de l'encaissement, LUI, suit le frais — contrairement à
 * DeplacerEncaissementVersFrais, où il ne bouge jamais parce que la cible
 * appartient au même étudiant. Sans cela, la ligne resterait rattachée au
 * frère : sa fiche continuerait de compter cet argent, la fiche de la sœur
 * afficherait un frais soldé par un paiement qui ne lui appartient pas, et
 * les deux écrans se contrediraient. Le PAYEUR d'origine n'est pas perdu
 * pour autant : il est inscrit dans l'entrée de journal, avec le motif.
 */
final class TransfererFraisVersAutreEtudiant
{
    public function __construct(
        private readonly GardePresencesInscription $gardePresences,
        private readonly CibleTransfertFrais $cible,
    ) {}

    public function handle(Encaissement $encaissement, Inscription $cible, string $motif): Encaissement
    {
        return DB::transaction(function () use ($encaissement, $cible, $motif): Encaissement {
            // Verrou : deux transferts simultanés du même paiement se
            // croiseraient sinon (CLAUDE.md §11).
            $row = Encaissement::query()
                ->with(['fee.inscription', 'cheque'])
                ->whereKey($encaissement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $fraisSource = $row->fee;

            if ($fraisSource === null || $fraisSource->inscription === null) {
                throw ValidationException::withMessages([
                    'encaissement_id' => __('This payment is not attached to a registration fee: there is nothing to transfer.'),
                ]);
            }

            $inscriptionSource = $fraisSource->inscription;

            // ⚠ Une ligne d'APPLICATION d'avance ne se transfère pas.
            //
            // Elle porte `applied_from_encaissement_id` : son argent
            // appartient à l'avance PARENTE, qui reste au nom de l'étudiant
            // d'origine. La déplacer vers une autre personne ferait compter
            // au parent (`Encaissement::montantUtilise()`) de l'argent
            // désormais dépensé sur le frais de quelqu'un d'autre — l'avance
            // du frère financerait la sœur sans que l'onglet Avances puisse
            // l'expliquer, et « à qui appartient cette avance » n'aurait plus
            // de réponse.
            //
            // Le read-model masque déjà ces lignes de l'onglet Encaissements,
            // mais un id forgé atteint l'endpoint : la garde vit donc ICI
            // (CLAUDE.md §5 — le prop client n'est qu'un confort d'interface).
            // Pour libérer l'argent d'une avance mal affectée, on détache
            // l'application (payments.detach), puis on ré-applique.
            if ($row->applied_from_encaissement_id !== null) {
                throw ValidationException::withMessages([
                    'encaissement_id' => __('This row is an advance allocation: its money belongs to the parent advance. Detach it first, then re-apply it.'),
                ]);
            }

            // ⚠ Symétrique : un paiement qui a lui-même servi de SOURCE à des
            // applications ne se transfère pas non plus. En pratique une
            // avance (donc sans frais) est déjà refusée plus haut, mais une
            // ligne rattachée à un frais PEUT porter des applications
            // héritées de l'import legacy. Changer son `student_id`
            // laisserait ses lignes filles au nom de l'ancien étudiant.
            if ($row->applications()->exists()) {
                throw ValidationException::withMessages([
                    'encaissement_id' => __('This payment has already funded advance allocations and cannot be transferred.'),
                ]);
            }

            // Un remboursement a déjà fait sortir cet argent de la caisse :
            // le rattacher ailleurs ferait apparaître payé un frais dont
            // l'argent est parti (même refus que DeplacerEncaissementVersFrais).
            if ($row->remboursements()->exists()) {
                throw ValidationException::withMessages([
                    'encaissement_id' => __('A refunded payment cannot be transferred.'),
                ]);
            }

            // ⚠ TOUT paiement adossé à un chèque suivi est refusé — pas
            // seulement un chèque rejeté.
            //
            // Un chèque appartient à quelqu'un (`cheques.student_id`) et
            // `EncaissementController@store` refuse déjà de payer avec le
            // chèque d'un AUTRE étudiant (« This cheque does not belong to
            // the selected student »). Transférer un paiement financé par
            // chèque vers une autre personne contournerait cette invariante
            // par la porte de derrière : le chèque de la sœur se retrouverait
            // à solder le frais du frère, et « à qui appartient cet argent »
            // n'aurait plus de réponse cohérente entre le module Chèques et
            // la fiche étudiant.
            //
            // Le cycle de vie d'un chèque appartient au module Chèques (même
            // raisonnement que `methodeRequalifiable` et `montantCorrigible`
            // dans GetEncaissementsList, qui excluent eux aussi tout
            // cheque_id). La marche à suivre pour un chèque est de passer par
            // ce module — jamais de déplacer le paiement sous lui.
            if ($row->cheque_id !== null) {
                throw ValidationException::withMessages([
                    'encaissement_id' => $row->cheque?->statut === Cheque::STATUT_REJETE
                        ? __('This payment was funded by a rejected cheque and cannot be transferred.')
                        : __('This payment is backed by a tracked cheque, which belongs to its own owner: it cannot be transferred to another student.'),
                ]);
            }

            // Verrou sur l'inscription CIBLE : deux transferts simultanés
            // vers le même dossier sérialisent ici, avant que chacun ne
            // choisisse « le » frais encore dû.
            $inscriptionCible = Inscription::query()
                ->whereKey($cible->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Le geste EXISTE pour franchir la frontière entre deux
            // étudiants : le faire vers le même étudiant n'a pas de sens et
            // relève de DeplacerEncaissementVersFrais.
            if ($inscriptionCible->student_id === $inscriptionSource->student_id) {
                throw ValidationException::withMessages([
                    'inscription_id' => __('This registration belongs to the same student. Use the ordinary payment-move tool instead.'),
                ]);
            }

            // ⚠ LA BORNE — zéro présence sur l'inscription SOURCE, vérifiée
            // ICI, dans la transaction : un enseignant peut valider un appel
            // entre un contrôle fait à l'extérieur et l'écriture.
            $this->gardePresences->assurerAucunePresence($inscriptionSource, 'encaissement_id');

            // MÊME CENTRE. L'argent d'un centre solde un frais de ce centre :
            // un transfert inter-centres déplacerait silencieusement du
            // chiffre d'affaires d'un établissement à un autre, alors que la
            // caisse créditée, elle, ne bouge pas.
            if ($inscriptionSource->etablissement_id !== $inscriptionCible->etablissement_id) {
                throw ValidationException::withMessages([
                    'inscription_id' => __('The target registration belongs to another centre. A fee can only be transferred within the same centre.'),
                ]);
            }

            // Le frais cible est DÉTECTÉ, jamais choisi à la main
            // (demande métier du 10/09/2026) : un « Frais d'inscription »
            // payé par le frère solde le « Frais d'inscription » de la sœur,
            // pas son « Frais de Mars ». Lecture SOUS VERROU des lignes
            // candidates, juste avant l'écriture (§11).
            $fraisCible = $this->trouverFraisCible($fraisSource, $inscriptionCible, (float) $row->montant);

            $ancienStudentId = $row->student_id;

            // Les DEUX seules colonnes réécrites. Journalisé par Auditable
            // (le modèle logue toutes ses colonnes), plus l'entrée explicite
            // ci-dessous qui raconte le geste métier.
            $row->update([
                'inscription_fee_id' => $fraisCible->getKey(),
                'student_id' => $inscriptionCible->student_id,
            ]);

            // Les deux frais changent d'état : la source redevient due, la
            // cible se rapproche du solde.
            $this->recalculerStatutFee($fraisSource->getKey());
            $this->recalculerStatutFee($fraisCible->getKey());

            $this->recalculerMontantTotal($inscriptionSource);
            $this->recalculerMontantTotal($inscriptionCible);

            activity('encaissement')
                ->performedOn($row)
                ->event('fee_transferred_between_students')
                ->withProperties([
                    'montant' => number_format((float) $row->montant, 2, '.', ''),
                    'motif' => $motif,
                    // Le payeur d'origine : c'est lui qui a remis l'argent à
                    // la caisse, et la ligne ne le porte plus.
                    'ancien_etudiant_id' => $ancienStudentId,
                    'ancien_etudiant' => $inscriptionSource->student?->nomComplet(),
                    'ancienne_inscription' => $inscriptionSource->reference,
                    'ancien_frais_id' => $fraisSource->getKey(),
                    'ancien_frais' => $fraisSource->nom,
                    'nouvel_etudiant_id' => $inscriptionCible->student_id,
                    'nouvel_etudiant' => $inscriptionCible->student?->nomComplet(),
                    'nouvelle_inscription' => $inscriptionCible->reference,
                    'nouveau_frais_id' => $fraisCible->getKey(),
                    'nouveau_frais' => $fraisCible->nom,
                    'etablissement_id' => $inscriptionSource->etablissement_id,
                ])
                ->log(sprintf(
                    'Frais transféré : encaissement %s (%s MAD) déplacé de %s (%s) vers %s (%s) — motif : %s',
                    $row->reference,
                    number_format((float) $row->montant, 2, '.', ' '),
                    $inscriptionSource->student?->nomComplet() ?? '?',
                    $inscriptionSource->reference,
                    $inscriptionCible->student?->nomComplet() ?? '?',
                    $inscriptionCible->reference,
                    $motif,
                ));

            return $row->refresh();
        });
    }

    /**
     * Le frais de l'inscription cible que ce paiement vient solder — LA
     * règle vit dans Support\CibleTransfertFrais, partagée avec le dropdown
     * du modal : la liste n'offre que ce qui passera ici. Lecture SOUS
     * VERROU des lignes candidates, juste avant l'écriture (§11) — deux
     * transferts vers le même dossier voient chacun le reste réduit par
     * l'autre.
     */
    private function trouverFraisCible(InscriptionFee $fraisSource, Inscription $inscriptionCible, float $montant): InscriptionFee
    {
        $resolution = $this->cible->resoudre($fraisSource, $inscriptionCible, $montant, verrouiller: true);

        if ($resolution['frais'] === null) {
            throw ValidationException::withMessages([
                'inscription_id' => $this->cible->message($resolution['raison'], $fraisSource, $montant, $resolution['reste']),
            ]);
        }

        return $resolution['frais'];
    }

    private function recalculerStatutFee(int $fraisId): void
    {
        $fee = InscriptionFee::query()->whereKey($fraisId)->first();

        if ($fee === null) {
            return;
        }

        $paye = $fee->montantPaye();

        $fee->update([
            'statut' => match (true) {
                $paye >= (float) $fee->montant => InscriptionFee::STATUT_PAYE,
                $paye > 0 => InscriptionFee::STATUT_PAYE_PARTIELLEMENT,
                default => InscriptionFee::STATUT_NON_PAYE,
            },
        ]);
    }

    /**
     * Aucun frais n'est ajouté ni retiré ici — seul un PAIEMENT change de
     * ligne — donc `montant_total` (la somme des frais visibles) ne devrait
     * pas bouger. On le recalcule quand même sur les deux inscriptions :
     * c'est la même définition que partout ailleurs, et cela garantit qu'un
     * écart hérité (import, correction manuelle) ne survit pas à un geste
     * qui touche justement ces deux dossiers.
     */
    private function recalculerMontantTotal(Inscription $inscription): void
    {
        $inscription->update([
            'montant_total' => $inscription->fees()->whereNull('masque_le')->sum('montant') ?: null,
        ]);
    }
}
