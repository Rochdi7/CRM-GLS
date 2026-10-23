<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Cotisation CNSS retenue sur un « Paiement prof » (06/10/2026).
 *
 * Une case à cocher — au calcul ET dans le modal — retient 1 700 MAD fixes
 * + le SALAIRE de l'enseignant payé (fiche employé, 23/10/2026) sur le net
 * versé. Sans salaire saisi, seuls les 1 700 MAD. La dépense STOCKE ce qui a
 * été retenu (`depenses.cnss_montant`) ; `montant` reste le net, seule
 * valeur que la caisse débite. Changer la retenue après coup change le net
 * versé : c'est une correction de montant, donc le même droit
 * (`expenses.update-amount`), et un refus plutôt qu'un drapeau posé en
 * silence sur un montant inchangé.
 */
final class PaiementProfCnssTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private TypeDepense $type;

    private TypeDepense $profType;

    private AnneeScolaire $annee;

    private ?Group $group = null;

    private ?Employee $prof = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
        $this->type = TypeDepense::create([
            'nom' => 'Fournitures', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF,
        ]);
        $this->profType = TypeDepense::create([
            'nom' => TypeDepense::SYSTEM_PAIEMENT_PROF, 'is_system' => true, 'statut' => TypeDepense::STATUT_ACTIF,
        ]);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
    }

    /** @param  list<string>  $extra  permissions beyond the front-office set */
    private function actor(array $extra = []): User
    {
        $user = User::factory()->create();
        foreach (['expenses.view', 'expenses.create', 'expenses.update', 'centers.access-all', ...$extra] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    /** The group's teacher — salary 1 700 MAD unless a test says otherwise. */
    private function prof(): Employee
    {
        return $this->prof ??= Employee::factory()->create([
            'etablissement_id' => $this->centre->id,
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'salaire' => '1700.00',
        ]);
    }

    private function group(): Group
    {
        return $this->group ??= Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'enseignant_id' => $this->prof()->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function profPayload(array $overrides = []): array
    {
        return array_merge([
            'type_depense_id' => $this->profType->id,
            'group_id' => $this->group()->id,
            'montant' => '2500',
            'methode_paiement' => 'Espèces',
            'date_depense' => '2025-09-30',
            'periode_debut' => '2025-09-01',
            'periode_fin' => '2025-09-30',
            'description' => 'Heures de septembre',
        ], $overrides);
    }

    public function test_a_checked_cnss_box_is_stored_as_the_amount_withheld(): void
    {
        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->profPayload(['cnss' => '1']))
            ->assertSessionDoesntHaveErrors();

        $depense = Depense::where('description', 'Heures de septembre')->firstOrFail();

        // The teacher's own salary is what is withheld.
        $this->assertSame('3400.00', $depense->cnss_montant);
        // `montant` is what the client sent — the NET — never re-deduced here.
        $this->assertSame('2500.00', $depense->montant);
        $this->assertTrue($depense->cnssDeduite());
    }

    public function test_an_unchecked_or_absent_cnss_box_stores_no_deduction(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload(['cnss' => '0', 'description' => 'Décoché']))
            ->assertSessionDoesntHaveErrors();
        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload(['description' => 'Absent', 'periode_debut' => '2025-10-01', 'periode_fin' => '2025-10-31', 'date_depense' => '2025-10-31']))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull(Depense::where('description', 'Décoché')->firstOrFail()->cnss_montant);
        $this->assertNull(Depense::where('description', 'Absent')->firstOrFail()->cnss_montant);
    }

    public function test_an_ordinary_depense_refuses_the_cnss_flag(): void
    {
        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), [
                'type_depense_id' => $this->type->id,
                'montant' => '120',
                'methode_paiement' => 'Espèces',
                'date_depense' => '2025-09-15',
                'description' => 'Fournitures de bureau',
                'cnss' => '1',
            ])
            ->assertSessionHasErrors('cnss');
    }

    public function test_the_withholding_is_the_paid_teachers_own_salary(): void
    {
        $this->prof()->forceFill(['salaire' => '2350.50'])->save();

        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->profPayload(['cnss' => '1']))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('4050.50', Depense::where('description', 'Heures de septembre')->firstOrFail()->cnss_montant);
    }

    public function test_a_teacher_with_no_salary_has_only_the_fixed_1700_withheld(): void
    {
        $this->prof()->forceFill(['salaire' => null])->save();

        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->profPayload(['cnss' => '1']))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('1700.00', Depense::query()->firstOrFail()->cnss_montant);
    }

    public function test_the_amount_withheld_reaches_the_list_and_the_salary_reaches_the_page(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload(['cnss' => '1']))
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($user)
            ->get(route('backoffice.depenses.index', ['tab' => 'paiements-prof']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Depenses/Index')
                // The group option carries 1 700 fixed + its teacher's salary (1 700).
                ->where('groups.0.cnssMontant', fn ($v): bool => (float) $v === 3400.0)
                ->where('paiementsProf.data.0.cnssMontant', '3400.00')
                ->where('paiementsProf.data.0.montant', '2500.00'));
    }

    public function test_an_unchanged_cnss_flag_can_be_re_saved_by_anyone_who_may_edit(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload(['cnss' => '1']))
            ->assertSessionDoesntHaveErrors();
        $depense = Depense::where('description', 'Heures de septembre')->firstOrFail();

        // The edit modal echoes the stored state (checked) and amount.
        $this->actingAs($user)
            ->put(route('backoffice.depenses.update', $depense), $this->profPayload([
                'cnss' => '1', 'note' => 'Relu',
            ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('3400.00', $depense->fresh()->cnss_montant);
        $this->assertSame('Relu', $depense->fresh()->note);
    }

    public function test_a_pending_payment_takes_the_current_withholding_not_the_stored_one(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload(['cnss' => '1']))
            ->assertSessionDoesntHaveErrors();
        $depense = Depense::where('description', 'Heures de septembre')->firstOrFail();
        $this->assertTrue($depense->isEnAttente());

        // Recorded under the old fixed rule, then the salary was entered.
        $depense->forceFill(['cnss_montant' => '1700.00'])->save();

        $this->actingAs($user)
            ->put(route('backoffice.depenses.update', $depense), $this->profPayload(['cnss' => '1']))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('3400.00', $depense->fresh()->cnss_montant);
    }

    public function test_changing_the_cnss_flag_after_the_fact_needs_the_amount_permission(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload())
            ->assertSessionDoesntHaveErrors();
        $depense = Depense::where('description', 'Heures de septembre')->firstOrFail();

        // Ticking the box on a recorded payment with the SAME amount would
        // state « 1 700 withheld » while nothing was — refused, not ignored.
        $this->actingAs($user)
            ->put(route('backoffice.depenses.update', $depense), $this->profPayload(['cnss' => '1']))
            ->assertSessionHasErrors('cnss');

        $this->assertNull($depense->fresh()->cnss_montant);
    }

    public function test_a_holder_of_update_amount_may_withhold_or_give_back_the_cnss(): void
    {
        $user = $this->actor(['expenses.update-amount']);

        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload())
            ->assertSessionDoesntHaveErrors();
        $depense = Depense::where('description', 'Heures de septembre')->firstOrFail();

        // Withhold: the net drops by the contribution (the modal does that
        // arithmetic), and the row records what was withheld.
        $this->actingAs($user)
            ->put(route('backoffice.depenses.update', $depense), $this->profPayload(['cnss' => '1', 'montant' => '800']))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('3400.00', $depense->fresh()->cnss_montant);
        $this->assertSame('800.00', $depense->fresh()->montant);

        // Give back: the flag clears and the net is restored.
        $this->actingAs($user)
            ->put(route('backoffice.depenses.update', $depense), $this->profPayload(['cnss' => '0', 'montant' => '2500']))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull($depense->fresh()->cnss_montant);
        $this->assertSame('2500.00', $depense->fresh()->montant);
    }

    public function test_switching_a_paiement_prof_to_an_ordinary_type_drops_the_deduction(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload(['cnss' => '1']))
            ->assertSessionDoesntHaveErrors();
        $depense = Depense::where('description', 'Heures de septembre')->firstOrFail();

        $this->actingAs($user)
            ->put(route('backoffice.depenses.update', $depense), [
                'type_depense_id' => $this->type->id,
                'montant' => '2500',
                'methode_paiement' => 'Espèces',
                'date_depense' => '2025-09-30',
                'description' => 'Heures de septembre',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertNull($depense->fresh()->cnss_montant);
        $this->assertNull($depense->fresh()->group_id);
    }
}
