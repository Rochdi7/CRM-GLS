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
use Tests\TestCase;

/**
 * The « Paiement prof » type has its OWN modal (26/08/2026), with a
 * different contract from an ordinary dépense — enforced server-side in
 * Store/UpdateDepenseRequest via the PaiementProfRules trait, so the two can
 * never be mixed by a crafted request:
 *
 *  | field              | Dépense    | Paiement prof |
 *  |--------------------|------------|---------------|
 *  | group_id           | prohibited | required      |
 *  | periode_debut/fin  | prohibited | required      |
 *  | reference_facture  | optional   | prohibited    |
 *  | description        | required   | required      |
 */
final class PaiementProfModalTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private TypeDepense $type;

    private TypeDepense $profType;

    private AnneeScolaire $annee;

    private ?Group $group = null;

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
        // The active-context year — AssertsContextScope refuses a group from
        // any other one, so every date below sits inside this window.
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
    }

    private function actor(): User
    {
        $user = User::factory()->create();
        foreach (['expenses.view', 'expenses.create', 'expenses.update', 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    /** ONE group per test — profPayload() is called repeatedly per case. */
    private function group(): Group
    {
        return $this->group ??= Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function depensePayload(array $overrides = []): array
    {
        return array_merge([
            'type_depense_id' => $this->type->id,
            'montant' => '120',
            'methode_paiement' => 'Espèces',
            'date_depense' => '2025-09-15',
            'description' => 'Fournitures de bureau',
        ], $overrides);
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
            'montant' => '800',
            'methode_paiement' => 'Espèces',
            'date_depense' => '2025-09-30',
            'periode_debut' => '2025-09-01',
            'periode_fin' => '2025-09-30',
            'description' => 'Heures de septembre',
        ], $overrides);
    }

    public function test_description_is_required_on_an_ordinary_depense(): void
    {
        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->depensePayload(['description' => '']))
            ->assertSessionHasErrors('description');
    }

    public function test_description_is_required_on_a_paiement_prof(): void
    {
        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->profPayload(['description' => '']))
            ->assertSessionHasErrors('description');
    }

    public function test_an_ordinary_depense_may_not_carry_a_group(): void
    {
        // The Dépenses modal no longer shows the field at all — a group
        // arriving here means a tampered request.
        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->depensePayload(['group_id' => $this->group()->id]))
            ->assertSessionHasErrors('group_id');
    }

    public function test_an_ordinary_depense_may_not_carry_a_payment_period(): void
    {
        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->depensePayload([
                'periode_debut' => '2025-09-01',
                'periode_fin' => '2025-09-30',
            ]))
            ->assertSessionHasErrors(['periode_debut', 'periode_fin']);
    }

    public function test_a_paiement_prof_requires_a_group(): void
    {
        $payload = $this->profPayload();
        unset($payload['group_id']);

        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $payload)
            ->assertSessionHasErrors('group_id');
    }

    public function test_a_paiement_prof_requires_both_period_dates(): void
    {
        $payload = $this->profPayload();
        unset($payload['periode_debut'], $payload['periode_fin']);

        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $payload)
            ->assertSessionHasErrors(['periode_debut', 'periode_fin']);
    }

    public function test_a_paiement_prof_period_may_not_end_before_it_starts(): void
    {
        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->profPayload([
                'periode_debut' => '2025-09-30',
                'periode_fin' => '2025-09-01',
            ]))
            ->assertSessionHasErrors('periode_fin');
    }

    public function test_a_paiement_prof_may_not_carry_a_supplier_invoice_reference(): void
    {
        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->profPayload(['reference_facture' => 'F-2025-001']))
            ->assertSessionHasErrors('reference_facture');
    }

    public function test_a_valid_paiement_prof_stores_its_group_and_period(): void
    {
        $group = $this->group();

        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->profPayload(['group_id' => $group->id]))
            ->assertSessionDoesntHaveErrors();

        $depense = Depense::where('description', 'Heures de septembre')->firstOrFail();

        $this->assertSame($this->profType->id, $depense->type_depense_id);
        $this->assertSame($group->id, $depense->group_id);
        $this->assertSame('2025-09-01', $depense->periode_debut->toDateString());
        $this->assertSame('2025-09-30', $depense->periode_fin->toDateString());
    }

    /**
     * « Enregistrer la dépense » depuis le calcul embarqué (30/10/2026) : la
     * redirection gardait les paramètres `pp*` du referer et rouvrait le
     * calcul qu'on venait d'enregistrer. Elle mène désormais à la LISTE de
     * l'onglet Paiements prof, filtres de liste conservés.
     */
    public function test_saving_from_the_calcul_lands_on_the_paiements_prof_list(): void
    {
        $group = $this->group();
        $referer = route('backoffice.depenses.index', [
            'tab' => 'paiements-prof',
            'search' => 'moussa',
            'ppGroup' => $group->id,
            'ppDebut' => '2025-09-01',
            'ppFin' => '2025-09-30',
        ]);

        $response = $this->actingAs($this->actor())
            ->withHeader('referer', $referer)
            ->post(route('backoffice.depenses.store'), $this->profPayload(['group_id' => $group->id]))
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();

        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame('paiements-prof', $query['tab'] ?? null);
        $this->assertSame('moussa', $query['search'] ?? null);
        $this->assertArrayNotHasKey('ppGroup', $query);
        $this->assertArrayNotHasKey('ppDebut', $query);
        $this->assertArrayNotHasKey('ppFin', $query);
    }

    public function test_a_valid_ordinary_depense_still_stores_without_a_group(): void
    {
        $this->actingAs($this->actor())
            ->post(route('backoffice.depenses.store'), $this->depensePayload())
            ->assertSessionDoesntHaveErrors();

        $depense = Depense::where('description', 'Fournitures de bureau')->firstOrFail();

        $this->assertNull($depense->group_id);
        $this->assertNull($depense->periode_debut);
        $this->assertNull($depense->periode_fin);
    }

    public function test_the_same_contract_holds_on_update(): void
    {
        $user = $this->actor();
        $group = $this->group();

        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload(['group_id' => $group->id]))
            ->assertSessionDoesntHaveErrors();

        $depense = Depense::where('description', 'Heures de septembre')->firstOrFail();

        // Same type, but the period stripped — refused just like on create.
        $this->actingAs($user)
            ->put(route('backoffice.depenses.update', $depense), [
                'type_depense_id' => $this->profType->id,
                'group_id' => $group->id,
                'methode_paiement' => 'Espèces',
                'date_depense' => '2025-09-30',
                'description' => 'Heures de septembre',
            ])
            ->assertSessionHasErrors(['periode_debut', 'periode_fin']);
    }
    /** Un groupe ne se paie qu'une fois par mois imputé (21/10/2026). */
    public function test_a_second_payment_on_the_same_month_is_refused(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload([
                'periode_debut' => '2025-09-24', 'periode_fin' => '2025-10-24',
            ]))
            ->assertSessionDoesntHaveErrors();

        // 01/09 → 15/09 : ne chevauche pas, mais c'est le même mois (septembre).
        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload([
                'periode_debut' => '2025-09-01', 'periode_fin' => '2025-09-15',
                'description' => 'Doublon',
            ]))
            ->assertSessionHasErrors('periode_debut');

        // 01/10 → 31/10 : chevauche le 24/09 → 24/10.
        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload([
                'periode_debut' => '2025-10-01', 'periode_fin' => '2025-10-31',
                'description' => 'Doublon',
            ]))
            ->assertSessionHasErrors('periode_debut');

        $this->assertSame(0, Depense::where('description', 'Doublon')->count());

        // 25/10 → 24/11 : mois suivant, sans chevauchement — accepté.
        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload([
                'periode_debut' => '2025-10-25', 'periode_fin' => '2025-11-24',
                'description' => 'Octobre',
            ]))
            ->assertSessionDoesntHaveErrors();
    }

    public function test_a_refused_payment_frees_its_month(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload())
            ->assertSessionDoesntHaveErrors();

        Depense::where('description', 'Heures de septembre')
            ->update(['statut' => Depense::STATUT_REFUSEE]);

        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload(['description' => 'Ressaisie']))
            ->assertSessionDoesntHaveErrors();
    }

    public function test_editing_a_payment_does_not_collide_with_itself(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->profPayload())
            ->assertSessionDoesntHaveErrors();

        $depense = Depense::where('description', 'Heures de septembre')->firstOrFail();

        $this->actingAs($user)
            ->put(route('backoffice.depenses.update', $depense), $this->profPayload([
                'description' => 'Heures de septembre (corrigé)',
            ]))
            ->assertSessionDoesntHaveErrors();
    }
}
