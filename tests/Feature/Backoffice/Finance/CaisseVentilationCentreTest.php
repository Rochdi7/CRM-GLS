<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Queries\GetCaisseJournal;
use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Models\Activity;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * « Ma caisse » suit le sélecteur de centre (04/09/2026).
 *
 * Signalé sur la prod : la caisse de Latifa Abou Elfath, étiquetée GLS
 * Marrakech, portait 6 200 DH encaissés à Marrakech + 4 100 DH encaissés sur
 * GLS Online — et l'onglet affichait les MÊMES 10 300 DH et les MÊMES lignes
 * quel que soit le centre choisi en haut. `caisseIds()` retournait directement
 * les caisses de l'employé pour le scope 'mine', sans jamais consulter le
 * contexte : le switcher ne pilotait rien.
 *
 * Le correctif ne filtre PAS sur `caisses.etablissement_id` : cela ferait
 * basculer la caisse EN BLOC (10 300 DH à Marrakech dont 4 100 qui n'y sont
 * pas, 0,00 DH sur Online où 4 100 DH dorment). Il ventile depuis le ledger,
 * dont chaque écriture estampille son centre (§11 « Centre dimension on the
 * ledger »), en gardant `caisses.solde` comme autorité : la somme des parts
 * doit retomber dessus.
 */
final class CaisseVentilationCentreTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $marrakech;

    private Etablissement $online;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->marrakech = Etablissement::factory()->create(['nom_centre' => 'GLS Marrakech']);
        $this->online = Etablissement::factory()->create(['nom_centre' => 'GLS Online']);
    }

    private function cash(Employee $agent, Etablissement $centre, float $montant): Encaissement
    {
        return app(EnregistrerEncaissement::class)->handle([
            'student_id' => Student::factory()->create(['etablissement_id' => $centre->id])->id,
            'inscription_fee_id' => null,
            'montant' => $montant,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-09-01',
            'caisse_id' => $agent->till()->firstOrFail()->id,
        ], $agent);
    }

    /** Rejoue le scénario Latifa : une caisse Marrakech qui encaisse aussi pour Online. */
    private function latifa(): Employee
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $agent = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->marrakech->id,
        ]);
        $agent->syncEtablissements([$this->marrakech->id, $this->online->id]);

        $this->cash($agent, $this->marrakech, 6200.0);
        $this->cash($agent, $this->online, 4100.0);

        return $agent->fresh();
    }

    /**
     * `CurrentContext` lit le centre actif en session ET vérifie la portée de
     * l'utilisateur connecté : sans `actingAs`, il ne résout aucun centre et
     * la ventilation ne s'applique pas.
     *
     * @return array<string, mixed>
     */
    private function journalFor(Employee $agent, ?int $centreId): array
    {
        $user = $agent->user->fresh();
        $this->actingAs($user);

        app()->forgetInstance(CurrentContext::class);
        app(CurrentContext::class)->setEtablissement($centreId);

        return app(GetCaisseJournal::class)($user, 'mine', '', '', '', 1);
    }

    public function test_le_solde_suit_le_centre_actif_au_lieu_de_basculer_la_caisse_en_bloc(): void
    {
        $agent = $this->latifa();
        $till = $agent->till()->firstOrFail();

        // L'autorité ne bouge pas : une seule caisse, un seul solde stocké.
        $this->assertSame(1, Caisse::query()->where('responsable_employee_id', $agent->id)->count());
        $this->assertSame('10300.00', (string) $till->fresh()->solde);

        // Marrakech ne voit QUE sa part — pas les 4 100 DH d'Online.
        $this->assertSame('6200.00', $this->journalFor($agent, $this->marrakech->id)['solde']);

        // Online voit ses 4 100 DH, là où l'écran affichait 10 300 DH avant.
        $this->assertSame('4100.00', $this->journalFor($agent, $this->online->id)['solde']);
    }

    public function test_la_somme_des_parts_par_centre_retombe_sur_le_solde_stocke(): void
    {
        $agent = $this->latifa();

        $parts = collect([$this->marrakech->id, $this->online->id])
            ->sum(fn (int $id): float => (float) $this->journalFor($agent, $id)['solde']);

        // C'est l'invariant : ventiler ne crée ni ne perd d'argent.
        $this->assertSame((float) $agent->till()->firstOrFail()->solde, $parts);
    }

    public function test_les_lignes_du_journal_suivent_aussi_le_centre(): void
    {
        $agent = $this->latifa();

        $marrakech = $this->journalFor($agent, $this->marrakech->id);
        $this->assertCount(1, $marrakech['rows']);
        $this->assertSame('6200.00', $marrakech['rows']->first()['montant']);
        $this->assertSame('GLS Marrakech', $marrakech['rows']->first()['centre']);

        $online = $this->journalFor($agent, $this->online->id);
        $this->assertCount(1, $online['rows']);
        $this->assertSame('4100.00', $online['rows']->first()['montant']);
        $this->assertSame('GLS Online', $online['rows']->first()['centre']);
    }

    public function test_le_kpi_encaissements_suit_le_centre(): void
    {
        $agent = $this->latifa();

        $this->assertSame('6200.00', $this->journalFor($agent, $this->marrakech->id)['totalEncaissements']);
        $this->assertSame('4100.00', $this->journalFor($agent, $this->online->id)['totalEncaissements']);
    }

    public function test_tous_les_centres_rend_la_caisse_entiere(): void
    {
        $agent = $this->latifa();

        // Super-admin sur « Tous les centres » : rien n'est ventilé, donc rien
        // n'est masqué — le solde entier reste lisible.
        $journal = $this->journalFor($agent, null);

        $this->assertSame('10300.00', $journal['solde']);
        $this->assertCount(2, $journal['rows']);
    }

    public function test_une_ecriture_historique_sans_centre_est_rattachee_au_centre_de_la_caisse(): void
    {
        $agent = $this->latifa();
        $till = $agent->till()->firstOrFail();

        // Simule les 33 642 écritures antérieures à la règle du 01/09/2026 :
        // la clé manque. §11 impose un fallback de LECTURE, jamais un backfill.
        Activity::query()
            ->where('subject_type', Caisse::class)
            ->where('subject_id', $till->id)
            ->get()
            ->each(function (Activity $entry): void {
                $props = $entry->properties->all();
                unset($props['etablissement_id']);
                // Écriture directe : le modèle Activity refuse tout update.
                DB::table('activity_log')
                    ->where('id', $entry->id)
                    ->update(['properties' => json_encode($props)]);
            });

        // Tout retombe sur le centre de rattachement de la caisse (Marrakech),
        // et rien n'est perdu.
        $this->assertSame('10300.00', $this->journalFor($agent, $this->marrakech->id)['solde']);
        $this->assertSame('0.00', $this->journalFor($agent, $this->online->id)['solde']);
    }
}
