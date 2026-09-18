<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Reports;

use App\Domain\Reports\Actions\GetDashboardStats;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La carte « Dépenses ce mois-ci » compte le MÊME argent que la liste
 * Dépenses vers laquelle elle pointe (18/09/2026).
 *
 * Elle affichait 0,00 MAD alors que des dépenses approuvées du mois
 * existaient, pour deux raisons indépendantes — chacune suffisant à vider la
 * carte, et aucune des deux ne s'appliquant à la liste :
 *
 *  1. la fenêtre d'année scolaire était ajoutée EN PLUS de la fenêtre du
 *     mois. Sur une année déjà clôturée, les deux ne se recouvrent jamais,
 *     donc la carte ne peut afficher que 0,00 — alors que « ce mois-ci » ne
 *     dépend d'aucune année ;
 *  2. la ventilation par centre était appelée sans `$avecSansCentre`, donc
 *     une dépense dont aucun centre n'est résoluble (colonne nulle ET caisse
 *     sans centre — la caisse centrale, un coffre « Externe ») disparaissait
 *     de la carte. La liste, elle, passe `true` et les affiche partout.
 *
 * Deux écrans du même argent qui se contredisent : l'utilisateur ne peut
 * plus savoir lequel croire (§11).
 */
final class DashboardDepensesMoisTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    private TypeDepense $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $this->type = TypeDepense::create(['nom' => 'Fournitures', 'statut' => TypeDepense::STATUT_ACTIF]);
    }

    private function annee(string $debut, string $fin): AnneeScolaire
    {
        return AnneeScolaire::create([
            'nom' => substr($debut, 0, 4).'/'.substr($fin, 0, 4),
            'date_debut' => $debut, 'date_fin' => $fin,
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
    }

    private function contexte(AnneeScolaire $annee, ?Etablissement $centre): CurrentContext
    {
        $user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->rabat->id]);
        $this->actingAs($user->fresh());

        $context = app(CurrentContext::class);
        $context->setAnneeScolaire($annee->id);
        $context->setEtablissement($centre?->id);

        return $context;
    }

    private function depense(?int $centreId, ?int $caisseCentreId, string $montant = '250.00'): Depense
    {
        $caisse = Caisse::factory()->create(['etablissement_id' => $caisseCentreId]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);

        return Depense::create([
            'reference' => 'DEP-'.fake()->unique()->numerify('###'),
            'type_depense_id' => $this->type->id,
            'caisse_id' => $caisse->id,
            'etablissement_id' => $centreId,
            'montant' => $montant,
            'methode_paiement' => 'Espèces',
            'date_depense' => now()->startOfMonth()->addDays(2)->toDateString(),
            'description' => 'Cartouches',
            'agent_id' => $agent->id,
            'statut' => Depense::STATUT_APPROUVEE,
        ]);
    }

    public function test_the_card_counts_this_month_even_when_the_active_year_is_closed(): void
    {
        // Année déjà clôturée : elle ne recouvre pas le mois en cours.
        $annee = $this->annee('2025-09-01', now()->subMonths(2)->toDateString());
        $this->depense($this->rabat->id, $this->rabat->id, '250.00');

        $stats = app(GetDashboardStats::class)($this->contexte($annee, $this->rabat));

        $this->assertSame(250.0, $stats->depensesMonth);
        $this->assertSame(1, $stats->depensesMonthCount);
    }

    public function test_the_card_counts_a_depense_no_centre_can_claim(): void
    {
        $annee = $this->annee(now()->startOfMonth()->subMonth()->toDateString(), now()->addYear()->toDateString());
        // Ni colonne centre, ni centre sur la caisse (caisse centrale) : la
        // liste Dépenses l'affiche dans tous les centres, la carte l'ignorait.
        $this->depense(null, null, '400.00');

        $stats = app(GetDashboardStats::class)($this->contexte($annee, $this->rabat));

        $this->assertSame(400.0, $stats->depensesMonth);
        $this->assertSame(1, $stats->depensesMonthCount);
    }

    public function test_a_depense_of_another_centre_is_still_excluded(): void
    {
        $autre = Etablissement::factory()->create(['nom_centre' => 'GLS Agadir']);
        $annee = $this->annee(now()->startOfMonth()->subMonth()->toDateString(), now()->addYear()->toDateString());
        $this->depense($autre->id, $autre->id, '900.00');

        $stats = app(GetDashboardStats::class)($this->contexte($annee, $this->rabat));

        $this->assertSame(0.0, $stats->depensesMonth);
        $this->assertSame(0, $stats->depensesMonthCount);
    }

    public function test_only_approved_money_is_counted(): void
    {
        $annee = $this->annee(now()->startOfMonth()->subMonth()->toDateString(), now()->addYear()->toDateString());
        $this->depense($this->rabat->id, $this->rabat->id, '250.00');
        $this->depense($this->rabat->id, $this->rabat->id, '999.00')->update(['statut' => Depense::STATUT_EN_ATTENTE]);

        $stats = app(GetDashboardStats::class)($this->contexte($annee, $this->rabat));

        $this->assertSame(250.0, $stats->depensesMonth);
    }
}
