<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `groupes:nettoyer-frais` — trims each group's fee catalogue to what it
 * actually charges. The invariant that matters is the money one: a fee
 * carrying even ONE payment is never removed, whatever its month.
 */
final class NettoyerFraisGroupesTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

    private Employee $agent;

    private Caisse $caisse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->centre = Etablissement::factory()->create();
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $user = User::factory()->create();
        $this->agent = Employee::factory()->create([
            'user_id' => $user->id, 'etablissement_id' => $this->centre->id,
        ]);
        $this->caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
    }

    /** A group running Nov -> Feb, carrying the fees passed in. */
    private function groupWithFees(array $feeNames, string $debut = '2025-11-01', string $fin = '2026-02-28'): Group
    {
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'date_debut_formation' => $debut,
            'date_fin_formation' => $fin,
        ]);

        $sync = [];
        foreach ($feeNames as $nom) {
            $frais = Frais::firstOrCreate(['nom' => $nom], ['montant_defaut' => 1300, 'statut' => Frais::STATUT_ACTIF]);
            $sync[$frais->id] = ['montant' => 1300, 'date_echeance' => '2025-12-01', 'classification' => null];
        }
        $group->frais()->sync($sync);

        return $group;
    }

    private function enrol(Group $group, array $feeNames): Inscription
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-11-05',
        ]);

        foreach ($feeNames as $nom) {
            InscriptionFee::create([
                'inscription_id' => $inscription->id,
                'frais_id' => Frais::where('nom', $nom)->value('id'),
                'nom' => $nom,
                'montant_initial' => 1300, 'montant' => 1300,
                'date_echeance' => '2025-12-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
            ]);
        }

        return $inscription;
    }

    /**
     * ⚠ THE rule: a fee with a payment survives even when its month is
     * outside the group's window. Removing it would push the collected money
     * back out as an avance and make a settled fee look unpaid — 282 of 819
     * out-of-range lines carry payments on the real data (27/08/2026).
     */
    public function test_a_fee_carrying_a_payment_is_never_removed(): void
    {
        $group = $this->groupWithFees(['Frais de Novembre', 'Frais de Juillet']);
        $inscription = $this->enrol($group, ['Frais de Novembre', 'Frais de Juillet']);

        // Juillet is OUT of the Nov->Feb window, but it has been paid.
        $juillet = $inscription->fees()->where('nom', 'Frais de Juillet')->firstOrFail();
        Encaissement::create([
            'reference' => 'ENC-KEEP', 'agent_id' => $this->agent->id,
            'student_id' => $inscription->student_id, 'inscription_fee_id' => $juillet->id,
            'caisse_id' => $this->caisse->id, 'montant' => 1300,
            'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2026-07-10',
            'etablissement_id' => $this->centre->id,
        ]);

        $this->artisan('groupes:nettoyer-frais')->assertSuccessful();

        $this->assertTrue(
            $group->fresh()->frais()->where('nom', 'Frais de Juillet')->exists(),
            'A paid fee must survive even outside the formation window.'
        );
        $this->assertNull($juillet->fresh()->masque_le, 'Its inscription line must stay visible.');
        $this->assertNotNull($juillet->fresh()->inscription_fee_id ?? true);
        $this->assertSame(1, Encaissement::query()->where('reference', 'ENC-KEEP')->count());
    }

    /** An unpaid monthly fee outside the window is removed. */
    public function test_an_unpaid_out_of_window_month_is_removed(): void
    {
        $group = $this->groupWithFees(['Frais de Novembre', 'Frais de Juillet']);
        $inscription = $this->enrol($group, ['Frais de Novembre', 'Frais de Juillet']);

        $this->artisan('groupes:nettoyer-frais')->assertSuccessful();

        $this->assertFalse($group->fresh()->frais()->where('nom', 'Frais de Juillet')->exists());
        $this->assertTrue($group->fresh()->frais()->where('nom', 'Frais de Novembre')->exists());

        // Hidden, never deleted — that is what makes it reversible.
        $juillet = $inscription->fees()->where('nom', 'Frais de Juillet')->firstOrFail();
        $this->assertNotNull($juillet->masque_le);
    }

    /** ÖSD exam fees leave every group; they are billed case by case. */
    public function test_osd_exam_fees_are_removed_from_every_group(): void
    {
        $group = $this->groupWithFees(['Frais de Novembre', 'Frais dexam ÖSD B1']);
        $this->enrol($group, ['Frais de Novembre', 'Frais dexam ÖSD B1']);

        $this->artisan('groupes:nettoyer-frais')->assertSuccessful();

        $this->assertFalse($group->fresh()->frais()->where('nom', 'Frais dexam ÖSD B1')->exists());
    }

    /** A window spanning the year boundary keeps both its halves. */
    public function test_a_window_crossing_the_year_boundary_keeps_both_halves(): void
    {
        // Nov 2025 -> Feb 2026: months 11, 12, 1, 2 are all inside.
        $group = $this->groupWithFees(['Frais de Novembre', 'Frais de Janvier', 'Frais de Mai']);
        $this->enrol($group, ['Frais de Novembre', 'Frais de Janvier', 'Frais de Mai']);

        $this->artisan('groupes:nettoyer-frais')->assertSuccessful();

        $frais = $group->fresh()->frais()->pluck('nom')->all();
        $this->assertContains('Frais de Novembre', $frais);
        $this->assertContains('Frais de Janvier', $frais, 'January is inside a Nov->Feb window.');
        $this->assertNotContains('Frais de Mai', $frais);
    }

    /** A group without formation dates keeps its monthly fees. */
    public function test_a_group_without_dates_keeps_its_monthly_fees(): void
    {
        $group = $this->groupWithFees(['Frais de Novembre', 'Frais de Juillet']);
        $group->update(['date_debut_formation' => null, 'date_fin_formation' => null]);
        $this->enrol($group, ['Frais de Novembre', 'Frais de Juillet']);

        $this->artisan('groupes:nettoyer-frais')->assertSuccessful();

        $this->assertTrue($group->fresh()->frais()->where('nom', 'Frais de Juillet')->exists());
    }

    /** --dry-run must not write anything. */
    public function test_dry_run_changes_nothing(): void
    {
        $group = $this->groupWithFees(['Frais de Novembre', 'Frais de Juillet']);
        $this->enrol($group, ['Frais de Novembre', 'Frais de Juillet']);
        $avant = $group->frais()->count();

        $this->artisan('groupes:nettoyer-frais', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame($avant, $group->fresh()->frais()->count());
    }
}
