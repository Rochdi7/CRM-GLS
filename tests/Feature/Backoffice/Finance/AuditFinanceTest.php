<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Actions\DemanderTransfertCaisse;
use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Models\AnneeScolaire;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `finance:auditer` — l'audit READ-ONLY des défauts de ventilation.
 *
 * Ce que ces tests garantissent avant tout : la commande **ne signale pas
 * ce qui va bien**. Les trois faux positifs du 11/09/2026 lui ont coûté
 * trois allers-retours sur la production —
 *
 *  - « la caisse sert deux centres » (Hafssa : 500 DH Online contre
 *    168 100 DH Rabat, repli sur Rabat exact) ;
 *  - « elle a transféré plus qu'elle n'a encaissé » (Maria, qui centralise
 *    les remises d'Ikram : tout se réconcilie au dirham) ;
 *  - un petit écart parts/solde (300 DH sur 17,6 M, mouvements antérieurs
 *    au 01/09, documenté et normal).
 *
 * Un audit qui crie au loup sur des données saines finit ignoré, et c'est
 * pire que pas d'audit du tout. D'où le critère unique retenu : une part de
 * centre NÉGATIVE.
 */
final class AuditFinanceTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    private Etablissement $online;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $this->online = Etablissement::factory()->create(['nom_centre' => 'GLS Online']);
    }

    private function caissiere(Etablissement $centre, string $categorie = Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE): Employee
    {
        $user = User::factory()->create()->assignRole('super-admin');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $centre->id,
            'categorie' => $categorie,
        ]);

        return $employee->fresh();
    }

    private function encaisser(Employee $agent, Etablissement $centre, float $montant): void
    {
        $student = Student::factory()->create(['etablissement_id' => $centre->id]);

        app(EnregistrerEncaissement::class)->handle([
            'student_id' => $student->id,
            'etablissement_id' => $centre->id,
            'inscription_fee_id' => null,
            'caisse_id' => $agent->till()->firstOrFail()->id,
            'montant' => $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2026-09-05',
        ], $agent);
    }

    private function contexte(Employee $agent, ?int $centreId): void
    {
        $this->actingAs($agent->user->fresh());

        app()->forgetInstance(CurrentContext::class);
        app(CurrentContext::class)->setEtablissement($centreId);
    }

    // ------------------------------------------------------------------ //

    public function test_un_reseau_sain_ne_signale_rien(): void
    {
        $caissiere = $this->caissiere($this->rabat);
        $this->encaisser($caissiere, $this->rabat, 5000);

        $this->artisan('finance:auditer')
            ->expectsOutputToContain('Aucun point à traiter')
            ->assertSuccessful();
    }

    /**
     * LE faux positif à ne jamais réintroduire : un tiroir qui sert deux
     * centres n'est pas un défaut. Hafssa encaissait 500 DH pour Online et
     * 168 100 DH pour Rabat — le repli sur Rabat était exact.
     */
    public function test_une_caisse_multi_centres_saine_n_est_pas_signalee(): void
    {
        $caissiere = $this->caissiere($this->rabat);

        $this->encaisser($caissiere, $this->rabat, 168100);
        $this->encaisser($caissiere, $this->online, 500);

        // Un transfert sans centre, comme tous ceux d'avant le 09/09/2026.
        $destinataire = $this->caissiere($this->rabat);
        $this->contexte($caissiere, $this->rabat->id);

        $transfert = app(DemanderTransfertCaisse::class)->handle([
            'caisse_source_id' => $caissiere->till()->firstOrFail()->id,
            'caisse_destination_id' => $destinataire->till()->firstOrFail()->id,
            'montant' => 112050,
        ], $caissiere);

        app(ValiderTransfertCaisse::class)->handle($transfert, $destinataire);
        $transfert->forceFill(['etablissement_id' => null])->saveQuietly();

        // Rabat garde 56 050 DH, Online ses 500 : aucune part négative.
        $this->artisan('finance:auditer')
            ->expectsOutputToContain('Aucun point à traiter')
            ->assertSuccessful();
    }

    /**
     * Une part réellement négative doit ressortir — le cas Ahmed du
     * 11/09/2026 : un tiroir rattaché à Online encaisse 1 800 DH pour
     * Kénitra, les transfère, et Online plonge à -1 800 DH.
     *
     * ⚠ Le scénario doit être fabriqué en base : `DemanderTransfertCaisse`
     * REFUSE désormais ce transfert (« ce centre ne détient que 0,00 MAD
     * dans cette caisse »), ce qui est exactement le garde-fou qui empêche
     * d'en créer de nouveaux. Seules les lignes HISTORIQUES, antérieures à
     * ce contrôle, peuvent encore produire une part négative — et c'est
     * celles-là que l'audit doit retrouver.
     */
    public function test_une_part_negative_est_signalee(): void
    {
        $caissiere = $this->caissiere($this->rabat);
        $this->encaisser($caissiere, $this->online, 5000);

        $destinataire = $this->caissiere($this->rabat);
        $caisseSource = $caissiere->till()->firstOrFail();

        // Transfert historique : validé, imputé à Rabat, alors que ce tiroir
        // n'a jamais rien encaissé pour Rabat.
        CaisseTransfer::create([
            'reference' => 'TRF-HIST',
            'caisse_source_id' => $caisseSource->id,
            'caisse_destination_id' => $destinataire->till()->firstOrFail()->id,
            'montant' => 5000,
            'date_transfert' => now()->subDays(10),
            'etablissement_id' => $this->rabat->id,
            'solde_source_avant' => 5000,
            'solde_source_apres' => 0,
            'solde_dest_avant' => 0,
            'solde_dest_apres' => 5000,
            'statut' => CaisseTransfer::STATUT_VALIDE,
            'requested_by' => $caissiere->id,
            'validated_by' => $destinataire->id,
        ]);

        $this->artisan('finance:auditer')
            ->expectsOutputToContain('Parts négatives')
            ->expectsOutputToContain('point(s) à traiter')
            ->assertSuccessful();
    }

    /** Un enseignant qui saisit un paiement ressort en section 7. */
    public function test_un_agent_non_encaisseur_est_signale(): void
    {
        $enseignant = $this->caissiere($this->rabat, Employee::CATEGORIE_ENSEIGNANT);
        $this->encaisser($enseignant, $this->rabat, 1200);

        $this->artisan('finance:auditer')
            ->expectsOutputToContain('Agent non-encaisseur')
            ->assertSuccessful();
    }

    /** Un frais dont le montant a été écrasé à 0 ressort en section 5. */
    public function test_un_frais_ecrase_a_zero_est_signale(): void
    {
        $caissiere = $this->caissiere($this->rabat);
        $student = Student::factory()->create(['etablissement_id' => $this->rabat->id]);

        $group = Group::factory()->create([
            'etablissement_id' => $this->rabat->id,
            'annee_scolaire_id' => AnneeScolaire::query()->value('id'),
        ]);

        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->rabat->id,
            'annee_scolaire_id' => AnneeScolaire::query()->value('id'),
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2026-09-15',
            'montant_total' => 0,
        ]);

        InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Décembre',
            'montant_initial' => 1200, 'montant' => 0,
            'date_echeance' => '2026-12-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $this->artisan('finance:auditer')
            ->expectsOutputToContain('montant écrasé')
            ->assertSuccessful();
    }

    /** `--strict` fait échouer la commande dès qu'un point est trouvé. */
    public function test_strict_echoue_sur_un_point_a_traiter(): void
    {
        $enseignant = $this->caissiere($this->rabat, Employee::CATEGORIE_ENSEIGNANT);
        $this->encaisser($enseignant, $this->rabat, 1200);

        $this->artisan('finance:auditer', ['--strict' => true])->assertFailed();
    }

    /** L'audit ne doit RIEN écrire — c'est sa garantie première. */
    public function test_l_audit_n_ecrit_jamais_rien(): void
    {
        $caissiere = $this->caissiere($this->rabat);
        $this->encaisser($caissiere, $this->rabat, 5000);

        $caisse = $caissiere->till()->firstOrFail();
        $soldeAvant = (float) $caisse->solde;
        $encaissementsAvant = Encaissement::count();

        $this->artisan('finance:auditer')->assertSuccessful();

        $this->assertSame($soldeAvant, (float) $caisse->fresh()->solde);
        $this->assertSame($encaissementsAvant, Encaissement::count());
    }
}
