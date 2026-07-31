<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\People;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\User;
use App\Services\Context\CurrentContext;
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
        ])->assertForbidden();

        $this->delete(route('backoffice.employees.destroy', $employeeInB))
            ->assertForbidden();
    }

    public function test_etablissement_id_is_forced_server_side_when_context_is_locked_to_one_center(): void
    {
        $centre = Etablissement::factory()->create();
        $otherCentre = Etablissement::factory()->create();

        $user = $this->userWith('employees.view', 'employees.create');
        $this->actingAs($user);

        app(CurrentContext::class)->setEtablissement(null); // no-op for non-global user, ensures locked path

        // Force the session context to the user's own center via a real request.
        session(['context.etablissement_id' => $centre->id]);

        $this->post(route('backoffice.employees.store'), [
            'nom' => 'Ziani',
            'prenom' => 'Nadia',
            'sexe' => 'Femme',
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => Employee::STATUT_ACTIF,
            // Client attempts to set a different center — must be ignored
            // because this user has no centers.access-all permission.
            'etablissement_id' => $otherCentre->id,
        ])->assertSessionDoesntHaveErrors();

        $employee = Employee::where('nom', 'Ziani')->firstOrFail();
        $this->assertNotSame($otherCentre->id, $employee->etablissement_id);
    }
}
