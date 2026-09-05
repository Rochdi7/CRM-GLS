<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Students;

use App\Domain\Payments\Actions\DeplacerEncaissementVersFrais;
use App\Domain\Students\Actions\FusionnerEtudiants;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Remboursement;
use App\Models\Student;
use App\Models\User;
use App\Support\Authorization\PermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * « Fusion de fiches & réaffectation des paiements » (05/09/2026).
 *
 * Les deux invariants qui comptent : rien de monétaire ne bouge (montant,
 * date, caisse, agent, solde), et les deux permissions restent hors de tout
 * preset de rôle.
 */
final class StudentMergeTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

    private Employee $agent;

    private Caisse $caisse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->annee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->caisse = $this->agent->till()->firstOrFail();
    }

    private function student(string $nom, string $prenom): Student
    {
        return Student::factory()->create([
            'etablissement_id' => $this->centre->id,
            'nom' => $nom,
            'prenom' => $prenom,
        ]);
    }

    private function inscription(Student $s, string $statut = Inscription::STATUT_ACTIVE): Inscription
    {
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        return Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $s->id,
            'group_id' => $group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => $statut,
            'date_inscription' => '2026-09-10',
        ]);
    }

    private function fee(Inscription $i, float $montant, string $nom = 'Frais test'): InscriptionFee
    {
        return InscriptionFee::create([
            'inscription_id' => $i->id, 'nom' => $nom,
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
    }

    private function paiement(Student $s, float $montant, ?InscriptionFee $fee = null): Encaissement
    {
        return Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'student_id' => $s->id,
            'inscription_fee_id' => $fee?->id,
            'montant' => $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2026-09-15',
            'caisse_id' => $this->caisse->id,
            'agent_id' => $this->agent->id,
        ]);
    }

    // ---------------------------------------------------------------- fusion

    public function test_merging_repoints_every_related_row_without_touching_money(): void
    {
        $garde = $this->student('ABOUTAJEDDINE', 'MOHAMMED');
        $doublon = $this->student('ABOUTAJEDDINE', 'MOHAMED');

        $inscription = $this->inscription($garde);
        $fee = $this->fee($inscription, 1000);
        $paiement = $this->paiement($doublon, 300);

        $soldeAvant = $this->caisse->fresh()->solde;

        app(FusionnerEtudiants::class)->handle($garde, $doublon);

        $paiement->refresh();

        // Seule la FK a bougé.
        $this->assertSame($garde->id, $paiement->student_id);

        // Rien de monétaire n'a changé — c'est la garantie demandée.
        $this->assertSame('300.00', $paiement->montant);
        $this->assertSame('Espèces', $paiement->methode);
        $this->assertSame('2026-09-15', $paiement->date_paiement->format('Y-m-d'));
        $this->assertSame($this->caisse->id, $paiement->caisse_id);
        $this->assertSame($this->agent->id, $paiement->agent_id);
        $this->assertSame($soldeAvant, $this->caisse->fresh()->solde);

        // Le frais n'a pas été touché non plus (la fusion ne réaffecte rien).
        $this->assertNull($paiement->inscription_fee_id);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);
    }

    public function test_the_duplicate_is_kept_but_renamed_never_deleted(): void
    {
        $garde = $this->student('ABOUTAJEDDINE', 'MOHAMMED');
        $doublon = $this->student('ABOUTAJEDDINE', 'MOHAMED');

        app(FusionnerEtudiants::class)->handle($garde, $doublon);

        $this->assertDatabaseHas('students', ['id' => $doublon->id]);
        $this->assertStringEndsWith(FusionnerEtudiants::SUFFIXE_DOUBLON, $doublon->fresh()->nom);
        $this->assertSame('ABOUTAJEDDINE', $garde->fresh()->nom);
    }

    public function test_a_refund_follows_the_kept_record(): void
    {
        $garde = $this->student('ABOUTAJEDDINE', 'MOHAMMED');
        $doublon = $this->student('ABOUTAJEDDINE', 'MOHAMED');
        $paiement = $this->paiement($doublon, 500);

        // remboursements.beneficiaire_id — la FK que l'ancienne commande CLI
        // oubliait, d'où ce test.
        $remboursement = Remboursement::create([
            'reference' => 'RMB-'.fake()->unique()->numerify('#####'),
            'beneficiaire_id' => $doublon->id,
            'encaissement_id' => $paiement->id,
            'caisse_id' => $this->caisse->id,
            'montant' => 100,
            'date_remboursement' => '2026-09-20',
            'motif' => 'Test',
            'agent_id' => $this->agent->id,
            'etablissement_id' => $this->centre->id,
        ]);

        app(FusionnerEtudiants::class)->handle($garde, $doublon);

        $this->assertSame($garde->id, $remboursement->fresh()->beneficiaire_id);
    }

    public function test_a_record_cannot_be_merged_into_itself(): void
    {
        $s = $this->student('ABOUTAJEDDINE', 'MOHAMMED');

        $this->expectException(ValidationException::class);
        app(FusionnerEtudiants::class)->handle($s, $s);
    }

    public function test_an_already_merged_record_cannot_be_merged_again(): void
    {
        $garde = $this->student('ABOUTAJEDDINE', 'MOHAMMED');
        $doublon = $this->student('ABOUTAJEDDINE', 'MOHAMED');

        app(FusionnerEtudiants::class)->handle($garde, $doublon);

        $this->expectException(ValidationException::class);
        app(FusionnerEtudiants::class)->handle($garde, $doublon->fresh());
    }

    // ------------------------------------------------- déplacement de paiement

    public function test_a_payment_moves_to_a_fee_of_a_cancelled_registration(): void
    {
        $s = $this->student('TEST', 'ETUDIANT');
        $source = $this->inscription($s, Inscription::STATUT_ACTIVE);
        $cible = $this->inscription($s, Inscription::STATUT_ANNULEE);

        $fraisSource = $this->fee($source, 1000, 'Frais de Septembre');
        $fraisCible = $this->fee($cible, 1000, 'Frais de Septembre');
        $paiement = $this->paiement($s, 400, $fraisSource);

        $soldeAvant = $this->caisse->fresh()->solde;

        // Un dossier Annulée est refusé partout ailleurs : ici c'est le but.
        app(DeplacerEncaissementVersFrais::class)->handle($paiement, $fraisCible);

        $paiement->refresh();
        $this->assertSame($fraisCible->id, $paiement->inscription_fee_id);

        // Aucune donnée monétaire réécrite.
        $this->assertSame('400.00', $paiement->montant);
        $this->assertSame('2026-09-15', $paiement->date_paiement->format('Y-m-d'));
        $this->assertSame($this->caisse->id, $paiement->caisse_id);
        $this->assertSame($this->agent->id, $paiement->agent_id);
        $this->assertSame($soldeAvant, $this->caisse->fresh()->solde);

        // Les deux frais ont vu leur statut recalculé.
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fraisSource->fresh()->statut);
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $fraisCible->fresh()->statut);
    }

    public function test_a_payment_can_be_detached_back_into_an_advance(): void
    {
        $s = $this->student('TEST', 'ETUDIANT');
        $i = $this->inscription($s);
        $frais = $this->fee($i, 1000);
        $paiement = $this->paiement($s, 400, $frais);

        app(DeplacerEncaissementVersFrais::class)->handle($paiement, null);

        $this->assertNull($paiement->fresh()->inscription_fee_id);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $frais->fresh()->statut);
    }

    public function test_money_never_crosses_to_another_student(): void
    {
        $a = $this->student('A', 'ETUDIANT');
        $b = $this->student('B', 'ETUDIANT');
        $fraisDeB = $this->fee($this->inscription($b), 1000);
        $paiementDeA = $this->paiement($a, 400);

        // Le SEUL garde-fou que cet outil ne relâche pas.
        $this->expectException(ValidationException::class);
        app(DeplacerEncaissementVersFrais::class)->handle($paiementDeA, $fraisDeB);
    }

    public function test_a_payment_cannot_exceed_what_the_target_fee_still_owes(): void
    {
        $s = $this->student('TEST', 'ETUDIANT');
        $i = $this->inscription($s);
        $petit = $this->fee($i, 100, 'Petit frais');
        $paiement = $this->paiement($s, 400);

        $this->expectException(ValidationException::class);
        app(DeplacerEncaissementVersFrais::class)->handle($paiement, $petit);
    }

    public function test_a_refunded_payment_cannot_be_moved(): void
    {
        $s = $this->student('TEST', 'ETUDIANT');
        $i = $this->inscription($s);
        $frais = $this->fee($i, 1000);
        $paiement = $this->paiement($s, 400);

        Remboursement::create([
            'reference' => 'RMB-'.fake()->unique()->numerify('#####'),
            'beneficiaire_id' => $s->id,
            'encaissement_id' => $paiement->id,
            'caisse_id' => $this->caisse->id,
            'montant' => 400,
            'date_remboursement' => '2026-09-20',
            'motif' => 'Test',
            'agent_id' => $this->agent->id,
            'etablissement_id' => $this->centre->id,
        ]);

        $this->expectException(ValidationException::class);
        app(DeplacerEncaissementVersFrais::class)->handle($paiement, $frais);
    }

    // ------------------------------------------------------------ permissions

    public function test_both_permissions_are_reserved_to_super_admins(): void
    {
        foreach (['students.merge', 'payments.move-fee'] as $permission) {
            $this->assertContains($permission, PermissionRegistry::superAdminOnly());

            foreach (PermissionRegistry::matrix() as $role => $permissions) {
                $this->assertNotContains(
                    $permission,
                    $permissions,
                    "Le rôle {$role} ne doit jamais porter {$permission}.",
                );
            }
        }
    }

    public function test_a_non_super_admin_is_refused_the_repair_page(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $employee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $user = $employee->user;
        // EmployeeObserver crée le login avec must_change_password = true,
        // et EnsurePasswordIsChanged redirigerait avant même la permission.
        $user->forceFill(['must_change_password' => false])->save();
        $user->syncRoles(['consultant']);

        $this->actingAs($user)->get('/backoffice/students/fusion')->assertForbidden();
    }

    public function test_a_super_admin_reaches_the_repair_page(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $employee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $user = $employee->user;
        $user->forceFill(['must_change_password' => false])->save();
        $user->syncRoles([\App\Models\Role::SUPER_ADMIN]);

        $this->actingAs($user)->get('/backoffice/students/fusion')->assertOk();
    }
}
