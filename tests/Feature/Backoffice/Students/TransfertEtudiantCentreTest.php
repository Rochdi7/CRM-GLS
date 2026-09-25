<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Students;

use App\Domain\Registrations\Queries\GetInscriptionFormOptions;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Presence;
use App\Models\Role;
use App\Models\Seance;
use App\Models\Student;
use App\Models\StudentTransfer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Transfert d'un étudiant d'un centre vers un autre (25/09/2026).
 *
 * Le flux : le front office du centre de départ DEMANDE (centre cible +
 * groupe d'affectation + motif), un super-admin VALIDE ou REFUSE. À la
 * validation : la fiche est COPIÉE dans le centre d'arrivée (Active), la
 * fiche d'origine passe « Transféré » et GARDE ses présences ; ses
 * dossiers Actifs passent « Transférée » ; tout l'argent (frais payés,
 * avances, chèques) suit la personne sur le nouveau dossier Actif du groupe
 * cible ; `caisses.solde` et les montants ne bougent pas.
 */
final class TransfertEtudiantCentreTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $rabat;

    private Etablissement $casa;

    private Employee $guichet;

    private Group $groupeRabat;

    private Group $groupeCasa;

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
        $this->guichet->user->givePermissionTo(['students.view', 'student-transfers.view', 'student-transfers.create', 'payments.create', 'payments.view']);

        $this->groupeRabat = Group::factory()->create(['etablissement_id' => $this->rabat->id, 'annee_scolaire_id' => $this->annee->id, 'nom' => 'Rabat A1']);
        $this->groupeCasa = Group::factory()->create(['etablissement_id' => $this->casa->id, 'annee_scolaire_id' => $this->annee->id, 'nom' => 'Casa A1']);

        // Catalogue du groupe cible : « Frais d'inscription » (déjà payé à
        // Rabat, ne doit pas être refacturé) + « Frais de Novembre ».
        $this->groupeCasa->frais()->attach($this->frais("Frais d'inscription")->id, ['montant' => 300, 'date_echeance' => '2026-09-01']);
        $this->groupeCasa->frais()->attach($this->frais('Frais de Novembre')->id, ['montant' => 600, 'date_echeance' => '2026-11-01']);
    }

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
        return Student::factory()->create([
            'etablissement_id' => $this->rabat->id,
            'nom' => 'BENALI', 'prenom' => 'Sara', 'telephone' => '+212600000001', 'cin' => 'AB1234',
            'legacy_ref' => 'P'.(++self::$sequence),
        ]);
    }

    private function inscription(Student $student, Group $group): Inscription
    {
        return Inscription::create([
            'reference' => 'INS-T'.(++self::$sequence),
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $group->etablissement_id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
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

    private function paiement(Student $student, ?InscriptionFee $fee, float $montant, Caisse $caisse): Encaissement
    {
        return Encaissement::create([
            'reference' => 'ENC-T'.(++self::$sequence),
            'student_id' => $student->id,
            'etablissement_id' => $this->rabat->id,
            'inscription_fee_id' => $fee?->id,
            'caisse_id' => $caisse->id,
            'agent_id' => $this->guichet->id,
            'montant' => $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2026-09-12',
        ]);
    }

    private function appeler(Student $student, Group $group, string $statut): Presence
    {
        $seance = Seance::create([
            'group_id' => $group->id,
            'date_seance' => '2026-09-15',
            'heure_debut' => '19:00', 'heure_fin' => '21:00',
            'etablissement_id' => $group->etablissement_id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_EFFECTUEE,
        ]);

        return Presence::create(['seance_id' => $seance->id, 'student_id' => $student->id, 'statut' => $statut]);
    }

    private function demander(Student $student, ?Group $groupe = null, ?Etablissement $cible = null, string $motif = 'Déménagement à Casablanca.')
    {
        return $this->actingAs($this->guichet->user)
            ->from(route('backoffice.students.index'))
            ->post(route('backoffice.student-transfers.store'), [
                'student_id' => $student->id,
                'etablissement_cible_id' => ($cible ?? $this->casa)->id,
                'group_cible_id' => ($groupe ?? $this->groupeCasa)->id,
                'motif' => $motif,
            ]);
    }

    // ---------------------------------------------------------------
    // 1. La DEMANDE — front office, avec le groupe d'affectation
    // ---------------------------------------------------------------

    public function test_the_front_office_files_a_request_naming_the_target_group(): void
    {
        $student = $this->etudiant();

        $this->demander($student)->assertSessionHasNoErrors()->assertRedirect();

        $transfert = StudentTransfer::query()->sole();
        $this->assertSame(StudentTransfer::STATUT_EN_ATTENTE, $transfert->statut);
        $this->assertSame($this->groupeCasa->id, $transfert->group_cible_id);
        $this->assertSame($this->casa->id, $transfert->etablissement_cible_id);
        $this->assertSame($this->rabat->id, $transfert->etablissement_source_id);
        $this->assertSame($this->guichet->id, $transfert->requested_by);
        $this->assertStringStartsWith('TRE-', $transfert->reference);

        // Rien ne bouge à la demande.
        $this->assertSame(Student::STATUT_ACTIF, $student->fresh()->statut);
        $this->assertSame(1, Student::count());
    }

    public function test_a_request_requires_a_group_of_the_target_centre(): void
    {
        $student = $this->etudiant();

        // Groupe du centre de DÉPART.
        $this->demander($student, $this->groupeRabat)->assertSessionHasErrors('group_cible_id');

        // Groupe clôturé du centre cible.
        $clos = Group::factory()->create(['etablissement_id' => $this->casa->id, 'annee_scolaire_id' => $this->annee->id, 'statut' => Group::STATUT_FIN_FORMATION]);
        $this->demander($student, $clos)->assertSessionHasErrors('group_cible_id');

        // Sans groupe du tout.
        $this->actingAs($this->guichet->user)->from(route('backoffice.students.index'))
            ->post(route('backoffice.student-transfers.store'), [
                'student_id' => $student->id, 'etablissement_cible_id' => $this->casa->id, 'motif' => 'x',
            ])->assertSessionHasErrors('group_cible_id');

        // Même centre que le départ.
        $this->demander($student, $this->groupeRabat, $this->rabat)->assertSessionHasErrors('etablissement_cible_id');

        $this->assertSame(0, StudentTransfer::count());
    }

    public function test_a_second_pending_request_for_the_same_student_is_refused(): void
    {
        $student = $this->etudiant();
        $this->demander($student)->assertSessionHasNoErrors();

        $this->demander($student)->assertSessionHasErrors('student_id');
        $this->assertSame(1, StudentTransfer::count());
    }

    public function test_the_target_groups_endpoint_lists_open_groups_of_the_target_centre_only(): void
    {
        Group::factory()->create(['etablissement_id' => $this->casa->id, 'annee_scolaire_id' => $this->annee->id, 'statut' => Group::STATUT_ANNULEE, 'nom' => 'Casa annulé']);

        $response = $this->actingAs($this->guichet->user)
            ->getJson(route('backoffice.student-transfers.groupes-cibles', $this->casa))
            ->assertOk();

        $this->assertSame(['Casa A1'], array_column($response->json(), 'nom'));
    }

    // ---------------------------------------------------------------
    // 2. La DÉCISION — super-admin uniquement
    // ---------------------------------------------------------------

    public function test_only_a_super_admin_validates_or_refuses(): void
    {
        $student = $this->etudiant();
        $this->demander($student);
        $transfert = StudentTransfer::query()->sole();

        $this->actingAs($this->guichet->user)
            ->put(route('backoffice.student-transfers.validate', $transfert))
            ->assertForbidden();
        $this->actingAs($this->guichet->user)
            ->put(route('backoffice.student-transfers.refuse', $transfert), ['motif_decision' => 'non'])
            ->assertForbidden();

        $this->assertSame(StudentTransfer::STATUT_EN_ATTENTE, $transfert->fresh()->statut);
        $this->assertSame(Student::STATUT_ACTIF, $student->fresh()->statut);
    }

    public function test_validation_copies_the_student_moves_the_money_and_keeps_presences_at_the_source(): void
    {
        $student = $this->etudiant();
        $inscription = $this->inscription($student, $this->groupeRabat);
        $feePaye = $this->fee($inscription, "Frais d'inscription", 300, InscriptionFee::STATUT_PAYE);
        $feeImpaye = $this->fee($inscription, "Frais d'Octobre", 700);

        $caisse = Caisse::factory()->create(['etablissement_id' => $this->rabat->id, 'solde' => 1000]);
        $paiement = $this->paiement($student, $feePaye, 300, $caisse);
        $avance = $this->paiement($student, null, 200, $caisse);
        $presence = $this->appeler($student, $this->groupeRabat, Presence::STATUT_ABSENT);

        $this->demander($student)->assertSessionHasNoErrors();
        $transfert = StudentTransfer::query()->sole();

        $this->actingAs($this->superAdmin())
            ->from(route('backoffice.student-transfers.index'))
            ->put(route('backoffice.student-transfers.validate', $transfert))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $transfert->refresh();
        $this->assertSame(StudentTransfer::STATUT_VALIDE, $transfert->statut);
        $this->assertSame('500.00', (string) $transfert->montant_transfere);
        $this->assertNotNull($transfert->nouveau_student_id);
        $this->assertNotNull($transfert->nouvelle_inscription_id);

        // La fiche d'origine : « Transféré », toujours à Rabat, liée à la copie.
        $source = $student->fresh();
        $this->assertSame(Student::STATUT_TRANSFERE, $source->statut);
        $this->assertSame($this->rabat->id, $source->etablissement_id);
        $this->assertSame($transfert->nouveau_student_id, $source->transfere_vers_student_id);

        // La copie : Active à Casablanca, mêmes données, nouvelle référence, pas de legacy_ref.
        $copie = Student::query()->findOrFail($transfert->nouveau_student_id);
        $this->assertSame(Student::STATUT_ACTIF, $copie->statut);
        $this->assertSame($this->casa->id, $copie->etablissement_id);
        $this->assertSame('BENALI', $copie->nom);
        $this->assertSame('Sara', $copie->prenom);
        $this->assertSame('+212600000001', $copie->telephone);
        $this->assertSame('AB1234', $copie->cin);
        $this->assertNull($copie->legacy_ref);
        $this->assertNotSame($source->reference, $copie->reference);
        $this->assertSame($source->id, $copie->transfere_depuis_student_id);

        // Le dossier de Rabat est clos « Transférée » ; son frais payé est PARTI,
        // son frais impayé est MASQUÉ (jamais supprimé).
        $inscription->refresh();
        $this->assertSame(Inscription::STATUT_TRANSFEREE, $inscription->statut);
        $this->assertStringContainsString('GLS Casablanca', (string) $inscription->note);
        $this->assertNotNull($feeImpaye->fresh()->masque_le);
        $this->assertSame(InscriptionFee::MASQUE_ORIGINE_TRANSFERT, $feeImpaye->fresh()->masque_origine);
        $this->assertNull($inscription->montant_total);

        // Le nouveau dossier : Actif dans le groupe cible, avec le frais payé
        // déplacé + le catalogue du groupe MOINS le frais déjà payé.
        $nouvelle = Inscription::query()->findOrFail($transfert->nouvelle_inscription_id);
        $this->assertSame(Inscription::STATUT_ACTIVE, $nouvelle->statut);
        $this->assertSame($this->groupeCasa->id, $nouvelle->group_id);
        $this->assertSame($this->casa->id, $nouvelle->etablissement_id);
        $this->assertSame($copie->id, $nouvelle->student_id);
        $this->assertSame($nouvelle->id, $feePaye->fresh()->inscription_id);
        $this->assertSame(InscriptionFee::STATUT_PAYE, $feePaye->fresh()->statut);
        $noms = $nouvelle->fees()->whereNull('masque_le')->pluck('nom')->sort()->values()->all();
        $this->assertSame(["Frais d'inscription", 'Frais de Novembre'], $noms);
        $this->assertSame(1, $nouvelle->fees()->where('nom', "Frais d'inscription")->count());
        $this->assertSame('900.00', (string) $nouvelle->montant_total);

        // L'argent suit la personne : student_id réécrit, RIEN d'autre.
        foreach ([$paiement, $avance] as $row) {
            $row->refresh();
            $this->assertSame($copie->id, $row->student_id);
            $this->assertSame($caisse->id, $row->caisse_id);
            $this->assertSame($this->rabat->id, $row->etablissement_id);
            $this->assertSame('2026-09-12', $row->date_paiement->toDateString());
        }
        $this->assertSame('300.00', (string) $paiement->montant);
        $this->assertSame($feePaye->id, $paiement->inscription_fee_id);
        $this->assertNull($avance->inscription_fee_id);
        $this->assertSame('1000.00', (string) $caisse->fresh()->solde);

        // Les présences RESTENT sur la fiche d'origine ; la copie n'en a aucune.
        $this->assertSame($source->id, $presence->fresh()->student_id);
        $this->assertSame(0, Presence::query()->where('student_id', $copie->id)->count());
        $this->assertSame(1, Presence::query()->where('student_id', $source->id)->count());

        // Le journal explique le geste.
        $this->assertDatabaseHas('activity_log', [
            'event' => 'student_transferred',
            'subject_type' => Student::class,
            'subject_id' => $source->id,
        ]);
    }

    public function test_validation_is_refused_twice_and_on_a_closed_target_group(): void
    {
        $student = $this->etudiant();
        $this->demander($student);
        $transfert = StudentTransfer::query()->sole();
        $admin = $this->superAdmin();

        $this->actingAs($admin)->put(route('backoffice.student-transfers.validate', $transfert))->assertSessionHasNoErrors();
        $this->actingAs($admin)->put(route('backoffice.student-transfers.validate', $transfert))->assertSessionHasErrors('validate');

        $this->assertSame(2, Student::count());

        // Un groupe clôturé entre la demande et la décision bloque la validation.
        $autre = $this->etudiant();
        $this->demander($autre);
        $t2 = StudentTransfer::query()->where('student_id', $autre->id)->sole();
        $this->groupeCasa->update(['statut' => Group::STATUT_FIN_FORMATION]);

        $this->actingAs($admin)->put(route('backoffice.student-transfers.validate', $t2))->assertSessionHasErrors('validate');
        $this->assertSame(StudentTransfer::STATUT_EN_ATTENTE, $t2->fresh()->statut);
        $this->assertSame(Student::STATUT_ACTIF, $autre->fresh()->statut);
    }

    public function test_a_refusal_needs_a_reason_and_moves_nothing(): void
    {
        $student = $this->etudiant();
        $this->demander($student);
        $transfert = StudentTransfer::query()->sole();
        $admin = $this->superAdmin();

        $this->actingAs($admin)->put(route('backoffice.student-transfers.refuse', $transfert), ['motif_decision' => ''])
            ->assertSessionHasErrors('motif_decision');

        $this->actingAs($admin)->put(route('backoffice.student-transfers.refuse', $transfert), ['motif_decision' => 'Le groupe cible est complet.'])
            ->assertSessionHasNoErrors();

        $transfert->refresh();
        $this->assertSame(StudentTransfer::STATUT_REFUSE, $transfert->statut);
        $this->assertSame('Le groupe cible est complet.', $transfert->motif_decision);
        $this->assertSame($admin->id, $transfert->decided_by);
        $this->assertSame(Student::STATUT_ACTIF, $student->fresh()->statut);
        $this->assertSame(1, Student::count());
    }

    public function test_the_requester_cancels_their_own_pending_request_but_not_a_colleagues(): void
    {
        $student = $this->etudiant();
        $this->demander($student);
        $transfert = StudentTransfer::query()->sole();

        $collegue = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);
        $collegue->user->forceFill(['must_change_password' => false])->save();
        $collegue->user->givePermissionTo(['student-transfers.view', 'student-transfers.create']);

        $this->actingAs($collegue->user)->put(route('backoffice.student-transfers.cancel', $transfert))->assertForbidden();
        $this->assertSame(StudentTransfer::STATUT_EN_ATTENTE, $transfert->fresh()->statut);

        $this->actingAs($this->guichet->user)->put(route('backoffice.student-transfers.cancel', $transfert))->assertSessionHasNoErrors();
        $this->assertSame(StudentTransfer::STATUT_ANNULE, $transfert->fresh()->statut);

        // Une demande annulée n'empêche pas d'en refaire une.
        $this->demander($student)->assertSessionHasNoErrors();
        $this->assertSame(2, StudentTransfer::count());
    }

    // ---------------------------------------------------------------
    // 3. Une fiche « Transféré » est close dans son centre
    // ---------------------------------------------------------------

    public function test_a_transferred_record_takes_no_new_money_and_leaves_the_enrolment_dropdown(): void
    {
        $student = $this->etudiant();
        $this->demander($student);
        $this->actingAs($this->superAdmin())->put(route('backoffice.student-transfers.validate', StudentTransfer::query()->sole()));

        $this->actingAs($this->guichet->user)
            ->from(route('backoffice.encaissements.index'))
            ->post(route('backoffice.avances.store'), [
                'student_id' => $student->id, 'montant' => 100, 'methode' => 'Espèces', 'date_paiement' => '2026-09-20',
            ])
            ->assertSessionHasErrors('student_id');
        $this->assertSame(0, Encaissement::count());

        $this->demander($student)->assertSessionHasErrors('student_id');

        $ids = app(GetInscriptionFormOptions::class)->students($this->guichet->user->fresh())->pluck('id')->all();
        $this->assertNotContains($student->id, $ids);
    }

    /**
     * La fiche d'arrivée MONTRE l'historique du centre de départ : le centre
     * qui reçoit ne peut en général pas ouvrir la fiche d'origine, et c'est
     * là que restent les présences et les anciens dossiers.
     */
    public function test_the_new_record_shows_attendance_and_dossiers_of_the_origin_centre(): void
    {
        $student = $this->etudiant();
        $this->inscription($student, $this->groupeRabat);
        $this->appeler($student, $this->groupeRabat, Presence::STATUT_ABSENT);
        $this->appeler($student, $this->groupeRabat, Presence::STATUT_PRESENT);
        $this->demander($student);
        $admin = $this->superAdmin();
        $this->actingAs($admin)->put(route('backoffice.student-transfers.validate', StudentTransfer::query()->sole()));

        $copie = Student::query()->findOrFail($student->fresh()->transfere_vers_student_id);

        $this->actingAs($admin)
            ->get(route('backoffice.students.show', $copie))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Backoffice/Students/Show')
                ->has('student.historiqueTransfert', 1)
                ->where('student.historiqueTransfert.0.centre', 'GLS Rabat')
                ->where('student.historiqueTransfert.0.presencesTotal', 2)
                ->where('student.historiqueTransfert.0.compteurs.Absent', 1)
                ->where('student.historiqueTransfert.0.compteurs.Présent', 1)
                ->has('student.historiqueTransfert.0.presences', 2)
                ->where('student.historiqueTransfert.0.presences.0.groupe', 'Rabat A1')
                ->where('student.historiqueTransfert.0.inscriptions.0.statut', Inscription::STATUT_TRANSFEREE));

        // La fiche d'origine, elle, n'a pas d'historique de transfert.
        $this->actingAs($admin)
            ->get(route('backoffice.students.show', $student))
            ->assertInertia(fn ($page) => $page->has('student.historiqueTransfert', 0));
    }

    public function test_the_list_page_shows_the_request_to_the_source_centre(): void
    {
        $student = $this->etudiant();
        $this->demander($student);

        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.student-transfers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Backoffice/StudentTransfers/Index')
                ->has('transfers.data', 1)
                ->where('transfers.data.0.groupeCible.nom', 'Casa A1')
                ->where('transfers.data.0.canCancel', true)
                ->where('transfers.data.0.canDecide', false));
    }
}
