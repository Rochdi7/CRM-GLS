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
 * ⚠ Une remise reste possible sur un frais DÉJÀ PAYÉ EN PARTIE (16/09/2026).
 *
 * L'ancienne borne « un frais ne peut pas être fixé en dessous du montant
 * déjà payé » rendait la remise impossible dès le premier dirham encaissé :
 * sur un frais de 1 300 DH réglé à 600 DH, accorder 50 % passait mais 60 %
 * était refusé, sans aucun autre chemin pour l'utilisateur — alors que
 * RETIRER la ligne libérait déjà cet argent sans difficulté.
 *
 * La règle qui la remplace tient en UNE phrase : un frais n'est jamais fixé
 * SOUS ce qu'il a déjà encaissé. Tout le reste est permis —
 *  - la remise se pose, se corrige et se retire à tout moment, y compris sur
 *    une ligne dont le RESTE est à 0,00 (souvent parce qu'une remise l'y a
 *    amenée) : la corriger rouvre une créance, cela ne rend aucun argent ;
 *  - augmenter un frais reste toujours possible ;
 *  - descendre sous le payé est le seul geste refusé, parce qu'il REND de
 *    l'argent — ce qui passe par un remboursement, jamais par un prix
 *    réécrit. Aucune avance n'est donc jamais créée par une remise.
 */
