<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Reports;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Gestion des rapports — « Liste des étudiants » : la page, ses filtres, et les
 * deux téléchargements (PDF / Excel).
 *
 * Même exigence que RapportInscriptionsTest : les trois sorties partent de la
 * MÊME requête Domain, donc le compteur affiché et le document téléchargé ne
 * peuvent pas raconter deux choses différentes.
 *
 * Le test le plus important du fichier est
 * test_the_year_switcher_never_filters_the_students_report : un étudiant ne
 * porte pas d'année (CLAUDE.md §11), et un rapport qui en filtrerait un
 * ferait disparaître des gens bien réels du document.
 */
final class RapportEtudiantsTest extends TestCase
{
    use RefreshDatabase;

    private const CLE = 'liste-etudiants';

    /** Compteur de références d'inscription, pour rester dans varchar(20). */
    private static int $refSeq = 0;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();

        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    /**
     * Une fiche étudiante créée à une date donnée. `created_at` est la colonne
     * que la fenêtre de dates du rapport borne, donc les tests la posent
     * explicitement plutôt que de dépendre de l'instant du test.
     */
    private function student(string $prenom, string $creeLe, ?string $sexe = 'Homme'): Student
    {
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id,
            'prenom' => $prenom,
            'sexe' => $sexe,
            'telephone' => '+212600000000',
        ]);

        $student->forceFill(['created_at' => $creeLe])->save();

        return $student->fresh();
    }

    private function enroll(Student $student, string $statut = Inscription::STATUT_ACTIVE, ?Group $group = null): Inscription
    {
        return Inscription::create([
            // `inscriptions.reference` est un varchar(20) : la référence de
            // test doit y tenir, d'où le compteur court plutôt qu'un uniqid().
            'reference' => 'INS-RAPE-'.$student->id.'-'.(++self::$refSeq),
            'student_id' => $student->id,
            'group_id' => ($group ?? $this->group)->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => ($group ?? $this->group)->annee_scolaire_id,
            'statut' => $statut,
            'date_inscription' => '2025-10-01',
        ]);
    }

    /** @return array<string, string> */
    private function params(array $extra = []): array
    {
        return ['rapport' => self::CLE, 'dateFrom' => '2025-09-01', 'dateTo' => '2026-08-31', ...$extra];
    }

    public function test_it_requires_the_reports_permission(): void
    {
        $user = $this->userWith('dashboard.view');

        $this->actingAs($user)->get(route('backoffice.rapports.index', $this->params()))->assertForbidden();
        $this->actingAs($user)->get(route('backoffice.rapports.pdf', $this->params()))->assertForbidden();
        $this->actingAs($user)->get(route('backoffice.rapports.excel', $this->params()))->assertForbidden();
    }

    public function test_the_report_is_offered_by_the_server_catalogue(): void
    {
        // Le sélecteur ne peut proposer que des rapports RÉELLEMENT servis :
        // la page lit le catalogue serveur au lieu de les écrire en dur.
        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->params()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Rapports/Index')
                ->where('filters.rapport', self::CLE)
                ->where('onglets.0.rapports.1.value', self::CLE)
            );
    }

    /**
     * La page ne dessine QUE les filtres que la requête Domain applique. Un
     * filtre inerte (« Groupe » sur un rapport d'étudiants) laisserait croire à
     * l'utilisateur qu'il a restreint son document alors qu'il ne l'a pas fait.
     */
    public function test_the_page_only_shows_the_filters_this_report_actually_applies(): void
    {
        $user = $this->userWith('reports.view');

        $this->actingAs($user)
            ->get(route('backoffice.rapports.index', $this->params()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filtresVisibles', ['sexeFilter', 'inscriptionFilter'])
            );

        // L'autre rapport garde les siens : le catalogue les distingue.
        $this->actingAs($user)
            ->get(route('backoffice.rapports.index', ['rapport' => 'liste-inscriptions', 'dateFrom' => '2025-09-01', 'dateTo' => '2026-08-31']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filtresVisibles', ['groupFilter', 'statutFilter'])
            );
    }

    public function test_it_counts_the_students_of_the_window(): void
    {
        $this->student('Alice', '2025-10-01 09:00:00');
        $this->student('Bob', '2025-10-02 09:00:00');

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->params()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nombreLignes', 2));
    }

    public function test_the_date_window_bounds_the_report(): void
    {
        $this->student('Dedans', '2025-10-15 09:00:00');
        $this->student('Avant', '2025-09-02 09:00:00');
        $this->student('Apres', '2025-12-20 09:00:00');

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->params(['dateFrom' => '2025-10-01', 'dateTo' => '2025-10-31'])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nombreLignes', 1));
    }

    /**
     * `created_at` est un TIMESTAMP : une fiche créée en cours de journée est
     * postérieure à « <jour> 00:00:00 ». Comparer en `<= dateTo` la ferait
     * tomber hors du rapport alors que l'utilisateur a demandé ce jour-là —
     * c'est le bug classique de la borne haute sur un timestamp.
     */
    public function test_the_last_day_of_the_window_is_included_whole(): void
    {
        $this->student('Fin de journee', '2025-10-31 23:30:00');

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->params(['dateFrom' => '2025-10-01', 'dateTo' => '2025-10-31'])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nombreLignes', 1));
    }

    public function test_the_gender_filter_is_optional_and_narrows_when_given(): void
    {
        $this->student('Homme', '2025-10-01 09:00:00', 'Homme');
        $this->student('Femme', '2025-10-01 09:00:00', 'Femme');

        $user = $this->userWith('reports.view');

        $this->actingAs($user)
            ->get(route('backoffice.rapports.index', $this->params()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nombreLignes', 2));

        $this->actingAs($user)
            ->get(route('backoffice.rapports.index', $this->params(['sexeFilter' => 'Femme'])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nombreLignes', 1));
    }

    /** Les trois états du filtre, avec les MÊMES clés machine que la liste Étudiants. */
    public function test_the_registration_state_filter_covers_its_three_states(): void
    {
        $actif = $this->student('Actif', '2025-10-01 09:00:00');
        $this->enroll($actif);

        $annule = $this->student('Annule', '2025-10-01 09:00:00');
        $this->enroll($annule, Inscription::STATUT_ANNULEE);

        $this->student('Sans', '2025-10-01 09:00:00');

        $user = $this->userWith('reports.view');

        foreach ([['active', 1], ['cancelled', 1], ['none', 1], ['', 3]] as [$etat, $attendu]) {
            $this->actingAs($user)
                ->get(route('backoffice.rapports.index', $this->params($etat === '' ? [] : ['inscriptionFilter' => $etat])))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->where('nombreLignes', $attendu));
        }
    }

    /**
     * ⚠ Le test qui protège la règle la plus facile à casser : un étudiant ne
     * porte PAS d'année scolaire, seules ses inscriptions en portent une
     * (CLAUDE.md §11). Basculer le sélecteur d'année ne doit RIEN retirer du
     * rapport — sinon des étudiants bien réels disparaissent d'un document
     * qu'on signe et qu'on tamponne.
     */
    public function test_the_year_switcher_never_filters_the_students_report(): void
    {
        $autreAnnee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);

        // Un étudiant inscrit UNIQUEMENT en 2025/2026...
        $ancien = $this->student('Ancien', '2025-10-01 09:00:00');
        $this->enroll($ancien);

        // ...et un étudiant sans aucune inscription.
        $this->student('Jamais inscrit', '2025-10-02 09:00:00');

        $user = $this->userWith('reports.view');

        // Le sélecteur du haut bascule sur l'AUTRE année.
        $this->actingAs($user)->post(route('backoffice.context.update'), [
            'annee_scolaire_id' => $autreAnnee->id,
            'etablissement_id' => $this->centre->id,
        ]);

        // Les deux étudiants sont toujours là : l'année n'a rien retiré.
        $this->actingAs($user)
            ->get(route('backoffice.rapports.index', $this->params()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nombreLignes', 2));
    }

    public function test_a_forged_filter_falls_back_to_no_filter_rather_than_reaching_the_query(): void
    {
        $this->student('Alice', '2025-10-01 09:00:00');

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->params([
                'sexeFilter' => "'; DROP TABLE",
                'inscriptionFilter' => 'tout',
            ])))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('nombreLignes', 1)
                ->where('filters.sexeFilter', '')
                ->where('filters.inscriptionFilter', '')
            );
    }

    public function test_it_downloads_a_pdf(): void
    {
        $this->student('Alice', '2025-10-01 09:00:00');

        $response = $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.pdf', $this->params()))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_it_downloads_an_excel_workbook(): void
    {
        $this->student('Alice', '2025-10-01 09:00:00');

        $response = $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.excel', $this->params()))
            ->assertOk();

        // .xlsx = une archive zip : « PK » est sa signature.
        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    public function test_an_empty_report_still_produces_a_document(): void
    {
        $response = $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.pdf', $this->params(['dateFrom' => '2025-09-01', 'dateTo' => '2025-09-02'])))
            ->assertOk();

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /**
     * Un rapport n'a AUCUN privilège de lecture : il imprime ce que
     * l'utilisateur pouvait déjà consulter dans la liste Étudiants.
     */
    public function test_a_report_never_reaches_past_the_readers_own_centres(): void
    {
        $autreCentre = Etablissement::factory()->create();

        $this->student('Chez moi', '2025-10-01 09:00:00');

        $ailleurs = Student::factory()->create(['etablissement_id' => $autreCentre->id, 'prenom' => 'Ailleurs']);
        $ailleurs->forceFill(['created_at' => '2025-10-01 09:00:00'])->save();

        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');
        $employee = \App\Models\Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centre->id,
        ]);
        $employee->syncEtablissements([$this->centre->id]);

        $this->actingAs($user->fresh())
            ->get(route('backoffice.rapports.index', $this->params()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nombreLignes', 1));
    }
}
