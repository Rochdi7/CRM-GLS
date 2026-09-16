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
 * La règle qui la remplace tient en deux temps :
 *  - partiellement payé ⇒ remise LIBRE ; le surplus éventuel est détaché en
 *    avance réapplicable (jamais supprimé, caisse inchangée) ;
 *  - entièrement payé ⇒ remise REFUSÉE : le frais est soldé, et rendre de
 *    l'argent passe par un remboursement.
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
     * Le cas signalé : 1 300 DH dont 600 déjà encaissés, remise de 60 %
     * (520 DH) — refusée jusqu'au 16/09/2026 alors que 50 % passait.
     */
    public function test_a_discount_below_the_paid_amount_is_accepted_on_a_partially_paid_fee(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);
        $this->pay($inscription, $fee, 600.0);

        $this->putFee($inscription, $fee, ['remise_pct' => '60'])
            ->assertSessionHasNoErrors();

        $this->assertSame('520.00', (string) $fee->fresh()->montant);
    }

    /**
     * Le surplus ne disparaît pas : il est DÉTACHÉ en avance réapplicable —
     * l'encaissement survit, la caisse ne bouge pas.
     */
    public function test_the_surplus_is_released_as_a_reapplicable_advance(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);
        $encaissement = $this->pay($inscription, $fee, 600.0);
        $soldeAvant = Caisse::find($encaissement->caisse_id)->solde;

        $this->putFee($inscription, $fee, ['remise_pct' => '60'])
            ->assertSessionHasNoErrors();

        $encaissement->refresh();
        // La ligne existe toujours (les enregistrements monétaires sont
        // append-only) mais n'est plus affectée au frais : c'est une avance.
        $this->assertNull($encaissement->inscription_fee_id);
        $this->assertSame('600.00', (string) $encaissement->montant);
        $this->assertSame($soldeAvant, Caisse::find($encaissement->caisse_id)->solde);
        // Le frais remisé redevient dû en entier.
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);
    }

    /**
     * On ne libère que ce qu'il faut, les paiements les plus RÉCENTS d'abord :
     * 1 300 payés en 300 + 600, remisés à 400 ⇒ seul le paiement de 600 part,
     * les 300 restent affectés au frais.
     */
    public function test_only_the_surplus_payments_are_released_newest_first(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);
        $ancien = $this->pay($inscription, $fee, 300.0, '2025-10-01');
        $recent = $this->pay($inscription, $fee, 600.0, '2025-11-01');

        $this->putFee($inscription, $fee, ['remise_montant' => '900'])
            ->assertSessionHasNoErrors();

        $this->assertSame('400.00', (string) $fee->fresh()->montant);
        $this->assertSame($fee->id, $ancien->fresh()->inscription_fee_id);
        $this->assertNull($recent->fresh()->inscription_fee_id);
        $this->assertSame(300.0, $fee->fresh()->montantPaye());
    }

    /**
     * Une remise qui reste AU-DESSUS du payé ne libère rien du tout.
     */
    public function test_a_discount_above_the_paid_amount_releases_nothing(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);
        $encaissement = $this->pay($inscription, $fee, 600.0);

        $this->putFee($inscription, $fee, ['remise_pct' => '20'])
            ->assertSessionHasNoErrors();

        $this->assertSame('1040.00', (string) $fee->fresh()->montant);
        $this->assertSame($fee->id, $encaissement->fresh()->inscription_fee_id);
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $fee->fresh()->statut);
    }

    /**
     * L'unique situation refusée : le frais est SOLDÉ. Le remiser reviendrait
     * à rendre de l'argent, ce qui passe par un remboursement.
     */
    public function test_a_discount_on_a_fully_paid_fee_is_refused(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);
        $encaissement = $this->pay($inscription, $fee, 1300.0);

        $this->putFee($inscription, $fee, ['remise_pct' => '20'])
            ->assertSessionHasErrors('fee_lines');

        // Rien n'a bougé : ni le prix, ni l'affectation de l'argent.
        $this->assertSame('1300.00', (string) $fee->fresh()->montant);
        $this->assertSame($fee->id, $encaissement->fresh()->inscription_fee_id);
    }

    /**
     * Un frais entièrement payé peut toujours être AUGMENTÉ — seule la remise
     * (descendre sous le payé) est refusée.
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

    /**
     * Une ligne SUR-payée n'est pas « soldée » : c'est une anomalie, et la
     * remiser reste permis — le surplus repart en avance comme ailleurs.
     */
    public function test_an_overpaid_line_can_itself_be_discounted(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(300.0);
        $this->pay($inscription, $fee, 600.0);

        $this->putFee($inscription, $fee, ['montant_initial' => '300', 'remise_montant' => '100'])
            ->assertSessionHasNoErrors();

        $this->assertSame('200.00', (string) $fee->fresh()->montant);
    }
}
