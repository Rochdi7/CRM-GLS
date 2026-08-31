<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Settings;

use App\Models\AnneeScolaire;
use App\Models\Banque;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\MotifAnnulation;
use App\Models\Role;
use App\Models\Salle;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Phase 6 (docs/phase-6-simple-crud-inventory.md) — Inertia/React Settings
 * page: Etablissements, Annees Scolaires, Salles, Frais tabs. Replaces the
 * retired Livewire-component tests (Livewire::test(...)) entirely — the UI
 * layer changed, not just its assertions.
 */
final class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }
        $this->actingAs($user->fresh());

        return $user->fresh();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);

        return $user;
    }

    // --- Page access / tab selection ----------------------------------------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('backoffice.settings'))->assertRedirect(route('backoffice.login'));
    }

    public function test_user_without_any_referential_permission_gets_403(): void
    {
        $this->userWith('dashboard.view');

        $this->get(route('backoffice.settings'))->assertForbidden();
    }

    public function test_user_with_any_referential_view_permission_can_open_settings(): void
    {
        $this->userWith('rooms.view');

        $this->get(route('backoffice.settings'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Settings/Index')
                ->where('activeTab', 'salles')
                ->where('availableTabs', ['salles'])
            );
    }

    public function test_requested_tab_is_honored_when_authorized(): void
    {
        $this->userWith('centers.view', 'rooms.view');

        $this->get(route('backoffice.settings', ['tab' => 'salles']))
            ->assertInertia(fn (Assert $page) => $page->where('activeTab', 'salles'));
    }

    public function test_an_unauthorized_tab_falls_back_to_the_first_available_one(): void
    {
        $this->userWith('centers.view');

        $this->get(route('backoffice.settings', ['tab' => 'frais']))
            ->assertInertia(fn (Assert $page) => $page->where('activeTab', 'etablissements'));
    }

    public function test_only_the_active_tab_dataset_is_sent(): void
    {
        $this->userWith('centers.view', 'rooms.view');

        $this->get(route('backoffice.settings', ['tab' => 'salles']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('salles')
                ->has('centerOptions')
                ->missing('etablissements')
            );
    }

    // --- Établissements tab ------------------------------------------------

    public function test_center_can_be_created_and_updated(): void
    {
        $this->userWith('centers.view', 'centers.create', 'centers.update');

        $this->post(route('backoffice.etablissements.store'), [
            'nom_centre' => 'GLS Casablanca',
            'ville' => 'Casablanca',
            'siege_social' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('etablissements', ['nom_centre' => 'GLS Casablanca', 'siege_social' => true]);

        $center = Etablissement::first();
        $this->put(route('backoffice.etablissements.update', $center), [
            'nom_centre' => $center->nom_centre,
            'ville' => 'Rabat',
        ])->assertRedirect();

        $this->assertSame('Rabat', $center->fresh()->ville);
    }

    public function test_center_creation_validates_required_fields(): void
    {
        $this->userWith('centers.view', 'centers.create');

        $this->post(route('backoffice.etablissements.store'), ['nom_centre' => '', 'ville' => ''])
            ->assertSessionHasErrors(['nom_centre', 'ville']);
    }

    public function test_user_without_create_permission_cannot_add_a_center(): void
    {
        $this->userWith('centers.view');

        $this->post(route('backoffice.etablissements.store'), ['nom_centre' => 'X', 'ville' => 'Y'])
            ->assertForbidden();
    }

    public function test_center_in_use_cannot_be_deleted(): void
    {
        $this->userWith('centers.view', 'centers.delete');
        $center = Etablissement::factory()->create();
        Salle::factory()->create(['etablissement_id' => $center->id]);

        $this->delete(route('backoffice.etablissements.destroy', $center))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('etablissements', ['id' => $center->id]);
    }

    // --- Années scolaires tab ------------------------------------------------

    public function test_setting_a_default_year_unsets_the_previous_one(): void
    {
        $this->userWith('academic-years.view', 'academic-years.create');

        $existing = AnneeScolaire::create([
            'nom' => '2024/2025', 'date_debut' => '2024-09-01', 'date_fin' => '2025-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $this->post(route('backoffice.annees-scolaires.store'), [
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true,
        ])->assertRedirect();

        $this->assertFalse($existing->fresh()->par_defaut);
        $this->assertTrue(AnneeScolaire::where('nom', '2025/2026')->first()->par_defaut);
        $this->assertSame(1, AnneeScolaire::where('par_defaut', true)->count());
    }

    public function test_default_year_can_be_switched_from_the_list_action(): void
    {
        $this->userWith('academic-years.view', 'academic-years.update');

        $old = AnneeScolaire::create([
            'nom' => '2024/2025', 'date_debut' => '2024-09-01', 'date_fin' => '2025-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $new = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);

        $this->patch(route('backoffice.annees-scolaires.set-default', $new))
            ->assertRedirect(route('backoffice.settings', ['tab' => 'annees-scolaires']));

        $this->assertTrue($new->fresh()->par_defaut);
        $this->assertFalse($old->fresh()->par_defaut);
        $this->assertSame(1, AnneeScolaire::where('par_defaut', true)->count());
    }

    public function test_user_without_update_permission_cannot_switch_the_default_year(): void
    {
        $this->userWith('academic-years.view');

        $year = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);

        $this->patch(route('backoffice.annees-scolaires.set-default', $year))->assertForbidden();
        $this->assertFalse($year->fresh()->par_defaut);
    }

    public function test_academic_year_end_date_must_be_after_start(): void
    {
        $this->userWith('academic-years.view', 'academic-years.create');

        $this->post(route('backoffice.annees-scolaires.store'), [
            'nom' => '2025/2026', 'date_debut' => '2026-01-01', 'date_fin' => '2025-01-01',
        ])->assertSessionHasErrors('date_fin');
    }

    public function test_academic_year_in_use_cannot_be_deleted(): void
    {
        $this->userWith('academic-years.view', 'academic-years.delete');
        $annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
        Group::factory()->create(['annee_scolaire_id' => $annee->id]);

        $this->delete(route('backoffice.annees-scolaires.destroy', $annee))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('annees_scolaires', ['id' => $annee->id]);
    }

    // --- Salles tab --------------------------------------------------------

    public function test_room_can_be_created_for_a_center(): void
    {
        $this->userWith('rooms.view', 'rooms.create', 'centers.access-all');
        $center = Etablissement::factory()->create();

        $this->post(route('backoffice.salles.store'), [
            'nom' => 'Salle 01', 'etablissement_id' => $center->id, 'capacite' => 20, 'statut' => 'Active',
        ])->assertRedirect();

        $this->assertDatabaseHas('salles', ['nom' => 'Salle 01', 'etablissement_id' => $center->id]);
    }

    public function test_room_requires_a_center(): void
    {
        $this->userWith('rooms.view', 'rooms.create', 'centers.access-all');

        $this->post(route('backoffice.salles.store'), ['nom' => 'Salle 01', 'statut' => 'Active'])
            ->assertSessionHasErrors('etablissement_id');
    }

    public function test_user_without_delete_permission_cannot_delete_a_room(): void
    {
        $this->userWith('rooms.view');
        $room = Salle::factory()->create(['etablissement_id' => Etablissement::factory()]);

        $this->delete(route('backoffice.salles.destroy', $room))->assertForbidden();

        $this->assertDatabaseHas('salles', ['id' => $room->id]);
    }

    /**
     * Phase 6 §Q3 fix: a center-limited user (no centers.access-all) cannot
     * create a room for a center they don't have access to, even though
     * that center genuinely exists — the pre-existing Livewire version had
     * no such check (only `exists:etablissements,id`).
     */
    public function test_a_center_limited_user_cannot_create_a_room_for_an_inaccessible_center(): void
    {
        $myCenter = Etablissement::factory()->create();
        $otherCenter = Etablissement::factory()->create();
        $user = $this->userWith('rooms.view', 'rooms.create');
        \App\Models\Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $myCenter->id]);

        $this->post(route('backoffice.salles.store'), [
            'nom' => 'Salle X', 'etablissement_id' => $otherCenter->id, 'statut' => 'Active',
        ])->assertSessionHasErrors('etablissement_id');

        $this->assertDatabaseMissing('salles', ['nom' => 'Salle X']);
    }

    public function test_a_center_limited_user_can_create_a_room_for_their_own_center(): void
    {
        $myCenter = Etablissement::factory()->create();
        $user = $this->userWith('rooms.view', 'rooms.create');
        \App\Models\Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $myCenter->id]);

        $this->post(route('backoffice.salles.store'), [
            'nom' => 'Salle Y', 'etablissement_id' => $myCenter->id, 'statut' => 'Active',
        ])->assertRedirect();

        $this->assertDatabaseHas('salles', ['nom' => 'Salle Y', 'etablissement_id' => $myCenter->id]);
    }

    public function test_room_in_use_cannot_be_deleted(): void
    {
        $this->userWith('rooms.view', 'rooms.delete', 'centers.access-all');
        $room = Salle::factory()->create();
        Group::factory()->create(['salle_id' => $room->id]);

        $this->delete(route('backoffice.salles.destroy', $room))->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('salles', ['id' => $room->id]);
    }

    /**
     * Ports CenterScopingTest::test_salles_tab_is_scoped_to_the_selected_center
     * (Livewire) — the Salles tab's list must follow the active top-bar
     * context center exactly like every other module's list, narrowing to
     * that center's rows (+ global NULL-center rows) once one is selected.
     * GetSallesList already implements this (same scopeToActiveCenter logic
     * as the retired SallesTab component); this test asserts it at the HTTP
     * level so the behavior stays covered once the Livewire component and
     * its own test are removed (docs/phase-11-livewire-cleanup-audit.md
     * §G.5).
     */
    public function test_salles_tab_is_scoped_to_the_selected_center(): void
    {
        $this->userWith('rooms.view', 'centers.access-all');
        $rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $casa = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);
        Salle::factory()->create(['nom' => 'SalleRabatX', 'etablissement_id' => $rabat->id]);
        Salle::factory()->create(['nom' => 'SalleCasaX', 'etablissement_id' => $casa->id]);

        // "Tous les centres" → both rooms visible.
        $this->get(route('backoffice.settings', ['tab' => 'salles']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('salles.data', fn ($rows) => collect($rows)->pluck('nom')->contains('SalleRabatX')
                    && collect($rows)->pluck('nom')->contains('SalleCasaX'))
            );

        // Rabat selected → Casa's room disappears from the tab.
        app(CurrentContext::class)->setEtablissement($rabat->id);
        $this->get(route('backoffice.settings', ['tab' => 'salles']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('salles.data', fn ($rows) => collect($rows)->pluck('nom')->contains('SalleRabatX')
                    && ! collect($rows)->pluck('nom')->contains('SalleCasaX'))
            );
    }

    // --- Frais tab -----------------------------------------------------------

    public function test_a_fee_can_be_created_and_updated(): void
    {
        $this->userWith('fees.view', 'fees.create', 'fees.update');

        $this->post(route('backoffice.frais.store'), ['nom' => 'Frais de Juillet', 'statut' => Frais::STATUT_ACTIF])
            ->assertRedirect();

        $this->assertDatabaseHas('frais', ['nom' => 'Frais de Juillet']);

        $frais = Frais::first();
        $this->put(route('backoffice.frais.update', $frais), ['nom' => 'Frais renommé', 'statut' => Frais::STATUT_ACTIF])
            ->assertRedirect();

        $this->assertSame('Frais renommé', $frais->fresh()->nom);
    }

    public function test_a_fee_carries_a_default_amount_that_groups_inherit(): void
    {
        // The catalog amount is what every group starts from; without it
        // group_frais lines were created at 0.00 and the payment modal
        // listed no fees at all (reste = montant - payé is never > 0).
        $this->userWith('fees.view', 'fees.create', 'fees.update');

        $this->post(route('backoffice.frais.store'), [
            'nom' => 'Frais de Juillet',
            'montant_defaut' => '1300.00',
            'statut' => Frais::STATUT_ACTIF,
        ])->assertRedirect();

        $this->assertSame('1300.00', (string) Frais::firstOrFail()->montant_defaut);

        $frais = Frais::firstOrFail();
        $this->put(route('backoffice.frais.update', $frais), [
            'nom' => 'Frais de Juillet',
            'montant_defaut' => '1450.50',
            'statut' => Frais::STATUT_ACTIF,
        ])->assertRedirect();

        $this->assertSame('1450.50', (string) $frais->fresh()->montant_defaut);
    }

    public function test_a_fee_is_priced_per_center(): void
    {
        // The same monthly fee costs 1400 in Rabat and 1200 in Agadir, so
        // one catalog entry is attached to both centers with each center's
        // own amount — never duplicated once per branch, which would fork
        // every group_frais link across seven rows.
        $this->admin();

        $rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $agadir = Etablissement::factory()->create(['nom_centre' => 'GLS Agadir']);

        $this->post(route('backoffice.frais.store'), [
            'nom' => 'Frais de Septembre',
            'montant_defaut' => '1300.00',
            'statut' => Frais::STATUT_ACTIF,
            'centres' => [
                ['etablissement_id' => $rabat->id, 'montant' => '1400.00'],
                ['etablissement_id' => $agadir->id, 'montant' => '1200.00'],
            ],
        ])->assertRedirect();

        $frais = Frais::firstOrFail();

        $this->assertDatabaseHas('frais_etablissement', [
            'frais_id' => $frais->id, 'etablissement_id' => $rabat->id, 'montant' => '1400.00',
        ]);
        $this->assertDatabaseHas('frais_etablissement', [
            'frais_id' => $frais->id, 'etablissement_id' => $agadir->id, 'montant' => '1200.00',
        ]);

        $this->assertSame(1400.0, $frais->montantPourCentre($rabat->id));
        $this->assertSame(1200.0, $frais->montantPourCentre($agadir->id));
    }

    public function test_a_center_without_its_own_price_falls_back_to_the_catalog_default(): void
    {
        // Attaching a fee to every branch is optional: a center with no
        // price line still gets a usable amount instead of 0.00, which
        // would read as "free" and hide the fee from the payment modal.
        $this->admin();

        $sansTarif = Etablissement::factory()->create(['nom_centre' => 'GLS Online']);

        $frais = Frais::create([
            'nom' => 'Frais de Juillet',
            'montant_defaut' => '1300.00',
            'statut' => Frais::STATUT_ACTIF,
        ]);

        $this->assertSame(1300.0, $frais->montantPourCentre($sansTarif->id));
        // No center at all (e.g. a group with no établissement) too.
        $this->assertSame(1300.0, $frais->montantPourCentre(null));
    }

    public function test_updating_a_fee_replaces_its_center_prices(): void
    {
        $this->admin();

        $rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $agadir = Etablissement::factory()->create(['nom_centre' => 'GLS Agadir']);

        $frais = Frais::create([
            'nom' => 'Frais de Mai', 'montant_defaut' => '1300.00', 'statut' => Frais::STATUT_ACTIF,
        ]);
        $frais->etablissements()->attach([
            $rabat->id => ['montant' => 1400],
            $agadir->id => ['montant' => 1200],
        ]);

        // Re-price Rabat and drop Agadir entirely.
        $this->put(route('backoffice.frais.update', $frais), [
            'nom' => 'Frais de Mai',
            'montant_defaut' => '1300.00',
            'statut' => Frais::STATUT_ACTIF,
            'centres' => [['etablissement_id' => $rabat->id, 'montant' => '1500.00']],
        ])->assertRedirect();

        $this->assertDatabaseHas('frais_etablissement', [
            'frais_id' => $frais->id, 'etablissement_id' => $rabat->id, 'montant' => '1500.00',
        ]);
        $this->assertDatabaseMissing('frais_etablissement', [
            'frais_id' => $frais->id, 'etablissement_id' => $agadir->id,
        ]);
    }

    public function test_omitting_the_default_amount_falls_back_to_zero_rather_than_failing(): void
    {
        // A *default* amount must itself default — requiring it would break
        // every caller that does not send one.
        $this->userWith('fees.view', 'fees.create');

        $this->post(route('backoffice.frais.store'), [
            'nom' => 'Frais sans montant',
            'statut' => Frais::STATUT_ACTIF,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('0.00', (string) Frais::firstOrFail()->montant_defaut);
    }

    public function test_a_negative_default_amount_is_rejected(): void
    {
        $this->userWith('fees.view', 'fees.create');

        $this->post(route('backoffice.frais.store'), [
            'nom' => 'Frais négatif',
            'montant_defaut' => '-10',
            'statut' => Frais::STATUT_ACTIF,
        ])->assertSessionHasErrors('montant_defaut');

        $this->assertDatabaseCount('frais', 0);
    }

    public function test_fee_name_must_be_unique(): void
    {
        $this->userWith('fees.view', 'fees.create');
        Frais::create(['nom' => 'Frais annuel', 'statut' => Frais::STATUT_ACTIF]);

        $this->post(route('backoffice.frais.store'), ['nom' => 'Frais annuel', 'statut' => Frais::STATUT_ACTIF])
            ->assertSessionHasErrors('nom');
    }

    public function test_fee_assigned_to_a_group_cannot_be_deleted(): void
    {
        $this->userWith('fees.view', 'fees.delete');
        $frais = Frais::create(['nom' => 'Frais assigné', 'statut' => Frais::STATUT_ACTIF]);
        $group = Group::factory()->create();
        $group->frais()->attach($frais->id, ['montant' => 100]);

        $this->delete(route('backoffice.frais.destroy', $frais))->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('frais', ['id' => $frais->id]);
    }

    public function test_user_without_create_permission_cannot_add_a_fee(): void
    {
        $this->userWith('fees.view');

        $this->post(route('backoffice.frais.store'), ['nom' => 'X', 'statut' => Frais::STATUT_ACTIF])
            ->assertForbidden();
    }

    public function test_super_admin_can_manage_every_tab(): void
    {
        $this->admin();
        $center = Etablissement::factory()->create();

        $this->post(route('backoffice.etablissements.store'), ['nom_centre' => 'A', 'ville' => 'B'])->assertRedirect();
        $this->post(route('backoffice.annees-scolaires.store'), [
            'nom' => '2030/2031', 'date_debut' => '2030-09-01', 'date_fin' => '2031-08-31',
        ])->assertRedirect();
        $this->post(route('backoffice.salles.store'), [
            'nom' => 'Salle Admin', 'etablissement_id' => $center->id, 'statut' => 'Active',
        ])->assertRedirect();
        $this->post(route('backoffice.frais.store'), ['nom' => 'Frais Admin', 'statut' => Frais::STATUT_ACTIF])->assertRedirect();
        $this->post(route('backoffice.banques.store'), ['nom' => 'Banque Admin', 'statut' => Banque::STATUT_ACTIF])->assertRedirect();
    }

    // --- Banques tab -----------------------------------------------------------

    public function test_a_bank_can_be_created_and_updated(): void
    {
        $this->userWith('banks.view', 'banks.create', 'banks.update');

        $this->post(route('backoffice.banques.store'), ['nom' => 'Banque de Juillet', 'statut' => Banque::STATUT_ACTIF])
            ->assertRedirect();

        $this->assertDatabaseHas('banques', ['nom' => 'Banque de Juillet']);

        $banque = Banque::first();
        $this->put(route('backoffice.banques.update', $banque), ['nom' => 'Banque renommée', 'statut' => Banque::STATUT_ACTIF])
            ->assertRedirect();

        $this->assertSame('Banque renommée', $banque->fresh()->nom);
    }

    public function test_bank_name_must_be_unique(): void
    {
        $this->userWith('banks.view', 'banks.create');
        Banque::create(['nom' => 'Attijariwafa Bank', 'statut' => Banque::STATUT_ACTIF]);

        $this->post(route('backoffice.banques.store'), ['nom' => 'Attijariwafa Bank', 'statut' => Banque::STATUT_ACTIF])
            ->assertSessionHasErrors('nom');
    }

    public function test_bank_used_by_a_payment_cannot_be_deleted(): void
    {
        $this->userWith('banks.view', 'banks.delete');
        $banque = Banque::create(['nom' => 'Banque utilisée', 'statut' => Banque::STATUT_ACTIF]);

        $center = Etablissement::factory()->create();
        $annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
        $student = Student::factory()->create(['etablissement_id' => $center->id]);
        $group = Group::factory()->create(['etablissement_id' => $center->id, 'annee_scolaire_id' => $annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-BQ', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $center->id, 'annee_scolaire_id' => $annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15', 'montant_total' => 500,
        ]);
        $fee = \App\Models\InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais', 'montant' => 500,
            'date_echeance' => '2025-10-01', 'statut' => 'Non payé',
        ]);
        $caisse = \App\Models\Caisse::factory()->create(['etablissement_id' => $center->id]);
        $agent = \App\Models\Employee::factory()->create(['etablissement_id' => $center->id]);

        Encaissement::create([
            'reference' => 'ENC-CHQ', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => 500, 'methode' => Encaissement::METHODE_CHEQUE, 'date_paiement' => '2025-10-01',
            'numero_cheque' => 'CHQ-001', 'banque' => $banque->nom, 'date_echeance_cheque' => '2025-11-01',
        ]);

        $this->delete(route('backoffice.banques.destroy', $banque))->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('banques', ['id' => $banque->id]);
    }

    public function test_user_without_create_permission_cannot_add_a_bank(): void
    {
        $this->userWith('banks.view');

        $this->post(route('backoffice.banques.store'), ['nom' => 'X', 'statut' => Banque::STATUT_ACTIF])
            ->assertForbidden();
    }

    /**
     * banks.* is deliberately absent from every role in
     * PermissionRegistry::matrix() — even the director role (which holds
     * every other tab's full CRUD set) must NOT reach the Banques tab.
     */
    public function test_director_role_cannot_manage_banks(): void
    {
        $user = User::factory()->create();
        $user->assignRole('director');
        $this->actingAs($user);

        $this->post(route('backoffice.banques.store'), ['nom' => 'X', 'statut' => Banque::STATUT_ACTIF])
            ->assertForbidden();

        $this->get(route('backoffice.settings', ['tab' => 'banques']))
            ->assertInertia(fn (Assert $page) => $page->where(
                'availableTabs',
                fn ($tabs) => ! collect($tabs)->contains('banques'),
            ));
    }

    // --- Raisons d'annulation tab ------------------------------------------------

    public function test_a_cancellation_reason_can_be_created_and_updated(): void
    {
        $this->userWith('cancellation-reasons.view', 'cancellation-reasons.create', 'cancellation-reasons.update');

        $this->post(route('backoffice.motifs-annulation.store'), ['nom' => 'Non-paiement', 'portee' => MotifAnnulation::PORTEE_INSCRIPTION, 'statut' => MotifAnnulation::STATUT_ACTIF])
            ->assertRedirect();

        $this->assertDatabaseHas('motifs_annulation', ['nom' => 'Non-paiement', 'is_system' => false]);

        $motif = MotifAnnulation::first();
        $this->put(route('backoffice.motifs-annulation.update', $motif), ['nom' => 'Non-paiement prolongé', 'portee' => MotifAnnulation::PORTEE_INSCRIPTION, 'statut' => MotifAnnulation::STATUT_INACTIF])
            ->assertRedirect();

        $this->assertSame('Non-paiement prolongé', $motif->fresh()->nom);
    }

    public function test_cancellation_reason_name_must_be_unique(): void
    {
        $this->userWith('cancellation-reasons.view', 'cancellation-reasons.create');
        MotifAnnulation::create(['nom' => 'Autre', 'statut' => MotifAnnulation::STATUT_ACTIF]);

        $this->post(route('backoffice.motifs-annulation.store'), ['nom' => 'Autre', 'statut' => MotifAnnulation::STATUT_ACTIF])
            ->assertSessionHasErrors('nom');
    }

    public function test_system_cancellation_reason_cannot_be_updated_or_deleted_even_by_super_admin(): void
    {
        $this->admin();
        $motif = MotifAnnulation::create([
            'nom' => MotifAnnulation::MOTIF_CHANGEMENT_GROUPE,
            'is_system' => true,
            'statut' => MotifAnnulation::STATUT_ACTIF,
        ]);

        $this->put(route('backoffice.motifs-annulation.update', $motif), ['nom' => 'X', 'portee' => MotifAnnulation::PORTEE_TOUS, 'statut' => MotifAnnulation::STATUT_ACTIF])
            ->assertForbidden();
        $this->delete(route('backoffice.motifs-annulation.destroy', $motif))->assertForbidden();

        $this->assertDatabaseHas('motifs_annulation', ['nom' => MotifAnnulation::MOTIF_CHANGEMENT_GROUPE]);
    }

    public function test_regular_cancellation_reason_can_be_deleted(): void
    {
        $this->userWith('cancellation-reasons.view', 'cancellation-reasons.delete');
        $motif = MotifAnnulation::create(['nom' => 'Ar', 'statut' => MotifAnnulation::STATUT_ACTIF]);

        $this->delete(route('backoffice.motifs-annulation.destroy', $motif))->assertRedirect();

        $this->assertDatabaseMissing('motifs_annulation', ['id' => $motif->id]);
    }

    public function test_user_without_create_permission_cannot_add_a_cancellation_reason(): void
    {
        $this->userWith('cancellation-reasons.view');

        $this->post(route('backoffice.motifs-annulation.store'), ['nom' => 'X', 'statut' => MotifAnnulation::STATUT_ACTIF])
            ->assertForbidden();
    }

    /**
     * cancellation-reasons.* is deliberately absent from every role in
     * PermissionRegistry::matrix() — even the director role must NOT reach
     * the Raisons d'annulation tab.
     */
    public function test_director_role_cannot_manage_cancellation_reasons(): void
    {
        $user = User::factory()->create();
        $user->assignRole('director');
        $this->actingAs($user);

        $this->post(route('backoffice.motifs-annulation.store'), ['nom' => 'X', 'statut' => MotifAnnulation::STATUT_ACTIF])
            ->assertForbidden();

        $this->get(route('backoffice.settings', ['tab' => 'motifs-annulation']))
            ->assertInertia(fn (Assert $page) => $page->where(
                'availableTabs',
                fn ($tabs) => ! collect($tabs)->contains('motifs-annulation'),
            ));
    }

    public function test_super_admin_can_manage_cancellation_reasons(): void
    {
        $this->admin();

        $this->post(route('backoffice.motifs-annulation.store'), ['nom' => 'Déménagement', 'portee' => MotifAnnulation::PORTEE_INSCRIPTION, 'statut' => MotifAnnulation::STATUT_ACTIF])
            ->assertRedirect();

        $this->assertDatabaseHas('motifs_annulation', ['nom' => 'Déménagement', 'is_system' => false]);

        $this->get(route('backoffice.settings', ['tab' => 'motifs-annulation']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Settings/Index')
                ->where('activeTab', 'motifs-annulation')
                ->has('motifsAnnulation.data', 1));
    }

    public function test_pagination_links_keep_the_active_tab(): void
    {
        $this->admin();
        for ($i = 1; $i <= 20; $i++) {
            Frais::create(['nom' => "Frais {$i}", 'statut' => Frais::STATUT_ACTIF]);
        }

        $this->get('/backoffice/settings?tab=frais')
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeTab', 'frais')
                // Without withQueryString() the paginator emits "?page=2"
                // with no tab, so page 2 falls back to the first tab.
                ->where('frais.links.2.url', fn ($url) => str_contains((string) $url, 'tab=frais')));
    }

    public function test_page_two_of_a_tab_stays_on_that_tab(): void
    {
        $this->admin();
        for ($i = 1; $i <= 20; $i++) {
            Frais::create(['nom' => "Frais {$i}", 'statut' => Frais::STATUT_ACTIF]);
        }

        $this->get('/backoffice/settings?tab=frais&page=2')
            ->assertInertia(fn (Assert $page) => $page
                ->where('activeTab', 'frais')
                ->where('frais.current_page', 2));
    }
}
