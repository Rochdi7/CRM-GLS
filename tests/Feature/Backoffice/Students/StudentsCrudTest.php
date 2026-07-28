<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Students;

use App\Livewire\Backoffice\Students\StudentsIndex;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

final class StudentsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function globalUser(string ...$permissions): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN); // global center access
        $this->actingAs($user);

        return $user;
    }

    private function centerUser(Etablissement $centre, string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);
        $this->actingAs($user->fresh());

        return $user->fresh();
    }

    public function test_page_requires_students_view(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('dashboard.view');
        $this->actingAs($u)->get(route('backoffice.students.index'))->assertForbidden();

        $u2 = User::factory()->create();
        $u2->givePermissionTo('students.view');
        $this->actingAs($u2)->get(route('backoffice.students.index'))->assertOk();
    }

    public function test_a_student_can_be_created_with_a_generated_reference(): void
    {
        $this->globalUser();

        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'Alaoui')
            ->set('prenom', 'Sara')
            ->set('niveau', 'A1.1')
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('nom', 'Alaoui')->first();
        $this->assertNotNull($student);
        $this->assertStringStartsWith('ETU-', $student->reference);
        $this->assertSame('A1.1', $student->niveau);
    }

    public function test_parent_details_are_saved_with_the_student(): void
    {
        $this->globalUser();

        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'Bennani')
            ->set('prenom', 'Yasmine')
            ->set('parent_nom', 'Bennani Karim')
            ->set('parent_relation', 'Le père')
            ->set('parent_sexe', 'Homme')
            ->set('parent_cin', 'AB123456')
            ->call('save')
            ->assertHasNoErrors();

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
        $this->globalUser();

        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'X')->set('prenom', 'Y')
            ->set('parent_relation', 'Le voisin')
            ->set('parent_sexe', 'Autre')
            ->call('save')
            ->assertHasErrors(['parent_relation', 'parent_sexe']);
    }

    public function test_level_must_be_a_valid_cefr_value(): void
    {
        $this->globalUser();

        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'X')->set('prenom', 'Y')
            ->set('niveau', 'Z9.9')
            ->call('save')
            ->assertHasErrors('niveau');
    }

    public function test_a_photo_can_be_attached(): void
    {
        $this->globalUser();

        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'Photo')->set('prenom', 'Test')
            ->set('photo', UploadedFile::fake()->image('avatar.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('nom', 'Photo')->first();
        $this->assertNotEmpty($student->getFirstMediaUrl('photo'));
        $this->assertStringContainsString('/media/', $student->getFirstMediaUrl('photo'));
    }

    public function test_a_student_can_be_edited(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['niveau' => 'A1.1']);

        Livewire::test(StudentsIndex::class)
            ->call('edit', $student->id)
            ->set('niveau', 'B2.3')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('B2.3', $student->fresh()->niveau);
    }

    public function test_center_scoped_user_only_sees_their_center_students(): void
    {
        $rabat = Etablissement::factory()->create();
        $casa = Etablissement::factory()->create();
        Student::factory()->create(['nom' => 'RabatStudent', 'etablissement_id' => $rabat->id]);
        Student::factory()->create(['nom' => 'CasaStudent', 'etablissement_id' => $casa->id]);

        $this->centerUser($rabat, 'students.view');

        Livewire::test(StudentsIndex::class)
            ->assertSee('RabatStudent')
            ->assertDontSee('CasaStudent');
    }

    public function test_center_scoped_user_cannot_view_another_centers_student(): void
    {
        $rabat = Etablissement::factory()->create();
        $casa = Etablissement::factory()->create();
        $casaStudent = Student::factory()->create(['etablissement_id' => $casa->id]);

        $this->centerUser($rabat, 'students.view');

        $this->get(route('backoffice.students.show', $casaStudent))->assertForbidden();
    }

    public function test_student_detail_page_renders(): void
    {
        $this->globalUser();
        $student = Student::factory()->create();

        $this->get(route('backoffice.students.show', $student))
            ->assertOk()
            ->assertSee($student->nomComplet());
    }

    public function test_age_is_computed_from_date_naissance(): void
    {
        $this->globalUser();
        $student = Student::factory()->create(['date_naissance' => now()->subYears(17)->subMonth()]);

        $this->assertSame(17, $student->age());
        $this->assertNull(Student::factory()->create(['date_naissance' => null])->age());

        Livewire::test(StudentsIndex::class)->assertSeeHtml('<td>17</td>');
    }

    public function test_students_can_be_sorted_by_age(): void
    {
        $this->globalUser();
        Student::factory()->create(['nom' => 'Older', 'date_naissance' => now()->subYears(24)]);
        Student::factory()->create(['nom' => 'Younger', 'date_naissance' => now()->subYears(17)]);
        Student::factory()->create(['nom' => 'NoBirthdate', 'date_naissance' => null]);

        Livewire::test(StudentsIndex::class)
            ->call('sortByAge')
            ->assertSet('ageSort', 'asc')
            ->assertSeeInOrder(['Younger', 'Older', 'NoBirthdate'])
            ->call('sortByAge')
            ->assertSet('ageSort', 'desc')
            ->assertSeeInOrder(['Older', 'Younger', 'NoBirthdate']);
    }

    public function test_phone_is_stored_with_country_dial_code_and_split_back_on_edit(): void
    {
        $this->globalUser();

        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'Berrada')
            ->set('prenom', 'Nora')
            ->set('telephone', '661954125')
            ->set('phonePays', 'HR')
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('nom', 'Berrada')->first();
        $this->assertSame('+385661954125', $student->telephone);

        // Editing splits the stored value back into country + national part.
        Livewire::test(StudentsIndex::class)
            ->call('edit', $student->id)
            ->assertSet('phonePays', 'HR')
            ->assertSet('telephone', '661954125');
    }

    public function test_invalid_phone_country_is_rejected(): void
    {
        $this->globalUser();

        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'X')
            ->set('prenom', 'Y')
            ->set('phonePays', 'ZZ')
            ->call('save')
            ->assertHasErrors('phonePays');
    }

    public function test_student_with_activity_cannot_be_deleted(): void
    {
        $this->globalUser();
        $student = Student::factory()->create();
        Inscription::create([
            'reference' => 'INS-TEST1', 'student_id' => $student->id,
            'group_id' => Group::factory()->create()->id,
            'statut' => 'Active', 'date_inscription' => '2026-01-01',
        ]);

        Livewire::test(StudentsIndex::class)
            ->call('delete', $student->id)
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }
}
