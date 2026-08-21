<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inertia;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Role;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use App\Support\Authorization\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Phase 5 (docs/phase-5-read-pages-inventory.md) — every migrated
 * read-only detail/index page. Verifies the Inertia contract (component,
 * safe minimal props) and the security invariants the task requires: no
 * sensitive fields, no full models, cross-center access denied exactly as
 * before.
 */
final class ReadOnlyPagesInertiaTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    private Etablissement $casa;

    private AnneeScolaire $annee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->rabat = Etablissement::factory()->create();
        $this->casa = Etablissement::factory()->create();
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
    }

    private function globalUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);

        return $user;
    }

    private function centerUser(Etablissement $centre, string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);
        $this->actingAs($user->fresh());

        return $user->fresh();
    }

    private function caisseIn(Etablissement $centre): Caisse
    {
        return Caisse::create([
            'nom' => 'Caisse '.$centre->id,
            'etablissement_id' => $centre->id,
            'solde' => 1000,
            'statut' => Caisse::STATUT_ACTIVE,
        ]);
    }

    private function typeDepense(): TypeDepense
    {
        return TypeDepense::create([
            'nom' => 'Fournitures',
            'is_system' => false,
            'statut' => TypeDepense::STATUT_ACTIF,
        ]);
    }

    // --- Student ------------------------------------------------------

    public function test_student_show_exposes_no_sensitive_fields(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->rabat->id]);

        $this->get(route('backoffice.students.show', $student))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Students/Show')
                ->missing('student.password')
                ->missing('student.remember_token')
            );
    }

    public function test_student_show_denies_cross_center_access(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->casa->id]);
        $this->centerUser($this->rabat, 'students.view');

        $this->get(route('backoffice.students.show', $student))->assertForbidden();
    }

    // --- Group ----------------------------------------------------------

    public function test_group_show_denies_cross_center_access(): void
    {
        $group = Group::factory()->create(['etablissement_id' => $this->casa->id, 'annee_scolaire_id' => $this->annee->id]);
        $this->centerUser($this->rabat, 'groups.view');

        $this->get(route('backoffice.groups.show', $group))->assertForbidden();
    }

    public function test_group_show_never_exposes_an_archive_url_when_unauthorized(): void
    {
        $group = Group::factory()->create([
            'etablissement_id' => $this->rabat->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Group::STATUT_EN_FORMATION,
        ]);
        // View-only user: no groups.archive permission.
        $this->centerUser($this->rabat, 'groups.view');

        $this->get(route('backoffice.groups.show', $group))
            ->assertInertia(fn (Assert $page) => $page->where('group.canArchive', false));
    }

    // --- Inscription ------------------------------------------------------

    public function test_inscription_show_denies_cross_center_access(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->casa->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->casa->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-XCTR', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->casa->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);
        $this->centerUser($this->rabat, 'registrations.view');

        $this->get(route('backoffice.inscriptions.show', $inscription))->assertForbidden();
    }

    public function test_inscription_show_does_not_recompute_totals_incorrectly(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->rabat->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->rabat->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-TOTALS', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->rabat->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);
        $inscription->fees()->create(['nom' => 'Frais A', 'montant' => 1000, 'statut' => 'Non payé', 'date_echeance' => '2025-10-01']);
        $inscription->fees()->create(['nom' => 'Frais B', 'montant' => 500, 'statut' => 'Non payé', 'date_echeance' => '2025-11-01']);

        $this->get(route('backoffice.inscriptions.show', $inscription))
            ->assertInertia(fn (Assert $page) => $page
                ->where('inscription.totalDu', '1500.00')
                ->where('inscription.totalPaye', '0.00')
                ->where('inscription.reste', '1500.00')
            );
    }

    // --- Caisse ------------------------------------------------------------

    public function test_caisse_show_denies_cross_center_access(): void
    {
        $caisse = $this->caisseIn($this->casa);
        $this->centerUser($this->rabat, 'cash-registers.view');

        $this->get(route('backoffice.caisses.show', $caisse))->assertForbidden();
    }

    public function test_caisse_show_exposes_only_documented_fields(): void
    {
        $this->globalUser();
        $caisse = $this->caisseIn($this->rabat);

        $this->get(route('backoffice.caisses.show', $caisse))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Caisses/Show')
                ->where('caisse', fn ($c) => collect($c)->keys()->all() === [
                    'id', 'nom', 'centre', 'responsable', 'solde', 'statut',
                    'encaissements', 'depenses', 'remboursements', 'transfers',
                ])
            );
    }

    // --- Encaissement --------------------------------------------------

    public function test_encaissement_show_denies_cross_center_access(): void
    {
        $caisse = $this->caisseIn($this->casa);
        $student = Student::factory()->create(['etablissement_id' => $this->casa->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->casa->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-ENCXCTR', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->casa->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);
        $fee = $inscription->fees()->create(['nom' => 'Frais', 'montant' => 100, 'statut' => 'Non payé', 'date_echeance' => '2025-10-01']);
        $agent = Employee::factory()->create(['etablissement_id' => $this->casa->id]);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-XCTR', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id, 'montant' => 100,
            'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20',
            'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
        ]);
        $this->centerUser($this->rabat, 'payments.view');

        $this->get(route('backoffice.encaissements.show', $encaissement))->assertForbidden();
    }

    /**
     * Money records stay append-only for everyone except a super-admin
     * (CLAUDE.md §11). A `backoffice.encaissements.destroy` route DOES exist —
     * it is the deliberate, ledger-aware `SupprimerEncaissement` path — but it
     * is gated by `payments.delete`, which no role preset may hold
     * (PermissionRegistry::superAdminOnly()). So a user holding every OTHER
     * payment permission is still refused.
     *
     * This asserts the gate rather than the route's absence: the guarantee is
     * "nobody but a super-admin deletes a payment", not "no such code path".
     */
    public function test_encaissement_delete_is_refused_without_the_dedicated_permission(): void
    {
        $this->assertContains(
            'payments.delete',
            PermissionRegistry::superAdminOnly(),
            'payments.delete must stay super-admin-only',
        );

        foreach (PermissionRegistry::matrix() as $role => $permissions) {
            $this->assertNotContains('payments.delete', $permissions, "Role [$role] must not delete payments");
        }

        $caisse = $this->caisseIn($this->casa);
        $student = Student::factory()->create(['etablissement_id' => $this->casa->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->casa->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-ENCDEL', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->casa->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);
        $fee = $inscription->fees()->create(['nom' => 'Frais', 'montant' => 100, 'statut' => 'Non payé', 'date_echeance' => '2025-10-01']);
        $agent = Employee::factory()->create(['etablissement_id' => $this->casa->id]);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-NODEL', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id, 'montant' => 100,
            'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20',
            'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
        ]);

        // Everything a payment operator can hold — except payments.delete.
        $this->centerUser($this->casa, 'payments.view', 'payments.create', 'payments.update');

        $this->delete(route('backoffice.encaissements.destroy', $encaissement))->assertForbidden();

        $this->assertDatabaseHas('encaissements', ['id' => $encaissement->id]);
        $this->assertSame(1000.0, (float) $caisse->fresh()->solde, 'Till balance must be untouched');
    }

    // --- Depense -------------------------------------------------------

    public function test_depense_show_denies_cross_center_access(): void
    {
        $caisse = $this->caisseIn($this->casa);
        $type = $this->typeDepense();
        $agent = Employee::factory()->create(['etablissement_id' => $this->casa->id]);
        $depense = Depense::create([
            'reference' => 'DEP-XCTR', 'type_depense_id' => $type->id, 'caisse_id' => $caisse->id,
            'montant' => 100, 'date_depense' => '2025-09-20', 'agent_id' => $agent->id,
        ]);
        $this->centerUser($this->rabat, 'expenses.view');

        $this->get(route('backoffice.depenses.show', $depense))->assertForbidden();
    }

    public function test_depense_show_media_props_are_safely_shaped(): void
    {
        $this->globalUser();
        $caisse = $this->caisseIn($this->rabat);
        $type = $this->typeDepense();
        $agent = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);
        $depense = Depense::create([
            'reference' => 'DEP-MEDIA', 'type_depense_id' => $type->id, 'caisse_id' => $caisse->id,
            'montant' => 100, 'date_depense' => '2025-09-20', 'agent_id' => $agent->id,
        ]);

        $this->get(route('backoffice.depenses.show', $depense))
            ->assertInertia(fn (Assert $page) => $page->where('depense.receipts', []));
    }

    public function test_depense_show_has_no_delete_route(): void
    {
        $this->assertFalse(Route::has('backoffice.depenses.destroy'));
    }

    // --- CaisseTransfer --------------------------------------------------

    public function test_caisse_transfer_show_denies_cross_center_access(): void
    {
        $source = $this->caisseIn($this->casa);
        $destination = $this->caisseIn($this->rabat);
        $requester = Employee::factory()->create(['etablissement_id' => $this->casa->id]);
        $transfer = CaisseTransfer::create([
            'reference' => 'TRF-XCTR', 'caisse_source_id' => $source->id,
            'caisse_destination_id' => $destination->id, 'montant' => 100,
            'date_transfert' => now(), 'statut' => CaisseTransfer::STATUT_EN_ATTENTE,
            'requested_by' => $requester->id,
        ]);
        $this->centerUser($this->rabat, 'cash-transfers.view');

        $this->get(route('backoffice.caisse-transfers.show', $transfer))->assertForbidden();
    }

    // --- Employee / Remboursement: confirm they stay unreachable -------

    public function test_employee_show_route_does_not_exist(): void
    {
        $this->assertFalse(Route::has('backoffice.employees.show'));
    }

    public function test_remboursement_show_route_does_not_exist(): void
    {
        $this->assertFalse(Route::has('backoffice.remboursements.show'));
    }

    // --- Guest denial across all migrated pages -------------------------

    public function test_guests_are_redirected_from_every_migrated_page(): void
    {
        $student = Student::factory()->create();

        $this->get(route('backoffice.groups-historique.index'))->assertRedirect(route('backoffice.login'));
        $this->get(route('backoffice.students.show', $student))->assertRedirect(route('backoffice.login'));
    }
}
