<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Expenses\Queries\GetDepensesList;
use App\Domain\Finance\Support\VentilationCentre;
use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Models\Activity;
use App\Models\AnneeScolaire;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Context\CurrentContext;
use App\Support\Settings\AppSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une dépense appartient au centre où elle a été SAISIE (18/09/2026).
 *
 * Une employée n'a qu'UNE caisse à vie, rattachée à son centre principal,
 * mais travaille dans plusieurs centres (§11). `depenses` ne portait pas de
 * centre : celui d'une dépense ordinaire retombait sur
 * `caisses.etablissement_id`, si bien qu'une dépense saisie sur GLS Online
 * était listée — et sa part de caisse débitée — sur le centre principal, et
 * restait invisible (donc non approuvable) depuis Online.
 *
 * La caisse DÉBITÉE ne change pas : c'est toujours le tiroir physique de
 * l'agent (un seul par employé). C'est l'IMPUTATION qui suit le centre actif.
 */
final class DepenseCentreActifTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    private Etablissement $online;

    private TypeDepense $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, true);
        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $this->online = Etablissement::factory()->create(['nom_centre' => 'GLS Online']);
        $this->type = TypeDepense::create(['nom' => 'Fournitures', 'statut' => TypeDepense::STATUT_ACTIF]);
    }

    /** Principal = Rabat (sa caisse y est rattachée), affectée aussi à Online. */
    private function cashier(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['expenses.view', 'expenses.create']);

        $employee = Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->rabat->id]);
        $employee->syncEtablissements([$this->rabat->id, $this->online->id], $this->rabat->id);

        return $user->fresh();
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->rabat->id]);

        return $user->fresh();
    }

    /** @return array<string, mixed> */
    private function payload(string $montant = '200.00'): array
    {
        return [
            'type_depense_id' => $this->type->id,
            'montant' => $montant,
            'methode_paiement' => 'Espèces',
            'date_depense' => '2026-09-18',
            'description' => 'Cartouches',
        ];
    }

    /** @return list<string> */
    private function referencesListees(User $user): array
    {
        return collect(app(GetDepensesList::class)($user)['data']->items())->pluck('reference')->all();
    }

    public function test_a_depense_keyed_from_online_belongs_to_online_not_to_the_primary_centre(): void
    {
        $user = $this->cashier();
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->online->id);

        $this->post(route('backoffice.depenses.store'), $this->payload())->assertSessionHasNoErrors();

        $depense = Depense::query()->sole();
        $till = $user->employee->till()->firstOrFail();

        // L'imputation suit le centre ACTIF…
        $this->assertSame($this->online->id, $depense->etablissement_id);
        $this->assertSame($this->online->id, $depense->centreId());
        // …mais l'argent sort toujours du SEUL tiroir de l'agent, qui reste
        // rattaché à Rabat : un profil ou un contexte ne déplace pas de caisse.
        $this->assertSame($till->id, $depense->caisse_id);
        $this->assertSame($this->rabat->id, $till->etablissement_id);

        // Visible là où elle a été saisie, et nulle part ailleurs.
        $this->assertSame([$depense->reference], $this->referencesListees($user));

        app(CurrentContext::class)->setEtablissement($this->rabat->id);
        $this->assertSame([], $this->referencesListees($user));
    }

    public function test_the_centre_is_never_taken_from_client_input(): void
    {
        $this->actingAs($this->cashier());
        app(CurrentContext::class)->setEtablissement($this->online->id);

        $this->post(route('backoffice.depenses.store'), [
            ...$this->payload(),
            'etablissement_id' => $this->rabat->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($this->online->id, Depense::query()->sole()->etablissement_id);
    }

    public function test_it_is_approved_from_online_and_debits_onlines_share_of_the_till(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier);
        app(CurrentContext::class)->setEtablissement($this->online->id);
        $this->post(route('backoffice.depenses.store'), $this->payload('200.00'))->assertSessionHasNoErrors();

        $depense = Depense::query()->sole();
        $till = $cashier->employee->till()->firstOrFail();
        $till->update(['solde' => '500.00']);

        // Depuis Rabat elle n'appartient pas au centre actif : refusée.
        $this->actingAs($this->superAdmin());
        app(CurrentContext::class)->setEtablissement($this->rabat->id);
        $this->put(route('backoffice.depenses.approve', $depense))->assertSessionHasErrors();
        $this->assertTrue($depense->fresh()->isEnAttente());

        // Depuis Online — l'écran où elle est listée — elle s'approuve.
        app(CurrentContext::class)->setEtablissement($this->online->id);
        $this->put(route('backoffice.depenses.approve', $depense))->assertSessionHasNoErrors();

        $this->assertSame(Depense::STATUT_APPROUVEE, $depense->fresh()->statut);
        $this->assertSame('300.00', (string) $till->fresh()->solde);

        // Le journal porte Online, pas le centre principal du créateur.
        $stamp = Activity::query()->where('log_name', 'caisse')->where('event', 'solde_movement')
            ->latest('id')->firstOrFail()->properties['etablissement_id'] ?? null;
        $this->assertSame($this->online->id, $stamp);

        // Et la ventilation du tiroir débite la part d'Online, pas celle de Rabat.
        $ventilation = app(VentilationCentre::class);
        $this->assertSame(200.0, $ventilation->depensesDuCentre($till->id, $this->online->id));
        $this->assertSame(0.0, $ventilation->depensesDuCentre($till->id, $this->rabat->id));
    }

    public function test_a_row_older_than_the_column_still_reads_through_its_till(): void
    {
        $user = $this->cashier();
        $till = $user->employee->till()->firstOrFail();

        $legacy = Depense::create([
            'reference' => 'DEP-00001',
            'type_depense_id' => $this->type->id,
            'caisse_id' => $till->id,
            'agent_id' => $user->employee->id,
            'montant' => '150.00',
            'date_depense' => '2026-09-10',
            'statut' => Depense::STATUT_APPROUVEE,
            'description' => 'Avant la colonne',
        ]);

        $this->assertNull($legacy->etablissement_id);
        $this->assertSame($this->rabat->id, $legacy->centreId());

        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->rabat->id);
        $this->assertSame(['DEP-00001'], $this->referencesListees($user));

        app(CurrentContext::class)->setEtablissement($this->online->id);
        $this->assertSame([], $this->referencesListees($user));

        $ventilation = app(VentilationCentre::class);
        $this->assertSame(150.0, $ventilation->depensesDuCentre($till->id, $this->rabat->id));
        $this->assertSame(0.0, $ventilation->depensesDuCentre($till->id, $this->online->id));
    }

    public function test_an_employee_does_not_see_a_depense_of_a_centre_they_are_not_assigned_to(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier);
        app(CurrentContext::class)->setEtablissement($this->online->id);
        $this->post(route('backoffice.depenses.store'), $this->payload())->assertSessionHasNoErrors();

        // Affectée à Rabat SEULEMENT : le tiroir débité est bien rattaché à
        // Rabat, mais la dépense est une dépense d'Online.
        $other = User::factory()->create();
        $other->givePermissionTo(['expenses.view']);
        Employee::factory()->create(['user_id' => $other->id, 'etablissement_id' => $this->rabat->id]);
        $other = $other->fresh();

        $this->actingAs($other);
        app(CurrentContext::class)->setEtablissement($this->rabat->id);

        $this->assertSame([], $this->referencesListees($other));
        $this->get(route('backoffice.depenses.show', Depense::query()->sole()))->assertForbidden();
    }
    // ── Le solde contrôlé est celui du CENTRE, pas du tiroir entier ─────

    /** 500 DH encaissés pour Rabat + 100 DH pour Online dans le MÊME tiroir. */
    private function alimenter(Employee $agent): void
    {
        foreach ([[$this->rabat, 500.0], [$this->online, 100.0]] as [$centre, $montant]) {
            app(EnregistrerEncaissement::class)->handle([
                'student_id' => Student::factory()->create(['etablissement_id' => $centre->id])->id,
                'inscription_fee_id' => null,
                'montant' => $montant,
                'methode' => Encaissement::METHODE_ESPECES,
                'date_paiement' => '2026-09-18',
                'caisse_id' => $agent->till()->firstOrFail()->id,
            ], $agent);
        }
    }

    public function test_a_depense_cannot_spend_another_centres_share_of_the_same_till(): void
    {
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, false);
        $user = $this->cashier();
        $this->actingAs($user);
        $this->alimenter($user->employee);
        $till = $user->employee->till()->firstOrFail();
        $this->assertSame('600.00', (string) $till->fresh()->solde);

        // Sur Online le tiroir contient 600 DH, mais Online n'en possède que 100.
        app(CurrentContext::class)->setEtablissement($this->online->id);
        $this->post(route('backoffice.depenses.store'), $this->payload('200.00'))
            ->assertSessionHasErrors('montant');
        $this->assertStringContainsString('GLS Online', session('errors')->first('montant'));
        $this->assertSame(0, Depense::query()->count());
        $this->assertSame('600.00', (string) $till->fresh()->solde);

        // Vider la part d'Online jusqu'à 0,00 reste légitime.
        $this->post(route('backoffice.depenses.store'), $this->payload('100.00'))->assertSessionHasNoErrors();

        // Rabat dépense SA part, intacte.
        app(CurrentContext::class)->setEtablissement($this->rabat->id);
        $this->post(route('backoffice.depenses.store'), $this->payload('500.00'))->assertSessionHasNoErrors();

        $this->assertSame('0.00', (string) $till->fresh()->solde);
        $ventilation = app(VentilationCentre::class);
        $this->assertSame(0.0, $ventilation->soldeDuCentre($till->fresh(), $this->online->id));
        $this->assertSame(0.0, $ventilation->soldeDuCentre($till->fresh(), $this->rabat->id));
    }

    public function test_a_super_admin_is_bound_by_the_active_centres_share_too(): void
    {
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, false);
        $admin = $this->superAdmin();
        $this->actingAs($admin);
        $this->alimenter($admin->employee);

        app(CurrentContext::class)->setEtablissement($this->online->id);
        $this->post(route('backoffice.depenses.store'), $this->payload('200.00'))
            ->assertSessionHasErrors('montant');

        $this->post(route('backoffice.depenses.store'), $this->payload('100.00'))->assertSessionHasNoErrors();
        $this->assertSame($this->online->id, Depense::query()->sole()->etablissement_id);
    }

    public function test_approval_is_bound_by_the_share_of_the_centre_the_depense_was_keyed_in(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier);
        $this->alimenter($cashier->employee);
        app(CurrentContext::class)->setEtablissement($this->online->id);
        $this->post(route('backoffice.depenses.store'), $this->payload('200.00'))->assertSessionHasNoErrors();
        $depense = Depense::query()->sole();

        $this->actingAs($this->superAdmin());
        app(CurrentContext::class)->setEtablissement($this->online->id);
        $this->put(route('backoffice.depenses.approve', $depense))->assertSessionHasErrors('statut');

        $this->assertTrue($depense->fresh()->isEnAttente());
        $this->assertSame('600.00', (string) $cashier->employee->till()->firstOrFail()->solde);
    }

    public function test_the_modal_shows_the_active_centres_share_not_the_whole_till(): void
    {
        $user = $this->cashier();
        $this->actingAs($user);
        $this->alimenter($user->employee);
        app(CurrentContext::class)->setEtablissement($this->online->id);

        $this->get(route('backoffice.depenses.index'))
            ->assertInertia(fn ($page) => $page
                ->where('soldeActuel', '100.00')
                ->where('soldeTiroir', '600.00')
                ->where('soldeVentileParCentre', true));
    }
}