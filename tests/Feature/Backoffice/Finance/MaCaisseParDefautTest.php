<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « Ma caisse » — le filtre Caisse de Gestion des paiements s'ouvre sur la
 * caisse de l'employé connecté (09/09/2026).
 *
 * Un caissier qui ouvre l'écran regarde l'argent passé par SES mains ; le
 * faire choisir son propre nom dans une liste de sept caisses à chaque visite
 * était la friction que l'ancien CRM évitait déjà avec son « Ma caisse » par
 * défaut.
 *
 * Le défaut suit le patron des fenêtres de dates de cette page : il est posé
 * par la redirection vers l'URL CANONIQUE d'une visite nue, jamais par un
 * `has('caisseFilter') ? … : maCaisse` — sinon vider le filtre le remettrait
 * aussitôt et la caisse ne serait plus jamais « toutes ».
 */
final class MaCaisseParDefautTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $this->centre = Etablissement::factory()->create();
    }

    private function caissier(): User
    {
        $user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centre->id,
            'categorie' => Employee::CATEGORIE_DIRECTEUR,
        ]);

        // Comme a l'ecran : le selecteur du bandeau est sur un centre. Le
        // filtre Caisse n'offre que les caisses joignables depuis le centre
        // actif, donc le defaut ne peut se poser que la.
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        return $user->fresh();
    }

    public function test_a_bare_visit_lands_on_the_users_own_till(): void
    {
        $user = $this->caissier();
        $till = $user->employee->till()->firstOrFail();
        $today = now()->toDateString();

        $this->actingAs($user)
            ->get('/backoffice/encaissements')
            ->assertRedirect(route('backoffice.encaissements.index', [
                'dateFrom' => $today,
                'dateTo' => $today,
                'caisseFilter' => $till->id,
            ]));
    }

    public function test_the_page_echoes_the_default_so_the_reset_button_can_restore_it(): void
    {
        $user = $this->caissier();
        $till = $user->employee->till()->firstOrFail();

        $this->actingAs($user)
            ->get('/backoffice/encaissements?dateFrom=-&dateTo=-')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Backoffice/Encaissements/Index')
                ->where('defaultCaisseId', $till->id));
    }

    public function test_clearing_the_filter_widens_back_to_every_till(): void
    {
        $user = $this->caissier();

        // Une visite portant déjà des filtres n'est plus « nue » : le défaut
        // ne se réinjecte pas, sinon le filtre serait invidable.
        $this->actingAs($user)
            ->get('/backoffice/encaissements?dateFrom=-&dateTo=-&caisseFilter=')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('filters.caisseFilter', ''));
    }

    public function test_an_account_without_a_till_still_opens_on_every_caisse(): void
    {
        // Un super-admin sans fiche employé n'a pas de caisse : la page
        // s'ouvre exactement comme avant, sans `caisseFilter` dans l'URL.
        $user = User::factory()->create()->assignRole('super-admin');
        $today = now()->toDateString();

        $this->actingAs($user)
            ->get('/backoffice/encaissements')
            ->assertRedirect(route('backoffice.encaissements.index', [
                'dateFrom' => $today,
                'dateTo' => $today,
            ]));
    }
}
