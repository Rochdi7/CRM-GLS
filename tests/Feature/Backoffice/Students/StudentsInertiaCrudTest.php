<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Students;

use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Covers the new Inertia/React Students endpoints (StudentController) built
 * alongside the unchanged Livewire StudentsIndex fallback — see
 * StudentsCrudTest for the Livewire-side coverage of the same business
 * rules (docs/phase-8-students-groups-inventory.md).
 */
final class StudentsInertiaCrudTest extends TestCase
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

    public function test_index_requires_students_view_and_renders_the_react_page(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.students.index'))
            ->assertForbidden();

        $this->actingAs($this->userWith('students.view'))
            ->get(route('backoffice.students.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Students/Index', false)
                ->has('students')
                ->has('niveaux')
                ->has('domaines')
                ->has('examenTypes')
                ->has('sexes')
                ->has('parentRelations')
                ->where('filters.perPage', 10)
            );
    }

    public function test_a_student_can_be_created_with_a_generated_reference(): void
    {
        $this->actingAs($this->userWith('students.view', 'students.create'));

        $this->post(route('backoffice.students.store'), [
            'nom' => 'Alaoui',
            'prenom' => 'Sara',
            'niveau' => 'A1.1',
            'phone_pays' => 'MA',
        ])->assertRedirect(route('backoffice.students.index'));

        $student = Student::where('nom', 'Alaoui')->first();
        $this->assertNotNull($student);
        $this->assertStringStartsWith('ETU-', $student->reference);
        $this->assertSame('A1.1', $student->niveau);
    }

    public function test_parent_details_are_saved_with_the_student(): void
    {
        $this->actingAs($this->userWith('students.view', 'students.create'));

        $this->post(route('backoffice.students.store'), [
            'nom' => 'Bennani',
            'prenom' => 'Yasmine',
            'phone_pays' => 'MA',
            'parent_nom' => 'Bennani Karim',
            'parent_relation' => 'Le père',
            'parent_sexe' => 'Homme',
            'parent_cin' => 'AB123456',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('students', [
            'nom' => 'Bennani',
            'parent_nom' => 'Bennani Karim',
            'parent_relation' => 'Le père',
            'parent_sexe' => 'Homme',
            'parent_cin' => 'AB123456',
        ]);
    }

    public function test_parent_relation_and_sexe_must_be_valid_values(): void
    {
        $this->actingAs($this->userWith('students.view', 'students.create'));

        $this->post(route('backoffice.students.store'), [
            'nom' => 'X', 'prenom' => 'Y',
            'parent_relation' => 'Le voisin',
            'parent_sexe' => 'Autre',
        ])->assertSessionHasErrors(['parent_relation', 'parent_sexe']);
    }

    public function test_level_must_be_a_valid_cefr_value(): void
    {
        $this->actingAs($this->userWith('students.view', 'students.create'));

        $this->post(route('backoffice.students.store'), [
            'nom' => 'X', 'prenom' => 'Y', 'niveau' => 'Z9.9',
        ])->assertSessionHasErrors('niveau');
    }

    public function test_domaine_is_required_for_arbeit_and_ausbildung_tracks(): void
    {
        $this->actingAs($this->userWith('students.view', 'students.create'));

        $this->post(route('backoffice.students.store'), [
            'nom' => 'X', 'prenom' => 'Y', 'niveau' => 'Arbeit', 'phone_pays' => 'MA',
        ])->assertSessionHasErrors('domaine');

        $this->post(route('backoffice.students.store'), [
            'nom' => 'X', 'prenom' => 'Y', 'niveau' => 'Arbeit', 'domaine' => 'Cuisine', 'phone_pays' => 'MA',
        ])->assertSessionDoesntHaveErrors();
    }

    public function test_examen_type_is_required_for_studium(): void
    {
        $this->actingAs($this->userWith('students.view', 'students.create'));

        $this->post(route('backoffice.students.store'), [
            'nom' => 'X', 'prenom' => 'Y', 'niveau' => 'Studium', 'phone_pays' => 'MA',
        ])->assertSessionHasErrors('examen_type');

        $this->post(route('backoffice.students.store'), [
            'nom' => 'X', 'prenom' => 'Y', 'niveau' => 'Studium', 'examen_type' => 'DSH', 'phone_pays' => 'MA',
        ])->assertSessionDoesntHaveErrors();
    }

    public function test_a_photo_can_be_attached(): void
    {
        Storage::fake('media');
        $this->actingAs($this->userWith('students.view', 'students.create'));

        $this->post(route('backoffice.students.store'), [
            'nom' => 'Photo', 'prenom' => 'Test', 'phone_pays' => 'MA',
            'photo' => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertSessionDoesntHaveErrors();

        $student = Student::where('nom', 'Photo')->firstOrFail();
        $this->assertCount(1, $student->getMedia('photo'));
    }

    public function test_a_student_can_be_updated(): void
    {
        $this->actingAs($this->userWith('students.view', 'students.update'));
        $student = Student::factory()->create(['niveau' => 'A1.1']);

        $this->put(route('backoffice.students.update', $student), [
            'nom' => $student->nom,
            'prenom' => $student->prenom,
            'niveau' => 'B2.3',
            'phone_pays' => 'MA',
        ])->assertRedirect(route('backoffice.students.index'));

        $this->assertSame('B2.3', $student->fresh()->niveau);
    }

    public function test_phone_is_stored_with_country_dial_code(): void
    {
        $this->actingAs($this->userWith('students.view', 'students.create'));

        $this->post(route('backoffice.students.store'), [
            'nom' => 'Berrada', 'prenom' => 'Nora',
            'phone_pays' => 'HR', 'telephone' => '661954125',
        ])->assertSessionDoesntHaveErrors();

        $student = Student::where('nom', 'Berrada')->firstOrFail();
        $this->assertSame('+385661954125', $student->telephone);
    }

    public function test_invalid_phone_country_is_rejected(): void
    {
        $this->actingAs($this->userWith('students.view', 'students.create'));

        $this->post(route('backoffice.students.store'), [
            'nom' => 'X', 'prenom' => 'Y', 'phone_pays' => 'ZZ',
        ])->assertSessionHasErrors('phone_pays');
    }

    public function test_student_with_activity_cannot_be_deleted(): void
    {
        $this->actingAs($this->userWith('students.view', 'students.delete'));
        $student = Student::factory()->create();
        Inscription::create([
            'reference' => 'INS-TEST1', 'student_id' => $student->id,
            'group_id' => Group::factory()->create()->id,
            'statut' => 'Active', 'date_inscription' => '2026-01-01',
        ]);

        $this->delete(route('backoffice.students.destroy', $student))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    public function test_student_without_activity_can_be_deleted(): void
    {
        $this->actingAs($this->userWith('students.view', 'students.delete'));
        $student = Student::factory()->create();

        $this->delete(route('backoffice.students.destroy', $student))
            ->assertRedirect(route('backoffice.students.index'));

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_user_without_create_permission_cannot_store(): void
    {
        $this->actingAs($this->userWith('students.view'));

        $this->post(route('backoffice.students.store'), [
            'nom' => 'X', 'prenom' => 'Y',
        ])->assertForbidden();
    }

    public function test_center_scoped_user_only_sees_their_center_students(): void
    {
        $rabat = Etablissement::factory()->create();
        $casa = Etablissement::factory()->create();
        Student::factory()->create(['nom' => 'RabatStudent', 'etablissement_id' => $rabat->id]);
        Student::factory()->create(['nom' => 'CasaStudent', 'etablissement_id' => $casa->id]);

        $user = $this->userWith('students.view');
        $user->employee()->save(\App\Models\Employee::factory()->make(['etablissement_id' => $rabat->id]));
        $this->actingAs($user->fresh());

        $this->get(route('backoffice.students.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('students.data', fn ($rows) => collect($rows)->contains(fn ($row) => $row['nom'] === 'RabatStudent')
                    && ! collect($rows)->contains(fn ($row) => $row['nom'] === 'CasaStudent'))
            );
    }

    public function test_update_and_delete_are_center_scoped_for_non_global_users(): void
    {
        $centerA = Etablissement::factory()->create();
        $centerB = Etablissement::factory()->create();

        $studentInB = Student::factory()->create(['etablissement_id' => $centerB->id]);

        $lockedAdmin = $this->userWith('students.view', 'students.update', 'students.delete');
        $lockedAdmin->employee()->save(\App\Models\Employee::factory()->make(['etablissement_id' => $centerA->id]));

        $this->actingAs($lockedAdmin->fresh());

        $this->put(route('backoffice.students.update', $studentInB), [
            'nom' => $studentInB->nom,
            'prenom' => $studentInB->prenom,
            'phone_pays' => 'MA',
        ])->assertForbidden();

        $this->delete(route('backoffice.students.destroy', $studentInB))
            ->assertForbidden();
    }
}
