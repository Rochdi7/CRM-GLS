<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Students;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Remboursement;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les paiements COCHÉS à la demande de transfert (14/10/2026,
 * `Domain\Students\Support\SelectionPaiementsTransfert`).
 *
 *  1. le modal reçoit l'arbre année → inscription → paiements, avec ce qui
 *     est cochable et pourquoi le reste ne l'est pas ;
 *  2. un paiement NON coché reste au centre source, son frais n'est ni
 *     déplacé ni masqué ;
 *  3. une sélection PARTIELLE d'un frais est REFUSÉE — un paiement reste
 *     un paiement, le frais part entier ou pas du tout ;
 *  4. le frais payé d'un dossier CLOS, coché, part entier sur le nouveau
 *     dossier, le dossier clos garde son statut ;
 *  5. un id hors règle refuse la demande ENTIÈRE ; un remboursement saisi
 *     entre la demande et la décision refuse la validation ;
 *  6. une demande antérieure à la colonne (NULL) garde l'ancien comportement.
 */
final class TransfertEtudiantSelectionPaiementsTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $rabat;

    private Etablissement $casa;

    private Employee $guichet;

    private Group $groupeRabat;

    private Group $groupeCasa;

    private Caisse $caisse;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $this->casa = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);

        $this->guichet = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);
        $this->guichet->user->forceFill(['must_change_password' => false])->save();
        $this->guichet->user->givePermissionTo(['students.view', 'student-transfers.view', 'student-transfers.create']);

        $this->groupeRabat = Group::factory()->create(['etablissement_id' => $this->rabat->id, 'annee_scolaire_id' => $this->annee->id, 'nom' => 'Rabat A1']);
        $this->groupeCasa = Group::factory()->create(['etablissement_id' => $this->casa->id, 'annee_scolaire_id' => $this->annee->id, 'nom' => 'Casa A1']);
        $this->groupeCasa->frais()->attach($this->frais("Frais d'inscription")->id, ['montant' => 300, 'date_echeance' => '2026-09-01']);

        $this->caisse = Caisse::factory()->create(['etablissement_id' => $this->rabat->id, 'solde' => 5000]);
    }

    public function test_the_picker_lists_payments_per_year_and_registration_and_says_what_cannot_move(): void
    {
        $student = $this->etudiant();
        $active = $this->inscription($student, $this->groupeRabat);
        $feeIns = $this->fee($active, "Frais d'inscription", 300, InscriptionFee::STATUT_PAYE);
        $paiement = $this->paiement($student, $feeIns, 300);
        $avance = $this->paiement($student, null, 1000);
        $application = $this->paiement($student, $feeIns, 100, ['applied_from_encaissement_id' => $avance->id]);

        $anneePassee = AnneeScolaire::create(['nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31']);
        $vieuxGroupe = Group::factory()->create(['etablissement_id' => $this->rabat->id, 'annee_scolaire_id' => $anneePassee->id, 'nom' => 'Rabat A0']);
        $close = $this->inscription($student, $vieuxGroupe, Inscription::STATUT_CHANGEMENT, $anneePassee);
        $feeClose = $this->fee($close, 'Frais de Mars', 500, InscriptionFee::STATUT_PAYE);
        $vieux = $this->paiement($student, $feeClose, 500);
        $rembourse = $this->paiement($student, $feeClose, 200);
        Remboursement::create([
            'reference' => 'RMB-S1', 'beneficiaire_id' => $student->id, 'encaissement_id' => $rembourse->id,
            'caisse_id' => $this->caisse->id, 'etablissement_id' => $this->rabat->id, 'agent_id' => $this->guichet->id,
            'montant' => 200, 'date_remboursement' => '2026-09-20', 'motif' => 'Erreur',
        ]);

        $tree = $this->actingAs($this->guichet->user)
            ->getJson(route('backoffice.student-transfers.paiements', $student))
            ->assertOk()
            ->json();

        $this->assertSame([$avance->id], array_column($tree['avances'], 'id'));
        $this->assertSame(['2026/2027', '2025/2026'], array_column($tree['annees'], 'annee'));

        $courante = $tree['annees'][0]['inscriptions'][0];
        $this->assertSame($active->id, $courante['id']);
        $this->assertTrue($courante['active']);
        $lignes = collect($courante['paiements'])->keyBy('id');
        $this->assertNull($lignes[$paiement->id]['blocage']);
        // L'application est un paiement de son frais : cochable avec lui, pour son montant.
        $this->assertSame('application', $lignes[$application->id]['type']);
        $this->assertNull($lignes[$application->id]['blocage']);
        $this->assertSame('100.00', $lignes[$application->id]['emporte']);
        // L'avance n'emporte que son RESTE.
        $this->assertSame('1000.00', $tree['avances'][0]['montant']);
        $this->assertSame('100.00', $tree['avances'][0]['utilise']);
        $this->assertSame('900.00', $tree['avances'][0]['reste']);
        $this->assertSame('900.00', $tree['avances'][0]['emporte']);

        $passee = $tree['annees'][1]['inscriptions'][0];
        $this->assertSame(Inscription::STATUT_CHANGEMENT, $passee['statut']);
        $lignes = collect($passee['paiements'])->keyBy('id');
        $this->assertNull($lignes[$vieux->id]['blocage']);
        $this->assertSame('rembourse', $lignes[$rembourse->id]['blocage']);
        $this->assertSame('200.00', $lignes[$rembourse->id]['rembourse']);

        // Pré-coché : avance + dossier Actif (application comprise) ; jamais le dossier clos ni le remboursé.
        $this->assertEqualsCanonicalizing([$paiement->id, $avance->id, $application->id], $tree['defautIds']);
        // 300 + 900 (reste) + 100 (application) + 500 + 200 : chaque dirham une fois.
        $this->assertSame('2000.00', $tree['total']);
    }

    public function test_an_unchecked_payment_stays_at_the_source_with_its_fee(): void
    {
        $student = $this->etudiant();
        $inscription = $this->inscription($student, $this->groupeRabat);
        $feeIns = $this->fee($inscription, "Frais d'inscription", 300, InscriptionFee::STATUT_PAYE);
        $feeOct = $this->fee($inscription, "Frais d'Octobre", 700, InscriptionFee::STATUT_PAYE);
        $pIns = $this->paiement($student, $feeIns, 300);
        $pOct = $this->paiement($student, $feeOct, 700);
        $avance = $this->paiement($student, null, 150);

        $this->demander($student, [$pIns->id])->assertSessionHasNoErrors();
        $transfert = StudentTransfer::query()->sole();
        $this->assertSame([$pIns->id], $transfert->encaissement_ids);

        $this->actingAs($this->superAdmin())
            ->from(route('backoffice.student-transfers.index'))
            ->put(route('backoffice.student-transfers.validate', $transfert))
            ->assertSessionHasNoErrors();

        $transfert->refresh();
        $copie = Student::query()->findOrFail($transfert->nouveau_student_id);
        $this->assertSame('300.00', (string) $transfert->montant_transfere);

        // Coché : parti avec son frais.
        $this->assertSame($copie->id, $pIns->fresh()->student_id);
        $this->assertSame($transfert->nouvelle_inscription_id, $feeIns->fresh()->inscription_id);

        // Non cochés : restés sur la fiche d'origine, frais visible et toujours payé.
        $this->assertSame($student->id, $pOct->fresh()->student_id);
        $this->assertSame($student->id, $avance->fresh()->student_id);
        $this->assertSame($inscription->id, $feeOct->fresh()->inscription_id);
        $this->assertNull($feeOct->fresh()->masque_le);
        $this->assertSame(InscriptionFee::STATUT_PAYE, $feeOct->fresh()->statut);
        $this->assertSame(Inscription::STATUT_TRANSFEREE, $inscription->fresh()->statut);
        $this->assertSame('700.00', (string) $inscription->fresh()->montant_total);

        // Aucune caisse n'a bougé.
        $this->assertSame('5000.00', (string) $this->caisse->fresh()->solde);
    }

    public function test_a_partial_selection_of_a_fee_is_refused_a_payment_stays_a_payment(): void
    {
        $student = $this->etudiant();
        $inscription = $this->inscription($student, $this->groupeRabat);
        $fee = $this->fee($inscription, "Frais d'Octobre", 1000, InscriptionFee::STATUT_PAYE);
        $p1 = $this->paiement($student, $fee, 600);
        $p2 = $this->paiement($student, $fee, 400);

        // Une moitié de frais ne part pas : refus nommant le frais, rien n'est créé.
        $this->demander($student, [$p2->id])->assertSessionHasErrors('encaissement_ids');
        $this->assertSame(0, StudentTransfer::query()->count());

        // Le frais entier part avec ses deux paiements, inchangés.
        $this->demander($student, [$p1->id, $p2->id])->assertSessionHasNoErrors();
        $transfert = StudentTransfer::query()->sole();
        $this->actingAs($this->superAdmin())->put(route('backoffice.student-transfers.validate', $transfert))->assertSessionHasNoErrors();
        $transfert->refresh();
        $copie = Student::query()->findOrFail($transfert->nouveau_student_id);

        foreach ([$p1, $p2] as $p) {
            $p->refresh();
            $this->assertSame($copie->id, $p->student_id);
            $this->assertSame($fee->id, $p->inscription_fee_id);
            $this->assertSame($this->caisse->id, $p->caisse_id);
        }
        $this->assertSame($transfert->nouvelle_inscription_id, $fee->fresh()->inscription_id);
        $this->assertSame(InscriptionFee::STATUT_PAYE, $fee->fresh()->statut);
        $this->assertSame('1000.00', (string) $transfert->montant_transfere);
        $this->assertSame('5000.00', (string) $this->caisse->fresh()->solde);
    }

    public function test_a_checked_fee_of_a_closed_registration_moves_whole_as_a_paid_fee(): void
    {
        $student = $this->etudiant();
        $close = $this->inscription($student, $this->groupeRabat, Inscription::STATUT_CHANGEMENT);
        $fee = $this->fee($close, 'Frais de Mars', 500, InscriptionFee::STATUT_PAYE);
        $vieux = $this->paiement($student, $fee, 500);
        $autreFee = $this->fee($close, "Frais d'Avril", 500, InscriptionFee::STATUT_PAYE);
        $autre = $this->paiement($student, $autreFee, 500);

        $this->demander($student, [$vieux->id])->assertSessionHasNoErrors();
        $transfert = StudentTransfer::query()->sole();
        $this->actingAs($this->superAdmin())->put(route('backoffice.student-transfers.validate', $transfert))->assertSessionHasNoErrors();
        $transfert->refresh();

        // Le paiement reste un paiement : il suit sa ligne de frais, PAYÉE, sur le nouveau dossier.
        $vieux->refresh();
        $this->assertSame($transfert->nouveau_student_id, $vieux->student_id);
        $this->assertSame($fee->id, $vieux->inscription_fee_id);
        $this->assertSame($transfert->nouvelle_inscription_id, $fee->fresh()->inscription_id);
        $this->assertSame(InscriptionFee::STATUT_PAYE, $fee->fresh()->statut);
        $nouvelle = Inscription::query()->findOrFail($transfert->nouvelle_inscription_id);
        $this->assertSame(["Frais d'inscription", 'Frais de Mars'], $nouvelle->fees()->orderBy('nom')->pluck('nom')->all());
        $this->assertSame('800.00', (string) $nouvelle->montant_total);

        // Le dossier clos garde son statut et son autre frais ; son total est recalculé.
        $this->assertSame(Inscription::STATUT_CHANGEMENT, $close->fresh()->statut);
        $this->assertSame($close->id, $autreFee->fresh()->inscription_id);
        $this->assertSame($student->id, $autre->fresh()->student_id);
        $this->assertSame('500.00', (string) $close->fresh()->montant_total);
        $this->assertSame('500.00', (string) $transfert->montant_transfere);
    }

    public function test_a_payment_outside_the_rule_refuses_the_whole_request(): void
    {
        $student = $this->etudiant();
        $inscription = $this->inscription($student, $this->groupeRabat);
        $fee = $this->fee($inscription, "Frais d'inscription", 300, InscriptionFee::STATUT_PAYE);
        $avance = $this->paiement($student, null, 300);
        $application = $this->paiement($student, $fee, 300, ['applied_from_encaissement_id' => $avance->id]);
        $autre = $this->paiement(Student::factory()->create(['etablissement_id' => $this->rabat->id]), null, 100);

        // Une avance entièrement appliquée n'a rien à emporter.
        $this->demander($student, [$avance->id])->assertSessionHasErrors('encaissement_ids');
        // Le paiement d'un autre étudiant non plus.
        $this->demander($student, [$autre->id])->assertSessionHasErrors('encaissement_ids');
        $this->assertSame(0, StudentTransfer::query()->count());

        // L'application part avec son FRAIS, l'avance épuisée reste ici.
        $this->demander($student, [$application->id])->assertSessionHasNoErrors();
        $transfert = StudentTransfer::query()->sole();
        $this->actingAs($this->superAdmin())->put(route('backoffice.student-transfers.validate', $transfert))->assertSessionHasNoErrors();
        $transfert->refresh();
        $this->assertSame($student->id, $avance->fresh()->student_id);
        $this->assertSame($transfert->nouveau_student_id, $application->fresh()->student_id);
        $this->assertSame($transfert->nouvelle_inscription_id, $fee->fresh()->inscription_id);
        $this->assertSame('300.00', (string) $transfert->montant_transfere);
    }

    /**
     * Le cas du 15/10/2026 : 1 200 DH convertis en avance en gardant 300 DH
     * sur le frais (scission). L'écran annonçait 1 200 DH pour l'avance et
     * traînait les 300 DH avec elle : on ne pouvait pas en laisser 300 au
     * centre de départ et envoyer les 900.
     */
    public function test_a_split_advance_carries_its_remainder_and_its_applied_part_stays_with_the_fee(): void
    {
        $student = $this->etudiant();
        $inscription = $this->inscription($student, $this->groupeRabat);
        $feeOct = $this->fee($inscription, "Frais d'Octobre", 1200, InscriptionFee::STATUT_PAYE_PARTIELLEMENT);
        $avance = $this->paiement($student, null, 1200);
        $application = $this->paiement($student, $feeOct, 300, ['applied_from_encaissement_id' => $avance->id]);

        $tree = $this->actingAs($this->guichet->user)
            ->getJson(route('backoffice.student-transfers.paiements', $student))
            ->assertOk()
            ->json();
        $this->assertSame('900.00', $tree['avances'][0]['emporte']);
        $this->assertSame('300.00', $tree['annees'][0]['inscriptions'][0]['paiements'][0]['emporte']);
        $this->assertSame('1200.00', $tree['total']);

        // Avance seule cochée : 900 partent, les 300 appliqués restent sur le frais d'origine.
        $this->demander($student, [$avance->id])->assertSessionHasNoErrors();
        $transfert = StudentTransfer::query()->sole();
        $this->actingAs($this->superAdmin())->put(route('backoffice.student-transfers.validate', $transfert))->assertSessionHasNoErrors();
        $transfert->refresh();

        $this->assertSame('900.00', (string) $transfert->montant_transfere);
        $this->assertSame($transfert->nouveau_student_id, $avance->fresh()->student_id);
        $this->assertSame($student->id, $application->fresh()->student_id);
        $this->assertSame($inscription->id, $feeOct->fresh()->inscription_id);
        $this->assertNull($feeOct->fresh()->masque_le);
        // Aucun montant n'a bougé : l'avance garde 900 de reste, où qu'elle soit.
        $this->assertSame('1200.00', (string) $avance->fresh()->montant);
        $this->assertSame(900.0, $avance->fresh()->montantRestant());
        $this->assertSame('5000.00', (string) $this->caisse->fresh()->solde);
    }

    public function test_a_refund_between_request_and_decision_refuses_the_validation(): void
    {
        $student = $this->etudiant();
        $inscription = $this->inscription($student, $this->groupeRabat);
        $fee = $this->fee($inscription, "Frais d'inscription", 300, InscriptionFee::STATUT_PAYE);
        $paiement = $this->paiement($student, $fee, 300);

        $this->demander($student, [$paiement->id])->assertSessionHasNoErrors();
        $transfert = StudentTransfer::query()->sole();

        Remboursement::create([
            'reference' => 'RMB-S2', 'beneficiaire_id' => $student->id, 'encaissement_id' => $paiement->id,
            'caisse_id' => $this->caisse->id, 'etablissement_id' => $this->rabat->id, 'agent_id' => $this->guichet->id,
            'montant' => 300, 'date_remboursement' => '2026-09-20', 'motif' => 'Désistement',
        ]);

        $this->actingAs($this->superAdmin())
            ->from(route('backoffice.student-transfers.index'))
            ->put(route('backoffice.student-transfers.validate', $transfert))
            ->assertSessionHasErrors('validate');

        $this->assertSame(StudentTransfer::STATUT_EN_ATTENTE, $transfert->fresh()->statut);
        $this->assertSame($student->id, $paiement->fresh()->student_id);
        $this->assertSame(Student::STATUT_ACTIF, $student->fresh()->statut);
    }

    public function test_a_request_older_than_the_column_keeps_the_former_behaviour(): void
    {
        $student = $this->etudiant();
        $inscription = $this->inscription($student, $this->groupeRabat);
        $feePaye = $this->fee($inscription, "Frais d'inscription", 300, InscriptionFee::STATUT_PAYE);
        $paiement = $this->paiement($student, $feePaye, 300);
        $avance = $this->paiement($student, null, 200);
        $close = $this->inscription($student, $this->groupeRabat, Inscription::STATUT_CHANGEMENT);
        $feeClose = $this->fee($close, 'Frais de Mars', 500, InscriptionFee::STATUT_PAYE);
        $vieux = $this->paiement($student, $feeClose, 500);

        $this->demander($student, [])->assertSessionHasNoErrors();
        $transfert = StudentTransfer::query()->sole();
        $this->assertSame([], $transfert->encaissement_ids);
        $transfert->forceFill(['encaissement_ids' => null])->save();

        // Le dialogue de validation relit la sélection par défaut.
        $tree = $this->actingAs($this->superAdmin())
            ->getJson(route('backoffice.student-transfers.paiements-demande', $transfert))
            ->assertOk()
            ->json();
        $this->assertSame([$avance->id], array_column($tree['avances'], 'id'));
        $this->assertCount(1, $tree['annees'][0]['inscriptions']);

        $this->actingAs($this->superAdmin())->put(route('backoffice.student-transfers.validate', $transfert))->assertSessionHasNoErrors();
        $transfert->refresh();
        $this->assertSame('500.00', (string) $transfert->montant_transfere);
        $this->assertSame($transfert->nouveau_student_id, $paiement->fresh()->student_id);
        $this->assertSame($transfert->nouveau_student_id, $avance->fresh()->student_id);
        $this->assertSame($student->id, $vieux->fresh()->student_id);
    }

    public function test_an_empty_selection_moves_the_person_and_no_money(): void
    {
        $student = $this->etudiant();
        $inscription = $this->inscription($student, $this->groupeRabat);
        $fee = $this->fee($inscription, "Frais d'inscription", 300, InscriptionFee::STATUT_PAYE);
        $paiement = $this->paiement($student, $fee, 300);

        $this->demander($student, [])->assertSessionHasNoErrors();
        $transfert = StudentTransfer::query()->sole();
        $this->actingAs($this->superAdmin())->put(route('backoffice.student-transfers.validate', $transfert))->assertSessionHasNoErrors();
        $transfert->refresh();

        $this->assertSame('0.00', (string) $transfert->montant_transfere);
        $this->assertSame($student->id, $paiement->fresh()->student_id);
        $this->assertSame($inscription->id, $fee->fresh()->inscription_id);
        // Le catalogue du groupe cible est facturé en entier : rien n'a été déplacé.
        $nouvelle = Inscription::query()->findOrFail($transfert->nouvelle_inscription_id);
        $this->assertSame(["Frais d'inscription"], $nouvelle->fees()->pluck('nom')->all());
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $nouvelle->fees()->sole()->statut);
    }

    // ---------------------------------------------------------------

    private function frais(string $nom): Frais
    {
        return Frais::firstOrCreate(['nom' => $nom], ['statut' => 'Actif', 'montant_defaut' => 100]);
    }

    private function superAdmin(): User
    {
        $employee = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);
        $user = $employee->user;
        $user->forceFill(['must_change_password' => false])->save();
        $user->syncRoles([Role::SUPER_ADMIN]);

        return $user->fresh();
    }

    private function etudiant(): Student
    {
        return Student::factory()->create(['etablissement_id' => $this->rabat->id, 'nom' => 'BENALI', 'prenom' => 'Sara']);
    }

    private function inscription(Student $student, Group $group, string $statut = Inscription::STATUT_ACTIVE, ?AnneeScolaire $annee = null): Inscription
    {
        return Inscription::create([
            'reference' => 'INS-S'.(++self::$sequence),
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $group->etablissement_id,
            'annee_scolaire_id' => ($annee ?? $this->annee)->id,
            'statut' => $statut,
            'date_inscription' => '2026-09-10',
        ]);
    }

    private function fee(Inscription $inscription, string $nom, float $montant, string $statut = InscriptionFee::STATUT_NON_PAYE): InscriptionFee
    {
        return InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'frais_id' => $this->frais($nom)->id,
            'nom' => $nom,
            'montant_initial' => $montant,
            'montant' => $montant,
            'date_echeance' => '2026-10-01',
            'statut' => $statut,
        ]);
    }

    private function paiement(Student $student, ?InscriptionFee $fee, float $montant, array $extra = []): Encaissement
    {
        return Encaissement::create([
            'reference' => 'ENC-S'.(++self::$sequence),
            'student_id' => $student->id,
            'etablissement_id' => $this->rabat->id,
            'inscription_fee_id' => $fee?->id,
            'caisse_id' => $this->caisse->id,
            'agent_id' => $this->guichet->id,
            'montant' => $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2026-09-12',
            ...$extra,
        ]);
    }

    /** @param  list<int>  $ids */
    private function demander(Student $student, array $ids)
    {
        return $this->actingAs($this->guichet->user)
            ->from(route('backoffice.students.index'))
            ->post(route('backoffice.student-transfers.store'), [
                'student_id' => $student->id,
                'etablissement_cible_id' => $this->casa->id,
                'group_cible_id' => $this->groupeCasa->id,
                'motif' => 'Déménagement.',
                'encaissement_ids' => $ids,
            ]);
    }
}
