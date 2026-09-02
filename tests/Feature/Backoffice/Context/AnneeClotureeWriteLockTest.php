<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Context;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Année clôturée » — the absolute write lock (02/09/2026).
 *
 * Reported incident: an employee left the top-bar year switcher on a past
 * année and keyed dépenses there. Nothing stopped them, because a dépense
 * carries no `annee_scolaire_id` at all (it is date-windowed, CLAUDE.md
 * §11) — so the existing context guard's year check could never fire for
 * exactly the record that went wrong.
 *
 * The lock is a BUSINESS INVARIANT, not a permission: a closed year refuses
 * every creation and modification for everyone, super-admin included. The
 * only way through is to un-tick the box in Paramètres → Années scolaires,
 * which is itself super-admin-only and audited.
 */
final class AnneeClotureeWriteLockTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $anneeOuverte;

    private AnneeScolaire $anneeCloturee;

    private Etablissement $centre;

    private TypeDepense $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->anneeOuverte = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true, 'cloturee' => false,
        ]);
        $this->anneeCloturee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true, 'cloturee' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->type = TypeDepense::create(['nom' => 'Fournitures', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centre->id,
        ]);
        $employee->etablissements()->syncWithoutDetaching([$this->centre->id]);

        return $user->fresh();
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centre->id,
        ]);
        $employee->etablissements()->syncWithoutDetaching([$this->centre->id]);

        return $user->fresh();
    }

    /**
     * Point the top-bar switcher at a given année, in $this->centre.
     *
     * CurrentContext is a per-REQUEST singleton that memoizes the resolved
     * year (see its own doc-block). A real browser gets a fresh instance on
     * every request, but inside one test the container survives across
     * $this->post()/$this->put() calls — so a test that switches years
     * mid-scenario must forget the instance too, or the second request
     * silently keeps the first one's year and proves nothing.
     */
    private function workIn(AnneeScolaire $annee): void
    {
        session([
            'context.annee_scolaire_id' => $annee->id,
            'context.etablissement_id' => $this->centre->id,
        ]);

        $this->app->forgetInstance(CurrentContext::class);
    }

    private function postDepense(string $description): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('backoffice.depenses.store'), [
            'type_depense_id' => $this->type->id,
            'montant' => '360',
            'methode_paiement' => 'Espèces',
            'date_depense' => '2026-08-22',
            'description' => $description,
        ]);
    }

    // --- The reported incident ---------------------------------------------

    /**
     * The exact scenario reported: an ordinary dépense (no group, no year
     * FK) keyed while the switcher sits on a closed année.
     */
    public function test_a_depense_cannot_be_created_in_a_closed_year(): void
    {
        $this->actingAs($this->userWith('expenses.view', 'expenses.create'));
        $this->workIn($this->anneeCloturee);

        $this->postDepense('Produits consommables')->assertSessionHasErrors('type_depense_id');

        $this->assertSame(0, Depense::count());
    }

    public function test_the_same_depense_is_accepted_in_an_open_year(): void
    {
        $this->actingAs($this->userWith('expenses.view', 'expenses.create'));
        $this->workIn($this->anneeOuverte);

        $this->postDepense('Produits consommables')->assertSessionHasNoErrors();

        $this->assertSame(1, Depense::count());
    }

    /**
     * The lock is an invariant, not a permission — Gate::before must not
     * open it. A super-admin who genuinely needs to correct the past has to
     * reopen the year first, leaving an audit trail.
     */
    public function test_a_super_admin_is_not_exempt_from_the_lock(): void
    {
        $this->actingAs($this->superAdmin());
        $this->workIn($this->anneeCloturee);

        $this->postDepense('Correction directeur')->assertSessionHasErrors('type_depense_id');

        $this->assertSame(0, Depense::count());
    }

    public function test_an_existing_depense_cannot_be_edited_in_a_closed_year(): void
    {
        $user = $this->userWith('expenses.view', 'expenses.create', 'expenses.update');
        $this->actingAs($user);

        // Created while the year was still open…
        $this->workIn($this->anneeOuverte);
        $this->postDepense('Avant clôture')->assertSessionHasNoErrors();
        $depense = Depense::where('description', 'Avant clôture')->firstOrFail();

        // …then the year is closed and the row must freeze.
        $this->workIn($this->anneeCloturee);

        $this->put(route('backoffice.depenses.update', $depense), [
            'type_depense_id' => $this->type->id,
            'montant' => '360',
            'methode_paiement' => 'Espèces',
            'date_depense' => '2026-08-22',
            'description' => 'Après clôture',
        ])->assertSessionHasErrors();

        $this->assertSame('Avant clôture', $depense->fresh()->description);
    }

    // --- Records that DO carry a year --------------------------------------

    public function test_an_inscription_cannot_be_created_in_a_closed_year(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $this->workIn($this->anneeCloturee);

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->anneeCloturee->id,
        ]);


        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2025-09-15',
            'fee_lines' => [['frais_id' => null, 'nom' => 'Frais', 'montant_initial' => '300']],
        ])->assertSessionHasErrors();

        $this->assertSame(0, Inscription::count());
    }

    public function test_a_group_cannot_be_created_in_a_closed_year(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.create'));
        $this->workIn($this->anneeCloturee);

        $this->post(route('backoffice.groups.store'), [
            'nom' => 'Groupe A1',
            'niveau' => 'A1',
            'date_debut' => '2025-09-15',
        ])->assertSessionHasErrors();

        $this->assertSame(0, Group::count());
    }

    // --- Reading stays open ------------------------------------------------

    /**
     * A closed year is « consultation uniquement », not « inaccessible » —
     * the whole point is that the history stays readable and printable.
     */
    public function test_a_closed_year_is_still_readable(): void
    {
        $this->actingAs($this->userWith('expenses.view'));
        $this->workIn($this->anneeCloturee);

        $this->get(route('backoffice.depenses.index'))->assertOk();
    }

    // --- The switch itself -------------------------------------------------

    public function test_only_a_super_admin_can_close_a_year(): void
    {
        $this->actingAs($this->userWith('academic-years.view', 'academic-years.update'));

        $this->put(route('backoffice.annees-scolaires.update', $this->anneeOuverte), [
            'nom' => $this->anneeOuverte->nom,
            'date_debut' => '2026-09-01',
            'date_fin' => '2027-08-31',
            'cloturee' => true,
        ])->assertSessionHasErrors('cloturee');

        $this->assertFalse($this->anneeOuverte->fresh()->estCloturee());
    }

    public function test_a_super_admin_can_reopen_a_closed_year(): void
    {
        $this->actingAs($this->superAdmin());

        $this->put(route('backoffice.annees-scolaires.update', $this->anneeCloturee), [
            'nom' => $this->anneeCloturee->nom,
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-08-31',
            'cloturee' => false,
        ])->assertSessionHasNoErrors();

        $this->assertFalse($this->anneeCloturee->fresh()->estCloturee());
    }

    /**
     * Closing the default year would drop every new session into a context
     * that accepts no input, with no obvious way out.
     */
    public function test_the_default_year_cannot_be_closed(): void
    {
        $this->actingAs($this->superAdmin());

        $this->put(route('backoffice.annees-scolaires.update', $this->anneeOuverte), [
            'nom' => $this->anneeOuverte->nom,
            'date_debut' => '2026-09-01',
            'date_fin' => '2027-08-31',
            'par_defaut' => true,
            'cloturee' => true,
        ])->assertSessionHasErrors('cloturee');

        $this->assertFalse($this->anneeOuverte->fresh()->estCloturee());
    }

    public function test_a_closed_year_cannot_be_made_the_default(): void
    {
        $this->actingAs($this->superAdmin());

        $this->patch(route('backoffice.annees-scolaires.set-default', $this->anneeCloturee))
            ->assertSessionHasErrors('default');

        $this->assertFalse($this->anneeCloturee->fresh()->par_defaut);
    }

    // --- Shared prop -------------------------------------------------------

    public function test_the_context_prop_reports_the_closed_year_to_the_ui(): void
    {
        $this->actingAs($this->userWith('expenses.view'));
        $this->workIn($this->anneeCloturee);

        $this->get(route('backoffice.depenses.index'))
            ->assertInertia(fn ($page) => $page->where('context.anneeCloturee', true));
    }
}
