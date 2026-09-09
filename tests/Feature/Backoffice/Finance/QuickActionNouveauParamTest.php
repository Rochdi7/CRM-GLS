<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le raccourci « Encaisser un paiement » du tableau de bord ouvre la liste
 * ET son modal, via `?nouveau=1` (Hooks/useAutoOpenCreate.ts).
 *
 * ⚠ Ce paramètre n'est PAS un filtre. La liste des encaissements redirige la
 * toute première visite « nue » vers son URL canonique portant la fenêtre du
 * jour (date-filter-defaults-canonical-url) ; sans exclusion explicite,
 * `?nouveau=1` faisait passer la visite pour filtrée et le caissier arrivait
 * sur une liste SANS fenêtre de dates, alors que le lien de la barre latérale
 * en recevait une. Le raccourci doit donner exactement la même liste que le
 * menu — seul le modal s'ouvre en plus.
 */
final class QuickActionNouveauParamTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        Etablissement::factory()->create();

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    public function test_the_quick_action_still_gets_the_default_date_window(): void
    {
        $user = $this->superAdmin();
        $today = now()->toDateString();

        $response = $this->actingAs($user)->get('/backoffice/encaissements?nouveau=1');

        // Même redirection que la visite nue, le paramètre du modal en plus.
        $response->assertRedirect(route('backoffice.encaissements.index', [
            'dateFrom' => $today,
            'dateTo' => $today,
            'nouveau' => '1',
        ]));
    }

    public function test_a_real_filter_still_suppresses_the_default_window(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)
            ->get('/backoffice/encaissements?nouveau=1&search=rochdi')
            ->assertOk();
    }
}
