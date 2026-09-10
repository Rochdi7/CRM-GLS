<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Activity;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * « Transfert d'un frais payé vers l'inscription d'un autre étudiant »
 * (10/09/2026, TransfererFraisVersAutreEtudiant).
 *
 * Le cas métier : un étudiant s'inscrit, paie, ne vient jamais ; sa sœur
 * prend sa place. Le geste déplace UNIQUEMENT le paiement — il ne crée
 * aucune inscription et n'en clôture aucune — et le frais cible est
 * DÉTECTÉ (même entrée du catalogue), jamais choisi à la main.
 *
 * Ce que ces tests verrouillent, par ordre d'importance :
 *   1. ZÉRO présence sur le dossier SOURCE — une seule ligne d'appel,
 *      quel qu'en soit le statut, interdit le transfert ;
 *   2. aucun encaissement n'est réécrit (date, montant, méthode, caisse,
 *      agent) et `caisses.solde` ne bouge pas ;
 *   3. la détection du frais cible : même catalogue, visible, reste dû ;
 *   4. le dossier du frère reste OUVERT et redevient dû ;
 *   5. même centre, motif obligatoire, permission dédiée, et les lignes
 *      qui ne se transfèrent jamais (avance, application, chèque, remboursé).
 */
final class TransfertFraisAutreEtudiantTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    private function transferrer(): User
    {
        return $this->userWith('payments.view', 'payments.create', 'payments.transfer-student');
    }

    private function makeGroup(?Etablissement $centre = null): Group
    {
        return Group::factory()->create([
            'etablissement_id' => ($centre ?? $this->centre)->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    private function inscription(Group $group, string $reference, ?Etablissement $centre = null): Inscription
    {
        $student = Student::factory()->create(['etablissement_id' => ($centre ?? $this->centre)->id]);

        return Inscription::create([
            'reference' => $reference,
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => ($centre ?? $this->centre)->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-09-15',
        ]);
    }

    private function fee(Inscription $inscription, float $montant, string $statut = InscriptionFee::STATUT_NON_PAYE, string $nom = 'Frais de rentrée'): InscriptionFee
    {
        $frais = Frais::firstOrCreate(['nom' => $nom], ['statut' => 'Actif']);

        return InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'frais_id' => $frais->id,
            'nom' => $nom,
            'montant_initial' => $montant,
            'montant' => $montant,
            'date_echeance' => '2026-01-01',
            'statut' => $statut,
        ]);
    }

    private function paiement(Inscription $inscription, InscriptionFee $fee, float $montant): Encaissement
    {
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        return Encaissement::create([
            'reference' => 'ENC-TR'.(++self::$sequence),
            'student_id' => $inscription->student_id,
            'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id,
            'agent_id' => $agent->id,
            'montant' => $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2025-09-20',
        ]);
    }

    private function appeler(Inscription $inscription, string $statut): void
    {
        $seance = Seance::create([
            'group_id' => $inscription->group_id,
            'date_seance' => '2025-10-01',
            'heure_debut' => '19:00', 'heure_fin' => '21:00',
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_EFFECTUEE,
        ]);

        Presence::create([
            'seance_id' => $seance->id,
            'student_id' => $inscription->student_id,
            'statut' => $statut,
        ]);
    }

    /**
     * Décor standard : le frère a payé 500 DH de « Frais de rentrée », la
     * sœur est déjà inscrite et doit 500 DH du MÊME frais.
     *
     * @return array{0: Inscription, 1: InscriptionFee, 2: Encaissement, 3: Inscription, 4: InscriptionFee}
     */
    private function scenario(float $montant = 500, float $du = 500): array
    {
        $group = $this->makeGroup();
        $frere = $this->inscription($group, 'INS-FRERE');
        $feeFrere = $this->fee($frere, $montant, InscriptionFee::STATUT_PAYE);
        $paiement = $this->paiement($frere, $feeFrere, $montant);

        $soeur = $this->inscription($group, 'INS-SOEUR');
        $feeSoeur = $this->fee($soeur, $du);

        return [$frere, $feeFrere, $paiement, $soeur, $feeSoeur];
    }

    /**
     * `post`, pas `postJson` : l'écran est une page Inertia, qui poste en
     * formulaire — un refus revient donc en redirect + session errors, pas
     * en 422 JSON. Tester en JSON exercerait un chemin que l'application
     * n'emprunte jamais.
     */
    private function transferer(Encaissement $paiement, Inscription $cible, string $motif = 'La sœur reprend la place, le frère ne suit pas les cours.')
    {
        return $this->from(route('backoffice.encaissements.index'))
            ->post(route('backoffice.encaissements.transferer-frais-etudiant'), [
                'encaissement_id' => $paiement->id,
                'inscription_id' => $cible->id,
                'motif' => $motif,
            ]);
    }

    // ---------------------------------------------------------------
    // 1. LA BORNE — zéro présence sur le dossier SOURCE
    // ---------------------------------------------------------------

    /**
     * ⚠ TOUTE ligne d'appel bloque, « Absent » et « Justifié » compris : le
     * critère n'est pas « a-t-il assisté ? » mais « son nom a-t-il été
     * appelé dans ce groupe ? ». Assouplir vers « Présent/Retard uniquement »
     * ferait de la règle « transférable tant que l'étudiant sèche », soit
     * l'inverse exact de l'intention.
     */
    #[DataProvider('statutsQuiBloquent')]
    public function test_any_attendance_on_the_source_blocks_the_transfer(string $statut): void
    {
        $this->actingAs($this->transferrer());
        [$frere, $feeFrere, $paiement, $soeur] = $this->scenario();
        $this->appeler($frere, $statut);

        $this->transferer($paiement, $soeur)->assertSessionHasErrors('encaissement_id');

        // L'argent n'a pas bougé d'un pouce.
        $this->assertSame($feeFrere->id, $paiement->fresh()->inscription_fee_id);
        $this->assertSame($frere->student_id, $paiement->fresh()->student_id);
    }

    /** @return array<string, array{0: string}> */
    public static function statutsQuiBloquent(): array
    {
        return [
            'Présent' => [Presence::STATUT_PRESENT],
            'Absent' => [Presence::STATUT_ABSENT],
            'Retard' => [Presence::STATUT_RETARD],
            'Justifié' => [Presence::STATUT_JUSTIFIE],
        ];
    }

    /**
     * Une présence de la SŒUR ne bloque rien : c'est le dossier qui PERD
     * l'argent qui doit être vierge, pas celui qui le reçoit. La sœur peut
     * déjà avoir commencé à étudier avant que la paperasse soit faite.
     */
    public function test_attendance_on_the_target_does_not_block(): void
    {
        $this->actingAs($this->transferrer());
        [, , $paiement, $soeur, $feeSoeur] = $this->scenario();
        $this->appeler($soeur, Presence::STATUT_PRESENT);

        $this->transferer($paiement, $soeur)->assertSessionHasNoErrors();

        $this->assertSame($feeSoeur->id, $paiement->fresh()->inscription_fee_id);
    }

    /**
     * ⚠ `inscriptions.group_id` est NULLABLE (`nullOnDelete`). Sans groupe,
     * il n'existe aucune séance à interroger : le compte de présences vaut 0,
     * ce qui se lirait « jamais consommé » alors que la vérité est
     * « invérifiable ». Le refus doit être explicite, sinon un dossier dont
     * on a supprimé le groupe devient transférable par accident — le sens le
     * plus dangereux de l'erreur.
     */
    public function test_a_registration_detached_from_its_group_cannot_be_transferred(): void
    {
        $this->actingAs($this->transferrer());
        [$frere, $feeFrere, $paiement, $soeur] = $this->scenario();

        $frere->update(['group_id' => null]);

        $this->transferer($paiement, $soeur)->assertSessionHasErrors('encaissement_id');

        $this->assertSame($feeFrere->id, $paiement->fresh()->inscription_fee_id);
    }

    // ---------------------------------------------------------------
    // 2. L'ARGENT — rien n'est réécrit, la caisse ne bouge pas
    // ---------------------------------------------------------------

    /**
     * LE test qui compte : seules l'affectation et le bénéficiaire changent.
     * La caisse du premier employé (celui qui a encaissé) n'est ni débitée,
     * ni réattribuée : `caisse_id`, `agent_id` et `caisses.solde` sont
     * identiques avant et après.
     */
    public function test_it_moves_the_payment_without_rewriting_it_nor_touching_the_till(): void
    {
        $this->actingAs($this->transferrer());
        [$frere, $feeFrere, $paiement, $soeur, $feeSoeur] = $this->scenario();
        $caisseAvant = $paiement->caisse_id;
        $agentAvant = $paiement->agent_id;
        $soldeAvant = (string) $paiement->caisse->fresh()->solde;

        $this->transferer($paiement, $soeur)->assertSessionHasNoErrors();

        $apres = $paiement->fresh();
        // L'affectation suit la sœur…
        $this->assertSame($feeSoeur->id, $apres->inscription_fee_id);
        $this->assertSame($soeur->student_id, $apres->student_id);
        // …mais RIEN du paiement lui-même n'a été réécrit.
        $this->assertSame('2025-09-20', $apres->date_paiement->toDateString());
        $this->assertSame('500.00', (string) $apres->montant);
        $this->assertSame('Espèces', $apres->methode);
        $this->assertSame($caisseAvant, $apres->caisse_id);
        $this->assertSame($agentAvant, $apres->agent_id);
        // Aucun mouvement de caisse : l'argent y est depuis le premier jour.
        $this->assertSame($soldeAvant, (string) Caisse::findOrFail($caisseAvant)->solde);

        // Les deux frais changent d'état.
        $this->assertSame(InscriptionFee::STATUT_PAYE, $feeSoeur->fresh()->statut);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $feeFrere->fresh()->statut);
        $this->assertSame(0.0, $feeFrere->fresh()->montantPaye());
        $this->assertSame(500.0, $feeSoeur->fresh()->montantPaye());

        // Le dossier du frère reste OUVERT — le transfert ne décide pas de
        // son sort ; il redevient simplement dû.
        $this->assertSame(Inscription::STATUT_ACTIVE, $frere->fresh()->statut);
    }

    // ---------------------------------------------------------------
    // 3. LA DÉTECTION DU FRAIS CIBLE — même catalogue, visible, reste dû
    // ---------------------------------------------------------------

    /**
     * Le frais est choisi par le SYSTÈME : « Frais de rentrée » du frère se
     * pose sur « Frais de rentrée » de la sœur, jamais sur son « Frais de
     * Mars », même si celui-ci a le plus gros reste dû.
     */
    public function test_it_picks_the_same_catalog_fee_on_the_target(): void
    {
        $this->actingAs($this->transferrer());
        [, , $paiement, $soeur, $feeSoeur] = $this->scenario();
        $feeMars = $this->fee($soeur, 2000, InscriptionFee::STATUT_NON_PAYE, 'Frais de Mars');

        $this->transferer($paiement, $soeur)->assertSessionHasNoErrors();

        $this->assertSame($feeSoeur->id, $paiement->fresh()->inscription_fee_id);
        $this->assertSame(0.0, $feeMars->fresh()->montantPaye());
    }

    /** Pas de ligne du même frais sur la cible : refus explicite, rien ne bouge. */
    public function test_it_refuses_when_the_target_has_no_matching_fee_line(): void
    {
        $this->actingAs($this->transferrer());
        $group = $this->makeGroup();
        $frere = $this->inscription($group, 'INS-FRERE');
        $feeFrere = $this->fee($frere, 500, InscriptionFee::STATUT_PAYE);
        $paiement = $this->paiement($frere, $feeFrere, 500);
        $soeur = $this->inscription($group, 'INS-SOEUR');
        $this->fee($soeur, 500, InscriptionFee::STATUT_NON_PAYE, 'Frais de Mars');

        $this->transferer($paiement, $soeur)->assertSessionHasErrors('inscription_id');

        $this->assertSame($feeFrere->id, $paiement->fresh()->inscription_fee_id);
    }

    /**
     * Audit R-01 : l'argent ne se pose jamais sur une ligne masquée (elle
     * n'est plus due). Même garde qu'AppliquerAvance et
     * EnregistrerEncaissement.
     */
    public function test_it_refuses_a_hidden_target_fee_line(): void
    {
        $this->actingAs($this->transferrer());
        [, $feeFrere, $paiement, $soeur, $feeSoeur] = $this->scenario();
        $feeSoeur->update(['masque_le' => now()]);

        $this->transferer($paiement, $soeur)->assertSessionHasErrors('inscription_id');

        $this->assertSame($feeFrere->id, $paiement->fresh()->inscription_fee_id);
    }

    /**
     * On ne verse jamais plus que ce que le frais cible doit encore : le
     * montant transféré est celui du paiement, il n'est pas fractionné.
     */
    public function test_it_refuses_a_payment_larger_than_what_the_target_fee_owes(): void
    {
        $this->actingAs($this->transferrer());
        [, $feeFrere, $paiement, $soeur] = $this->scenario(montant: 500, du: 300);

        $this->transferer($paiement, $soeur)->assertSessionHasErrors('inscription_id');

        $this->assertSame($feeFrere->id, $paiement->fresh()->inscription_fee_id);
    }

    // ---------------------------------------------------------------
    // 4. LES LIGNES QUI NE SE TRANSFÈRENT JAMAIS
    // ---------------------------------------------------------------

    public function test_a_refunded_payment_cannot_be_transferred(): void
    {
        $this->actingAs($this->transferrer());
        [$frere, $feeFrere, $paiement, $soeur] = $this->scenario();

        $paiement->remboursements()->create([
            'reference' => 'RMB-TR1',
            'beneficiaire_id' => $frere->student_id,
            'caisse_id' => $paiement->caisse_id,
            'agent_id' => $paiement->agent_id,
            'montant' => 500,
            'methode_paiement' => 'Espèces',
            'date_remboursement' => '2025-10-01',
            'motif' => 'Test',
        ]);

        $this->transferer($paiement, $soeur)->assertSessionHasErrors('encaissement_id');

        $this->assertSame($feeFrere->id, $paiement->fresh()->inscription_fee_id);
    }

    /** Une avance n'est rattachée à aucun frais : elle n'a rien à céder. */
    public function test_an_advance_cannot_be_transferred(): void
    {
        $this->actingAs($this->transferrer());
        [, , $paiement, $soeur] = $this->scenario();
        $paiement->update(['inscription_fee_id' => null]);

        $this->transferer($paiement, $soeur)->assertSessionHasErrors('encaissement_id');
    }

    /**
     * ⚠ Une ligne d'APPLICATION d'avance porte bien un `inscription_fee_id`,
     * donc elle franchit le test « est-ce une avance ? ». Mais son argent
     * appartient à l'avance PARENTE, qui reste au nom de l'étudiant
     * d'origine : la transférer ferait financer la sœur par l'avance du
     * frère, sans que l'onglet Avances puisse l'expliquer.
     *
     * Le read-model masque ces lignes de l'onglet Encaissements, mais un id
     * forgé atteint l'endpoint : la garde doit vivre côté serveur.
     */
    public function test_an_advance_allocation_row_cannot_be_transferred(): void
    {
        $this->actingAs($this->transferrer());
        [$frere, $feeFrere, $paiement, $soeur] = $this->scenario();

        $avance = $this->paiement($frere, $feeFrere, 500);
        $avance->update(['inscription_fee_id' => null]);
        $paiement->update(['applied_from_encaissement_id' => $avance->id]);

        $this->transferer($paiement, $soeur)->assertSessionHasErrors('encaissement_id');

        $this->assertSame($feeFrere->id, $paiement->fresh()->inscription_fee_id);
        $this->assertSame($frere->student_id, $paiement->fresh()->student_id);
    }

    /**
     * Symétrique : un paiement qui a lui-même SERVI de source à des
     * applications laisserait ses lignes filles au nom de l'ancien étudiant.
     */
    public function test_a_payment_that_funded_allocations_cannot_be_transferred(): void
    {
        $this->actingAs($this->transferrer());
        [$frere, $feeFrere, $paiement, $soeur] = $this->scenario();

        $fille = $this->paiement($frere, $feeFrere, 100);
        $fille->update(['applied_from_encaissement_id' => $paiement->id]);

        $this->transferer($paiement, $soeur)->assertSessionHasErrors('encaissement_id');

        $this->assertSame($feeFrere->id, $paiement->fresh()->inscription_fee_id);
    }

    /**
     * ⚠ TOUT chèque suivi bloque, pas seulement un chèque rejeté.
     *
     * Un chèque appartient à quelqu'un (`cheques.student_id`) et
     * EncaissementController@store refuse déjà de payer avec le chèque d'un
     * AUTRE étudiant. Transférer un paiement adossé à un chèque
     * contournerait cette invariante par la porte de derrière : le chèque du
     * frère solderait le frais de la sœur.
     */
    public function test_a_payment_backed_by_a_tracked_cheque_cannot_be_transferred(): void
    {
        $this->actingAs($this->transferrer());
        [$frere, $feeFrere, $paiement, $soeur] = $this->scenario();

        $cheque = Cheque::create([
            'reference' => 'CHQ-TR1',
            'source' => Cheque::SOURCE_ETUDIANT,
            'type' => Cheque::TYPE_A_DEPOSER,
            'student_id' => $frere->student_id,
            'etablissement_id' => $this->centre->id,
            'agent_id' => $paiement->agent_id,
            'montant' => 500,
            'numero_cheque' => '123456',
            'banque' => 'Banque test',
            'date_reception' => '2025-09-20',
            'date_echeance' => '2025-12-01',
            'statut' => Cheque::STATUT_EN_POSSESSION,
        ]);
        $paiement->update(['cheque_id' => $cheque->id, 'methode' => 'Chèque']);

        $this->transferer($paiement, $soeur)->assertSessionHasErrors('encaissement_id');

        $this->assertSame($feeFrere->id, $paiement->fresh()->inscription_fee_id);
        $this->assertSame($frere->student_id, $paiement->fresh()->student_id);
        // Le chèque reste au nom de son propriétaire.
        $this->assertSame($frere->student_id, $cheque->fresh()->student_id);
    }

    /**
     * Le dropdown « Inscription » du modal n'offre QUE les dossiers qui
     * passeront : ligne du même frais présente, visible, et pas déjà
     * soldée (10/09/2026 : « si le frais est déjà payé sur cette
     * inscription, ne pas la proposer »). Même règle que l'action
     * (CibleTransfertFrais), donc jamais « proposé puis refusé ».
     */
    public function test_the_target_dropdown_only_lists_registrations_that_can_receive_the_payment(): void
    {
        $this->actingAs($this->transferrer());
        $group = $this->makeGroup();
        $frere = $this->inscription($group, 'INS-FRERE');
        $feeFrere = $this->fee($frere, 500, InscriptionFee::STATUT_PAYE);
        $paiement = $this->paiement($frere, $feeFrere, 500);

        // Sœur A : même frais, encore dû → proposée, avec son reste.
        $soeurA = $this->inscription($group, 'INS-A');
        $this->fee($soeurA, 500);
        // Sœur B : même frais, DÉJÀ PAYÉ → absente.
        $soeurB = $this->inscription($group, 'INS-B');
        $feeB = $this->fee($soeurB, 500, InscriptionFee::STATUT_PAYE);
        $this->paiement($soeurB, $feeB, 500);
        // Sœur C : n'a pas ce frais du tout → absente.
        $soeurC = $this->inscription($group, 'INS-C');
        $this->fee($soeurC, 500, InscriptionFee::STATUT_NON_PAYE, 'Frais de Mars');
        // Sœur D : même frais, mais reste dû insuffisant (300 < 500) → absente.
        $soeurD = $this->inscription($group, 'INS-D');
        $this->fee($soeurD, 300);

        $url = fn (Inscription $i): string => route('backoffice.encaissements.transfer-targets', [$paiement, $i->student_id]);

        $a = $this->getJson($url($soeurA))->assertOk()->json('inscriptions');
        $this->assertCount(1, $a);
        $this->assertSame($soeurA->id, $a[0]['id']);
        $this->assertSame('500.00', $a[0]['reste']);

        $this->assertSame([], $this->getJson($url($soeurB))->assertOk()->json('inscriptions'));
        $this->assertSame([], $this->getJson($url($soeurC))->assertOk()->json('inscriptions'));
        $this->assertSame([], $this->getJson($url($soeurD))->assertOk()->json('inscriptions'));
    }

    /** Sans la permission, le dropdown ne révèle rien. */
    public function test_the_target_dropdown_requires_the_permission(): void
    {
        $this->actingAs($this->userWith('payments.view', 'payments.create'));
        [, , $paiement, $soeur] = $this->scenario();

        $this->getJson(route('backoffice.encaissements.transfer-targets', [$paiement, $soeur->student_id]))
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // 5. LES AUTRES BORNES — centre, même étudiant, motif, permission, trace
    // ---------------------------------------------------------------

    /**
     * L'argent d'un centre solde un frais de ce centre : un transfert
     * inter-centres déplacerait du chiffre d'affaires d'un établissement à
     * un autre alors que la caisse créditée, elle, ne bouge pas.
     */
    public function test_the_target_must_be_in_the_same_centre(): void
    {
        $this->actingAs($this->transferrer());
        [, $feeFrere, $paiement] = $this->scenario();

        $autreCentre = Etablissement::factory()->create();
        $autreInscription = $this->inscription($this->makeGroup($autreCentre), 'INS-AUTRE', $autreCentre);
        $this->fee($autreInscription, 500);

        $this->transferer($paiement, $autreInscription)->assertSessionHasErrors('inscription_id');

        $this->assertSame($feeFrere->id, $paiement->fresh()->inscription_fee_id);
    }

    /** Vers le MÊME étudiant, ce geste n'a pas de sens (outil dédié). */
    public function test_it_refuses_a_target_of_the_same_student(): void
    {
        $this->actingAs($this->transferrer());
        $group = $this->makeGroup();
        $frere = $this->inscription($group, 'INS-FRERE');
        $feeA = $this->fee($frere, 500, InscriptionFee::STATUT_PAYE);
        $paiement = $this->paiement($frere, $feeA, 500);

        $autreDossier = Inscription::create([
            'reference' => 'INS-FRERE-2', 'student_id' => $frere->student_id,
            'group_id' => $this->makeGroup()->id, 'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id, 'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-09-15',
        ]);
        $this->fee($autreDossier, 500);

        $this->transferer($paiement, $autreDossier)->assertSessionHasErrors('inscription_id');

        $this->assertSame($feeA->id, $paiement->fresh()->inscription_fee_id);
    }

    /**
     * Le motif est ce que le journal conserve pour expliquer pourquoi cet
     * argent a payé pour quelqu'un d'autre.
     */
    public function test_the_reason_is_mandatory(): void
    {
        $this->actingAs($this->transferrer());
        [, $feeFrere, $paiement, $soeur] = $this->scenario();

        $this->from(route('backoffice.encaissements.index'))
            ->post(route('backoffice.encaissements.transferer-frais-etudiant'), [
                'encaissement_id' => $paiement->id,
                'inscription_id' => $soeur->id,
            ])->assertSessionHasErrors('motif');

        $this->assertSame($feeFrere->id, $paiement->fresh()->inscription_fee_id);
    }

    /** Le geste laisse une trace lisible, motif compris. */
    public function test_it_journals_both_students_and_the_reason(): void
    {
        $this->actingAs($this->transferrer());
        [$frere, , $paiement, $soeur] = $this->scenario();
        $motif = 'Le frère a payé mais n\'étudie pas ; sa sœur prend la place.';

        $this->transferer($paiement, $soeur, $motif)->assertSessionHasNoErrors();

        $entry = Activity::query()
            ->where('event', 'fee_transferred_between_students')
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame($motif, $entry->properties['motif']);
        $this->assertSame($frere->student_id, $entry->properties['ancien_etudiant_id']);
        $this->assertSame($soeur->student_id, $entry->properties['nouvel_etudiant_id']);
        $this->assertSame('INS-FRERE', $entry->properties['ancienne_inscription']);
        $this->assertSame('INS-SOEUR', $entry->properties['nouvelle_inscription']);
    }

    /**
     * Le front-office constate l'arrangement de famille, il ne l'arbitre
     * pas : pouvoir enregistrer un paiement ne donne pas le droit de le
     * faire payer pour quelqu'un d'autre.
     */
    public function test_creating_payments_is_not_enough(): void
    {
        $this->actingAs($this->userWith('payments.view', 'payments.create', 'payments.update'));
        [, $feeFrere, $paiement, $soeur] = $this->scenario();

        $this->transferer($paiement, $soeur)->assertForbidden();

        $this->assertSame($feeFrere->id, $paiement->fresh()->inscription_fee_id);
    }

    /**
     * ⚠ L'exception ne doit PAS fuiter dans l'outil ordinaire : celui-ci
     * reste same-student, quelle que soit la permission détenue.
     */
    public function test_the_ordinary_move_tool_still_refuses_another_student(): void
    {
        $this->actingAs($this->userWith('payments.view', 'payments.move-fee', 'payments.transfer-student'));
        [, $feeFrere, $paiement, , $feeSoeur] = $this->scenario();

        $this->from(route('backoffice.students.merge.index'))
            ->post(route('backoffice.students.merge.move-payment'), [
                'encaissement_id' => $paiement->id,
                'fee_id' => $feeSoeur->id,
            ])->assertSessionHasErrors('fee_id');

        $this->assertSame($feeFrere->id, $paiement->fresh()->inscription_fee_id);
    }
}
