<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Livewire\Backoffice\Inscriptions\InscriptionsIndex;
use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The inline "Nouvel étudiant" block of the registration modal: CIN and the
 * German-track orientation must reach the created student.
 */
final class InscriptionStudentFieldsTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-08-31',
            'par_defaut' => true,
            'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();

        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);
    }

    private function makeGroup(): Group
    {
        return Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    public function test_mode_select_remains_a_single_native_select_through_conditional_branch_changes(): void
    {
        $component = Livewire::test(InscriptionsIndex::class)
            ->call('create');

        foreach (['new', 'existing', 'new', 'existing', 'new'] as $mode) {
            $html = $component
                ->set('inscriptionMode', $mode)
                ->html();

            // i-mode is a plain wire:model.live select — no Select2/Alpine
            // island, so no wire:ignore and no gls-select2-* wrapper key.
            $this->assertSame(1, substr_count($html, 'id="i-mode"'));
            $this->assertSame(0, substr_count($html, 'wire:key="gls-select2-i-mode-'));
            $this->assertMatchesRegularExpression(
                '/<select[^>]*id="i-mode"[^>]*wire:model\.live="inscriptionMode"[^>]*>\s*<option value="new">.*?<\/option>\s*<option value="existing">.*?<\/option>\s*<\/select>/s',
                $html,
            );

            if ($mode === 'existing') {
                $this->assertSame(1, substr_count($html, 'id="i-student"'));
                $this->assertStringContainsString('wire:key="ins-existing-student"', $html);
            } else {
                $this->assertSame(0, substr_count($html, 'id="i-student"'));
                $this->assertStringContainsString('wire:key="ins-new-nom"', $html);
            }
        }
    }

    public function test_group_select_renders_the_available_group_options_inside_the_livewire_updatable_select(): void
    {
        $group = $this->makeGroup();

        $html = Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->html();

        $this->assertSame(1, substr_count($html, 'id="i-group"'));
        $this->assertSame(1, substr_count($html, 'wire:key="gls-select2-i-group-'));
        $this->assertStringContainsString('wire:ignore', $html);
        $this->assertMatchesRegularExpression(
            '/<select[^>]*id="i-group"[^>]*>.*?<option value="'.$group->id.'">.*?<\/option>.*?<\/select>/s',
            $html,
        );
    }

    public function test_a_new_student_is_created_with_cin_and_a_professional_field(): void
    {
        $group = $this->makeGroup();

        Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'new')
            ->set('new_nom', 'Bennani')
            ->set('new_prenom', 'Yassine')
            ->set('new_cin', 'AB123456')
            ->set('new_niveau', 'Ausbildung')
            ->set('new_domaine', 'Mécanique automobile')
            ->set('group_id', $group->id)
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('nom', 'Bennani')->firstOrFail();
        $this->assertSame('AB123456', $student->cin);
        $this->assertSame('Ausbildung', $student->niveau);
        $this->assertSame('Mécanique automobile', $student->domaine);
        $this->assertNull($student->examen_type);
    }

    public function test_a_new_student_can_be_created_with_an_entrance_exam(): void
    {
        $group = $this->makeGroup();

        Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'new')
            ->set('new_nom', 'Cherkaoui')
            ->set('new_prenom', 'Nada')
            ->set('new_niveau', 'Studium')
            ->set('new_examen_type', 'STK')
            ->set('group_id', $group->id)
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('nom', 'Cherkaoui')->firstOrFail();
        $this->assertSame('STK', $student->examen_type);
        $this->assertNull($student->domaine);
    }

    public function test_the_field_is_required_when_the_track_asks_for_it(): void
    {
        $group = $this->makeGroup();

        Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'new')
            ->set('new_nom', 'Idrissi')
            ->set('new_prenom', 'Omar')
            ->set('new_niveau', 'Arbeit')
            ->set('group_id', $group->id)
            ->call('save')
            ->assertHasErrors(['new_domaine' => 'required']);
    }

    public function test_changing_the_level_clears_the_stale_orientation(): void
    {
        Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'new')
            ->set('new_niveau', 'Studium')
            ->set('new_examen_type', 'DSH')
            ->set('new_niveau', 'Arbeit')
            ->assertSet('new_examen_type', null);
    }

    /**
     * The Parent tab carries the same six fields as the Student modal:
     * relation, name, gender, CIN, phone and WhatsApp.
     */
    public function test_the_full_parent_block_is_saved_on_the_new_student(): void
    {
        $group = $this->makeGroup();

        Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'new')
            ->set('new_nom', 'Tazi')
            ->set('new_prenom', 'Hind')
            ->set('new_parent_relation', 'La mère')
            ->set('new_parent_nom', 'Alaoui')
            ->set('new_parent_sexe', 'Femme')
            ->set('new_parent_cin', 'AB123456')
            ->set('new_parent_telephone', '661954125')
            ->set('new_parent_whatsapp', '661954126')
            ->set('group_id', $group->id)
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('nom', 'Tazi')->firstOrFail();
        $this->assertSame('La mère', $student->parent_relation);
        $this->assertSame('Alaoui', $student->parent_nom);
        $this->assertSame('Femme', $student->parent_sexe);
        $this->assertSame('AB123456', $student->parent_cin);
        $this->assertStringContainsString('661954125', (string) $student->parent_telephone);
        $this->assertStringContainsString('661954126', (string) $student->parent_whatsapp);
    }

    public function test_an_unknown_parent_relation_is_rejected(): void
    {
        $group = $this->makeGroup();

        Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'new')
            ->set('new_nom', 'Idrissi')
            ->set('new_prenom', 'Omar')
            ->set('new_parent_relation', 'Le voisin')
            ->set('group_id', $group->id)
            ->call('save')
            ->assertHasErrors('new_parent_relation');
    }

    /** A plain CEFR level needs neither sub-field. */
    public function test_a_cefr_level_needs_no_orientation(): void
    {
        $group = $this->makeGroup();

        Livewire::test(InscriptionsIndex::class)
            ->call('create')
            ->set('inscriptionMode', 'new')
            ->set('new_nom', 'Rafik')
            ->set('new_prenom', 'Salma')
            ->set('new_niveau', 'A1.2')
            ->set('group_id', $group->id)
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::where('nom', 'Rafik')->firstOrFail();
        $this->assertNull($student->domaine);
        $this->assertNull($student->examen_type);
    }
}
