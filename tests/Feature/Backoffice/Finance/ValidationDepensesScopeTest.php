<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
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
 * « Validation des dépenses » must list EVERY pending dépense — both the
 * ordinary ones and the « Paiement prof » ones.
 *
 * The Dépenses / Paiements prof tab split (GetDepensesList::SCOPE_*) is a
 * READABILITY choice for the two browsing tabs. Validation is not browsing:
 * it is the only screen where a pending dépense is approved or refused, so a
 * row it does not list can never be decided and its money stays held in the
 * till indefinitely.
 *
 * Reported 04/09/2026 on production: the tab reused the HORS_PAIEMENT_PROF
 * list, so 10 « Paiement prof » rows were unreachable — the badge announced
 * 5 pending while the database held 15, and the « En attente » figure showed
 * 3 585.12 MAD instead of 35 610.62 MAD.
 */
final class ValidationDepensesScopeTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private TypeDepense $type;

    private TypeDepense $profType;

    private AnneeScolaire $annee;

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

    /** A super-admin — `expenses.approve` is in no role preset by design. */
    private function approver(): User
    {
        $user = User::factory()->create();
        $user->assignRole(\App\Models\Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    /**
     * The employee's OWN physical till — EmployeeObserver already provisions
     * one per employee (caisses_une_caissiere_par_employe forbids a second),
     * so it is fetched, never re-created.
     */
    private function till(Employee $employee): Caisse
    {
        $caisse = $employee->till()->first();
        $caisse->update(['solde' => '10000.00']);

        return $caisse->fresh();
    }

    private function pending(TypeDepense $type, string $montant, ?Group $group = null): Depense
    {
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        return Depense::query()->create([
            'reference' => 'DEP-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'type_depense_id' => $type->id,
            'caisse_id' => $this->till($agent)->id,
            'montant' => $montant,
            'statut' => Depense::STATUT_EN_ATTENTE,
            'date_depense' => '2025-09-30',
            'group_id' => $group?->id,
            'periode_debut' => $group !== null ? '2025-09-01' : null,
            'periode_fin' => $group !== null ? '2025-09-30' : null,
            'description' => 'Ligne de test',
            'agent_id' => $agent->id,
        ]);
    }

    private function group(): Group
    {
        return Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    public function test_validation_tab_lists_paiement_prof_rows(): void
    {
        $ordinaire = $this->pending($this->type, '120.00');
        $prof = $this->pending($this->profType, '7650.00', $this->group());

        $this->actingAs($this->approver())
            ->get(route('backoffice.depenses.index'))
            ->assertInertia(function ($page) use ($ordinaire, $prof): void {
                $refs = collect($page->toArray()['props']['validationDepenses']['data'])
                    ->pluck('reference')
                    ->all();

                // The whole point: BOTH kinds reach the screen that decides them.
                $this->assertContains($ordinaire->reference, $refs);
                $this->assertContains($prof->reference, $refs);
            });
    }

    public function test_validation_totals_count_both_kinds(): void
    {
        $this->pending($this->type, '120.00');
        $this->pending($this->profType, '7650.00', $this->group());

        $this->actingAs($this->approver())
            ->get(route('backoffice.depenses.index'))
            ->assertInertia(function ($page): void {
                $props = $page->toArray()['props'];

                // The badge and the « En attente » figure must report the real
                // amount held across the tills, not the Dépenses tab's share.
                $this->assertSame(2, $props['validationEnAttenteCount']);
                $this->assertSame('7770.00', $props['validationMontantEnAttente']);
            });
    }

    public function test_browsing_tabs_keep_their_split(): void
    {
        $ordinaire = $this->pending($this->type, '120.00');
        $prof = $this->pending($this->profType, '7650.00', $this->group());

        $this->actingAs($this->approver())
            ->get(route('backoffice.depenses.index'))
            ->assertInertia(function ($page) use ($ordinaire, $prof): void {
                $props = $page->toArray()['props'];
                $depenses = collect($props['depenses']['data'])->pluck('reference')->all();
                $profs = collect($props['paiementsProf']['data'])->pluck('reference')->all();

                // Unchanged: the readability split still holds for browsing.
                $this->assertContains($ordinaire->reference, $depenses);
                $this->assertNotContains($prof->reference, $depenses);
                $this->assertContains($prof->reference, $profs);
                $this->assertNotContains($ordinaire->reference, $profs);
            });
    }

    public function test_a_paiement_prof_can_actually_be_approved(): void
    {
        $prof = $this->pending($this->profType, '7650.00', $this->group());
        $caisse = $prof->caisse;
        $soldeAvant = (float) $caisse->solde;

        $this->actingAs($this->approver())
            ->put(route('backoffice.depenses.approve', $prof))
            ->assertSessionHasNoErrors();

        $this->assertSame(Depense::STATUT_APPROUVEE, $prof->fresh()->statut);
        // Approval is the single moment the till moves for this expense.
        $this->assertEqualsWithDelta($soldeAvant - 7650.00, (float) $caisse->fresh()->solde, 0.001);
    }
}
