<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Students;

use App\Domain\Students\Queries\GetStudentDetails;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Student detail page rules (24/08/2026): inscriptions grouped per année
 * scolaire, newest first; Paiements scoped to the Active inscription(s),
 * falling back to Annulée, then Changement.
 */
final class StudentShowDetailsTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $ancienne;

    private AnneeScolaire $courante;

    private Etablissement $centre;

    private Student $student;

    private Caisse $caisse;

    private Employee $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ancienne = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
        $this->courante = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->caisse = Caisse::factory()->create([
            'etablissement_id' => $this->centre->id,
            'responsable_employee_id' => $this->agent->id,
        ]);
    }

    private function inscription(AnneeScolaire $annee, string $statut, string $date): Inscription
    {
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $annee->id,
        ]);

        return Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $this->student->id,
            'group_id' => $group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $annee->id,
            'statut' => $statut,
            'date_inscription' => $date,
        ]);
    }

    private function paiement(Inscription $inscription, float $montant): Encaissement
    {
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais test',
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2026-09-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'student_id' => $this->student->id,
            'inscription_fee_id' => $fee->id,
            'montant' => $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2026-01-10',
            'caisse_id' => $this->caisse->id,
            'agent_id' => $this->agent->id,
        ]);
    }

    public function test_inscriptions_are_grouped_per_year_newest_first_and_payments_follow_the_active_one(): void
    {
        $annulee = $this->inscription($this->ancienne, Inscription::STATUT_ANNULEE, '2026-01-05');
        $active = $this->inscription($this->courante, Inscription::STATUT_ACTIVE, '2026-09-10');
        $paiementAnnulee = $this->paiement($annulee, 500);
        $paiementActive = $this->paiement($active, 800);

        $details = app(GetStudentDetails::class)($this->student->fresh());

        $this->assertSame(['2026/2027', '2025/2026'], array_column($details['inscriptionsParAnnee'], 'annee'));
        $this->assertSame($active->reference, $details['inscriptionsParAnnee'][0]['inscriptions'][0]['reference']);
        $this->assertSame($annulee->reference, $details['inscriptionsParAnnee'][1]['inscriptions'][0]['reference']);

        $this->assertSame(Inscription::STATUT_ACTIVE, $details['paiementsScope']);
        $this->assertSame([$paiementActive->reference], array_column($details['paiements'], 'reference'));
        $this->assertSame('800.00', $details['paiementsTotal']);
        $this->assertNotContains($paiementAnnulee->reference, array_column($details['paiements'], 'reference'));
    }

    public function test_without_an_active_inscription_payments_fall_back_to_the_cancelled_one(): void
    {
        $annulee = $this->inscription($this->ancienne, Inscription::STATUT_ANNULEE, '2026-01-05');
        $changement = $this->inscription($this->ancienne, Inscription::STATUT_CHANGEMENT, '2025-12-01');
        $paiementAnnulee = $this->paiement($annulee, 500);
        $this->paiement($changement, 300);

        $details = app(GetStudentDetails::class)($this->student->fresh());

        $this->assertSame(Inscription::STATUT_ANNULEE, $details['paiementsScope']);
        $this->assertSame([$paiementAnnulee->reference], array_column($details['paiements'], 'reference'));
        $this->assertSame('500.00', $details['paiementsTotal']);
    }

    public function test_without_any_inscription_every_payment_shows(): void
    {
        $details = app(GetStudentDetails::class)($this->student->fresh());

        $this->assertNull($details['paiementsScope']);
        $this->assertSame([], $details['inscriptionsParAnnee']);
        $this->assertSame([], $details['paiements']);
    }
}
