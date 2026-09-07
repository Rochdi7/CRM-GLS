<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Access;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Centre reach governs every « Établissement » dropdown — audit 07/09/2026,
 * findings C-2 and C-3.
 *
 * C-2: StudentController, EmployeeController and CaisseController each built
 * their centre list with a bare `Etablissement::query()->get()`, so all seven
 * GLS branches were offered to the 25 staff accounts confined to a single
 * centre — both as a list filter and as a create/edit target. §16 makes
 * « Centres affectés » the ONE authority on reach, so the list must come from
 * the same funnel Settings/Salles/Frais already use.
 *
 * C-3: on the Employees form that unfiltered list was also the source of the
 * « Centres affectés » MultiSelect, which made the silent substitution in
 * resolveCenterIds() reachable: a submission naming only out-of-reach centres
 * was replaced by ALL of the actor's own centres and then written over the
 * employee's real assignment — changing the victim's own centre reach, with a
 * success flash and no error. The request must be REFUSED instead.
 */
final class CentreDropdownScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** A user confined to exactly the given centres via the pivot. */
    private function userConfinedTo(array $centres, string ...$permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $p) {
            $user->givePermissionTo($p);
        }

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $centres[0]->id,
        ]);
        $employee->syncEtablissements(array_map(fn ($c) => $c->id, $centres));

        return $user->fresh();
    }

    public function test_students_page_offers_only_the_centres_the_user_can_reach(): void
    {
        $mine = Etablissement::factory()->create(['nom_centre' => 'GLS Marrakech']);
        Etablissement::factory()->create(['nom_centre' => 'GLS Agadir']);

        $user = $this->userConfinedTo([$mine], 'students.view');

        $this->actingAs($user)
            ->get(route('backoffice.students.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Backoffice/Students/Index', false)
                ->where('etablissements', fn ($centres) => collect($centres)->pluck('id')->all() === [$mine->id])
            );
    }

    public function test_employees_page_offers_only_the_centres_the_user_can_reach(): void
    {
        $mine = Etablissement::factory()->create(['nom_centre' => 'GLS Marrakech']);
        Etablissement::factory()->create(['nom_centre' => 'GLS Agadir']);

        $user = $this->userConfinedTo([$mine], 'employees.view');

        $this->actingAs($user)
            ->get(route('backoffice.employees.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Backoffice/Employees/Index', false)
                ->where('etablissements', fn ($centres) => collect($centres)->pluck('id')->all() === [$mine->id])
            );
    }

    /**
     * A super-admin keeps the full network: Gate::before answers
     * centers.access-all, so hasGlobalAccess() is true and nothing is
     * narrowed. Proving this guards against "fixing" C-2 by over-filtering.
     */
    public function test_a_super_admin_still_sees_every_centre(): void
    {
        Etablissement::factory()->count(3)->create();

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin->fresh())
            ->get(route('backoffice.students.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('etablissements', fn ($centres) => count($centres) === Etablissement::count())
            );
    }

    /**
     * C-3 — the heart of it: on an employee the actor CAN edit (the policy's
     * centre check passes), submitting only out-of-reach centres must fail
     * validation, never silently rewrite the assignment.
     *
     * Note the policy already returns 403 for an employee of a centre the
     * actor cannot reach at all (ResourcePolicy::withinCenter) — that is a
     * separate, stronger guard. The hole this covers is the reachable case:
     * the employee is in the actor's centre, and the FORM names another one.
     */
    public function test_assigning_only_unreachable_centres_is_refused_not_substituted(): void
    {
        $mine = Etablissement::factory()->create();
        $foreign = Etablissement::factory()->create();

        $actor = $this->userConfinedTo([$mine], 'employees.view', 'employees.update');

        // Editable by the actor: primary centre is theirs.
        $employee = Employee::factory()->create(['etablissement_id' => $mine->id]);
        $employee->syncEtablissements([$mine->id]);

        $this->actingAs($actor)->put(
            route('backoffice.employees.update', $employee),
            [
                'nom' => $employee->nom,
                'prenom' => $employee->prenom,
                'sexe' => $employee->sexe,
                'categorie' => $employee->categorie,
                'statut' => $employee->statut,
                'etablissement_ids' => [$foreign->id],
            ],
        )->assertSessionHasErrors('etablissement_ids');

        // Untouched — the old code replaced this with the ACTOR's centres.
        $this->assertSame(
            [$mine->id],
            $employee->fresh()->etablissements()->pluck('etablissements.id')->all(),
        );
    }

    /**
     * The counterpart: on CREATE the long-standing narrowing is KEPT.
     * There is no prior assignment to destroy, and "you hire into your own
     * centre" is the deliberate documented behaviour
     * (EmployeesInertiaCrudTest::test_a_user_cannot_assign_an_employee_to_a
     * _center_it_does_not_hold asserts it). Pinned here so the C-3 refusal is
     * never widened to create() by a later change.
     */
    public function test_creating_narrows_to_the_actors_centres_instead_of_refusing(): void
    {
        $mine = Etablissement::factory()->create();
        $foreign = Etablissement::factory()->create();

        $actor = $this->userConfinedTo([$mine], 'employees.view', 'employees.create');

        $this->actingAs($actor)->post(route('backoffice.employees.store'), [
            'nom' => 'Ziani',
            'prenom' => 'Nadia',
            'sexe' => 'Femme',
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => Employee::STATUT_ACTIF,
            'etablissement_ids' => [$foreign->id],
        ])->assertSessionHasNoErrors();

        $created = Employee::where('nom', 'Ziani')->firstOrFail();

        $this->assertSame(
            [$mine->id],
            $created->etablissements()->pluck('etablissements.id')->all(),
        );
    }

    /**
     * Editing an employee who also belongs to a centre the actor cannot see
     * must PRESERVE that centre, not drop it — a manager of {Marrakech}
     * fixing a phone number must never un-assign the person from Rabat.
     */
    public function test_editing_preserves_centres_outside_the_actors_reach(): void
    {
        $mine = Etablissement::factory()->create();
        $foreign = Etablissement::factory()->create();

        $actor = $this->userConfinedTo([$mine], 'employees.view', 'employees.update');

        $employee = Employee::factory()->create(['etablissement_id' => $mine->id]);
        $employee->syncEtablissements([$mine->id, $foreign->id]);

        $this->actingAs($actor)->put(
            route('backoffice.employees.update', $employee),
            [
                'nom' => 'Bennani',
                'prenom' => $employee->prenom,
                'sexe' => $employee->sexe,
                'categorie' => $employee->categorie,
                'statut' => $employee->statut,
                'etablissement_ids' => [$mine->id],
            ],
        )->assertSessionHasNoErrors();

        $centres = $employee->fresh()->etablissements()->pluck('etablissements.id')->all();

        sort($centres);
        $expected = [$mine->id, $foreign->id];
        sort($expected);

        $this->assertSame($expected, $centres, 'The out-of-reach centre must survive the edit.');
    }
}
