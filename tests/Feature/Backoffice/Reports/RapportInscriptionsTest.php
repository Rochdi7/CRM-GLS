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
 * Gestion des rapports — « Liste des inscriptions » : la page, ses filtres, et
 * les deux téléchargements (PDF / Excel).
 *
 * Le point le plus important couvert ici est que les trois sorties partent de
 * la MÊME requête Domain : le compteur affiché à l'écran et le contenu du
 * document ne peuvent pas raconter deux choses différentes.
 */
final class RapportInscriptionsTest extends TestCase
{
    use RefreshDatabase;

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

    private function enroll(
        string $prenom,
        string $dateInscription,
        string $statut = Inscription::STATUT_ACTIVE,
        ?Group $group = null,
    ): Inscription {
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id,
            'prenom' => $prenom,
            'telephone' => '+212600000000',
        ]);

        return Inscription::create([
            'reference' => 'INS-RAP-'.$student->id,
            'student_id' => $student->id,
            'group_id' => ($group ?? $this->group)->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => $statut,
            'date_inscription' => $dateInscription,
        ]);
    }

    /** @return array<string, string> */
    private function window(): array
    {
        return ['dateFrom' => '2025-09-01', 'dateTo' => '2026-08-31'];
    }

    public function test_it_requires_the_reports_permission(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertForbidden();
    }

    public function test_downloads_require_the_reports_permission_too(): void
    {
        $user = $this->userWith('dashboard.view');

        $this->actingAs($user)->get(route('backoffice.rapports.pdf', $this->window()))->assertForbidden();
        $this->actingAs($user)->get(route('backoffice.rapports.excel', $this->window()))->assertForbidden();
    }

    /**
     * « Accessible à tous par défaut » décrit le point de DÉPART des rôles, pas
     * un droit câblé en dur : révoquer `reports.view` doit réellement fermer
     * l'écran. Sans cette vérification, « par défaut pour tous » pourrait
     * dériver vers « pour tous, quoi qu'on fasse » — et l'écran des
     * permissions ne piloterait plus rien.
     */
    public function test_revoking_the_permission_actually_closes_the_screen(): void
    {
        $user = $this->userWith('reports.view');

        $this->actingAs($user)
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk();

        $user->revokePermissionTo('reports.view');

        $this->actingAs($user->fresh())
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertForbidden();
    }

    /**
     * Tout rôle livré ouvre l'écran sans réglage préalable (demande métier).
     * Le pendant côté registre est
     * RolesAndPermissionsSeederTest::test_every_role_can_open_the_reports_screen ;
     * ici on vérifie la vraie requête HTTP, pas seulement la matrice.
     */
    public function test_every_seeded_role_reaches_the_screen_out_of_the_box(): void
    {
        foreach (array_keys(\App\Support\Authorization\PermissionRegistry::roles()) as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            // La portée passe par « Centres affectés », JAMAIS par une
            // permission (CLAUDE.md §16 : `centers.access-all` n'est
            // attribuable à personne). On rattache donc l'employé au centre,
            // comme le ferait la fiche employé.
            $employee = \App\Models\Employee::factory()->create([
                'user_id' => $user->id,
                'etablissement_id' => $this->centre->id,
            ]);
            $employee->syncEtablissements([$this->centre->id]);

            $this->actingAs($user->fresh())
                ->get(route('backoffice.rapports.index', $this->window()))
                ->assertOk("Le rôle {$role} doit pouvoir ouvrir Gestion des rapports.");
        }
    }

    public function test_a_bare_visit_redirects_to_the_canonical_url_with_a_default_window(): void
    {
        // La fenêtre par défaut est posée par UNE redirection, pas en douce à
        // chaque requête : sinon vider les dates puis changer un filtre
        // réappliquerait le défaut (CLAUDE.md §5).
        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index'))
            ->assertRedirect();
    }

    public function test_it_renders_the_page_with_its_tabs_and_row_count(): void
    {
        $this->enroll('Alice', '2025-10-01');
        $this->enroll('Bob', '2025-10-02');

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Rapports/Index')
                ->where('nombreLignes', 2)
                // Le catalogue porte les sept domaines visés ; la page n'en
                // dessine plus d'onglets et n'expose au sélecteur que ceux qui
                // ont un rapport.
                ->count('onglets', 7)
            );
    }

    public function test_the_date_window_bounds_the_report(): void
    {
        $this->enroll('Dedans', '2025-10-15');
        $this->enroll('Avant', '2025-09-02');
        $this->enroll('Apres', '2025-12-20');

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', ['dateFrom' => '2025-10-01', 'dateTo' => '2025-10-31']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nombreLignes', 1));
    }

    public function test_the_group_filter_is_optional_and_narrows_when_given(): void
    {
        $autre = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->enroll('Ici', '2025-10-01');
        $this->enroll('Ailleurs', '2025-10-01', Inscription::STATUT_ACTIVE, $autre);

        $user = $this->userWith('reports.view');

        // Sans groupe : tout le monde (c'est la demande métier — le filtre est
        // facultatif, et vide il ne retire rien).
        $this->actingAs($user)
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nombreLignes', 2));

        // Avec groupe : ce groupe seulement.
        $this->actingAs($user)
            ->get(route('backoffice.rapports.index', [...$this->window(), 'groupFilter' => (string) $autre->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nombreLignes', 1));
    }

    public function test_the_status_filter_narrows_the_report(): void
    {
        $this->enroll('Active', '2025-10-01');
        $this->enroll('Annulee', '2025-10-01', Inscription::STATUT_ANNULEE);

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', [...$this->window(), 'statutFilter' => Inscription::STATUT_ANNULEE]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nombreLignes', 1));
    }

    public function test_a_forged_status_falls_back_to_no_filter_rather_than_reaching_the_query(): void
    {
        $this->enroll('Alice', '2025-10-01');

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', [...$this->window(), 'statutFilter' => "'; DROP TABLE"]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('nombreLignes', 1)
                ->where('filters.statutFilter', '')
            );
    }

    public function test_it_downloads_a_pdf(): void
    {
        $this->enroll('Alice', '2025-10-01');

        $response = $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.pdf', $this->window()))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Un vrai PDF, pas une page d'erreur rendue avec le bon en-tête.
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_it_downloads_an_excel_workbook(): void
    {
        $this->enroll('Alice', '2025-10-01');

        $response = $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.excel', $this->window()))
            ->assertOk();

        // .xlsx = une archive zip : « PK » est sa signature.
        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    public function test_an_empty_report_still_produces_a_document(): void
    {
        // Aucune inscription dans la fenêtre : le document doit sortir en
        // disant qu'il est vide, et surtout ne pas planter — c'est le cas
        // qu'un utilisateur rencontre en se trompant de période.
        $response = $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.pdf', ['dateFrom' => '2025-09-01', 'dateTo' => '2025-09-02']))
            ->assertOk();

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_a_report_never_reaches_past_the_readers_own_centres(): void
    {
        // Le rapport n'a AUCUN privilège de lecture : il imprime ce que
        // l'utilisateur pouvait déjà consulter. Un lecteur rattaché à un seul
        // centre ne doit pas voir les inscriptions d'un autre.
        $autreCentre = Etablissement::factory()->create();
        $autreGroupe = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $autreCentre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->enroll('Chez moi', '2025-10-01');

        $student = Student::factory()->create(['etablissement_id' => $autreCentre->id, 'prenom' => 'Ailleurs']);
        Inscription::create([
            'reference' => 'INS-RAP-AUTRE',
            'student_id' => $student->id,
            'group_id' => $autreGroupe->id,
            'etablissement_id' => $autreCentre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-10-01',
        ]);

        // Un utilisateur SANS centers.access-all, rattaché au seul centre local.
        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');
        $employee = \App\Models\Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centre->id,
        ]);
        $employee->syncEtablissements([$this->centre->id]);

        $this->actingAs($user->fresh())
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('nombreLignes', 1));
    }
}
