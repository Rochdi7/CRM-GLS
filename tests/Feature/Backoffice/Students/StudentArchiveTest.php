<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Students;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Statut « Archivé » d'un étudiant (19/10/2026) : une fiche mise en pause
 * (motif + date de retour), sortie de la liste courante vers l'onglet
 * « Étudiants archivés », comptée à part sur le tableau de bord, et
 * réactivable d'un clic. Rien d'autre ne bouge.
 */
final class StudentArchiveTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    private Employee $guichet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);

        $this->guichet = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);
        $this->guichet->user->forceFill(['must_change_password' => false])->save();
        $this->guichet->user->givePermissionTo(['dashboard.view', 'students.view', 'students.archive', 'registrations.view', 'registrations.create']);
    }

    private function etudiant(string $nom = 'BENALI'): Student
    {
        return Student::factory()->create(['etablissement_id' => $this->rabat->id, 'nom' => $nom, 'prenom' => 'Sara']);
    }

    private function archiver(Student $student, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->guichet->user)->post(route('backoffice.students.archive', $student), $payload + [
            'motif' => 'Voyage à l’étranger',
            'date_retour' => now()->addMonth()->toDateString(),
        ]);
    }

    public function test_archiving_requires_a_reason_and_a_future_return_date(): void
    {
        $student = $this->etudiant();

        $this->actingAs($this->guichet->user)
            ->from(route('backoffice.students.index'))
            ->post(route('backoffice.students.archive', $student), ['motif' => '', 'date_retour' => ''])
            ->assertSessionHasErrors(['motif', 'date_retour']);

        $this->archiver($student, ['date_retour' => now()->toDateString()])
            ->assertSessionHasErrors(['date_retour']);

        $this->assertSame(Student::STATUT_ACTIF, $student->fresh()->statut);
    }

    public function test_archiving_stamps_reason_return_date_author_and_moves_the_record_to_the_archives_tab(): void
    {
        $student = $this->etudiant();
        $autre = $this->etudiant('ALAOUI');

        $this->archiver($student)->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame(Student::STATUT_ARCHIVE, $student->statut);
        $this->assertSame('Voyage à l’étranger', $student->archive_motif);
        $this->assertSame(now()->addMonth()->toDateString(), $student->archive_date_retour->toDateString());
        $this->assertNotNull($student->archive_le);
        $this->assertSame($this->guichet->user->id, $student->archive_par_user_id);

        // Liste courante : la fiche archivée a disparu, l'autre reste.
        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.students.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('archivesView', false)
                ->has('students.data', 1)
                ->where('students.data.0.id', $autre->id)
                ->where('tabCounts./backoffice/students/archives', 1));

        // Onglet « Étudiants archivés » : elle seule, avec motif / retour / auteur.
        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.students.archives'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Students/Index', false)
                ->where('archivesView', true)
                ->has('students.data', 1)
                ->where('students.data.0.id', $student->id)
                ->where('students.data.0.statut', 'Archivé')
                ->where('students.data.0.archiveMotif', 'Voyage à l’étranger')
                ->where('students.data.0.archiveDateRetour', now()->addMonth()->format('d/m/Y'))
                ->where('students.data.0.archiveRetourDepasse', false)
                ->where('students.data.0.archivePar', $this->guichet->user->name)
                ->where('permissions.archive', true));

        $this->assertDatabaseHas('activity_log', ['event' => 'student_archived', 'subject_id' => $student->id]);
    }

    public function test_reactivating_puts_the_student_back_in_the_current_list_and_clears_the_archive_fields(): void
    {
        $student = $this->etudiant();
        $this->archiver($student)->assertSessionHasNoErrors();

        $this->actingAs($this->guichet->user)
            ->post(route('backoffice.students.reactivate', $student))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backoffice.students.archives'));

        $student->refresh();
        $this->assertSame(Student::STATUT_ACTIF, $student->statut);
        $this->assertNull($student->archive_motif);
        $this->assertNull($student->archive_date_retour);
        $this->assertNull($student->archive_le);
        $this->assertNull($student->archive_par_user_id);
        $this->assertDatabaseHas('activity_log', ['event' => 'student_reactivated', 'subject_id' => $student->id]);

        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.students.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('students.data', 1)
                ->where('students.data.0.id', $student->id)
                ->missing('tabCounts./backoffice/students/archives'));

        // Réactiver une fiche qui ne l'est pas est refusé, jamais silencieux.
        $this->actingAs($this->guichet->user)
            ->post(route('backoffice.students.reactivate', $student))
            ->assertSessionHasErrors(['archive']);
    }

    public function test_a_past_return_date_is_flagged_as_overdue(): void
    {
        $student = $this->etudiant();
        $this->archiver($student)->assertSessionHasNoErrors();

        // La date passe sans réactivation.
        Student::query()->whereKey($student->id)->update(['archive_date_retour' => now()->subDays(3)->toDateString()]);

        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.students.archives'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('students.data.0.archiveRetourDepasse', true));

        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.students.show', $student))
            ->assertInertia(fn (Assert $page) => $page
                ->where('student.archive.retourDepasse', true)
                ->where('student.archive.motif', 'Voyage à l’étranger'));
    }

    public function test_the_dashboard_counts_archived_students_apart_and_out_of_the_total(): void
    {
        $this->etudiant('UN');
        $this->etudiant('DEUX');
        $archive = $this->etudiant('TROIS');
        $this->archiver($archive)->assertSessionHasNoErrors();

        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.studentsTotal', 2)
                ->where('stats.studentsArchives', 1));
    }

    public function test_a_transferred_or_already_archived_student_cannot_be_archived(): void
    {
        $transfere = $this->etudiant('PARTI');
        Student::query()->whereKey($transfere->id)->update(['statut' => Student::STATUT_TRANSFERE]);

        $this->archiver($transfere)->assertSessionHasErrors(['archive']);
        $this->assertSame(Student::STATUT_TRANSFERE, $transfere->fresh()->statut);

        $deja = $this->etudiant('DEJA');
        $this->archiver($deja)->assertSessionHasNoErrors();
        $this->archiver($deja, ['motif' => 'Autre motif'])->assertSessionHasErrors(['archive']);
        $this->assertSame('Voyage à l’étranger', $deja->fresh()->archive_motif, 'Le premier motif n’est jamais réécrit.');
    }

    public function test_a_new_registration_on_an_archived_student_is_refused_and_names_the_way_out(): void
    {
        $student = $this->etudiant();
        $this->archiver($student)->assertSessionHasNoErrors();

        $group = Group::factory()->create([
            'etablissement_id' => $this->rabat->id,
            'annee_scolaire_id' => AnneeScolaire::query()->firstOrFail()->id,
            'statut' => Group::STATUT_EN_INSCRIPTION,
        ]);

        $this->actingAs($this->guichet->user)
            ->post(route('backoffice.inscriptions.store'), [
                'inscription_mode' => 'existing',
                'student_id' => $student->id,
                'group_id' => $group->id,
                'date_inscription' => now()->toDateString(),
                'fee_lines' => [],
            ])
            ->assertSessionHasErrors(['student_id']);

        $this->assertDatabaseMissing('inscriptions', ['student_id' => $student->id]);

        // Le formulaire LISTE la fiche, désactivée avec la raison — jamais retirée.
        $this->actingAs($this->guichet->user)
            ->get(route('backoffice.inscriptions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('students.0.id', $student->id)
                ->where('students.0.statut', 'Archivé')
                ->where('students.0.archiveDateRetour', now()->addMonth()->format('d/m/Y')));
    }

    public function test_archiving_needs_the_dedicated_permission_and_stays_within_centre_reach(): void
    {
        $student = $this->etudiant();

        $sansDroit = User::factory()->create();
        $sansDroit->givePermissionTo(['students.view', 'students.update']);

        $this->actingAs($sansDroit)
            ->post(route('backoffice.students.archive', $student), ['motif' => 'x', 'date_retour' => now()->addMonth()->toDateString()])
            ->assertForbidden();

        // Un autre centre : le droit ne suffit pas, la portée « Centres affectés » décide.
        $casa = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);
        $ailleurs = Employee::factory()->create(['etablissement_id' => $casa->id]);
        $ailleurs->user->forceFill(['must_change_password' => false])->save();
        $ailleurs->user->givePermissionTo(['students.view', 'students.archive']);

        $this->actingAs($ailleurs->user->fresh())
            ->post(route('backoffice.students.archive', $student), ['motif' => 'x', 'date_retour' => now()->addMonth()->toDateString()])
            ->assertForbidden();

        $this->assertSame(Student::STATUT_ACTIF, $student->fresh()->statut);
    }

    public function test_the_archives_tab_follows_the_active_centre(): void
    {
        $casa = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);
        $admin = User::factory()->create();
        $admin->assignRole(Role::SUPER_ADMIN);

        $rabat = $this->etudiant('RABAT');
        $casab = Student::factory()->create(['etablissement_id' => $casa->id, 'nom' => 'CASA', 'prenom' => 'Ali']);
        foreach ([$rabat, $casab] as $s) {
            Student::query()->whereKey($s->id)->update([
                'statut' => Student::STATUT_ARCHIVE, 'archive_motif' => 'm', 'archive_date_retour' => now()->addWeek()->toDateString(),
            ]);
        }

        $this->actingAs($admin);
        app(CurrentContext::class)->setEtablissement($casa->id);

        $this->get(route('backoffice.students.archives'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('students.data', 1)
                ->where('students.data.0.id', $casab->id)
                ->where('tabCounts./backoffice/students/archives', 1));
    }
}
