<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Livewire\Backoffice\Inscriptions\InscriptionsIndex;
use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

final class InscriptionsCrudTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create(['nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31', 'par_defaut' => true, 'inscription_ouverte' => true]);
        $this->centre = Etablissement::factory()->create();
    }

    private function globalUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);

        return $user;
    }

    private function makeGroup(): Group
    {
        return Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
    }

    public function test_page_requires_registrations_view(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('dashboard.view');
        $this->actingAs($u)->get(route('backoffice.inscriptions.index'))->assertForbidden();

        $u2 = User::factory()->create();
        $u2->givePermissionTo('registrations.view');
        $this->actingAs($u2)->get(route('backoffice.inscriptions.index'))->assertOk();
    }

    /** A group with two catalog fees assigned (300 + 1300). */
    private function groupWithFees(): Group
    {
        $group = $this->makeGroup();
        $f1 = Frais::create(['nom' => 'Frais d\'inscription', 'statut' => 'Actif']);
        $f2 = Frais::create(['nom' => 'Frais de Juillet', 'statut' => 'Actif']);
        $group->frais()->attach([$f1->id => ['montant' => 300], $f2->id => ['montant' => 1300]]);

        return $group;
    }

    public function test_selecting_a_group_loads_its_assigned_fees(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();

        $component = Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('student_id', $student->id)
            ->set('group_id', $group->id); // triggers updatedGroupId

        // The group's 2 assigned fees are now available (all billed, no checkbox).
        $this->assertCount(2, $component->get('feeLines'));
        $this->assertSame('300', $component->get('feeLines')[0]['montant_initial']);
    }

    public function test_a_registration_bills_the_selected_group_fees_with_discount(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();

        $component = Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'existing')
            ->set('student_id', $student->id)
            ->set('group_id', $group->id)
            ->set('date_inscription', '2025-09-15')
            // The status field is hidden on create; even a tampered client
            // value must be ignored — new registrations always start Active.
            ->set('statut', 'Annulée');

        // Apply a 75% discount to the second fee line (1300 → 325).
        $component->set('feeLines.1.remise_pct', '75')
            ->call('save')
            ->assertHasNoErrors();

        $inscription = Inscription::where('student_id', $student->id)->first();
        $this->assertNotNull($inscription);
        $this->assertSame(Inscription::STATUT_ACTIVE, $inscription->statut);
        // 300 + (1300 − 75%) = 300 + 325 = 625.
        $this->assertSame('625.00', (string) $inscription->montant_total);
        $this->assertSame(2, $inscription->fees()->count());

        $discounted = $inscription->fees()->where('nom', 'Frais de Juillet')->first();
        $this->assertSame('1300.00', (string) $discounted->montant_initial);
        $this->assertSame('325.00', (string) $discounted->montant);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $discounted->statut);
        // Inherited scope from the group.
        $this->assertSame($this->centre->id, $inscription->etablissement_id);
        $this->assertSame($this->annee->id, $inscription->annee_scolaire_id);
    }

    public function test_all_group_fees_are_billed(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();

        // No checkbox: every fee the group carries is billed on the inscription.
        Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'existing')
            ->set('student_id', $student->id)
            ->set('group_id', $group->id)
            ->set('date_inscription', '2025-09-15')
            ->call('save')
            ->assertHasNoErrors();

        $inscription = Inscription::where('student_id', $student->id)->first();
        $this->assertSame(2, $inscription->fees()->count());
        // 300 + 1300, both carried from the group.
        $this->assertSame('1600.00', (string) $inscription->montant_total);
    }

    public function test_fee_line_due_date_is_prefilled_from_the_group(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $frais = Frais::create(['nom' => 'Frais annuel', 'statut' => 'Actif']);
        // The group bills this fee with a specific due date.
        $group->frais()->attach($frais->id, ['montant' => 500, 'date_echeance' => '2025-10-18']);

        $component = Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'existing')
            ->set('student_id', $student->id)
            ->set('group_id', $group->id);

        // The fee line inherits the group's per-fee due date.
        $this->assertSame('2025-10-18', $component->get('feeLines')[0]['date_echeance']);
    }

    public function test_only_active_catalog_fees_are_available(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();

        $active = Frais::create(['nom' => 'Active fee', 'statut' => 'Actif']);
        $inactive = Frais::create(['nom' => 'Inactive fee', 'statut' => 'Inactif']);
        $group->frais()->attach([$active->id => ['montant' => 300], $inactive->id => ['montant' => 500]]);

        $component = Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'existing')
            ->set('student_id', $student->id)
            ->set('group_id', $group->id);

        // Only the active fee shows up as available.
        $lines = collect($component->get('feeLines'));
        $this->assertCount(1, $lines);
        $this->assertSame('Active fee', $lines->first()['nom']);
    }

    public function test_percentage_discount_auto_fills_the_dh_amount(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees(); // fee[1] = 1300

        $component = Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'existing')
            ->set('student_id', $student->id)
            ->set('group_id', $group->id)
            ->set('feeLines.1.remise_pct', '75'); // 75% of 1300

        // DH auto-filled from the percentage.
        $this->assertSame('975', $component->get('feeLines')[1]['remise_montant']);
    }

    public function test_dh_discount_auto_fills_the_percentage(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees(); // fee[1] = 1300

        $component = Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'existing')
            ->set('student_id', $student->id)
            ->set('group_id', $group->id)
            ->set('feeLines.1.remise_montant', '650'); // 650 of 1300 = 50%

        $this->assertSame('50', $component->get('feeLines')[1]['remise_pct']);
    }

    public function test_dates_are_auto_filled_from_the_selected_group(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();
        $group->update(['date_debut_formation' => '2025-10-01', 'date_fin_formation' => '2026-06-30']);

        $component = Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'existing')
            ->set('student_id', $student->id)
            ->set('group_id', $group->id); // triggers updatedGroupId → auto-fill

        // The dates were pulled from the group's training period.
        $this->assertSame('2025-10-01', $component->get('date_debut'));
        $this->assertSame('2026-06-30', $component->get('date_fin'));

        $component->set('date_inscription', '2025-09-15')->call('save')->assertHasNoErrors();

        $inscription = Inscription::where('student_id', $student->id)->first();
        $this->assertSame('2025-10-01', $inscription->date_debut->toDateString());
        $this->assertSame('2026-06-30', $inscription->date_fin->toDateString());
    }

    public function test_a_tampered_date_is_ignored_and_the_group_date_wins(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();
        $group->update(['date_debut_formation' => '2025-10-01', 'date_fin_formation' => '2026-06-30']);

        Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'existing')
            ->set('student_id', $student->id)
            ->set('group_id', $group->id)
            ->set('date_inscription', '2025-09-15')
            ->set('date_debut', '2020-01-01') // tampered readonly field
            ->call('save')
            ->assertHasNoErrors();

        // Server re-derives from the group, ignoring the tampered value.
        $this->assertSame('2025-10-01', Inscription::where('student_id', $student->id)->first()->date_debut->toDateString());
    }

    public function test_new_student_mode_creates_the_student_and_enrolls_them(): void
    {
        $this->globalUser();
        $group = $this->groupWithFees();

        Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'new')
            ->set('new_nom', 'Zeroual')
            ->set('new_prenom', 'Amine')
            ->set('new_sexe', 'Homme')
            ->set('new_niveau', 'A1.1')
            ->set('group_id', $group->id)
            ->set('date_inscription', '2025-09-20')
            ->call('save')
            ->assertHasNoErrors();

        // A brand-new student was created, scoped to the group's center.
        $student = Student::where('nom', 'Zeroual')->where('prenom', 'Amine')->first();
        $this->assertNotNull($student);
        $this->assertStringStartsWith('ETU-', $student->reference);
        $this->assertSame('A1.1', $student->niveau);
        $this->assertSame($this->centre->id, $student->etablissement_id);

        // …and enrolled with the group's fees.
        $inscription = Inscription::where('student_id', $student->id)->first();
        $this->assertNotNull($inscription);
        $this->assertSame(2, $inscription->fees()->count());
    }

    public function test_new_student_contact_and_parent_fields_are_saved(): void
    {
        $this->globalUser();
        $group = $this->groupWithFees();

        Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'new')
            ->set('new_nom', 'Haddad')
            ->set('new_prenom', 'Sami')
            ->set('new_email', 'sami@example.com')
            ->set('new_telephone', '661000000')
            ->set('new_whatsapp', '662000000')
            ->set('new_adresse', '7 rue des fleurs')
            ->set('new_parent_nom', 'Haddad')
            ->set('new_parent_telephone', '663000000')
            ->set('group_id', $group->id)
            ->set('date_inscription', '2025-09-20')
            ->call('save')
            ->assertHasNoErrors();

        // Phones are stored combined with the shared country dial code
        // (Morocco +212 by default).
        $student = Student::where('nom', 'Haddad')->first();
        $this->assertSame('sami@example.com', $student->email);
        $this->assertSame('+212661000000', $student->telephone);
        $this->assertSame('+212662000000', $student->whatsapp);
        $this->assertSame('7 rue des fleurs', $student->adresse);
        $this->assertSame('Haddad', $student->parent_nom);
        $this->assertSame('+212663000000', $student->parent_telephone);
    }

    public function test_new_student_mode_requires_name_not_student_id(): void
    {
        $this->globalUser();
        $group = $this->groupWithFees();

        Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'new')
            ->set('group_id', $group->id)
            ->set('date_inscription', '2025-09-20')
            ->set('new_nom', '')
            ->set('new_prenom', '')
            ->call('save')
            ->assertHasErrors(['new_nom', 'new_prenom'])
            ->assertHasNoErrors('student_id'); // student_id not required in new mode
    }

    public function test_student_and_group_are_required(): void
    {
        $this->globalUser();

        Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'existing') // pick-existing mode requires student_id
            ->set('student_id', null)
            ->set('group_id', null)
            ->call('save')
            ->assertHasErrors(['student_id', 'group_id']);
    }

    public function test_a_registration_can_be_edited(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-EDIT1', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);

        Livewire::test(InscriptionsIndex::class)
            ->call('edit', $inscription->id)
            ->set('statut', 'Annulée')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Annulée', $inscription->fresh()->statut);
    }

    public function test_center_scoped_user_only_sees_their_center_registrations(): void
    {
        $rabat = Etablissement::factory()->create();
        $casa = Etablissement::factory()->create();

        $rStudent = Student::factory()->create(['nom' => 'RabatStu', 'etablissement_id' => $rabat->id]);
        $cStudent = Student::factory()->create(['nom' => 'CasaStu', 'etablissement_id' => $casa->id]);
        $rGroup = Group::factory()->create(['etablissement_id' => $rabat->id, 'annee_scolaire_id' => $this->annee->id]);
        $cGroup = Group::factory()->create(['etablissement_id' => $casa->id, 'annee_scolaire_id' => $this->annee->id]);

        Inscription::create(['reference' => 'INS-R', 'student_id' => $rStudent->id, 'group_id' => $rGroup->id, 'etablissement_id' => $rabat->id, 'annee_scolaire_id' => $this->annee->id, 'statut' => 'Active', 'date_inscription' => '2025-09-15']);
        Inscription::create(['reference' => 'INS-C', 'student_id' => $cStudent->id, 'group_id' => $cGroup->id, 'etablissement_id' => $casa->id, 'annee_scolaire_id' => $this->annee->id, 'statut' => 'Active', 'date_inscription' => '2025-09-15']);

        // A user assigned to Rabat.
        $user = User::factory()->create();
        $user->givePermissionTo('registrations.view');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $rabat->id]);
        $this->actingAs($user->fresh());

        Livewire::test(InscriptionsIndex::class)
            ->assertSee('INS-R')
            ->assertDontSee('INS-C');
    }

    public function test_detail_page_shows_fees_and_payment_summary(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-SHOW', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15', 'montant_total' => 1500,
        ]);
        InscriptionFee::create(['inscription_id' => $inscription->id, 'nom' => 'Frais de Juillet', 'montant' => 1500, 'date_echeance' => '2025-07-31', 'statut' => 'Non payé']);

        $this->get(route('backoffice.inscriptions.show', $inscription))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Inscriptions/Show')
                ->where('inscription.reference', 'INS-SHOW')
                ->where('inscription.fees.0.nom', 'Frais de Juillet')
            );
    }
}
