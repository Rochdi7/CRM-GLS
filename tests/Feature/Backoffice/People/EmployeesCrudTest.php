<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\People;

use App\Livewire\Backoffice\Employees\EmployeesIndex;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class EmployeesCrudTest extends TestCase
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

    public function test_page_requires_employees_view(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.employees.index'))
            ->assertForbidden();

        $this->actingAs($this->userWith('employees.view'))
            ->get(route('backoffice.employees.index'))
            ->assertOk();
    }

    public function test_creating_an_employee_generates_a_login_and_shows_credentials(): void
    {
        $this->actingAs($this->userWith('employees.view', 'employees.create'));

        Livewire::test(EmployeesIndex::class)
            ->call('create')
            ->set('nom', 'Bennani')
            ->set('prenom', 'Salma')
            ->set('categorie', Employee::CATEGORIE_COMMERCIAL)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('newUsername', fn ($v) => is_string($v) && $v !== '')
            ->assertSet('newPassword', fn ($v) => is_string($v) && $v !== '');

        $employee = Employee::where('nom', 'Bennani')->first();
        $this->assertNotNull($employee);
        $this->assertSame(Employee::CATEGORIE_COMMERCIAL, $employee->categorie);
        // Auto-created login (EmployeeObserver).
        $this->assertNotNull($employee->user_id);
    }

    public function test_hr_fields_are_saved_and_only_sexe_is_required(): void
    {
        $this->actingAs($this->userWith('employees.view', 'employees.create'));

        // sexe missing → validation error (it's the only required new field).
        Livewire::test(EmployeesIndex::class)
            ->call('create')
            ->set('nom', 'Idrissi')
            ->set('prenom', 'Youssef')
            ->set('sexe', '')
            ->call('save')
            ->assertHasErrors('sexe');

        // All optional HR fields persist; the others stay nullable.
        Livewire::test(EmployeesIndex::class)
            ->call('create')
            ->set('nom', 'Idrissi')
            ->set('prenom', 'Youssef')
            ->set('sexe', 'Homme')
            ->set('date_naissance', '1990-05-10')
            ->set('date_embauche', '2024-01-15')
            ->set('salaire', '8500.50')
            ->set('email', 'youssef@gls.test')
            ->set('note', 'Bilingue FR/EN')
            ->call('save')
            ->assertHasNoErrors();

        $emp = Employee::where('nom', 'Idrissi')->first();
        $this->assertSame('Homme', $emp->sexe);
        $this->assertSame('1990-05-10', $emp->date_naissance->toDateString());
        $this->assertSame('2024-01-15', $emp->date_embauche->toDateString());
        $this->assertSame('8500.50', (string) $emp->salaire);
        $this->assertSame('Bilingue FR/EN', $emp->note);
    }

    public function test_category_must_be_one_of_the_allowed_values(): void
    {
        $this->actingAs($this->userWith('employees.view', 'employees.create'));

        Livewire::test(EmployeesIndex::class)
            ->call('create')
            ->set('nom', 'X')
            ->set('prenom', 'Y')
            ->set('categorie', 'Hacker')
            ->call('save')
            ->assertHasErrors('categorie');
    }

    public function test_the_new_screenshot_categories_exist(): void
    {
        foreach (['Directeur', 'Commercial', 'Enseignant', 'Comptable', 'Responsable Marketing',
            'Assistante administrative', 'Directeur des opérations', 'Directrice pédagogique',
            'Directeur Qualité et Amélioration continue', 'Autre'] as $cat) {
            $this->assertContains($cat, Employee::CATEGORIES);
        }
        $this->assertCount(10, Employee::CATEGORIES);
    }

    public function test_an_employee_can_be_edited(): void
    {
        $this->actingAs($this->userWith('employees.view', 'employees.update'));
        $emp = Employee::factory()->create(['user_id' => User::factory()->create()->id]);

        Livewire::test(EmployeesIndex::class)
            ->call('edit', $emp->id)
            ->set('categorie', Employee::CATEGORIE_COMPTABLE)
            ->set('statut', Employee::STATUT_INACTIF)
            ->call('save')
            ->assertHasNoErrors();

        $emp->refresh();
        $this->assertSame(Employee::CATEGORIE_COMPTABLE, $emp->categorie);
        $this->assertSame(Employee::STATUT_INACTIF, $emp->statut);
    }

    public function test_user_without_create_permission_cannot_open_add(): void
    {
        $this->actingAs($this->userWith('employees.view'));

        Livewire::test(EmployeesIndex::class)->call('create')->assertForbidden();
    }

    public function test_employee_with_activity_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWith('employees.view', 'employees.delete'));

        $centre = Etablissement::factory()->create();
        $teacher = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
        Group::factory()->create(['enseignant_id' => $teacher->id, 'etablissement_id' => $centre->id]);

        Livewire::test(EmployeesIndex::class)
            ->call('delete', $teacher->id)
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('employees', ['id' => $teacher->id]);
    }
}
