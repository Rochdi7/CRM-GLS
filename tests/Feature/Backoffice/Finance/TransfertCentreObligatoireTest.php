<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Actions\DemanderTransfertCaisse;
use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Domain\Finance\Support\VentilationCentre;
use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un transfert de caisse porte TOUJOURS un centre (11/09/2026).
 *
 * `caisse_transfers.etablissement_id` dit de quel centre l'argent est
 * physiquement sorti. Laissé NULL, deux choses cassent :
 *
 *  - `VentilationCentre` retombe sur `caisses.etablissement_id` et impute la
 *    sortie au centre de RATTACHEMENT du tiroir. Cas réel : la caisse
 *    d'Ahmed Khadimerrahman (GLS Online) encaisse 500 DH pour Online et
 *    1 800 DH pour Kénitra, puis transfère tout. TRF-028 sort les 1 800 DH
 *    de Kénitra mais en débite Online, qui affiche **-1 800,00 DH** pendant
 *    que Kénitra garde un argent qui n'y est plus ;
 *  - la valeur devient un résultat re-dérivé à chaque lecture, qui change
 *    si la caisse est un jour re-rattachée à un autre centre.
 *
 * D'où le repli FIGÉ à l'écriture : sur « Tous les centres » la colonne
 * prend le centre de rattachement de la caisse source — la même valeur,
 * mais écrite une fois et auditée.
 *
 * Réparation du passé : `php artisan transferts:imputer-centre`, qui exige
 * une PREUVE (le centre nommé doit avoir alimenté le tiroir) — jamais un
 * backfill en masse, interdit par §11.
 */
final class TransfertCentreObligatoireTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $online;

    private Etablissement $kenitra;

    private Employee $ahmed;

    private Employee $yassine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $this->online = Etablissement::factory()->create(['nom_centre' => 'GLS Online']);
        $this->kenitra = Etablissement::factory()->create(['nom_centre' => 'GLS Kénitra']);

        // Le tiroir d'Ahmed est RATTACHÉ à Online, mais il encaisse aussi
        // pour Kénitra — c'est toute la difficulté.
        $userAhmed = User::factory()->create()->assignRole('super-admin');
        $this->ahmed = Employee::factory()->create([
            'user_id' => $userAhmed->id,
            'etablissement_id' => $this->online->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);
        $this->ahmed->syncEtablissements([$this->online->id, $this->kenitra->id], $this->online->id);

        $userYassine = User::factory()->create()->assignRole('super-admin');
        $this->yassine = Employee::factory()->create([
            'user_id' => $userYassine->id,
            'etablissement_id' => $this->kenitra->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ]);

        $this->ahmed = $this->ahmed->fresh();
        $this->yassine = $this->yassine->fresh();
    }

    /** Encaisse en espèces POUR un centre donné, dans le tiroir d'Ahmed. */
    private function encaisserPour(Etablissement $centre, float $montant): void
    {
        $student = Student::factory()->create(['etablissement_id' => $centre->id]);

        app(EnregistrerEncaissement::class)->handle([
            'student_id' => $student->id,
            'etablissement_id' => $centre->id,
            'inscription_fee_id' => null,
            'caisse_id' => $this->ahmed->till()->firstOrFail()->id,
            'montant' => $montant,
            'methode' => 'Espèces',
            'date_paiement' => '2026-09-05',
        ], $this->ahmed);
    }

    /**
     * Place le contexte sur un centre.
     *
     * `CurrentContext` lit la session ET vérifie la portée de l'utilisateur
     * connecté : sans `actingAs`, il ne résout aucun centre — le singleton
     * doit donc être oublié entre deux changements.
     */
    private function contexte(?int $centreId): void
    {
        $this->actingAs($this->ahmed->user->fresh());

        app()->forgetInstance(CurrentContext::class);
        app(CurrentContext::class)->setEtablissement($centreId);
    }

    private function demanderTransfert(float $montant): CaisseTransfer
    {
        return app(DemanderTransfertCaisse::class)->handle([
            'caisse_source_id' => $this->ahmed->till()->firstOrFail()->id,
            'caisse_destination_id' => $this->yassine->till()->firstOrFail()->id,
            'montant' => $montant,
        ], $this->ahmed);
    }

    // ------------------------------------------------------------------ //

    /**
     * Sur « Tous les centres », la colonne n'est jamais laissée NULL : elle
     * FIGE le centre de rattachement de la caisse source — la même valeur que
     * la ventilation dérivait à chaque lecture, mais écrite une fois et
     * auditée.
     */
    public function test_sans_centre_actif_le_transfert_fige_celui_de_la_caisse_source(): void
    {
        $this->encaisserPour($this->online, 500);

        $this->contexte(null);

        $transfert = $this->demanderTransfert(500);

        $this->assertNotNull($transfert->etablissement_id, 'la colonne ne doit jamais rester NULL');
        $this->assertSame(
            $this->online->id,
            (int) $transfert->etablissement_id,
            'le repli doit être le centre de rattachement de la caisse source'
        );
    }

    public function test_le_transfert_porte_le_centre_actif(): void
    {
        $this->encaisserPour($this->kenitra, 1800);

        $this->contexte($this->kenitra->id);

        $transfert = $this->demanderTransfert(1800);

        $this->assertSame($this->kenitra->id, (int) $transfert->etablissement_id);
    }

    /**
     * Le cas Ahmed, joué en entier : sans centre sur le transfert, Online
     * plongeait à -1 800 DH. Avec le centre exact, chaque part retombe à 0.
     */
    public function test_le_centre_du_transfert_evite_une_part_negative(): void
    {
        $this->encaisserPour($this->online, 500);
        $this->encaisserPour($this->kenitra, 1800);

        $caisse = $this->ahmed->till()->firstOrFail();

        // Les 500 DH d'Online partent (validés pour que la ventilation compte).
        $this->contexte($this->online->id);
        $this->validerTransfert($this->demanderTransfert(500));

        // Puis les 1 800 DH de Kénitra.
        $this->contexte($this->kenitra->id);
        $this->validerTransfert($this->demanderTransfert(1800));

        $ventilation = app(VentilationCentre::class);
        $caisse = $caisse->fresh();

        $this->assertSame(0.0, $ventilation->soldeDuCentre($caisse, $this->online->id), 'Online devrait être à 0');
        $this->assertSame(0.0, $ventilation->soldeDuCentre($caisse, $this->kenitra->id), 'Kénitra devrait être à 0');
        $this->assertSame(0.0, (float) $caisse->solde, 'le tiroir devrait être vide');
    }

    /** La commande de réparation refuse un centre qui n'a jamais alimenté le tiroir. */
    public function test_la_commande_refuse_une_imputation_non_prouvee(): void
    {
        $this->encaisserPour($this->kenitra, 1800);

        $this->contexte($this->kenitra->id);
        $transfert = $this->demanderTransfert(1800);

        // On efface le centre pour rejouer l'état d'un transfert d'avant le
        // 09/09/2026.
        $transfert->forceFill(['etablissement_id' => null])->saveQuietly();

        // Online n'a rien mis dans ce tiroir : l'imputation est refusée.
        $this->artisan('transferts:imputer-centre', [
            'reference' => $transfert->reference,
            '--centre' => 'Online',
        ])->assertFailed();

        $this->assertNull($transfert->fresh()->etablissement_id);
    }

    /** Et l'accepte, prouvée par les encaissements, sans toucher à l'argent. */
    public function test_la_commande_impute_un_centre_prouve_sans_toucher_l_argent(): void
    {
        $this->encaisserPour($this->kenitra, 1800);

        $this->contexte($this->kenitra->id);
        $transfert = $this->demanderTransfert(1800);
        $transfert->forceFill(['etablissement_id' => null])->saveQuietly();

        $caisse = Caisse::findOrFail($transfert->caisse_source_id);
        $soldeAvant = (float) $caisse->solde;
        $montantAvant = (float) $transfert->montant;

        $this->artisan('transferts:imputer-centre', [
            'reference' => $transfert->reference,
            '--centre' => 'Kénitra',
        ])->assertSuccessful();

        $apres = $transfert->fresh();

        $this->assertSame($this->kenitra->id, (int) $apres->etablissement_id);
        $this->assertSame($montantAvant, (float) $apres->montant, 'le montant a changé');
        $this->assertSame($soldeAvant, (float) $caisse->fresh()->solde, 'le solde a changé');
    }

    /** Le dry-run n'écrit rien. */
    public function test_le_dry_run_de_la_commande_n_ecrit_rien(): void
    {
        $this->encaisserPour($this->kenitra, 1800);

        $this->contexte($this->kenitra->id);
        $transfert = $this->demanderTransfert(1800);
        $transfert->forceFill(['etablissement_id' => null])->saveQuietly();

        $this->artisan('transferts:imputer-centre', [
            'reference' => $transfert->reference,
            '--centre' => 'Kénitra',
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertNull($transfert->fresh()->etablissement_id);
    }

    private function validerTransfert(CaisseTransfer $transfert): void
    {
        app(ValiderTransfertCaisse::class)
            ->handle($transfert, $this->yassine);
    }
}
