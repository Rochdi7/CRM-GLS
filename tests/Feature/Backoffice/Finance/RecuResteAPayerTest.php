<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Support\SituationFraisRecu;
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
 * « Total payé / Reste à payer » sur le reçu (04/09/2026).
 *
 * Demandé par l'utilisateur : un étudiant qui règle 500 DH sur des frais de
 * 1 500 DH repartait avec un reçu ne portant que « Montant : 500 DH » — rien
 * ne lui disait ce qu'il doit encore. La situation du frais est désormais
 * imprimée sur TOUS les reçus et tous les formats (A6, A5, A5×2 deux copies,
 * groupé, PDF WhatsApp, email).
 *
 * Les deux pièges que ces tests verrouillent :
 *   - « Total payé » est CUMULATIF (tous les versements du frais), jamais le
 *     montant du reçu courant : le reçu de la 2ᵉ tranche doit lire 1 000, pas
 *     500, sinon le document contredit la caisse ;
 *   - une avance NON allouée ne solde aucun frais : elle n'affiche ni dû ni
 *     reste, sans quoi « Reste : 0 » ferait croire la scolarité soldée.
 */
final class RecuResteAPayerTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();

        $this->user = User::factory()->create();
        foreach (['payments.view', 'payments.create', 'payments.update', 'centers.access-all'] as $p) {
            $this->user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $this->user->id, 'etablissement_id' => $this->centre->id]);
        $this->user = $this->user->fresh();
    }

    private function feeFor(Student $student, string $nom, float $montant = 1500): InscriptionFee
    {
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => $montant,
        ]);

        return InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => $nom,
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
    }

    private function paiement(Student $student, ?InscriptionFee $fee, float $montant): Encaissement
    {
        /** @var Caisse $caisse */
        $caisse = $this->user->employee->till()->firstOrFail();

        return Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'etablissement_id' => $this->centre->id,
            'student_id' => $student->id,
            'inscription_fee_id' => $fee?->id,
            'caisse_id' => $caisse->id,
            'montant' => $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2025-09-20',
            'agent_id' => $this->user->employee->id,
        ]);
    }

    public function test_a_partial_payment_reports_the_remaining_balance(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de scolarité', 1500);
        $encaissement = $this->paiement($student, $fee, 500);

        $situation = SituationFraisRecu::pour($encaissement->fresh());

        $this->assertTrue($situation->disponible);
        $this->assertSame(1500.0, $situation->totalFrais);
        $this->assertSame(500.0, $situation->totalPaye);
        $this->assertSame(1000.0, $situation->reste);
    }

    public function test_the_paid_total_is_cumulative_not_this_receipt_alone(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de scolarité', 1500);
        $this->paiement($student, $fee, 500);
        $deuxieme = $this->paiement($student, $fee, 500);

        // Le reçu de la 2e tranche : 1 000 payés sur 1 500, reste 500.
        // S'il lisait $encaissement->montant il annoncerait 500 payés — le
        // document contredirait alors la caisse.
        $situation = SituationFraisRecu::pour($deuxieme->fresh());

        $this->assertSame(1000.0, $situation->totalPaye);
        $this->assertSame(500.0, $situation->reste);
    }

    public function test_a_fully_paid_fee_reports_a_zero_balance(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de scolarité', 1500);
        $encaissement = $this->paiement($student, $fee, 1500);

        $this->assertSame(0.0, SituationFraisRecu::pour($encaissement->fresh())->reste);
    }

    public function test_an_unallocated_advance_has_no_fee_situation(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $avance = $this->paiement($student, null, 800);

        // Rien à solder : ni dû ni reste ne doivent s'imprimer.
        $this->assertFalse(SituationFraisRecu::pour($avance->fresh()->load('applications'))->disponible);
    }

    public function test_an_applied_advance_reports_the_fee_it_paid(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de scolarité', 1500);
        $avance = $this->paiement($student, null, 500);

        app(AppliquerAvance::class)->handle($avance, $fee, 500.0);

        $situation = SituationFraisRecu::pour($avance->fresh()->load('applications.fee'));

        $this->assertTrue($situation->disponible);
        $this->assertSame(1500.0, $situation->totalFrais);
        $this->assertSame(500.0, $situation->totalPaye);
        $this->assertSame(1000.0, $situation->reste);
    }

    /**
     * La ligne « Montant » disparaît quand elle répète « Total payé »
     * (1er versement) : le même chiffre deux fois de suite se lit comme une
     * erreur de document.
     */
    public function test_the_amount_row_is_hidden_when_it_repeats_the_paid_total(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de scolarité', 300);
        $encaissement = $this->paiement($student, $fee, 100);

        $html = $this->actingAs($this->user)
            ->get(route('backoffice.encaissements.recu', $encaissement))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('>Montant<', $html);
        $this->assertStringContainsString('Total payé', $html);
        $this->assertStringContainsString('Reste à payer', $html);
    }

    /**
     * ⚠ Le pendant du test précédent : dès la 2e tranche, « Montant » est la
     * SEULE ligne qui dit ce qui a été remis aujourd'hui — elle doit revenir.
     */
    public function test_the_amount_row_returns_when_it_differs_from_the_paid_total(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de scolarité', 300);
        $this->paiement($student, $fee, 100);
        $deuxieme = $this->paiement($student, $fee, 100);

        $html = $this->actingAs($this->user)
            ->get(route('backoffice.encaissements.recu', $deuxieme))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>Montant<', $html);
        // 100 remis aujourd'hui, 200 cumulés, 100 restants.
        $this->assertStringContainsString('200 DH', $html);
        $this->assertStringContainsString('100 DH', $html);
    }

    /** Une avance non allouée garde « Montant » : rien d'autre ne l'énonce. */
    public function test_an_unallocated_advance_keeps_its_amount_row(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $avance = $this->paiement($student, null, 800);

        $html = $this->actingAs($this->user)
            ->get(route('backoffice.encaissements.recu', $avance))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>Montant<', $html);
        $this->assertStringContainsString('800 DH', $html);
    }

    /** Les trois formats du reçu imprimable portent les mêmes lignes. */
    public function test_every_printable_format_shows_the_remaining_balance(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de scolarité', 1500);
        $encaissement = $this->paiement($student, $fee, 500);

        foreach (['a6', 'a5', 'a5x2'] as $format) {
            $html = $this->actingAs($this->user)
                ->get(route('backoffice.encaissements.recu', ['encaissement' => $encaissement, 'format' => $format]))
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString('Reste à payer', $html, "format {$format}");
            $this->assertStringContainsString('1 000 DH', $html, "format {$format}");
            $this->assertStringContainsString('Total payé', $html, "format {$format}");
            // 1er versement : « Montant » répéterait « Total payé ».
            $this->assertStringNotContainsString('>Montant<', $html, "format {$format}");
        }
    }

    /** a5x2 = deux exemplaires : la situation doit figurer sur les DEUX. */
    public function test_both_copies_of_the_a5x2_sheet_carry_the_balance(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de scolarité', 1500);
        $encaissement = $this->paiement($student, $fee, 500);

        $html = $this->actingAs($this->user)
            ->get(route('backoffice.encaissements.recu', ['encaissement' => $encaissement, 'format' => 'a5x2']))
            ->assertOk()
            ->getContent();

        $this->assertSame(2, substr_count($html, 'Reste à payer'));
    }

    public function test_the_grouped_receipt_totals_the_remaining_balance(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de scolarité', 1500);
        $inscriptionId = $fee->inscription_id;

        // Deux frais de la MÊME inscription (contrainte du reçu groupé).
        $second = InscriptionFee::create([
            'inscription_id' => $inscriptionId, 'nom' => 'Frais de livres',
            'montant_initial' => 500, 'montant' => 500,
            'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $a = $this->paiement($student, $fee, 500);
        $b = $this->paiement($student, $second, 200);

        $html = $this->actingAs($this->user)
            ->get(route('backoffice.encaissements.recu-groupe', ['ids' => $a->id.','.$b->id]))
            ->assertOk()
            ->getContent();

        // 2 000 dus, 700 payés, 1 300 restants sur l'ensemble du lot.
        $this->assertStringContainsString('Reste à payer', $html);
        $this->assertStringContainsString('1 300', $html);
    }

    public function test_an_unallocated_advance_receipt_prints_no_balance(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $avance = $this->paiement($student, null, 800);

        $html = $this->actingAs($this->user)
            ->get(route('backoffice.encaissements.recu', $avance))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Reste à payer', $html);
    }
}
