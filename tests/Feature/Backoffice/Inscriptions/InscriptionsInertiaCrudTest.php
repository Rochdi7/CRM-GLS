<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Covers the new Inertia/React Inscriptions endpoints (InscriptionController)
 * built alongside the unchanged Livewire InscriptionsIndex fallback — see
 * InscriptionsCrudTest for the Livewire-side coverage of the same business
 * rules (docs/phase-9-inscriptions-audit.md +
 * docs/phase-9-inscriptions-mapping.md). Money/discount math mirrors
 * InscriptionFee::computeMontant() and is asserted server-side only — the
 * client-side preview is display-only and never trusted.
 */
final class InscriptionsInertiaCrudTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

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

    /**
     * Grants centers.access-all alongside the requested permissions so these
     * tests (which create registrations across an arbitrary center) aren't
     * blocked by InscriptionPolicy::withinCenter() — center-scoping itself is
     * covered separately by test_center_scoped_user_cannot_view_other_center_registrations.
     */
    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    private function makeGroup(): Group
    {
        return Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    /** A group with two catalog fees assigned (300 + 1300), one active-only fee among them. */
    private function groupWithFees(): Group
    {
        $group = $this->makeGroup();
        $f1 = Frais::create(['nom' => "Frais d'inscription", 'statut' => 'Actif']);
        $f2 = Frais::create(['nom' => 'Frais de Juillet', 'statut' => 'Actif']);
        $group->frais()->attach([$f1->id => ['montant' => 300], $f2->id => ['montant' => 1300]]);

        return $group;
    }

    // --- index -----------------------------------------------------------

    public function test_index_requires_registrations_view_and_renders_the_react_page(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.inscriptions.index'))
            ->assertForbidden();

        $this->actingAs($this->userWith('registrations.view'))
            ->get(route('backoffice.inscriptions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Inscriptions/Index', false)
                ->has('inscriptions')
                ->has('students')
                ->has('groups')
                ->has('statuts')
                ->has('niveaux')
                // The phone-country catalog is client-side now
                // (resources/js/Data/countries.ts) — no server prop
                // (Phase 12 payload optimization).
                ->missing('countries')
            );
    }

    // --- group fee lookup --------------------------------------------------

    public function test_group_fees_endpoint_returns_only_active_catalog_fees_with_dates(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $group = $this->makeGroup();
        $active = Frais::create(['nom' => 'Active fee', 'statut' => 'Actif']);
        $inactive = Frais::create(['nom' => 'Inactive fee', 'statut' => 'Inactif']);
        $group->frais()->attach([
            $active->id => ['montant' => 500, 'date_echeance' => '2025-10-18'],
            $inactive->id => ['montant' => 999, 'date_echeance' => null],
        ]);
        $group->update(['date_debut_formation' => '2025-10-01', 'date_fin_formation' => '2026-06-30']);

        $response = $this->get(route('backoffice.groups.inscription-fees', $group))
            ->assertOk()
            ->json();

        $this->assertCount(1, $response['fees']);
        $this->assertSame('Active fee', $response['fees'][0]['nom']);
        $this->assertSame('500.00', $response['fees'][0]['montantInitial']);
        $this->assertSame('2025-10-18', $response['fees'][0]['dateEcheance']);
        $this->assertSame('2025-10-01', $response['dateDebut']);
        $this->assertSame('2026-06-30', $response['dateFin']);
    }

    public function test_group_fees_endpoint_requires_registrations_create(): void
    {
        $group = $this->makeGroup();

        $this->actingAs($this->userWith('registrations.view'))
            ->get(route('backoffice.groups.inscription-fees', $group))
            ->assertForbidden();
    }

    // --- create: existing student -------------------------------------

    public function test_a_registration_bills_the_selected_group_fees_with_discount(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2025-09-15',
            // Tampered: a create request has no `statut` field at all —
            // even if sent, it must be ignored.
            'statut' => 'Annulée',
            'fee_lines' => [
                ['frais_id' => null, 'nom' => "Frais d'inscription", 'montant_initial' => '300'],
                ['frais_id' => null, 'nom' => 'Frais de Juillet', 'montant_initial' => '1300', 'remise_pct' => '75'],
            ],
        ])->assertRedirect(route('backoffice.inscriptions.index'));

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
        // Inherited from the group, not CurrentContext.
        $this->assertSame($this->centre->id, $inscription->etablissement_id);
        $this->assertSame($this->annee->id, $inscription->annee_scolaire_id);
    }

    // --- read-only fee list (edit modal) ------------------------------------

    public function test_fees_endpoint_returns_the_inscriptions_billed_fees(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2025-09-15',
            'fee_lines' => [
                ['frais_id' => null, 'nom' => "Frais d'inscription", 'montant_initial' => '300'],
                ['frais_id' => null, 'nom' => 'Frais de Juillet', 'montant_initial' => '1300', 'remise_montant' => '100'],
            ],
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $inscription = Inscription::where('student_id', $student->id)->first();

        $response = $this->get(route('backoffice.inscriptions.fees', $inscription))
            ->assertOk()
            ->json();

        $this->assertCount(2, $response['fees']);
        $discounted = collect($response['fees'])->firstWhere('nom', 'Frais de Juillet');
        $this->assertSame('1300.00', $discounted['montantInitial']);
        $this->assertSame('100.00', $discounted['remiseMontant']);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $discounted['statut']);
        $this->assertNotNull($discounted['id']);
    }

    public function test_fees_endpoint_requires_registrations_view(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-FEES', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('backoffice.inscriptions.fees', $inscription))
            ->assertForbidden();
    }

    // --- editing fees on an existing inscription (registrations.manage-fees) ---

    private function inscriptionWithFee(float $montantInitial = 1300.0): array
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-EDIT-FEES', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15', 'montant_total' => $montantInitial,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Juillet',
            'montant_initial' => $montantInitial, 'montant' => $montantInitial,
            'date_echeance' => '2025-10-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$inscription, $fee];
    }

    public function test_updating_fees_requires_manage_fees_not_just_update(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();

        $this->actingAs($this->userWith('registrations.view', 'registrations.update'))
            ->put(route('backoffice.inscriptions.fees.update', $inscription), [
                'fee_lines' => [['id' => $fee->id, 'nom' => $fee->nom, 'montant_initial' => '1000']],
            ])
            ->assertForbidden();

        $this->assertSame('1300.00', (string) $fee->fresh()->montant_initial);
    }

    public function test_an_unpaid_fee_amount_can_be_edited(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->put(route('backoffice.inscriptions.fees.update', $inscription), [
                'fee_lines' => [[
                    'id' => $fee->id, 'nom' => $fee->nom,
                    'montant_initial' => '1300', 'remise_montant' => '100',
                ]],
            ])
            ->assertRedirect(route('backoffice.inscriptions.index'));

        $fresh = $fee->fresh();
        // 1300 − 100 DH = 1200.00 exactly (not a rounded-percentage artifact).
        $this->assertSame('1200.00', (string) $fresh->montant);
        $this->assertSame('1200.00', (string) $inscription->fresh()->montant_total);
    }

    public function test_a_new_fee_line_can_be_added_on_edit(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->put(route('backoffice.inscriptions.fees.update', $inscription), [
                'fee_lines' => [
                    ['id' => $fee->id, 'nom' => $fee->nom, 'montant_initial' => '1300'],
                    ['nom' => 'Frais supplémentaire', 'montant_initial' => '200'],
                ],
            ])
            ->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertSame(2, $inscription->fees()->count());
        $this->assertSame('1500.00', (string) $inscription->fresh()->montant_total);
    }

    public function test_a_fee_line_omitted_from_the_payload_is_removed(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();
        $otherFee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Autre frais',
            'montant_initial' => 100, 'montant' => 100,
            'date_echeance' => '2025-10-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->put(route('backoffice.inscriptions.fees.update', $inscription), [
                'fee_lines' => [['id' => $fee->id, 'nom' => $fee->nom, 'montant_initial' => '1300']],
            ])
            ->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertNull(InscriptionFee::find($otherFee->id));
        $this->assertSame(1, $inscription->fees()->count());
    }

    /**
     * Since 31/08/2026 removing a PAID fee line no longer fails: its payments
     * are released as unallocated avances first (the same release
     * RetirerFraisGroupe performs), so the line goes and the money stays on
     * the student, visible on the Avances tab and re-applicable to another
     * fee. Before that the delete hit the encaissements FK-restrict and the
     * whole edit was refused, leaving a wrongly-added fee stuck forever.
     */
    public function test_removing_a_paid_fee_line_releases_its_money_as_an_avance(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();
        $caisse = \App\Models\Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        \App\Models\Encaissement::create([
            'reference' => 'ENC-EDIT-FEES', 'student_id' => $inscription->student_id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => 500, 'methode' => 'Espèces', 'date_paiement' => '2025-10-01',
        ]);

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->put(route('backoffice.inscriptions.fees.update', $inscription), ['fee_lines' => []])
            ->assertRedirect(route('backoffice.inscriptions.index'));

        // The line is gone…
        $this->assertNull(InscriptionFee::find($fee->id));

        // …but the money record is never deleted: it is now an unallocated,
        // re-applicable avance sitting on the student.
        $paiement = \App\Models\Encaissement::query()->where('reference', 'ENC-EDIT-FEES')->firstOrFail();
        $this->assertNull($paiement->inscription_fee_id);
        $this->assertTrue($paiement->isAvance());
        $this->assertSame(500.0, $paiement->montantRestant());
    }

    public function test_editing_a_partially_paid_fees_amount_recomputes_its_statut(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1000.0);
        $caisse = \App\Models\Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        \App\Models\Encaissement::create([
            'reference' => 'ENC-PARTIAL', 'student_id' => $inscription->student_id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => 400, 'methode' => 'Espèces', 'date_paiement' => '2025-10-01',
        ]);

        // Lowering the fee to exactly what's already been paid should flip it to Payé.
        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->put(route('backoffice.inscriptions.fees.update', $inscription), [
                'fee_lines' => [['id' => $fee->id, 'nom' => $fee->nom, 'montant_initial' => '400']],
            ])
            ->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertSame(InscriptionFee::STATUT_PAYE, $fee->fresh()->statut);
    }

    public function test_dh_discount_takes_effect_when_percentage_is_absent(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2025-09-15',
            'fee_lines' => [
                ['nom' => 'Frais de Juillet', 'montant_initial' => '1300', 'remise_montant' => '650'],
            ],
        ])->assertSessionDoesntHaveErrors();

        $inscription = Inscription::where('student_id', $student->id)->first();
        // 1300 − 650 = 650.
        $this->assertSame('650.00', (string) $inscription->fees()->first()->montant);
    }

    public function test_percentage_discount_takes_priority_over_fixed_amount_when_both_present(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2025-09-15',
            'fee_lines' => [
                // Both present: pct (50% => 650) must win over the DH figure (which would give 300).
                ['nom' => 'Frais de Juillet', 'montant_initial' => '1300', 'remise_pct' => '50', 'remise_montant' => '1000'],
            ],
        ])->assertSessionDoesntHaveErrors();

        $inscription = Inscription::where('student_id', $student->id)->first();
        $this->assertSame('650.00', (string) $inscription->fees()->first()->montant);
    }

    /**
     * The period belongs to the REGISTRATION, not to the group: a student may
     * join a running group, so the form's (today-prefilled) dates are stored
     * as submitted and never overwritten by the group's training period.
     */
    public function test_dates_come_from_the_form_not_the_group(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();
        $group->update(['date_debut_formation' => '2025-10-01', 'date_fin_formation' => '2026-06-30']);

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2025-11-15',
            'date_debut' => '2025-11-15',
            'date_fin' => '2026-03-15',
        ])->assertSessionDoesntHaveErrors();

        $inscription = Inscription::where('student_id', $student->id)->first();
        $this->assertSame('2025-11-15', $inscription->date_debut->toDateString());
        $this->assertSame('2026-03-15', $inscription->date_fin->toDateString());
    }

    public function test_end_date_cannot_precede_start_date(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2025-11-15',
            'date_debut' => '2025-11-15',
            'date_fin' => '2025-11-01',
        ])->assertSessionHasErrors('date_fin');
    }

    public function test_student_and_group_are_required_in_existing_mode(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'date_inscription' => '2025-09-15',
        ])->assertSessionHasErrors(['student_id', 'group_id']);
    }

    // --- create: new student inline ----------------------------------------

    public function test_new_student_mode_creates_the_student_and_enrolls_them(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $group = $this->groupWithFees();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'new',
            'new_nom' => 'Zeroual',
            'new_prenom' => 'Amine',
            'new_sexe' => 'Homme',
            'new_niveau' => 'A1.1',
            'phone_pays' => 'MA',
            'group_id' => $group->id,
            'date_inscription' => '2025-09-20',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $student = Student::where('nom', 'Zeroual')->where('prenom', 'Amine')->first();
        $this->assertNotNull($student);
        $this->assertStringStartsWith('ETU-', $student->reference);
        $this->assertSame('A1.1', $student->niveau);
        $this->assertSame($this->centre->id, $student->etablissement_id);

        $inscription = Inscription::where('student_id', $student->id)->first();
        $this->assertNotNull($inscription);
        $this->assertStringStartsWith('INS-', $inscription->reference);
    }

    public function test_new_student_contact_and_parent_fields_are_saved_with_combined_phone(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $group = $this->groupWithFees();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'new',
            'new_nom' => 'Haddad',
            'new_prenom' => 'Sami',
            'new_email' => 'sami@example.com',
            'new_telephone' => '661000000',
            'new_whatsapp' => '662000000',
            'new_adresse' => '7 rue des fleurs',
            'new_parent_nom' => 'Haddad',
            'new_parent_telephone' => '663000000',
            'phone_pays' => 'MA',
            'group_id' => $group->id,
            'date_inscription' => '2025-09-20',
        ])->assertSessionDoesntHaveErrors();

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
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $group = $this->groupWithFees();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'new',
            'group_id' => $group->id,
            'date_inscription' => '2025-09-20',
            'new_nom' => '',
            'new_prenom' => '',
            'phone_pays' => 'MA',
        ])
            ->assertSessionHasErrors(['new_nom', 'new_prenom'])
            ->assertSessionDoesntHaveErrors('student_id');
    }

    public function test_user_without_create_permission_cannot_store(): void
    {
        $this->actingAs($this->userWith('registrations.view'));
        $group = $this->makeGroup();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2025-09-15',
        ])->assertForbidden();
    }

    /**
     * Ports InscriptionStudentFieldsTest::test_a_new_student_is_created_
     * with_cin_and_a_professional_field (Livewire).
     */
    public function test_new_student_mode_saves_cin_and_a_professional_field(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $group = $this->makeGroup();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'new',
            'new_nom' => 'Bennani',
            'new_prenom' => 'Yassine',
            'new_cin' => 'AB123456',
            'new_niveau' => 'Ausbildung',
            'new_domaine' => 'Mécanique automobile',
            'phone_pays' => 'MA',
            'group_id' => $group->id,
            'date_inscription' => '2025-09-20',
        ])->assertSessionDoesntHaveErrors();

        $student = Student::where('nom', 'Bennani')->firstOrFail();
        $this->assertSame('AB123456', $student->cin);
        $this->assertSame('Ausbildung', $student->niveau);
        $this->assertSame('Mécanique automobile', $student->domaine);
        $this->assertNull($student->examen_type);
    }

    /**
     * Ports InscriptionStudentFieldsTest::test_a_new_student_can_be_
     * created_with_an_entrance_exam (Livewire).
     */
    public function test_new_student_mode_saves_an_entrance_exam(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $group = $this->makeGroup();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'new',
            'new_nom' => 'Cherkaoui',
            'new_prenom' => 'Nada',
            'new_niveau' => 'Studium',
            'new_examen_type' => 'STK',
            'phone_pays' => 'MA',
            'group_id' => $group->id,
            'date_inscription' => '2025-09-20',
        ])->assertSessionDoesntHaveErrors();

        $student = Student::where('nom', 'Cherkaoui')->firstOrFail();
        $this->assertSame('STK', $student->examen_type);
        $this->assertNull($student->domaine);
    }

    /**
     * Ports InscriptionStudentFieldsTest::test_the_field_is_required_when_
     * the_track_asks_for_it (Livewire).
     */
    public function test_new_student_mode_requires_domaine_for_arbeit(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $group = $this->makeGroup();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'new',
            'new_nom' => 'Idrissi',
            'new_prenom' => 'Omar',
            'new_niveau' => 'Arbeit',
            'phone_pays' => 'MA',
            'group_id' => $group->id,
            'date_inscription' => '2025-09-20',
        ])->assertSessionHasErrors('new_domaine');
    }

    /**
     * Ports InscriptionStudentFieldsTest::test_an_unknown_parent_relation_
     * is_rejected (Livewire).
     */
    public function test_new_student_mode_rejects_an_unknown_parent_relation(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $group = $this->makeGroup();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'new',
            'new_nom' => 'Idrissi',
            'new_prenom' => 'Omar',
            'new_parent_relation' => 'Le voisin',
            'phone_pays' => 'MA',
            'group_id' => $group->id,
            'date_inscription' => '2025-09-20',
        ])->assertSessionHasErrors('new_parent_relation');
    }

    // --- update --------------------------------------------------------

    public function test_update_only_touches_the_five_editable_fields(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-EDIT1', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
            'date_debut' => '2025-09-01', 'date_fin' => '2026-06-01', 'montant_total' => 1000,
        ]);

        $this->put(route('backoffice.inscriptions.update', $inscription), [
            'student_id' => $student->id,
            'statut' => 'Annulée',
            'date_inscription' => '2025-09-15',
            // Deliberately different from the group's training period — on
            // update these are trusted as submitted (asymmetry vs create).
            'date_debut' => '2025-09-10',
            'date_fin' => '2026-07-01',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $fresh = $inscription->fresh();
        // A bare statut flip is never accepted here (audit CRUD-F4):
        // cancelling needs cancel() + a reason.
        $this->assertSame('Active', $fresh->statut);
        $this->assertNull($fresh->motif_annulation);
        $this->assertSame('2025-09-10', $fresh->date_debut->toDateString());
        $this->assertSame('2026-07-01', $fresh->date_fin->toDateString());
        // Untouched by update.
        $this->assertSame('1000.00', (string) $fresh->montant_total);
    }

    /**
     * group_id is no longer accepted by this endpoint at all — a tampered
     * client sending a different group must have it silently ignored;
     * moving a student to another group only ever happens through
     * changeGroup() (fee migration + archival snapshot).
     */
    public function test_update_ignores_a_tampered_group_id(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $originalGroup = $this->makeGroup();
        $otherGroup = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-EDIT-GROUP', 'student_id' => $student->id, 'group_id' => $originalGroup->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);

        $this->put(route('backoffice.inscriptions.update', $inscription), [
            'student_id' => $student->id,
            'group_id' => $otherGroup->id,
            'statut' => 'Active',
            'date_inscription' => '2025-09-15',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertSame($originalGroup->id, $inscription->fresh()->group_id);
    }

    // --- update-statut (row-menu "Annuler"/"Réactiver") ------------------

    /**
     * "Changement" is deliberately NOT reachable through this endpoint —
     * only the dedicated changeGroup() flow (which also migrates fees and
     * creates a replacement Active inscription) may set that status.
     */
    public function test_update_statut_rejects_changement_as_a_target(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-NOARCHIVE', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);

        $this->patch(route('backoffice.inscriptions.update-statut', $inscription), [
            'statut' => 'Changement',
        ])->assertStatus(422);

        $this->assertSame('Active', $inscription->fresh()->statut);
    }

    /**
     * Cancelling is NOT reachable through this endpoint any more — it needs a
     * reason, an end date and a fee-cleanup decision, so it goes through
     * inscriptions.cancel(). Leaving a bare `statut=Annulée` path open would
     * be a way to cancel with no reason recorded.
     */
    public function test_update_statut_rejects_annulee_as_a_target(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-CANCEL', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);

        $this->patch(route('backoffice.inscriptions.update-statut', $inscription), [
            'statut' => 'Annulée',
        ])->assertStatus(422);

        $this->assertSame('Active', $inscription->fresh()->statut);
    }

    /**
     * "Réactiver" — Annulée OR Changement -> Active. The invariant is DB-08
     * (never enrolled twice), so since 31/08/2026 the guard checks what is
     * ACTUALLY live rather than the historical statut: a « Changement » row
     * whose successor is still Active stays refused, but once that successor
     * is gone the original row is the only thing left to bring back.
     */
    public function test_reactiver_sets_statut_back_to_active(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-REACTIVATE', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Changement', 'date_inscription' => '2025-09-15',
        ]);

        // A live successor (what changeGroup() creates) still blocks it.
        $successeur = Inscription::create([
            'reference' => 'INS-REACTIVATE-2', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-16',
        ]);

        $this->from(route('backoffice.inscriptions.index'))
            ->patch(route('backoffice.inscriptions.update-statut', $inscription), ['statut' => 'Active'])
            ->assertSessionHasErrors('statut');
        $this->assertSame('Changement', $inscription->fresh()->statut);

        // Once the successor is no longer active, the original comes back.
        $successeur->update(['statut' => 'Annulée']);

        $this->patch(route('backoffice.inscriptions.update-statut', $inscription), [
            'statut' => 'Active',
        ])->assertRedirect(route('backoffice.inscriptions.index'));
        $this->assertSame('Active', $inscription->fresh()->statut);

        // And a cancelled registration reactivates as it always did.
        $successeur->delete();
        $inscription->update(['statut' => 'Annulée']);

        $this->patch(route('backoffice.inscriptions.update-statut', $inscription), [
            'statut' => 'Active',
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertSame('Active', $inscription->fresh()->statut);
    }

    public function test_update_statut_refuses_reactivating_an_already_active_inscription(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-ALREADY-ACT', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);

        $this->patch(route('backoffice.inscriptions.update-statut', $inscription), [
            'statut' => 'Active',
        ])->assertSessionHasErrors('statut');

        $this->assertSame('Active', $inscription->fresh()->statut);
    }

    public function test_update_statut_rejects_an_invalid_statut_value(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-BAD-STATUT', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);

        $this->patch(route('backoffice.inscriptions.update-statut', $inscription), [
            'statut' => 'Bogus',
        ])->assertStatus(422);

        $this->assertSame('Active', $inscription->fresh()->statut);
    }

    public function test_user_without_update_permission_cannot_update_statut(): void
    {
        $this->actingAs($this->userWith('registrations.view'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-STATUT-NOPERM', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Annulée', 'date_inscription' => '2025-09-15',
        ]);

        // Reactivation, i.e. the one transition this endpoint still accepts —
        // so a 403 here proves the permission gate, not the value guard.
        $this->patch(route('backoffice.inscriptions.update-statut', $inscription), [
            'statut' => 'Active',
        ])->assertForbidden();

        $this->assertSame('Annulée', $inscription->fresh()->statut);
    }

    public function test_user_without_update_permission_cannot_update(): void
    {
        $this->actingAs($this->userWith('registrations.view'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-NOEDIT', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);

        $this->put(route('backoffice.inscriptions.update', $inscription), [
            'student_id' => $student->id, 'group_id' => $group->id,
            'statut' => 'Annulée', 'date_inscription' => '2025-09-15',
        ])->assertForbidden();
    }

    // --- delete ----------------------------------------------------------

    public function test_a_registration_without_payments_can_be_deleted(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.delete'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-DEL1', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);

        $this->delete(route('backoffice.inscriptions.destroy', $inscription))
            ->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertNull(Inscription::find($inscription->id));
    }

    public function test_a_registration_with_payments_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.delete'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->makeGroup();
        $inscription = Inscription::create([
            'reference' => 'INS-DEL2', 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15', 'montant_total' => 500,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais', 'montant' => 500,
            'date_echeance' => '2025-10-01', 'statut' => 'Non payé',
        ]);
        $caisse = \App\Models\Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        \App\Models\Encaissement::create([
            'reference' => 'ENC-1', 'student_id' => $student->id, 'inscription_fee_id' => $fee->id,
            'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => 500, 'methode' => 'Espèces', 'date_paiement' => '2025-10-01',
        ]);

        $this->delete(route('backoffice.inscriptions.destroy', $inscription))
            ->assertSessionHasErrors('delete');

        $this->assertNotNull(Inscription::find($inscription->id));
    }

    // --- center scoping --------------------------------------------------

    public function test_center_scoped_user_cannot_view_other_center_registrations(): void
    {
        $centerA = Etablissement::factory()->create();
        $centerB = Etablissement::factory()->create();
        $studentB = Student::factory()->create(['etablissement_id' => $centerB->id]);
        $groupB = Group::factory()->create(['etablissement_id' => $centerB->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscriptionB = Inscription::create([
            'reference' => 'INS-B', 'student_id' => $studentB->id, 'group_id' => $groupB->id,
            'etablissement_id' => $centerB->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15',
        ]);

        $lockedUser = User::factory()->create();
        $lockedUser->givePermissionTo('registrations.view', 'registrations.update');
        $lockedUser->employee()->save(Employee::factory()->make(['etablissement_id' => $centerA->id]));
        $this->actingAs($lockedUser->fresh());

        $this->put(route('backoffice.inscriptions.update', $inscriptionB), [
            'student_id' => $studentB->id, 'group_id' => $groupB->id,
            'statut' => 'Annulée', 'date_inscription' => '2025-09-15',
        ])->assertForbidden();
    }
}
