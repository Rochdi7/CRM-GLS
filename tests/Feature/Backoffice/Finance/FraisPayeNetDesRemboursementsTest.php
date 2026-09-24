<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Actions\AnnulerRemboursement;
use App\Domain\Finance\Actions\EnregistrerRemboursement;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Remboursement;
use App\Models\Student;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * « Payé » = encaissé − remboursé (24/09/2026).
 *
 * ENC-26191 (1 200 DH) remboursé en entier par RMB-003 laissait le « Frais de
 * Septembre » de LOUBNA SOUILH en « Payé » : aucune lecture ne soustrayait un
 * remboursement, et un remboursement ne touchait jamais le frais. Ce que ces
 * tests figent :
 *   - le payé d'un frais est NET des remboursements non annulés ;
 *   - rembourser rafraîchit le statut stocké, annuler le remboursement aussi ;
 *   - la lecture EN LOT (`avecPayeNet` / `payeNet`) donne le même chiffre que
 *     l'accesseur de ligne — une seule définition pour toutes les listes ;
 *   - la commande de rattrapage corrige un statut écrit avant le correctif.
 */
final class FraisPayeNetDesRemboursementsTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private Employee $agent;

    private Caisse $caisse;

    private InscriptionFee $fee;

    private Encaissement $paiement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->centre = Etablissement::factory()->create();
        $annee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $annee->id,
        ]);

        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->caisse = $this->agent->till()->firstOrFail();
        Caisse::query()->whereKey($this->caisse->id)->update(['solde' => 5000]);

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-NET',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2026-09-01',
        ]);
        $this->fee = InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'nom' => 'Frais de Septembre',
            'montant_initial' => 1200,
            'montant' => 1200,
            'date_echeance' => '2026-09-06',
            'statut' => InscriptionFee::STATUT_PAYE,
        ]);
        $this->paiement = Encaissement::create([
            'reference' => 'ENC-NET',
            'etablissement_id' => $this->centre->id,
            'student_id' => $student->id,
            'inscription_fee_id' => $this->fee->id,
            'montant' => 1200,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-09-02',
            'caisse_id' => $this->caisse->id,
            'agent_id' => $this->agent->id,
        ]);
    }

    private function rembourser(float $montant): Remboursement
    {
        return app(EnregistrerRemboursement::class)->handle([
            'beneficiaire_id' => $this->paiement->student_id,
            'encaissement_id' => $this->paiement->id,
            'caisse_id' => $this->caisse->id,
            'montant' => $montant,
            'date_remboursement' => '2026-09-08',
            'motif' => 'Inscription annulée',
        ], $this->agent);
    }

    private function payeEnLot(): float
    {
        return InscriptionFee::query()->whereKey($this->fee->id)->avecPayeNet()->firstOrFail()->payeNet();
    }

    public function test_un_remboursement_total_remet_le_frais_non_paye(): void
    {
        $this->rembourser(1200);

        $this->fee->refresh();
        $this->assertSame(0.0, $this->fee->montantPaye());
        $this->assertSame(0.0, $this->payeEnLot(), 'La lecture en lot doit dire la même chose que l’accesseur.');
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $this->fee->statut);
    }

    public function test_un_remboursement_partiel_laisse_le_frais_paye_partiellement(): void
    {
        $this->rembourser(500);

        $this->fee->refresh();
        $this->assertSame(700.0, $this->fee->montantPaye());
        $this->assertSame(700.0, $this->payeEnLot());
        $this->assertSame(InscriptionFee::STATUT_PAYE_PARTIELLEMENT, $this->fee->statut);
    }

    public function test_annuler_le_remboursement_remet_le_frais_paye(): void
    {
        $remboursement = $this->rembourser(1200);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $this->fee->fresh()->statut);

        app(AnnulerRemboursement::class)->handle($remboursement, 'Saisi par erreur');

        $this->fee->refresh();
        $this->assertSame(1200.0, $this->fee->montantPaye());
        $this->assertSame(1200.0, $this->payeEnLot());
        $this->assertSame(InscriptionFee::STATUT_PAYE, $this->fee->statut);
    }

    /** L'argent ne bouge pas pour le frais : seul le statut change, la caisse suit le remboursement. */
    public function test_le_paiement_lui_meme_est_intact(): void
    {
        $this->rembourser(1200);

        $this->paiement->refresh();
        $this->assertSame('1200.00', (string) $this->paiement->montant);
        $this->assertSame($this->fee->id, $this->paiement->inscription_fee_id);
        $this->assertSame('3800.00', (string) Caisse::query()->whereKey($this->caisse->id)->value('solde'));
    }

    /** Un paiement posé sur un frais MASQUÉ ne se rembourse pas : rien ne bouge, caisse comprise. */
    public function test_un_paiement_sur_frais_masque_ne_se_rembourse_pas(): void
    {
        $this->fee->update(['masque_le' => now()]);

        try {
            $this->rembourser(1200);
            $this->fail('Le remboursement aurait dû être refusé.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('encaissement_id', $e->errors());
        }

        $this->assertSame(0, Remboursement::query()->count());
        $this->assertSame('5000.00', (string) Caisse::query()->whereKey($this->caisse->id)->value('solde'));
    }

    /** Le sélecteur le LISTE avec la raison au lieu de le cacher. */
    public function test_le_selecteur_liste_le_paiement_bloque_avec_sa_raison(): void
    {
        $this->fee->update(['masque_le' => now()]);

        $ligne = app(\App\Domain\Finance\Queries\GetStudentPaymentsForRefund::class)($this->paiement->student_id)
            ->firstWhere('id', $this->paiement->id);

        $this->assertNotNull($ligne, 'Le paiement reste listé.');
        $this->assertNotNull($ligne['bloqueRaison']);
        $this->assertStringContainsString('Frais de Septembre', $ligne['bloqueRaison']);
    }

    public function test_la_commande_corrige_un_statut_ecrit_avant_le_correctif(): void
    {
        // État de production avant le correctif : remboursé, mais « Payé » stocké.
        $this->rembourser(1200);
        DB::table('inscription_fees')->where('id', $this->fee->id)->update(['statut' => InscriptionFee::STATUT_PAYE]);

        $this->artisan('frais:recalculer-statuts-rembourses')->assertSuccessful();
        $this->assertSame(InscriptionFee::STATUT_PAYE, $this->fee->fresh()->statut, 'La simulation n’écrit rien.');

        $this->artisan('frais:recalculer-statuts-rembourses', ['--apply' => true])->assertSuccessful();
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $this->fee->fresh()->statut);
    }
}
