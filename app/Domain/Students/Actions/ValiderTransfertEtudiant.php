<?php

declare(strict_types=1);

namespace App\Domain\Students\Actions;

use App\Domain\Registrations\Queries\GetGroupInscriptionFees;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Domain\Students\Support\SelectionPaiementsTransfert;
use App\Models\Cheque;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * VALIDATION d'un transfert d'étudiant vers un autre centre (25/09/2026,
 * super-admin uniquement — `student-transfers.validate` est dans
 * PermissionRegistry::superAdminOnly()).
 *
 * Décision métier (CEO, 25/09/2026) — « l'étudiant transféré emporte TOUT
 * son paiement ; le centre d'origine garde sa version (présences, historique
 * d'absences) ; le centre d'arrivée reçoit une copie de la fiche, Active,
 * affectée au groupe choisi à la demande ». Concrètement, dans UNE
 * transaction, sous verrou :
 *
 *  1. la fiche SOURCE est COPIÉE dans le centre cible (nouvelle référence
 *     ETU-, pas de `legacy_ref` — l'ancien CRM du centre d'arrivée ne l'a
 *     jamais connue ; photo et documents recopiés) ; la fiche source passe
 *     « Transféré » et les deux fiches se pointent l'une l'autre ;
 *  2. chaque inscription encore « Active » du centre source passe
 *     « Transférée » (date_fin = aujourd'hui, note ajoutée — jamais
 *     écrasée). Ses lignes de frais qui ont REÇU de l'argent sont
 *     DÉPLACÉES telles quelles sur le nouveau dossier (même mécanisme que
 *     « Changement de groupe » : la ligne bouge, ses encaissements la
 *     suivent, rien n'est détaché ni réécrit) ; ses lignes impayées sont
 *     MASQUÉES (`MASQUE_ORIGINE_TRANSFERT`), jamais supprimées ;
 *  3. un NOUVEAU dossier Actif est ouvert pour la copie dans le groupe
 *     cible, avec les frais du catalogue du groupe MOINS ceux déjà payés
 *     et déplacés (un « Frais d'inscription » réglé à Rabat ne se facture
 *     pas une seconde fois à Casablanca) ;
 *  4. l'argent SUIT la personne — SELON CE QUI A ÉTÉ COCHÉ à la demande
 *     (14/10/2026, `student_transfers.encaissement_ids`, règle unique dans
 *     `SelectionPaiementsTransfert::plan()`, rejouée ici SOUS VERROU) : une
 *     avance cochée passe sous le `student_id` de la copie ; un FRAIS coché
 *     est déplacé ENTIER sur le nouveau dossier, ses paiements avec lui —
 *     y compris depuis un dossier déjà clos (Changement, Annulée…) : un
 *     paiement reste un paiement, il n'est jamais converti en avance. Les
 *     chèques de l'étudiant suivent toujours (c'est la personne qu'ils
 *     désignent). Ce qui n'est pas coché reste l'histoire du centre source.
 *
 * ⚠ AUCUN MONTANT NE BOUGE et `caisses.solde` n'est ni lu ni écrit :
 * `montant`, `methode`, `date_paiement`, `caisse_id`, `agent_id` et
 * `encaissements.etablissement_id` (le stamp de ventilation, §11) sont
 * inchangés. L'argent a été encaissé dans une caisse du centre source et y
 * reste physiquement — la ventilation par centre, le journal de caisse et
 * le ledger continuent de le dire. Ce qui change est à QUI et à QUEL
 * DOSSIER cet argent est affecté : la liste des paiements et la fiche
 * étudiant le lisent par le `student_id` (« le centre d'un paiement est
 * celui de l'ÉTUDIANT », GetEncaissementsList), donc le centre d'arrivée
 * voit le frais payé et l'avance disponible. Rapatrier le cash reste un
 * transfert de caisse ordinaire, décidé par les caissiers.
 *
 * ⚠ LES PRÉSENCES NE SONT NI DÉPLACÉES NI COPIÉES : une ligne d'appel
 * appartient à une séance d'un groupe du centre source. Les recopier sur
 * la fiche d'arrivée doublerait les statistiques d'absence de ce groupe.
 * L'historique reste lisible sur la fiche d'origine, que la copie référence
 * (`transfere_depuis_student_id`).
 *
 * Tests : tests/Feature/Backoffice/Students/TransfertEtudiantCentreTest.php
 */
final class ValiderTransfertEtudiant
{
    public function __construct(
        private readonly GetGroupInscriptionFees $getGroupInscriptionFees,
        private readonly CurrentContext $context,
    ) {}

    public function handle(StudentTransfer $transfert, User $validatedBy): StudentTransfer
    {
        return DB::transaction(function () use ($transfert, $validatedBy): StudentTransfer {
            $transfert = StudentTransfer::query()->whereKey($transfert->getKey())->lockForUpdate()->firstOrFail();

            // Re-vérifié SOUS VERROU, jamais avant la transaction : un
            // double-clic sur « Valider » créerait deux copies et deux
            // dossiers (§11).
            if (! $transfert->estEnAttente()) {
                throw ValidationException::withMessages([
                    'statut' => __('This transfer request has already been processed.'),
                ]);
            }

            $source = Student::query()->whereKey($transfert->student_id)->lockForUpdate()->firstOrFail();

            if ($source->estTransfere()) {
                throw ValidationException::withMessages([
                    'statut' => __('This student has already been transferred to another center.'),
                ]);
            }

            if ((int) $source->etablissement_id === (int) $transfert->etablissement_cible_id) {
                throw ValidationException::withMessages([
                    'etablissement_cible_id' => __("The target center must be different from the student's current center."),
                ]);
            }

            // Le groupe a pu être clôturé ou supprimé depuis la demande.
            $groupe = DemanderTransfertEtudiant::assertGroupeCible(
                $transfert->group_cible_id === null ? null : Group::query()->whereKey($transfert->group_cible_id)->lockForUpdate()->first(),
                (int) $transfert->etablissement_cible_id,
            );

            // Le plan des paiements cochés, relu sur les lignes VERROUILLÉES :
            // un remboursement ou une application saisis depuis la demande
            // changent la réponse, et c'est ici qu'elle compte.
            $plan = SelectionPaiementsTransfert::plan($source, $transfert->encaissement_ids, lock: true);

            // 1. La copie dans le centre cible.
            $copie = $this->copierFiche($source, $transfert);

            // 2. Clôture des dossiers Actifs du centre source ; les lignes
            //    de frais cochées sont collectées pour être déplacées.
            $actives = $source->inscriptions()
                ->where('statut', Inscription::STATUT_ACTIVE)
                ->lockForUpdate()
                ->get();

            $feesDeplaces = collect();
            $feesMasques = 0;

            foreach ($actives as $inscription) {
                [$payes, $masques] = $this->partagerFrais($inscription, $plan['feesEntiers']);
                $feesDeplaces = $feesDeplaces->merge($payes);
                $feesMasques += $masques;

                $inscription->update([
                    'statut' => Inscription::STATUT_TRANSFEREE,
                    'date_fin' => now()->toDateString(),
                    'note' => $this->appendNote($inscription->note, sprintf(
                        'Transféré vers %s le %s (%s)',
                        $transfert->etablissementCible?->nom_centre ?? '#'.$transfert->etablissement_cible_id,
                        now()->format('d/m/Y'),
                        $transfert->reference,
                    )),
                ]);
            }

            // Les frais cochés d'un dossier déjà CLOS partent aussi, tels
            // quels : le dossier garde son statut et son motif, seule la
            // ligne de frais change de dossier.
            $dejaCollectes = $feesDeplaces->pluck('id')->map(fn ($id): int => (int) $id)->all();
            $feesDossiersClos = InscriptionFee::query()
                ->whereIn('id', array_diff($plan['feesEntiers'], $dejaCollectes))
                ->lockForUpdate()
                ->get();
            $feesDeplaces = $feesDeplaces->merge($feesDossiersClos);
            $inscriptionsClosesTouchees = Inscription::query()
                ->whereIn('id', $feesDossiersClos->pluck('inscription_id')->unique()->all())
                ->lockForUpdate()
                ->get();

            // 3. Le nouveau dossier Actif dans le groupe cible.
            $nouvelle = $this->creerInscription($copie, $groupe, $transfert, $feesDeplaces, $validatedBy);

            // Déplacement des lignes payées : par modèle, jamais en masse,
            // pour que le re-parentage d'un frais payé soit journalisé
            // (même règle que ChangerGroupeInscription).
            $feesDeplaces->each(fn (InscriptionFee $fee) => $fee->update(['inscription_id' => $nouvelle->id]));

            foreach ($actives->merge($inscriptionsClosesTouchees) as $inscription) {
                $inscription->update([
                    'montant_total' => $inscription->fees()->whereNull('masque_le')->sum('montant') ?: null,
                ]);
            }

            $nouvelle->update([
                'montant_total' => $nouvelle->fees()->whereNull('masque_le')->sum('montant') ?: null,
            ]);

            // 4. L'argent suit la personne — ce qui a été coché.
            $encaissementsDeplaces = $this->deplacerArgent($source, $copie, $feesDeplaces, $plan['avances']);
            $montant = $plan['montant'];
            $chequesDeplaces = $this->deplacerCheques($source, $copie);

            // 5. La fiche source est close.
            $source->update([
                'statut' => Student::STATUT_TRANSFERE,
                'transfere_vers_student_id' => $copie->id,
            ]);

            $transfert->update([
                'statut' => StudentTransfer::STATUT_VALIDE,
                'nouveau_student_id' => $copie->id,
                'nouvelle_inscription_id' => $nouvelle->id,
                'montant_transfere' => $montant,
                'decided_by' => $validatedBy->id,
                'decided_at' => now(),
            ]);

            activity('student')
                ->performedOn($source)
                ->event('student_transferred')
                ->withProperties([
                    'transfert_reference' => $transfert->reference,
                    'transfert_id' => $transfert->id,
                    'source_student_id' => $source->id,
                    'source_reference' => $source->reference,
                    'nouveau_student_id' => $copie->id,
                    'nouvelle_reference' => $copie->reference,
                    'etablissement_source_id' => $transfert->etablissement_source_id,
                    'etablissement_cible_id' => $transfert->etablissement_cible_id,
                    'group_cible_id' => $groupe->id,
                    'nouvelle_inscription_id' => $nouvelle->id,
                    'inscriptions_transferees' => $actives->pluck('reference')->all(),
                    'frais_deplaces' => $feesDeplaces->pluck('id')->all(),
                    'frais_masques' => $feesMasques,
                    'encaissements_deplaces' => $encaissementsDeplaces,
                    'encaissements_coches' => $plan['ids'],
                    'cheques_deplaces' => $chequesDeplaces,
                    'montant_transfere' => number_format($montant, 2, '.', ''),
                    'motif' => $transfert->motif,
                    'valide_par' => $validatedBy->name,
                ])
                ->log(sprintf(
                    'Étudiant %s transféré vers %s (%s) : %s DH emportés, nouvelle fiche %s',
                    $source->reference,
                    $transfert->etablissementCible?->nom_centre ?? '#'.$transfert->etablissement_cible_id,
                    $transfert->reference,
                    number_format($montant, 2, ',', ' '),
                    $copie->reference,
                ));

            return $transfert;
        });
    }

    /**
     * Copie « coller » de la fiche : mêmes données d'identité, de contact et
     * de parent, nouvelle référence, centre cible, aucun `legacy_ref`.
     */
    private function copierFiche(Student $source, StudentTransfer $transfert): Student
    {
        $attributs = collect($source->getAttributes())
            ->except([
                'id', 'reference', 'legacy_ref', 'legacy_source', 'etablissement_id',
                'statut', 'transfere_vers_student_id', 'transfere_depuis_student_id',
                'created_at', 'updated_at',
            ])
            ->all();

        $copie = Student::create([
            ...$attributs,
            'reference' => ReferenceGenerator::make('ETU', 'students'),
            'etablissement_id' => $transfert->etablissement_cible_id,
            'statut' => Student::STATUT_ACTIF,
            'transfere_depuis_student_id' => $source->id,
        ]);

        foreach (['photo', 'documents'] as $collection) {
            $source->getMedia($collection)->each(fn (Media $media) => $media->copy($copie, $collection));
        }

        return $copie;
    }

    /**
     * Trie les lignes VISIBLES d'un dossier en trois : celles cochées
     * ENTIÈRES partent avec l'étudiant ; celles qui gardent de l'argent
     * (net des remboursements) RESTENT ici — un paiement non coché, ou
     * conservé par une sélection partielle, est l'histoire de ce centre et
     * ne se masque jamais ; celles sans un dirham sont masquées, elles ne
     * sont plus dues ici.
     *
     * @param  list<int>  $feesEntiers
     * @return array{0: Collection<int, InscriptionFee>, 1: int}
     */
    private function partagerFrais(Inscription $inscription, array $feesEntiers): array
    {
        $fees = $inscription->fees()->whereNull('masque_le')->lockForUpdate()->get();

        [$partent, $restent] = $fees->partition(fn (InscriptionFee $fee): bool => in_array((int) $fee->id, $feesEntiers, true));

        $impayes = $restent->filter(fn (InscriptionFee $fee): bool => $fee->montantPaye() <= 0);

        $impayes->each(fn (InscriptionFee $fee) => $fee->update([
            'masque_le' => now(),
            'masque_origine' => InscriptionFee::MASQUE_ORIGINE_TRANSFERT,
        ]));

        return [$partent->values(), $impayes->count()];
    }

    /**
     * @param  Collection<int, InscriptionFee>  $feesDeplaces
     */
    private function creerInscription(Student $copie, Group $groupe, StudentTransfer $transfert, Collection $feesDeplaces, User $validatedBy): Inscription
    {
        // Une ligne déplacée remplace la ligne du catalogue du groupe pour
        // la même entrée : un frais payé ne se facture pas deux fois.
        $fraisDeplaces = $feesDeplaces->pluck('frais_id')->filter()->all();

        $lignes = $this->getGroupInscriptionFees->__invoke($groupe)
            ->reject(fn (array $fee): bool => in_array($fee['fraisId'], $fraisDeplaces, true))
            ->map(fn (array $fee): array => [
                'frais_id' => $fee['fraisId'],
                'nom' => $fee['nom'],
                'montant_initial' => (float) $fee['montantInitial'],
                'remise_pct' => null,
                'remise_montant' => null,
                'montant' => (float) $fee['montantInitial'],
                'date_echeance' => $fee['dateEcheance'],
                'statut' => InscriptionFee::STATUT_NON_PAYE,
            ]);

        $nouvelle = Inscription::create([
            'reference' => ReferenceGenerator::make('INS', 'inscriptions'),
            'student_id' => $copie->id,
            'group_id' => $groupe->id,
            'etablissement_id' => $groupe->etablissement_id,
            'annee_scolaire_id' => $groupe->annee_scolaire_id ?? $this->context->anneeScolaireId(),
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => now()->toDateString(),
            'date_debut' => now()->toDateString(),
            'date_fin' => $groupe->date_fin_formation?->toDateString(),
            'montant_total' => $lignes->sum('montant') ?: null,
            'note' => sprintf(
                'Transféré depuis %s le %s (%s)',
                $transfert->etablissementSource?->nom_centre ?? '#'.$transfert->etablissement_source_id,
                now()->format('d/m/Y'),
                $transfert->reference,
            ),
            'created_by' => $validatedBy->employee?->id,
        ]);

        foreach ($lignes as $ligne) {
            $nouvelle->fees()->create($ligne);
        }

        return $nouvelle;
    }

    /**
     * Les encaissements qui suivent l'étudiant : les avances COCHÉES et les
     * lignes posées sur un frais déplacé — y compris les lignes
     * d'application d'avance qui y reposent. Seule la colonne `student_id`
     * est réécrite, en masse comme FusionnerEtudiants ; le détail est dans
     * l'entrée de journal du transfert.
     *
     * @param  Collection<int, InscriptionFee>  $feesDeplaces
     * @param  list<int>  $avances
     * @return list<int> ids déplacés
     */
    private function deplacerArgent(Student $source, Student $copie, Collection $feesDeplaces, array $avances): array
    {
        $rows = Encaissement::query()
            ->where('student_id', $source->id)
            ->where(fn ($q) => $q
                ->whereIn('id', $avances)
                ->orWhereIn('inscription_fee_id', $feesDeplaces->pluck('id')->all()))
            ->lockForUpdate()
            ->get(['id']);

        if ($rows->isEmpty()) {
            return [];
        }

        Encaissement::query()->whereIn('id', $rows->pluck('id'))->update(['student_id' => $copie->id]);

        return $rows->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * Un chèque désigne son PROPRIÉTAIRE (`cheques.student_id`) : c'est la
     * personne, qui part. Sans cela, le reste d'un chèque de garantie ne
     * pourrait plus financer un paiement de la copie
     * (EncaissementController@store refuse le chèque d'un autre étudiant).
     *
     * @return list<int>
     */
    private function deplacerCheques(Student $source, Student $copie): array
    {
        $ids = Cheque::query()->where('student_id', $source->id)->lockForUpdate()->pluck('id');

        if ($ids->isEmpty()) {
            return [];
        }

        Cheque::query()->whereIn('id', $ids)->update(['student_id' => $copie->id]);

        return $ids->all();
    }

    private function appendNote(?string $existing, string $added): string
    {
        return trim(($existing ?? '') === '' ? $added : $existing."\n".$added);
    }
}