final class RemiseFraisPartiellementPayeTest extends TestCase
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
            'reference' => 'INS-R'.(++self::$counter), 'student_id' => $student->id, 'group_id' => $group->id,
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
            'reference' => 'ENC-R'.(++self::$counter), 'student_id' => $inscription->student_id,
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
     * Le cas signalé : 1 300 DH dont 600 déjà encaissés, remise de 50 %
     * (650 DH) — au-dessus du payé, donc parfaitement légitime.
     */
    public function test_a_discount_is_allowed_on_a_partially_paid_fee(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);
        $this->pay($inscription, $fee, 600.0);

        $this->putFee($inscription, $fee, ['remise_pct' => '50'])
            ->assertSessionHasNoErrors();

        $this->assertSame('650.00', (string) $fee->fresh()->montant);
    }

    /**
     * ⚠ La SEULE borne : descendre sous l'argent déjà reçu. 1 300 DH payés
     * 600, remisés à 520 (60 %) ⇒ refusé — cela rendrait 80 DH, ce qui passe
     * par un remboursement.
     */
    public function test_pricing_a_fee_below_what_it_received_is_refused(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);
        $encaissement = $this->pay($inscription, $fee, 600.0);

        $this->putFee($inscription, $fee, ['remise_pct' => '60'])
            ->assertSessionHasErrors('fee_lines');

        // Rien n'a bougé : ni le prix, ni l'affectation de l'argent.
        $this->assertSame('1300.00', (string) $fee->fresh()->montant);
        $this->assertSame($fee->id, $encaissement->fresh()->inscription_fee_id);
    }

    /**
     * ⚠ Le cas du 16/09/2026 : 1 200 DH remisés de 200 et PAYÉS 1 000, donc
     * reste à 0,00. La remise doit rester CORRIGEABLE — la ramener à 150
     * rouvre une créance de 50 DH, cela ne rend aucun argent. Verrouiller le
     * champ dès « RESTE = 0 » rendait toute remise définitive à la seconde
     * où l'étudiant réglait le montant remisé.
     */
    public function test_a_discount_stays_editable_on_a_settled_fee(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1200.0);
        $fee->update(['montant_initial' => 1200.0, 'remise_montant' => 200.0, 'montant' => 1000.0]);
        $this->pay($inscription, $fee, 1000.0);

        $this->putFee($inscription, $fee, ['montant_initial' => '1200', 'remise_montant' => '150'])
            ->assertSessionHasNoErrors();

        $this->assertSame('1050.00', (string) $fee->fresh()->montant);
    }

    /**
     * Et on peut retirer la remise entièrement : le frais repart à 1 200 DH,
     * l'étudiant doit de nouveau 200 DH.
     */
    public function test_a_discount_can_be_removed_entirely_on_a_settled_fee(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1200.0);
        $fee->update(['montant_initial' => 1200.0, 'remise_montant' => 200.0, 'montant' => 1000.0]);
        $this->pay($inscription, $fee, 1000.0);

        $this->putFee($inscription, $fee, ['montant_initial' => '1200'])
            ->assertSessionHasNoErrors();

        $this->assertSame('1200.00', (string) $fee->fresh()->montant);
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $fee->fresh()->statut);
    }

    /**
     * Mais pas AUGMENTER la remise sous le payé : 1 200 payés 1 000, remise
     * portée à 300 (⇒ 900) rendrait 100 DH.
     */
    public function test_deepening_a_discount_below_the_paid_amount_is_refused(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1200.0);
        $fee->update(['montant_initial' => 1200.0, 'remise_montant' => 200.0, 'montant' => 1000.0]);
        $this->pay($inscription, $fee, 1000.0);

        $this->putFee($inscription, $fee, ['montant_initial' => '1200', 'remise_montant' => '300'])
            ->assertSessionHasErrors('fee_lines');

        $this->assertSame('1000.00', (string) $fee->fresh()->montant);
    }

    /**
     * Un frais entièrement payé peut toujours être AUGMENTÉ — seule la baisse
     * sous le payé est refusée.
     */
    public function test_a_fully_paid_fee_can_still_be_raised(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);
        $this->pay($inscription, $fee, 1300.0);

        $this->putFee($inscription, $fee, ['montant_initial' => '1500'])
            ->assertSessionHasNoErrors();

        $this->assertSame('1500.00', (string) $fee->fresh()->montant);
    }

    /**
     * ⚠ Aucune AVANCE n'est jamais créée par une remise : le plancher garantit
     * qu'il n'existe pas de surplus à détacher. L'argent ne quitte une ligne
     * que par le retrait du frais ou par un remboursement.
     */
    public function test_a_discount_never_releases_money_as_an_advance(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);
        $encaissement = $this->pay($inscription, $fee, 600.0);

        $this->putFee($inscription, $fee, ['remise_pct' => '50'])
            ->assertSessionHasNoErrors();

        $this->assertSame($fee->id, $encaissement->fresh()->inscription_fee_id);
        $this->assertSame(600.0, $fee->fresh()->montantPaye());
    }

    /**
     * ⚠ Le cas réellement signalé (inscription 807) : la table entière est UN
     * payload, donc remiser « Frais de Février » renvoyait aussi « Frais
     * d'inscription A1/A2/B1 », une ligne SUR-PAYÉE par l'import legacy
     * (600 DH sur un frais de 300 — 155 lignes en base). La règle butait sur
     * cette ligne-là et nommait, dans son refus, un frais auquel
     * l'utilisateur n'avait pas touché.
     */
    public function test_an_untouched_overpaid_line_never_blocks_an_edit_to_another_line(): void
    {
        [$inscription, $cible] = $this->inscriptionWithFee(1300.0);

        // La ligne parasite : 300 DH de frais, 600 DH encaissés.
        $overpaid = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => "Frais d'inscription A1/A2/B1",
            'montant_initial' => 300.0, 'montant' => 300.0,
            'date_echeance' => '2025-10-01', 'statut' => InscriptionFee::STATUT_PAYE,
        ]);
        $this->pay($inscription, $overpaid, 600.0);

        // On remise la CIBLE, en renvoyant les deux lignes comme le fait la
        // table réelle — la ligne sur-payée est soumise INCHANGÉE.
        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->put(route('backoffice.inscriptions.fees.update', $inscription), [
                'fee_lines' => [
                    [
                        'id' => $cible->id, 'nom' => $cible->nom,
                        'montant_initial' => '1300', 'remise_montant' => '200',
                        'date_echeance' => '2025-10-01',
                    ],
                    [
                        'id' => $overpaid->id, 'nom' => $overpaid->nom,
                        'montant_initial' => '300', 'date_echeance' => '2025-10-01',
                    ],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('1100.00', (string) $cible->fresh()->montant);
        // La ligne parasite est restée exactement comme elle était.
        $this->assertSame('300.00', (string) $overpaid->fresh()->montant);
        $this->assertSame(600.0, $overpaid->fresh()->montantPaye());
    }
}
