<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Students;

use App\Livewire\Backoffice\Students\StudentsIndex;
use App\Models\Group;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * German-track levels (Arbeit / Studium / Ausbildung) and the conditional
 * second dropdown they open: a professional field for Arbeit/Ausbildung,
 * an entrance exam (STK/DSH) for Studium.
 */
final class StudentOrientationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);
    }

    public function test_the_level_list_offers_the_german_tracks_on_top_of_cefr(): void
    {
        $this->assertContains('A1.1', Student::NIVEAUX);
        $this->assertContains('Arbeit', Student::NIVEAUX);
        $this->assertContains('Studium', Student::NIVEAUX);
        $this->assertContains('Ausbildung', Student::NIVEAUX);
    }

    /** Groups stay CEFR-only — a group's level drives the per-fee classification. */
    public function test_groups_do_not_offer_the_german_tracks(): void
    {
        $this->assertContains('A1.1', Group::NIVEAUX);
        $this->assertNotContains('Arbeit', Group::NIVEAUX);
        $this->assertNotContains('Studium', Group::NIVEAUX);
        $this->assertNotContains('Ausbildung', Group::NIVEAUX);
    }

    public function test_arbeit_stores_a_professional_field(): void
    {
        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'Bennani')
            ->set('prenom', 'Yassine')
            ->set('niveau', 'Arbeit')
            ->set('domaine', 'Cuisine')
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('nom', 'Bennani')->firstOrFail();
        $this->assertSame('Arbeit', $student->niveau);
        $this->assertSame('Cuisine', $student->domaine);
        $this->assertNull($student->examen_type);
        $this->assertSame('Cuisine', $student->orientation());
    }

    public function test_ausbildung_also_asks_for_a_professional_field(): void
    {
        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'Fassi')
            ->set('prenom', 'Amine')
            ->set('niveau', 'Ausbildung')
            ->set('domaine', 'Électricien')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Électricien', Student::where('nom', 'Fassi')->firstOrFail()->domaine);
    }

    public function test_studium_stores_an_entrance_exam(): void
    {
        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'Cherkaoui')
            ->set('prenom', 'Nada')
            ->set('niveau', 'Studium')
            ->set('examen_type', 'DSH')
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('nom', 'Cherkaoui')->firstOrFail();
        $this->assertSame('DSH', $student->examen_type);
        $this->assertNull($student->domaine);
        $this->assertSame('DSH', $student->orientation());
    }

    public function test_the_field_is_required_for_arbeit(): void
    {
        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'Idrissi')
            ->set('prenom', 'Omar')
            ->set('niveau', 'Arbeit')
            ->call('save')
            ->assertHasErrors(['domaine' => 'required']);
    }

    public function test_the_entrance_exam_is_required_for_studium(): void
    {
        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'Idrissi')
            ->set('prenom', 'Omar')
            ->set('niveau', 'Studium')
            ->call('save')
            ->assertHasErrors(['examen_type' => 'required']);
    }

    public function test_an_unknown_field_is_rejected(): void
    {
        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'Idrissi')
            ->set('prenom', 'Omar')
            ->set('niveau', 'Arbeit')
            ->set('domaine', 'Astronaute')
            ->call('save')
            ->assertHasErrors('domaine');
    }

    /** A plain CEFR level keeps both orientation columns empty. */
    public function test_a_cefr_level_stores_no_orientation(): void
    {
        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'Rafik')
            ->set('prenom', 'Salma')
            ->set('niveau', 'B1.2')
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('nom', 'Rafik')->firstOrFail();
        $this->assertNull($student->domaine);
        $this->assertNull($student->examen_type);
        $this->assertNull($student->orientation());
    }

    /**
     * Switching Studium → Arbeit must drop the exam, otherwise a student could
     * be saved as "Arbeit + DSH".
     */
    public function test_changing_the_level_clears_the_stale_orientation(): void
    {
        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('niveau', 'Studium')
            ->set('examen_type', 'STK')
            ->set('niveau', 'Arbeit')
            ->assertSet('examen_type', null)
            ->set('domaine', 'Hôtellerie')
            ->set('niveau', 'A2.1')
            ->assertSet('domaine', null);
    }

    public function test_a_stale_orientation_is_not_persisted_after_a_level_change(): void
    {
        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'Tazi')
            ->set('prenom', 'Hind')
            ->set('niveau', 'Studium')
            ->set('examen_type', 'STK')
            ->set('niveau', 'B2.1')
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('nom', 'Tazi')->firstOrFail();
        $this->assertNull($student->examen_type);
        $this->assertNull($student->domaine);
    }

    public function test_the_student_cin_is_stored_and_searchable(): void
    {
        Livewire::test(StudentsIndex::class)
            ->call('create')
            ->set('nom', 'Alami')
            ->set('prenom', 'Karim')
            ->set('cin', 'AB123456')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('AB123456', Student::where('nom', 'Alami')->firstOrFail()->cin);

        Livewire::test(StudentsIndex::class)
            ->set('search', 'AB123456')
            ->assertSee('Alami');
    }
}
