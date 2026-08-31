<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Support\RecuPdfRenderer;
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
 * Le libellé « Frais scolaires » du reçu (31/08/2026).
 *
 * Signalé par l'utilisateur : une avance CONVERTIE/APPLIQUÉE à des frais
 * imprimait encore « Avance » sur le reçu remis à l'étudiant. La liste des
 * encaissements, elle, affichait bien « Avance — Appliquée : 200.00 MAD ».
 * L'étudiant tient un document qui ne lui dit pas ce qu'il a payé.
 *
 * La règle est désormais UNIQUE (`Encaissement::libelleFrais()`) et partagée
 * par les quatre reçus : imprimé, groupé, PDF (WhatsApp) et email.
 */
final class RecuLibelleFraisTest extends TestCase
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

    /** Une inscription avec UN frais nommé. */
    private function feeFor(Student $student, string $nom, float $montant = 1000): InscriptionFee
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

    /** Une avance : un encaissement sans `inscription_fee_id`. */
    private function avanceFor(Student $student, float $montant): Encaissement
    {
        /** @var Caisse $caisse */
        $caisse = $this->user->employee->till()->firstOrFail();

        return Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'etablissement_id' => $this->centre->id,
            'student_id' => $student->id,
            'inscription_fee_id' => null,
            'caisse_id' => $caisse->id,
            'montant' => $montant,
            'methode' => 'Virement',
            'date_paiement' => '2025-09-20',
            'agent_id' => $this->user->employee->id,
        ]);
    }

    public function test_a_payment_attached_to_a_fee_names_that_fee(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de Juillet');

        $encaissement = $this->avanceFor($student, 1000);
        $encaissement->update(['inscription_fee_id' => $fee->id]);

        $this->assertSame('Frais de Juillet', $encaissement->fresh()->libelleFrais());
    }

    public function test_an_unallocated_advance_is_still_labelled_avance(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $avance = $this->avanceFor($student, 200);

        // Rien n'a encore été alloué : « Avance » est la vérité.
        $this->assertSame('Avance', $avance->load('applications.fee')->libelleFrais());
    }

    public function test_an_applied_advance_names_the_fee_the_money_paid(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de Juillet');
        $avance = $this->avanceFor($student, 200);

        app(AppliquerAvance::class)->handle($avance, $fee, 200.0);

        // C'est LE bug signalé : le reçu disait « Avance » alors que l'argent
        // avait payé « Frais de Juillet ».
        $this->assertSame('Frais de Juillet', $avance->fresh()->load('applications.fee')->libelleFrais());
    }

    public function test_an_advance_split_across_two_fees_names_both(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $juillet = $this->feeFor($student, 'Frais de Juillet', 100);
        $aout = $this->feeFor($student, 'Frais d\'Août', 100);
        $avance = $this->avanceFor($student, 200);

        app(AppliquerAvance::class)->handle($avance, $juillet, 100.0);
        app(AppliquerAvance::class)->handle($avance, $aout, 100.0);

        $this->assertSame(
            'Frais de Juillet + Frais d\'Août',
            $avance->fresh()->load('applications.fee')->libelleFrais(),
        );
    }

    public function test_the_printable_receipt_shows_the_applied_fee_not_avance(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais de Juillet');
        $avance = $this->avanceFor($student, 200);

        app(AppliquerAvance::class)->handle($avance, $fee, 200.0);

        $html = $this->actingAs($this->user)
            ->get(route('backoffice.encaissements.recu', $avance))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Frais de Juillet', $html);
    }

    public function test_every_receipt_path_loads_the_application_rows(): void
    {
        // Sans ces relations, libelleFrais() déclencherait une requête par
        // reçu (ou, sur un modèle sérialisé en queue, retomberait sur
        // « Avance »).
        $this->assertContains('applications.fee', RecuPdfRenderer::RELATIONS);
    }
}
