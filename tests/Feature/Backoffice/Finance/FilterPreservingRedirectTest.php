<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A money mutation must send the user back to the list THEY WERE LOOKING AT,
 * filters intact (Concerns\RedirectsPreservingFilters, 30/08/2026).
 *
 * The reported symptom: a cashier narrows the Avances tab to one student,
 * applies an avance, and lands back on an unfiltered page 1 — so the filter
 * had to be re-entered after every single application. Filters are only ever
 * cleared by the explicit "Réinitialiser les filtres" button now, never as a
 * side effect of a write.
 */
final class FilterPreservingRedirectTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
    }

    public function test_applying_an_avance_returns_to_the_list_with_its_filters(): void
    {
        [$user, $avance, $fee] = $this->avanceReadyToApply();

        $filtered = route('backoffice.encaissements.index', [
            'view' => 'avance',
            'studentFilter' => (string) $avance->student_id,
            'soldeFilter' => 'tous',
        ]);

        $response = $this->actingAs($user)
            ->from($filtered)
            ->post(route('backoffice.avances.apply', $avance), [
                'inscription_id' => $fee->inscription_id,
                'fee_id' => $fee->id,
                'montant' => '200',
            ]);

        $target = $response->headers->get('Location');

        $this->assertStringContainsString('view=avance', (string) $target);
        $this->assertStringContainsString('studentFilter='.$avance->student_id, (string) $target);
        $this->assertStringContainsString('soldeFilter=tous', (string) $target);
    }

    public function test_a_direct_post_without_a_referer_still_lands_on_the_avances_tab(): void
    {
        [$user, $avance, $fee] = $this->avanceReadyToApply();

        $response = $this->actingAs($user)->post(route('backoffice.avances.apply', $avance), [
            'inscription_id' => $fee->inscription_id,
            'fee_id' => $fee->id,
            'montant' => '200',
        ]);

        $response->assertRedirect(route('backoffice.encaissements.index', ['view' => 'avance']));
    }

    /**
     * A referer pointing at another host is attacker-controlled input — the
     * redirect must fall back to the named route rather than send the user
     * off-site.
     */
    public function test_an_offsite_referer_is_ignored(): void
    {
        [$user, $avance, $fee] = $this->avanceReadyToApply();

        $response = $this->actingAs($user)
            ->from('https://evil.example.com/backoffice/encaissements?view=avance')
            ->post(route('backoffice.avances.apply', $avance), [
                'inscription_id' => $fee->inscription_id,
                'fee_id' => $fee->id,
                'montant' => '200',
            ]);

        $response->assertRedirect(route('backoffice.encaissements.index', ['view' => 'avance']));
    }

    /**
     * The row can move after a write (a fully-used avance leaves the default
     * « Disponibles » view), so page 7 of a now-shorter list would be blank.
     */
    public function test_the_page_number_is_not_carried_over(): void
    {
        [$user, $avance, $fee] = $this->avanceReadyToApply();

        $response = $this->actingAs($user)
            ->from(route('backoffice.encaissements.index', ['view' => 'avance', 'page' => 7]))
            ->post(route('backoffice.avances.apply', $avance), [
                'inscription_id' => $fee->inscription_id,
                'fee_id' => $fee->id,
                'montant' => '200',
            ]);

        $this->assertStringNotContainsString('page=7', (string) $response->headers->get('Location'));
    }


    /**
     * Clearing a filter must only ever WIDEN a result set. An avance dated
     * outside the active year used to vanish from the Encaissements tab the
     * moment both date fields were cleared, because the year window was
     * applied to it whenever the AVANCES TAB was not the one open — so a
     * student showing 5 200 MAD dropped to 0.00 MAD (30/08/2026).
     */
    public function test_clearing_the_dates_keeps_avances_dated_outside_the_active_year(): void
    {
        [$user, $avance, $fee] = $this->avanceReadyToApply();

        // Dated in the PREVIOUS academic year — outside 2026/2027's window.
        $avance->update(['date_paiement' => '2026-01-12']);

        // '-' is the page's explicit "cleared" marker for a date field.
        $response = $this->actingAs($user)->get(route('backoffice.encaissements.index', [
            'dateFrom' => '-',
            'dateTo' => '-',
            'studentFilter' => (string) $avance->student_id,
        ]));

        $response->assertOk();

        $references = collect($response->viewData('page')['props']['encaissements']['data'])
            ->pluck('reference');

        $this->assertTrue(
            $references->contains($avance->reference),
            'An unallocated avance must stay listed whatever its date.',
        );
    }

    /**
     * Builds a cashier working in the active context plus one avance of 500
     * for a student who has an unpaid 1 000 fee to apply it to.
     *
     * @return array{0: User, 1: Encaissement, 2: InscriptionFee}
     */
    private function avanceReadyToApply(): array
    {
        $user = User::factory()->create();
        foreach (['payments.view', 'payments.create', 'payments.update', 'centers.access-all'] as $permission) {
            $user->givePermissionTo($permission);
        }
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centre->id,
        ]);
        $user = $user->fresh();

        session([
            'context.annee_scolaire_id' => $this->annee->id,
            'context.etablissement_id' => $this->centre->id,
        ]);

        // The EmployeeObserver already provisions the cashier's till; fall
        // back to creating one so the fixture does not depend on that.
        $caisse = $employee->till ?? Caisse::create([
            'nom' => 'Caisse test',
            'type' => Caisse::TYPE_CAISSIERE,
            'responsable_employee_id' => $employee->id,
            'etablissement_id' => $this->centre->id,
            'solde' => 0,
            'statut' => Caisse::STATUT_ACTIF,
        ]);

        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2026-09-15',
            'montant_total' => 1000,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'nom' => 'Frais de scolarité',
            'montant_initial' => 1000,
            'montant' => 1000,
            'date_echeance' => '2026-10-31',
            'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        // An avance is a payment attached to a student but to no fee yet.
        $avance = Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id,
            'inscription_fee_id' => null,
            'montant' => 500,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-09-20',
            'caisse_id' => $caisse->id,
            'agent_id' => $employee->id,
        ]);

        return [$user, $avance, $fee];
    }
}
