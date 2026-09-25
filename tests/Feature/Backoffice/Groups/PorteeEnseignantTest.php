<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Models\AnneeScolaire;
use App\Models\Creneau;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use App\Support\Authorization\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Portée enseignant (24/09/2026, PorteeEnseignant) : un prof voit SES
 * groupes, leurs étudiants, leurs séances et leur emploi du temps — jamais
 * ceux d'un collègue du même centre — et ne crée ni ne modifie rien d'autre
 * que l'appel.
 */
final class PorteeEnseignantTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Employee $moi;

    private Employee $collegue;

    private Group $monGroupe;

    private Group $groupeCollegue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();

        $this->moi = $this->prof('Driss');
        $this->collegue = $this->prof('Nizar');
        $this->monGroupe = $this->groupe($this->moi, 'DRISS 19H');
        $this->groupeCollegue = $this->groupe($this->collegue, 'NIZAR 13H');
    }

    private function prof(string $prenom): Employee
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->assignRole('teacher');

        return Employee::factory()->create([
            'prenom' => $prenom,
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'etablissement_id' => $this->centre->id,
            'user_id' => $user->id,
        ]);
    }

    private function groupe(Employee $prof, string $nom): Group
    {
        return Group::factory()->create([
            'nom' => $nom,
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'enseignant_id' => $prof->id,
            'date_debut_formation' => '2025-09-01',
        ]);
    }

    private function inscrire(Group $groupe, string $nom): Student
    {
        $student = Student::factory()->create(['nom' => $nom, 'etablissement_id' => $this->centre->id]);
        Inscription::create([
            'reference' => 'INS-'.$nom,
            'student_id' => $student->id,
            'group_id' => $groupe->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-09-10',
        ]);

        return $student;
    }

    private function seance(Group $groupe, Employee $prof, string $date = '2026-03-02'): Seance
    {
        return Seance::create([
            'group_id' => $groupe->id, 'date_seance' => $date, 'enseignant_id' => $prof->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Seance::STATUT_PREVUE,
        ]);
    }

    #[Test]
    public function the_teacher_preset_holds_no_front_office_or_money_permission(): void
    {
        $this->assertEqualsCanonicalizing(
            ['dashboard.view', 'dashboard.espace-enseignant', 'groups.view-own', 'attendance.view', 'attendance.mark'],
            PermissionRegistry::matrix()['teacher'],
        );
    }

    #[Test]
    public function the_groups_list_shows_only_the_teachers_own_groups_without_amounts(): void
    {
        Creneau::create(['group_id' => $this->monGroupe->id, 'jour_semaine' => 1, 'heure_debut' => '19:00', 'heure_fin' => '21:00']);

        // Sa propre vue en cartes, consultation seule — jamais la table
        // d'administration et ses modals de création.
        $this->actingAs($this->moi->user)
            ->get('/backoffice/groups')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Groups/MesGroupes')
                ->has('groups.data', 1)
                ->where('groups.data.0.nom', 'DRISS 19H')
                ->where('groups.data.0.fraisLignes', [])
                ->where('groups.data.0.emploiDuTemps.0.jour', 'Lundi')
                ->where('groups.data.0.emploiDuTemps.0.heureDebut', '19:00')
                ->missing('enseignants')
                ->missing('fraisCatalog'));
    }

    #[Test]
    public function the_roster_modal_opens_only_on_his_own_groups(): void
    {
        $this->inscrire($this->monGroupe, 'ALAMI');
        $this->inscrire($this->groupeCollegue, 'BENNANI');

        $this->actingAs($this->moi->user)
            ->getJson(route('backoffice.groups.students-by-segment', ['group' => $this->monGroupe, 'segment' => 'active']))
            ->assertOk()
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.nom', 'ALAMI');

        $this->actingAs($this->moi->user)
            ->getJson(route('backoffice.groups.students-by-segment', ['group' => $this->groupeCollegue, 'segment' => 'active']))
            ->assertForbidden();
    }

    #[Test]
    public function a_teacher_opens_his_group_but_never_a_colleagues(): void
    {
        $this->actingAs($this->moi->user)->get(route('backoffice.groups.show', $this->monGroupe))->assertOk();
        $this->actingAs($this->moi->user)->get(route('backoffice.groups.show', $this->groupeCollegue))->assertForbidden();
    }

    #[Test]
    public function a_group_where_he_holds_an_open_slot_is_his_too(): void
    {
        // Deux profs sur un même groupe, un par jour : le créneau suffit.
        Creneau::create([
            'group_id' => $this->groupeCollegue->id, 'jour_semaine' => 3,
            'heure_debut' => '10:00', 'heure_fin' => '12:00', 'enseignant_id' => $this->moi->id,
        ]);

        $this->actingAs($this->moi->user)->get(route('backoffice.groups.show', $this->groupeCollegue))->assertOk();
    }

    #[Test]
    public function the_students_list_shows_only_students_of_his_groups_and_no_crud(): void
    {
        $mien = $this->inscrire($this->monGroupe, 'ALAMI');
        $autre = $this->inscrire($this->groupeCollegue, 'BENNANI');

        $this->actingAs($this->moi->user)
            ->get('/backoffice/students')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                // Sa vue en cartes, consultation seule, ligne RÉDUITE : ni CIN,
                // ni adresse, ni données du parent.
                ->component('Backoffice/Students/MesEtudiants')
                ->has('students.data', 1)
                ->where('students.data.0.id', $mien->id)
                ->where('students.data.0.groupes.0.nom', 'DRISS 19H')
                ->missing('students.data.0.cin')
                ->missing('students.data.0.adresse')
                ->missing('students.data.0.parentTelephone')
                ->has('groupOptions', 1));

        // Le filtre « Groupe » ne sort jamais un étudiant d'un groupe voisin.
        $this->actingAs($this->moi->user)
            ->get('/backoffice/students?groupeFilter='.$this->groupeCollegue->id)
            ->assertInertia(fn (Assert $page) => $page->has('students.data', 0));

        // La fiche étudiant porte les paiements : jamais pour un prof.
        $this->actingAs($this->moi->user)->get(route('backoffice.students.show', $mien))->assertForbidden();
        $this->actingAs($this->moi->user)->get(route('backoffice.students.show', $autre))->assertForbidden();
    }

    #[Test]
    public function a_teacher_cannot_create_a_student_a_registration_or_a_group(): void
    {
        $this->actingAs($this->moi->user)->post(route('backoffice.students.store'), [])->assertForbidden();
        $this->actingAs($this->moi->user)->post(route('backoffice.inscriptions.store'), [])->assertForbidden();
        $this->actingAs($this->moi->user)->post(route('backoffice.groups.store'), [])->assertForbidden();
        $this->actingAs($this->moi->user)->get('/backoffice/inscriptions')->assertForbidden();
    }

    #[Test]
    public function seances_are_limited_to_his_groups_and_his_replacements(): void
    {
        $mienne = $this->seance($this->monGroupe, $this->moi);
        $collegue = $this->seance($this->groupeCollegue, $this->collegue);
        $remplacement = $this->seance($this->groupeCollegue, $this->moi, '2026-03-03');

        $this->actingAs($this->moi->user)
            ->get('/backoffice/seances')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Seances/MesSeances')
                ->has('seances.data', 2));

        $this->actingAs($this->moi->user)->get(route('backoffice.seances.show', $mienne))->assertOk();
        $this->actingAs($this->moi->user)->get(route('backoffice.seances.show', $remplacement))->assertOk();
        $this->actingAs($this->moi->user)->get(route('backoffice.seances.show', $collegue))->assertForbidden();
        $eleve = $this->inscrire($this->groupeCollegue, 'CHERKAOUI');
        $this->actingAs($this->moi->user)
            ->put(route('backoffice.seances.presences.update', $collegue), [
                'presences' => [$eleve->id => ['statut' => Presence::STATUT_ABSENT, 'note' => '']],
            ])
            ->assertForbidden();
    }

    #[Test]
    public function absence_par_groupe_offers_only_his_groups_and_refuses_a_colleagues(): void
    {
        $this->inscrire($this->groupeCollegue, 'BENNANI');

        $this->actingAs($this->moi->user)
            ->get('/backoffice/seances/absence-par-groupe')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('groupOptions', 1)
                ->where('groupOptions.0.value', $this->monGroupe->id));

        // Un id forgé dans l'URL ne sort pas la liste d'un groupe voisin.
        $this->actingAs($this->moi->user)
            ->get('/backoffice/seances/absence-par-groupe?dateFrom=&dateTo=&groupFilter='.$this->groupeCollegue->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('matrice.students', []));
    }

    #[Test]
    public function a_teacher_validates_his_seance_but_never_cancels_it(): void
    {
        $seance = $this->seance($this->monGroupe, $this->moi);

        $this->actingAs($this->moi->user)
            ->get('/backoffice/seances')
            ->assertInertia(fn (Assert $page) => $page
                // Aucune action de ligne : la vue enseignant ne reçoit même pas
                // les motifs d'annulation.
                ->component('Backoffice/Seances/MesSeances')
                ->missing('permissions')
                ->missing('motifsAnnulation'));

        $this->assertTrue($this->moi->user->can('validate', $seance));
        $this->assertFalse($this->moi->user->can('cancel', $seance));
        $this->actingAs($this->moi->user)
            ->post(route('backoffice.seances.annuler', $seance), ['motif' => 'Test'])
            ->assertForbidden();
    }

    #[Test]
    public function the_timetable_shows_only_his_slots(): void
    {
        Creneau::create(['group_id' => $this->monGroupe->id, 'jour_semaine' => 1, 'heure_debut' => '19:00', 'heure_fin' => '21:00']);
        Creneau::create(['group_id' => $this->groupeCollegue->id, 'jour_semaine' => 2, 'heure_debut' => '13:00', 'heure_fin' => '15:00']);

        $this->actingAs($this->moi->user)
            ->get('/backoffice/emploi-du-temps')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('creneaux', 1)
                ->where('creneaux.0.groupNom', 'DRISS 19H')
                ->has('groupOptions', 1)
                ->has('enseignantOptions', 1));
    }

    #[Test]
    public function the_dashboard_serves_no_centre_figures_to_a_teacher(): void
    {
        $this->seance($this->monGroupe, $this->moi, now()->toDateString());
        $this->seance($this->groupeCollegue, $this->collegue, now()->toDateString());

        $this->actingAs($this->moi->user)
            ->get('/backoffice/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('porteeEnseignant', true)
                ->where('nouvellesInscriptions', null)
                ->where('annualFrais', null)
                ->where('stats.studentsTotal', 0)
                ->where('stats.paymentsMonth', '0.00')
                ->has('seancesCalendar.days.'.now()->toDateString(), 1));
    }

    #[Test]
    public function a_user_holding_groups_view_is_never_restricted(): void
    {
        $directeur = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $directeur->assignRole('director');
        Employee::factory()->create([
            'categorie' => Employee::CATEGORIE_DIRECTEUR,
            'etablissement_id' => $this->centre->id,
            'user_id' => $directeur->id,
        ]);

        $this->actingAs($directeur)
            ->get('/backoffice/groups')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Groups/Index')
                ->has('groups.data', 2)
                ->where('groups.data.0.emploiDuTemps', []));
    }
}
