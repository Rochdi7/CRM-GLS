<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

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
 * Semaines d'un frais MENSUEL (02/10/2026) : Sem 1–4 valent chacune le quart
 * du montant initial ; les cases cochées sont stockées dans
 * `inscription_fees.semaines` et la remise est recalculée par le serveur.
 */
final class FraisSemainesTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private static int $counter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    /**
     * @return array{0: Inscription, 1: InscriptionFee}
     */
    private function inscriptionWithFee(float $montant = 1300.0): array
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $inscription = Inscription::create([
            'reference' => 'INS-S'.(++self::$counter), 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15', 'montant_total' => $montant,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Septembre',
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-10-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$inscription, $fee];
    }

    private function pay(Inscription $inscription, InscriptionFee $fee, float $montant, string $date = '2025-10-01'): Encaissement
    {
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        $encaissement = Encaissement::create([
            'reference' => 'ENC-S'.(++self::$counter), 'student_id' => $inscription->student_id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => $montant, 'methode' => 'Espèces', 'date_paiement' => $date,
        ]);

        $paye = $fee->fresh()->montantPaye();
        $fee->update([
            'statut' => match (true) {
                $paye >= (float) $fee->montant => InscriptionFee::STATUT_PAYE,
                $paye > 0 => InscriptionFee::STATUT_PAYE_PARTIELLEMENT,
                default => InscriptionFee::STATUT_NON_PAYE,
            },
        ]);

        return $encaissement;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function putFee(Inscription $inscription, InscriptionFee $fee, array $overrides)
    {
        return $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->put(route('backoffice.inscriptions.fees.update', $inscription), [
                'fee_lines' => [array_merge([
                    'id' => $fee->id,
                    'nom' => $fee->nom,
                    'montant_initial' => (string) $fee->montant_initial,
                    'date_echeance' => '2025-10-01',
                ], $overrides)],
            ]);
    }

    /**
     * Sem 1 seule cochée sur 1 300 DH ⇒ 325 DH, et la case cochée est
     * STOCKÉE telle quelle : la rouvrir montre Sem 1, jamais Sem 4.
     */
    public function test_ticked_weeks_are_stored_and_price_the_fee(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);

        $this->putFee($inscription, $fee, ['semaines' => [1], 'remise_montant' => '0'])
            ->assertSessionHasNoErrors();

        $fee->refresh();
        $this->assertSame([1], $fee->semaines);
        $this->assertSame('325.00', (string) $fee->montant);
        // La remise est recalculée par le serveur, pas reprise du client.
        $this->assertSame('975.00', (string) $fee->remise_montant);
    }

    public function test_two_non_adjacent_weeks_keep_their_exact_positions(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);

        $this->putFee($inscription, $fee, ['semaines' => [3, 1, 3]])
            ->assertSessionHasNoErrors();

        $fee->refresh();
        $this->assertSame([1, 3], $fee->semaines);
        $this->assertSame('650.00', (string) $fee->montant);
    }

    public function test_no_week_or_all_four_weeks_is_the_full_month(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);

        $this->putFee($inscription, $fee, ['semaines' => [1, 2, 3, 4]])
            ->assertSessionHasNoErrors();

        $fee->refresh();
        $this->assertNull($fee->semaines);
        $this->assertSame('1300.00', (string) $fee->montant);
    }

    public function test_a_week_outside_one_to_four_is_refused(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);

        $this->putFee($inscription, $fee, ['semaines' => [5]])
            ->assertSessionHasErrors('fee_lines.0.semaines.0');
    }
}
