<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\People;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Covers the new Inertia/React Employees endpoints
 * (Backoffice\Employees\EmployeeController) built in parallel with the
 * unchanged Livewire EmployeesIndex fallback — see EmployeesCrudTest for the
 * Livewire-side coverage of the same business rules.
 */
final class EmployeesInertiaCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * An employee must now be assigned to at least one center
     * (employee_etablissement pivot), so every valid store/update payload
     * carries `etablissement_ids`.
     *
     * @return list<int>
     */
    private function someCenterIds(): array
    {
        return [Etablissement::factory()->create()->id];
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    public function test_index_requires_employees_view_and_renders_the_react_page(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.employees.index'))
            ->assertForbidden();

        $this->actingAs($this->userWith('employees.view'))
            ->get(route('backoffice.employees.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // shouldExist: false — the React page component itself is
                // built by the parallel frontend agent (Phase 7 split); this
                // test only verifies the backend's Inertia contract (props).
                ->component('Backoffice/Employees/Index', false)
                ->has('employees')
                ->has('categories')
                ->has('statuts')
                ->has('sexes')
                ->has('etablissements')
                ->where('filters.perPage', 10)
            );
    }

    public function test_creating_an_employee_generates_a_login_and_flashes_credentials(): void
    {
        $this->actingAs($this->userWith('employees.view', 'employees.create'));

        $response = $this->post(route('backoffice.employees.store'), [
            'nom' => 'Bennani',
            'prenom' => 'Salma',
            'sexe' => 'Femme',
            'categorie' => Employee::CATEGORIE_COMMERCIAL,
            'statut' => Employee::STATUT_ACTIF,
            'etablissement_ids' => $this->someCenterIds(),
        ]);

        $response->assertRedirect(route('backoffice.employees.index'));
        $response->assertSessionHas('new_employee_username');
        $response->assertSessionHas('new_employee_password');

        $employee = Employee::where('nom', 'Bennani')->first();
        $this->assertNotNull($employee);
        $this->assertSame(Employee::CATEGORIE_COMMERCIAL, $employee->categorie);
        $this->assertNotNull($employee->user_id);
    }

    public function test_one_time_credentials_are_shown_at_most_once(): void
    {
        $this->actingAs($this->userWith('employees.view', 'employees.create'));

        $this->post(route('backoffice.employees.store'), [
            'nom' => 'Alaoui',
            'prenom' => 'Yassine',
            'sexe' => 'Homme',
            'categorie' => Employee::CATEGORIE_COMMERCIAL,
            'statut' => Employee::STATUT_ACTIF,
            'etablissement_ids' => $this->someCenterIds(),
        ])->assertRedirect(route('backoffice.employees.index'));

        // The very next request (the redirect's target, exactly like the
        // React modal's own follow-up render) must still see the one-time
        // credentials — this is the one render they're meant for.
        $this->get(route('backoffice.employees.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Employees/Index', false)
                ->where('flash.newEmployeeCredentials.username', fn ($value) => filled($value))
                ->where('flash.newEmployeeCredentials.password', fn ($value) => filled($value))
            );

        // A SECOND, later request in the same session must NOT see them
        // again — regression guard for the flash-lifecycle bug where
        // session()->get() (instead of ->pull()) let a one-time secret
        // resurface on a subsequent unrelated Inertia visit.
        $this->get(route('backoffice.employees.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Employees/Index', false)
                ->where('flash.newEmployeeCredentials', null)
            );
    }

    public function test_store_validates_the_full_rule_set(): void
    {
        $this->actingAs($this->userWith('employees.view', 'employees.create'));

        // sexe missing entirely.
        $this->post(route('backoffice.employees.store'), [
            'nom' => 'Idrissi',
            'prenom' => 'Youssef',
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => Employee::STATUT_ACTIF,
        ])->assertSessionHasErrors('sexe');

        // Invalid categorie.
        $this->post(route('backoffice.employees.store'), [
            'nom' => 'X',
            'prenom' => 'Y',
            'sexe' => 'Homme',
            'categorie' => 'Hacker',
            'statut' => Employee::STATUT_ACTIF,
        ])->assertSessionHasErrors('categorie');

        // date_naissance must be before today.
        $this->post(route('backoffice.employees.store'), [
            'nom' => 'X',
            'prenom' => 'Y',
            'sexe' => 'Homme',
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => Employee::STATUT_ACTIF,
            'date_naissance' => now()->addDay()->toDateString(),
        ])->assertSessionHasErrors('date_naissance');
    }

    public function test_hr_fields_are_saved(): void
    {
        $this->actingAs($this->userWith('employees.view', 'employees.create'));

        $this->post(route('backoffice.employees.store'), [
            'nom' => 'Idrissi',
            'prenom' => 'Youssef',
            'sexe' => 'Homme',
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => Employee::STATUT_ACTIF,
            'date_naissance' => '1990-05-10',
            'date_embauche' => '2024-01-15',
            'salaire' => '8500.50',
            'email' => 'youssef@gls.test',
            'note' => 'Bilingue FR/EN',
            'adresse' => '12 rue Al Massira',
            'phone_pays' => 'MA',
            'telephone' => '661954125',
            'etablissement_ids' => $this->someCenterIds(),
        ])->assertSessionDoesntHaveErrors();

        $emp = Employee::where('nom', 'Idrissi')->firstOrFail();
        $this->assertSame('1990-05-10', $emp->date_naissance->toDateString());
        $this->assertSame('2024-01-15', $emp->date_embauche->toDateString());
        $this->assertSame('8500.50', (string) $emp->salaire);
        $this->assertSame('Bilingue FR/EN', $emp->note);
        $this->assertSame('12 rue Al Massira', $emp->adresse);
        $this->assertSame('+212661954125', $emp->telephone);
    }

    public function test_a_photo_is_stored_in_the_media_collection(): void
    {
        Storage::fake('media');
        $this->actingAs($this->userWith('employees.view', 'employees.create'));

        $this->post(route('backoffice.employees.store'), [
            'nom' => 'Fassi',
            'prenom' => 'Karim',
            'sexe' => 'Homme',
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => Employee::STATUT_ACTIF,
            'photo' => UploadedFile::fake()->image('staff.jpg'),
            'etablissement_ids' => $this->someCenterIds(),
        ])->assertSessionDoesntHaveErrors();

        $employee = Employee::where('nom', 'Fassi')->firstOrFail();
        $this->assertCount(1, $employee->getMedia('photo'));
    }

    /**
     * Ports EmployeeProfileFieldsTest::test_uploading_a_new_photo_replaces_
     * the_previous_one (Livewire) — "photo" is a single-file media
     * collection: a second upload on edit replaces the first, never adds a
     * second image.
     */
    public function test_uploading_a_new_photo_replaces_the_previous_one(): void
    {
        Storage::fake('media');
        $this->actingAs($this->userWith('employees.view', 'employees.update'));
        $employee = Employee::factory()->create();
        $employee->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('photo');

        $this->put(route('backoffice.employees.update', $employee), [
            'nom' => $employee->nom,
            'prenom' => $employee->prenom,
            'sexe' => $employee->sexe,
            'categorie' => $employee->categorie,
            'statut' => $employee->statut,
            'photo' => UploadedFile::fake()->image('new.jpg'),
            'etablissement_ids' => $this->someCenterIds(),
        ])->assertSessionDoesntHaveErrors();

        $media = $employee->fresh()->getMedia('photo');
        $this->assertCount(1, $media);
    }

    /**
     * Ports EmployeeProfileFieldsTest::test_an_oversized_photo_is_rejected
     * (Livewire) — the server-side `max:2048` KB rule is the one line of
     * defense that still applies over a real HTTP upload (Livewire's own
     * client-side preview_mimes rejection, tested by the Livewire-only
     * test_a_non_image_upload_is_refused_before_saving, is a Livewire
     * temporary-upload-layer concept with no Inertia equivalent — not
     * ported).
     */
    public function test_an_oversized_photo_is_rejected(): void
    {
        Storage::fake('media');
        $this->actingAs($this->userWith('employees.view', 'employees.create'));

        $this->post(route('backoffice.employees.store'), [
            'nom' => 'Idrissi',
            'prenom' => 'Omar',
            'sexe' => 'Homme',
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => Employee::STATUT_ACTIF,
            'photo' => UploadedFile::fake()->image('huge.jpg')->size(3000),
        ])->assertSessionHasErrors('photo');
    }

    /**
     * Ports EmployeeProfileFieldsTest::test_employees_can_be_searched_by_
     * address (Livewire).
     */
    public function test_employees_can_be_searched_by_address(): void
    {
        $this->actingAs($this->userWith('employees.view', 'centers.access-all'));
        Employee::factory()->create(['nom' => 'Bennani', 'adresse' => 'Avenue Hassan II']);
        Employee::factory()->create(['nom' => 'Cherkaoui', 'adresse' => 'Rue de Safi']);

        $this->get(route('backoffice.employees.index', ['search' => 'Hassan II']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('employees.data.0.nom', 'Bennani')
                ->count('employees.data', 1)
            );
    }

    public function test_an_employee_can_be_updated(): void
    {
        $this->actingAs($this->userWith('employees.view', 'employees.update'));
        $emp = Employee::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->put(route('backoffice.employees.update', $emp), [
            'nom' => $emp->nom,
            'prenom' => $emp->prenom,
            'sexe' => $emp->sexe,
            'categorie' => Employee::CATEGORIE_COMPTABLE,
            'statut' => Employee::STATUT_INACTIF,
            'etablissement_ids' => $this->someCenterIds(),
        ])->assertRedirect(route('backoffice.employees.index'));

        $emp->refresh();
        $this->assertSame(Employee::CATEGORIE_COMPTABLE, $emp->categorie);
        $this->assertSame(Employee::STATUT_INACTIF, $emp->statut);
    }

    public function test_employee_with_activity_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWith('employees.view', 'employees.delete'));

        $centre = Etablissement::factory()->create();
        $teacher = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
        Group::factory()->create(['enseignant_id' => $teacher->id, 'etablissement_id' => $centre->id]);

        $this->delete(route('backoffice.employees.destroy', $teacher))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('employees', ['id' => $teacher->id]);
    }

    public function test_an_employee_without_activity_can_be_deleted(): void
    {
        $this->actingAs($this->userWith('employees.view', 'employees.delete'));
        $emp = Employee::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->delete(route('backoffice.employees.destroy', $emp))
            ->assertRedirect(route('backoffice.employees.index'));

        $this->assertDatabaseMissing('employees', ['id' => $emp->id]);
    }

    public function test_user_without_create_permission_cannot_store(): void
    {
        $this->actingAs($this->userWith('employees.view'));

        $this->post(route('backoffice.employees.store'), [
            'nom' => 'X', 'prenom' => 'Y', 'sexe' => 'Homme',
            'categorie' => Employee::CATEGORIE_ENSEIGNANT, 'statut' => Employee::STATUT_ACTIF,
        ])->assertForbidden();
    }

    public function test_update_and_delete_are_center_scoped_for_non_global_users(): void
    {
        $centerA = Etablissement::factory()->create();
        $centerB = Etablissement::factory()->create();

        $employeeInB = Employee::factory()->create([
            'etablissement_id' => $centerB->id,
            'user_id' => User::factory()->create()->id,
        ]);

        // A user confined to center A (no centers.access-all) tries to touch
        // an employee scoped to center B — must be refused (this is the
        // deliberate behavior tightening vs. the Livewire component, which
        // never checked this).
        $lockedAdmin = $this->userWith('employees.view', 'employees.update', 'employees.delete');
        $lockedAdmin->employee()->save(Employee::factory()->make(['etablissement_id' => $centerA->id]));

        $this->actingAs($lockedAdmin);

        $this->put(route('backoffice.employees.update', $employeeInB), [
            'nom' => $employeeInB->nom,
            'prenom' => $employeeInB->prenom,
            'sexe' => $employeeInB->sexe,
            'categorie' => $employeeInB->categorie,
            'statut' => $employeeInB->statut,
            // A complete payload on purpose: the 403 must come from the
            // policy (wrong center), not from a validation failure.
            'etablissement_ids' => [$centerB->id],
        ])->assertForbidden();

        $this->delete(route('backoffice.employees.destroy', $employeeInB))
            ->assertForbidden();
    }

    public function test_a_user_cannot_assign_an_employee_to_a_center_it_does_not_hold(): void
    {
        $centre = Etablissement::factory()->create();
        $otherCentre = Etablissement::factory()->create();

        // A user confined to $centre (its employee profile is based there).
        // The "Centres affectés" field is always shown, so the submitted list
        // is honored — but only after being narrowed to the centres this user
        // may actually assign.
        $user = $this->userWith('employees.view', 'employees.create');
        $user->employee()->save(Employee::factory()->make(['etablissement_id' => $centre->id]));
        $user->employee->etablissements()->sync([$centre->id]);
        $this->actingAs($user->fresh());

        $this->post(route('backoffice.employees.store'), [
            'nom' => 'Ziani',
            'prenom' => 'Nadia',
            'sexe' => 'Femme',
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => Employee::STATUT_ACTIF,
            // Client attempts to assign a center this user cannot reach —
            // narrowed back to its own centre server-side.
            'etablissement_ids' => [$otherCentre->id],
        ])->assertSessionDoesntHaveErrors();

        $employee = Employee::where('nom', 'Ziani')->firstOrFail();
        $this->assertSame($centre->id, $employee->etablissement_id);
        $this->assertSame([$centre->id], $employee->etablissements()->pluck('etablissements.id')->all());
    }

    // --- Multi-center assignment -------------------------------------------

    public function test_an_employee_must_be_assigned_to_at_least_one_center(): void
    {
        $this->actingAs($this->userWith('employees.view', 'employees.create'));

        $base = [
            'nom' => 'Sansu',
            'prenom' => 'Amine',
            'sexe' => 'Homme',
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => Employee::STATUT_ACTIF,
        ];

        // Omitted entirely.
        $this->post(route('backoffice.employees.store'), $base)
            ->assertSessionHasErrors('etablissement_ids');

        // Explicitly empty.
        $this->post(route('backoffice.employees.store'), [...$base, 'etablissement_ids' => []])
            ->assertSessionHasErrors('etablissement_ids');

        $this->assertDatabaseMissing('employees', ['nom' => 'Sansu']);
    }

    public function test_an_employee_can_be_assigned_to_several_centers(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();

        $this->actingAs($this->userWith('employees.view', 'employees.create', 'centers.access-all'));

        $this->post(route('backoffice.employees.store'), [
            'nom' => 'Tazi',
            'prenom' => 'Meryem',
            'sexe' => 'Femme',
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => Employee::STATUT_ACTIF,
            'etablissement_ids' => [$a->id, $b->id],
        ])->assertSessionDoesntHaveErrors();

        $employee = Employee::where('nom', 'Tazi')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$a->id, $b->id],
            $employee->etablissements()->pluck('etablissements.id')->all(),
        );
        // The primary column points at the first assigned center.
        $this->assertSame($a->id, $employee->etablissement_id);
    }

    public function test_updating_centers_replaces_the_assignment_without_moving_the_primary_center(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();
        $c = Etablissement::factory()->create();

        $this->actingAs($this->userWith('employees.view', 'employees.update', 'centers.access-all'));

        $employee = Employee::factory()->create(['etablissement_id' => $a->id]);
        $employee->etablissements()->sync([$a->id]);

        // Adding a center must NOT move the employee's base (its Caisse
        // lives there) — $a is still among the ids, so it stays primary.
        $this->put(route('backoffice.employees.update', $employee), [
            'nom' => $employee->nom,
            'prenom' => $employee->prenom,
            'sexe' => $employee->sexe,
            'categorie' => $employee->categorie,
            'statut' => $employee->statut,
            'etablissement_ids' => [$b->id, $a->id],
        ])->assertSessionDoesntHaveErrors();

        $employee->refresh();
        $this->assertSame($a->id, $employee->etablissement_id);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $employee->etablissements()->pluck('etablissements.id')->all());

        // Dropping the primary center re-points it at the first remaining one.
        $this->put(route('backoffice.employees.update', $employee), [
            'nom' => $employee->nom,
            'prenom' => $employee->prenom,
            'sexe' => $employee->sexe,
            'categorie' => $employee->categorie,
            'statut' => $employee->statut,
            'etablissement_ids' => [$c->id],
        ])->assertSessionDoesntHaveErrors();

        $employee->refresh();
        $this->assertSame($c->id, $employee->etablissement_id);
        $this->assertSame([$c->id], $employee->etablissements()->pluck('etablissements.id')->all());
    }

    public function test_a_multi_center_employee_is_listed_under_each_of_its_centers(): void
    {
        $a = Etablissement::factory()->create();
        $b = Etablissement::factory()->create();

        $shared = Employee::factory()->create(['nom' => 'Partagee', 'etablissement_id' => $a->id]);
        $shared->etablissements()->sync([$a->id, $b->id]);

        $this->actingAs($this->userWith('employees.view', 'centers.access-all'));

        // Filtering on the SECONDARY center must still find the employee,
        // even though its primary `etablissement_id` points at $a.
        $this->get(route('backoffice.employees.index', ['etablissementFilter' => $b->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('employees.data.0.nom', 'Partagee')
                ->count('employees.data', 1)
            );
    }

    public function test_the_submitted_centers_are_honored_even_while_the_top_bar_is_locked(): void
    {
        $centre = Etablissement::factory()->create();
        $autreCentre = Etablissement::factory()->create();

        // The "Centres affectés" multi-select is shown in every context, so a
        // user who may reach both centres can assign both even while the top
        // bar is locked to one of them — that assignment is what grants the
        // employee access and builds its own centre switcher.
        $employee = Employee::factory()->create(['etablissement_id' => $centre->id]);
        $employee->etablissements()->sync([$centre->id]);

        $this->actingAs($this->userWith('employees.view', 'employees.update', 'centers.access-all'));
        session(['context.etablissement_id' => $centre->id]);

        $this->put(route('backoffice.employees.update', $employee), [
            'nom' => $employee->nom,
            'prenom' => $employee->prenom,
            'sexe' => $employee->sexe,
            'categorie' => $employee->categorie,
            'statut' => $employee->statut,
            'etablissement_ids' => [$centre->id, $autreCentre->id],
        ])->assertSessionDoesntHaveErrors();

        $this->assertEqualsCanonicalizing(
            [$centre->id, $autreCentre->id],
            $employee->fresh()->etablissements()->pluck('etablissements.id')->all(),
        );
    }
}
