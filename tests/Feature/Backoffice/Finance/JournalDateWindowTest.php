<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Support\CaisseLedger;
use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F1 — « Journal de caisse » : les deux dates vidées à la main arrivent au
 * serveur comme '' + '', et GetCaisseJournal y substitue SILENCIEUSEMENT la
 * fenêtre de l'année active. Effacer un filtre RÉDUIT alors les lignes au
 * lieu de les élargir (§5). Les trois autres écrans finance ont reçu un
 * `$dateFilterEngaged` le 14/09/2026 ; celui-ci n'en a pas, et son endpoint
 * JSON ne passe pas par le correctif `queryStringResolver`.
 *
 * Mesuré sur les LIGNES, pas sur un champ d'écho : la réponse ne renvoie pas
 * la fenêtre effective.
 */
final class JournalDateWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_clearing_both_dates_lists_rows_from_previous_years(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        // Année ACTIVE = 2026/2027 ; le mouvement ci-dessous est en 2025/2026.
        AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-26',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-08-27', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $centre = Etablissement::factory()->create();
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $employee = Employee::factory()->create([
            'user_id' => $user->id, 'etablissement_id' => $centre->id,
        ]);

        $till = $employee->till()->first();

        // Une dépense APPROUVÉE datée de l'année PRÉCÉDENTE — le journal ne
        // compte que les lignes qui ont bougé la caisse.
        $depense = \App\Models\Depense::query()->create([
            'reference' => 'DEP-90001',
            'type_depense_id' => \App\Models\TypeDepense::create([
                'nom' => 'Fournitures', 'is_system' => false,
                'statut' => \App\Models\TypeDepense::STATUT_ACTIF,
            ])->id,
            'caisse_id' => $till->id,
            'montant' => '25.00',
            'statut' => \App\Models\Depense::STATUT_APPROUVEE,
            'date_depense' => '2026-01-15',
            'description' => 'Ligne année précédente',
            'agent_id' => $employee->id,
        ]);

        DB::table('caisses')->where('id', $till->id)->update(['solde' => '1000.00']);

        $rowsCleared = $this->actingAs($user->fresh())
            ->get(route('backoffice.caisses.journal', [
                'scope' => 'mine', 'dateFrom' => '', 'dateTo' => '',
            ]))->assertOk()->json('rows');

        $refs = collect($rowsCleared)->pluck('reference')->filter()->all();

        $this->assertContains(
            $depense->reference,
            $refs,
            'Les deux dates effacées, le journal a réarmé la fenêtre de '
            .'l\'année active et masqué un mouvement de l\'année précédente : '
            .'effacer un filtre doit ÉLARGIR (§5).',
        );
    }
}
