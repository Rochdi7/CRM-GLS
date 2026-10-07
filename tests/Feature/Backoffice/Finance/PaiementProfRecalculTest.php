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
 * « Modifier le calcul » d'un paiement prof (07/10/2026).
 *
 * Un paiement prof EN ATTENTE n'a débité aucune caisse : son calcul peut
 * être refait et reporté sur la MÊME dépense — montant compris — par qui
 * peut la modifier ET par l'employé qui l'a saisie (DepensePolicy@recalculer).
 * Une fois décidé, plus personne ne le recalcule, super-admin compris.
 */
final class PaiementProfRecalculTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private TypeDepense $profType;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
        $this->profType = TypeDepense::create([
            'nom' => TypeDepense::SYSTEM_PAIEMENT_PROF, 'is_system' => true, 'statut' => TypeDepense::STATUT_ACTIF,
        ]);
        $annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $annee->id,
        ]);
    }

    /** @param  list<string>  $permissions */
    private function actor(array $permissions): User
    {
        $user = User::factory()->create();
        foreach (['centers.access-all', ...$permissions] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function frontOffice(): User
    {
        return $this->actor(['expenses.view', 'expenses.create', 'prof-payments.calculate']);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type_depense_id' => $this->profType->id,
            'group_id' => $this->group->id,
            'montant' => '2500',
            'methode_paiement' => 'Espèces',
            'date_depense' => '2025-09-30',
            'periode_debut' => '2025-09-01',
            'periode_fin' => '2025-09-30',
            'description' => 'Heures de septembre',
        ], $overrides);
    }

    private function createdBy(User $user): Depense
    {
        $this->actingAs($user)
            ->post(route('backoffice.depenses.store'), $this->payload())
            ->assertSessionDoesntHaveErrors();

        $depense = Depense::query()->latest('id')->firstOrFail();
        $this->assertTrue($depense->isEnAttente());

        return $depense;
    }

    private function recalculer(User $user, Depense $depense, string $montant = '3100')
    {
        return $this->actingAs($user)->post(route('backoffice.depenses.update', $depense), $this->payload([
            '_method' => 'put',
            'montant' => $montant,
            'periode_fin' => '2025-10-05',
            'description' => 'Recalculé',
        ]));
    }

    public function test_the_employee_who_keyed_a_pending_payment_can_recalculate_it(): void
    {
        $user = $this->frontOffice();
        $depense = $this->createdBy($user);

        $this->recalculer($user, $depense)->assertSessionDoesntHaveErrors()->assertRedirect();

        $depense->refresh();
        $this->assertSame('3100.00', $depense->montant);
        $this->assertSame('2025-10-05', $depense->periode_fin->toDateString());
        $this->assertSame('Recalculé', $depense->description);
        $this->assertTrue($depense->isEnAttente());
    }

    public function test_the_show_page_offers_the_recalculation_link_for_a_pending_payment(): void
    {
        $user = $this->frontOffice();
        $depense = $this->createdBy($user);

        $this->actingAs($user)->get(route('backoffice.depenses.show', $depense))
            ->assertInertia(fn (Assert $page) => $page
                ->where('recalculUrl', fn (?string $url): bool => $url !== null
                    && str_contains($url, 'ppEdit='.$depense->id)
                    && str_contains($url, 'ppGroup='.$this->group->id)
                    && str_contains($url, 'tab=paiements-prof')));

        $this->actingAs($user)
            ->get(route('backoffice.depenses.index', ['tab' => 'paiements-prof', 'ppEdit' => $depense->id]))
            ->assertInertia(fn (Assert $page) => $page->where('recalculDepense.id', $depense->id));
    }

    public function test_another_front_office_employee_cannot_recalculate_it(): void
    {
        $depense = $this->createdBy($this->frontOffice());
        $other = $this->frontOffice();

        $this->recalculer($other, $depense)->assertForbidden();
        $this->assertSame('2500.00', $depense->fresh()->montant);

        $this->actingAs($other)->get(route('backoffice.depenses.index', ['ppEdit' => $depense->id]))
            ->assertInertia(fn (Assert $page) => $page->where('recalculDepense', null));
    }

    public function test_recalculating_requires_the_calculation_permission(): void
    {
        $user = $this->actor(['expenses.view', 'expenses.create']);
        $depense = $this->createdBy($user);

        $this->recalculer($user, $depense)->assertForbidden();
        $this->assertSame('2500.00', $depense->fresh()->montant);
    }

    public function test_a_decided_payment_is_no_longer_recalculable_even_for_a_super_admin(): void
    {
        $depense = $this->createdBy($this->frontOffice());
        $depense->forceFill(['statut' => Depense::STATUT_APPROUVEE])->saveQuietly();

        $manager = $this->actor(['expenses.view', 'expenses.create', 'expenses.update', 'prof-payments.calculate']);
        $this->recalculer($manager, $depense)->assertSessionHasErrors('montant');
        $this->assertSame('2500.00', $depense->fresh()->montant);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $superAdmin->id, 'etablissement_id' => $this->centre->id]);

        $this->assertFalse($superAdmin->fresh()->can('recalculer', $depense->fresh()));
        $this->actingAs($superAdmin->fresh())->get(route('backoffice.depenses.show', $depense))
            ->assertInertia(fn (Assert $page) => $page->where('recalculUrl', null));
    }

    public function test_a_super_admin_can_recalculate_a_pending_payment(): void
    {
        $depense = $this->createdBy($this->frontOffice());

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $superAdmin->id, 'etablissement_id' => $this->centre->id]);

        $this->recalculer($superAdmin->fresh(), $depense)->assertSessionDoesntHaveErrors();
        $this->assertSame('3100.00', $depense->fresh()->montant);
    }
}
