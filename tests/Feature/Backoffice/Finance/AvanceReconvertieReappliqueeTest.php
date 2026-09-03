<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Domain\Payments\Support\ResoudreAllocationsAvance;
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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Une avance appliquée, RECONVERTIE, puis RÉ-APPLIQUÉE (02/09/2026).
 *
 * Signalé sur ENC-1586 (WIJDANE IDRISSI) : la liste des encaissements
 * affichait « Avance — Appliquée : 300.00 MAD → Frais non lié : 300.00 MAD »
 * alors que l'argent venait d'être ré-appliqué à un frais. La ligne
 * d'application avait été reconvertie (frais détaché, lien au parent gardé)
 * puis ré-appliquée — ce qui crée une ligne FILLE de la ligne détachée, pas
 * une deuxième fille de l'avance. Les lectures ne suivaient qu'UN niveau.
 *
 * La chaîne a maintenant une définition unique (`ResoudreAllocationsAvance`)
 * partagée par la liste, la page détail et le libellé du reçu.
 */
final class AvanceReconvertieReappliqueeTest extends TestCase
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

        $this->user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $this->user->id, 'etablissement_id' => $this->centre->id]);
        $this->user = $this->user->fresh();
    }

    /** Une inscription avec UN frais nommé. */
    private function feeFor(Student $student, string $nom, float $montant = 300): InscriptionFee
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
            'methode' => 'Espèces',
            'date_paiement' => '2025-12-02',
            'agent_id' => $this->user->employee->id,
        ]);
    }

    /**
     * Le scénario signalé : avance → appliquée sur « Frais A » → reconvertie
     * → ré-appliquée sur « Frais B ». Rend [avance, application détachée].
     *
     * @return array{0: Encaissement, 1: Encaissement, 2: InscriptionFee}
     */
    private function chaine(float $reapplique = 300): array
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $feeA = $this->feeFor($student, 'Frais A');
        $feeB = $this->feeFor($student, 'Frais de Juillet');
        $avance = $this->avanceFor($student, 300);

        $application = app(AppliquerAvance::class)->handle($avance, $feeA, 300.0);
        app(ConvertirEncaissementsEnAvance::class)->handle($feeA->inscription, [$application->id]);
        app(AppliquerAvance::class)->handle($application->fresh(), $feeB, $reapplique);

        return [$avance->fresh(), $application->fresh(), $feeB];
    }

    public function test_the_chain_is_what_the_report_described(): void
    {
        [$avance, $application] = $this->chaine();

        // La ré-application est rattachée à la ligne DÉTACHÉE, pas à l'avance.
        $this->assertNull($application->inscription_fee_id);
        $this->assertSame($avance->id, $application->applied_from_encaissement_id);
        $this->assertSame(1, $avance->applications()->count());
        $this->assertSame(1, $application->applications()->count());
        $this->assertSame(0.0, $avance->montantRestant());
    }

    public function test_the_list_names_the_fee_the_money_finally_paid(): void
    {
        [$avance] = $this->chaine();

        // The bare list defaults its date window to today; the avance is
        // dated 02/12/2025, so the window is set explicitly.
        $this->actingAs($this->user)
            ->get(route('backoffice.encaissements.index', ['dateFrom' => '2025-12-01', 'dateTo' => '2025-12-31']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('encaissements.data', function ($rows) use ($avance): bool {
                    $row = collect($rows)->firstWhere('id', $avance->id);
                    $this->assertNotNull($row, 'The avance must be listed on the Encaissements tab.');
                    $this->assertTrue($row['isAvance']);
                    $this->assertSame('300.00', $row['montantUtilise']);
                    // C'est LE bug : ceci lisait « Frais non lié : 300.00 ».
                    $this->assertSame(['Frais de Juillet'], array_column($row['fraisAppliques'], 'frais'));
                    $this->assertSame(['300.00'], array_column($row['fraisAppliques'], 'montant'));

                    return true;
                }));
    }

    public function test_a_partial_reapplication_keeps_the_remainder_as_unlinked(): void
    {
        [$avance] = $this->chaine(100);

        $allocations = ResoudreAllocationsAvance::terminales([$avance->id])[$avance->id];

        $this->assertSame(
            [[ResoudreAllocationsAvance::KIND_NON_LIE, 200.0], [ResoudreAllocationsAvance::KIND_FRAIS, 100.0]],
            array_map(fn (array $a): array => [$a['kind'], $a['montant']], $allocations),
        );
        // Le détail concorde toujours avec « Appliquée : X ».
        $this->assertSame(300.0, array_sum(array_column($allocations, 'montant')));
    }

    public function test_the_detail_page_and_the_receipt_follow_the_chain_too(): void
    {
        [$avance] = $this->chaine();

        $this->actingAs($this->user)
            ->get(route('backoffice.encaissements.show', $avance))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('encaissement.applications.0.frais', 'Frais de Juillet')
                ->where('encaissement.applications.0.montant', '300.00')
                ->count('encaissement.applications', 1));

        $this->assertSame('Frais de Juillet', $avance->fresh()->load('applications.fee')->libelleFrais());
    }

    public function test_a_single_hop_application_is_unchanged(): void
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, 'Frais A');
        $avance = $this->avanceFor($student, 300);
        app(AppliquerAvance::class)->handle($avance, $fee, 300.0);

        $allocations = ResoudreAllocationsAvance::terminales([$avance->id])[$avance->id];

        $this->assertCount(1, $allocations);
        $this->assertSame(ResoudreAllocationsAvance::KIND_FRAIS, $allocations[0]['kind']);
        $this->assertSame('Frais A', ResoudreAllocationsAvance::libelle($allocations[0]));
    }
}
