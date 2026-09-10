<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Remboursement;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Support\Authorization\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * ⚠ Choisir la caisse DÉBITÉE par un remboursement est une permission
 * (`refunds.choose-till`, 10/09/2026) — directeur + super-admin.
 *
 * Le champ « Caisse à débiter » listait TOUTES les caisses espèces du centre
 * actif, avec leur solde, à quiconque tenait `refunds.create`. Une assistante
 * administrative pouvait donc rendre 300 DH depuis le tiroir d'un collègue :
 * le solde de quelqu'un d'autre baissait sans qu'il ait ouvert son tiroir, et
 * son comptage de fin de journée tombait faux sans explication. Elle rend
 * l'argent qu'elle a physiquement en main, donc sa propre caisse — dérivée au
 * serveur, jamais choisie.
 *
 * Trois bornes indissociables :
 *  (1) un `caisse_id` étranger soumis SANS le droit est REFUSÉ (422), jamais
 *      ignoré en silence — accepter puis débiter ailleurs ferait mentir
 *      l'écran ;
 *  (2) la LISTE des caisses n'est même pas servie sans le droit (elle
 *      divulguait les soldes des collègues) ;
 *  (3) le directeur garde le choix : c'est un arbitrage, pas un geste de
 *      guichet.
 */
final class RemboursementCaisseChoisieTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function agent(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function collegueTill(): Caisse
    {
        // Un employé possède exactement UNE caisse « Caissière »
        // (caisses_une_caissiere_par_employe, §11) : on prend la sienne.
        $collegue = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $till = $collegue->till()->firstOrFail();
        $till->update(['solde' => 5000]);

        return $till;
    }

    public function test_an_administrative_assistant_cannot_debit_a_colleagues_till(): void
    {
        $user = $this->agent(['refunds.view', 'refunds.create']);
        $this->actingAs($user);

        $propre = $user->employee->till()->firstOrFail();
        $propre->update(['solde' => 1000]);
        $autre = $this->collegueTill();

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $autre->id,
            'montant' => '300',
            'date_remboursement' => now()->toDateString(),
        ])->assertSessionHasErrors('caisse_id');

        // Refusé, donc RIEN n'a bougé : ni la caisse visée, ni la sienne.
        $this->assertSame('5000.00', (string) $autre->fresh()->solde);
        $this->assertSame('1000.00', (string) $propre->fresh()->solde);
        $this->assertSame(0, Remboursement::query()->count());
    }

    /**
     * Le cas normal du front-office : pas de champ, pas de caisse_id, et
     * l'argent sort bien de SA caisse.
     */
    public function test_without_the_permission_the_refund_debits_the_agents_own_till(): void
    {
        $user = $this->agent(['refunds.view', 'refunds.create']);
        $this->actingAs($user);

        $propre = $user->employee->till()->firstOrFail();
        $propre->update(['solde' => 1000]);
        $autre = $this->collegueTill();

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'montant' => '300',
            'date_remboursement' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('700.00', (string) $propre->fresh()->solde);
        $this->assertSame('5000.00', (string) $autre->fresh()->solde);
        $this->assertSame($propre->id, Remboursement::query()->firstOrFail()->caisse_id);
    }

    /**
     * Sa PROPRE caisse envoyée explicitement reste acceptée — c'est ce que le
     * formulaire pré-remplit, et la refuser casserait l'écran.
     */
    public function test_submitting_ones_own_till_stays_accepted(): void
    {
        $user = $this->agent(['refunds.view', 'refunds.create']);
        $this->actingAs($user);

        $propre = $user->employee->till()->firstOrFail();
        $propre->update(['solde' => 1000]);

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $propre->id,
            'montant' => '300',
            'date_remboursement' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('700.00', (string) $propre->fresh()->solde);
    }

    public function test_a_director_may_debit_another_till(): void
    {
        $user = $this->agent(['refunds.view', 'refunds.create', 'refunds.choose-till']);
        $this->actingAs($user);

        $propre = $user->employee->till()->firstOrFail();
        $propre->update(['solde' => 1000]);
        $autre = $this->collegueTill();

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $autre->id,
            'montant' => '300',
            'date_remboursement' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('4700.00', (string) $autre->fresh()->solde);
        $this->assertSame('1000.00', (string) $propre->fresh()->solde);
    }

    /**
     * L'écran ne propose que ce que le serveur accepte : sans le droit, la
     * liste est VIDE (elle divulguait le solde des collègues) et la caisse
     * par défaut est nommée à la place.
     */
    public function test_the_till_options_are_only_served_to_holders(): void
    {
        $this->actingAs($this->agent(['refunds.view', 'refunds.create']))
            ->get(route('backoffice.depenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canChooseRemboursementCaisse', false)
                ->where('remboursementCaisses', [])
                ->has('remboursementCaisseParDefaut')
            );

        $this->actingAs($this->agent(['refunds.view', 'refunds.create', 'refunds.choose-till']))
            ->get(route('backoffice.depenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canChooseRemboursementCaisse', true)
                // Au moins sa propre caisse : la liste n'est plus vide.
                ->has('remboursementCaisses.0')
            );
    }

    /**
     * ⚠ Une caisse ne se débite jamais au-delà de ce qu'elle contient
     * (10/09/2026) — la règle qu'une dépense portait déjà depuis le
     * 09/09/2026, sur EXACTEMENT la même caisse physique. Sans elle, rendre
     * 5 000 DH depuis un tiroir qui en contenait 300 laissait la caisse à
     * -4 700,00 DH.
     */
    public function test_a_refund_cannot_exceed_the_tills_balance(): void
    {
        $user = $this->agent(['refunds.view', 'refunds.create']);
        $this->actingAs($user);

        $propre = $user->employee->till()->firstOrFail();
        $propre->update(['solde' => 300]);

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'montant' => '5000',
            'date_remboursement' => now()->toDateString(),
        ])->assertSessionHasErrors('montant');

        $this->assertSame('300.00', (string) $propre->fresh()->solde);
        $this->assertSame(0, Remboursement::query()->count());
    }

    /**
     * Vider un tiroir jusqu'à 0,00 reste légitime — même borne que
     * ValiderTransfertCaisse et ApprouverDepense : `montant <= solde`.
     */
    public function test_a_refund_may_empty_the_till_exactly(): void
    {
        $user = $this->agent(['refunds.view', 'refunds.create']);
        $this->actingAs($user);

        $propre = $user->employee->till()->firstOrFail();
        $propre->update(['solde' => 300]);

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'montant' => '300',
            'date_remboursement' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('0.00', (string) $propre->fresh()->solde);
    }

    /**
     * ⚠ La borne du solde ne vaut QUE pour une caisse physique.
     *
     * Un compte de MÉTHODE (TPE / Chèque / Virement) n'est pas un tiroir :
     * c'est le compte du centre pour cette méthode, et le seul remboursement
     * qui l'atteint est la contrepassation d'un chèque REJETÉ. Cet argent n'a
     * jamais existé — la banque l'a refusé — donc le compte peut légitimement
     * ne rien contenir au moment où on l'annule. Y appliquer la borne du
     * tiroir bloquerait le remède même du chèque en bois.
     *
     * Ce test le prouve par le TYPE, sans rejouer tout le cycle du chèque :
     * `AvanceApplicabilityTest` et `ComptesMethodeTest` couvrent le parcours
     * complet.
     */
    public function test_the_balance_guard_applies_to_physical_tills_only(): void
    {
        $user = $this->agent(['refunds.view', 'refunds.create']);
        $propre = $user->employee->till()->firstOrFail();

        $this->assertTrue($propre->isEspeces(), 'La caisse d\'un employé est un tiroir physique.');

        $compte = Caisse::query()
            ->where('etablissement_id', $this->centre->id)
            ->whereIn('type', Caisse::TYPES_METHODE)
            ->firstOrFail();

        $this->assertFalse(
            $compte->isEspeces(),
            'Un compte de méthode n\'est pas un tiroir : la borne du solde ne doit pas s\'y appliquer.',
        );
    }

    /**
     * Le droit est porté par le SEUL rôle `director` (+ le bypass
     * super-admin) : l'y ajouter ailleurs doit rester une décision explicite.
     */
    public function test_only_the_director_role_holds_the_permission(): void
    {
        $porteurs = [];

        foreach (array_keys(PermissionRegistry::roles()) as $name) {
            if ($name === 'super-admin') {
                continue;
            }

            if (Role::findByName($name)->hasPermissionTo('refunds.choose-till')) {
                $porteurs[] = $name;
            }
        }

        $this->assertSame(['director'], $porteurs);
    }
}
