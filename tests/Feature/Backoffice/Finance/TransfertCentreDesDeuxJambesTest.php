<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Finance\Actions\DemanderTransfertCaisse;
use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Domain\Finance\Queries\GetCaisseGlobale;
use App\Domain\Finance\Support\VentilationCentre;
use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Models\AnneeScolaire;
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
 * Un transfert change de TIROIR, jamais de CENTRE (11/09/2026).
 *
 * Les deux jambes portent le centre d'où l'argent SORT. La sortie suivait
 * déjà cette règle depuis le 09/09 ; l'entrée retombait sur le centre de
 * rattachement du tiroir qui reçoit, si bien que l'argent changeait de
 * centre en chemin.
 *
 * Mesuré sur la production : 6 transferts inter-centres, 228 840,00 DH
 * concernés. Les 157 600,00 DH remis par Rabat à la caisse centrale
 * devenaient du Marrakech ; les 69 440,00 DH versés par Casablanca à Yassine
 * (TRF-032, dont le tiroir est rattaché à Kénitra) devenaient du Kénitra.
 * Casablanca voyait son argent quitter ses comptes sans que personne ne le
 * reçoive — de l'argent qui s'évapore d'un centre pour réapparaître dans un
 * autre.
 *
 * ⚠ La règle a UNE seule implémentation, `VentilationCentre::centreDeLaJambe()`,
 * partagée par le solde ventilé ET par les lignes de `GetCaisseDetails` :
 * deux copies finiraient par diverger, et l'écran montrerait des lignes que
 * son propre total ne compte pas.
 */
final class TransfertCentreDesDeuxJambesTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $casablanca;

    private Etablissement $kenitra;

    private Employee $maria;

    private Employee $yassine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $this->casablanca = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);
        $this->kenitra = Etablissement::factory()->create(['nom_centre' => 'GLS Kénitra']);

        // Maria encaisse à Casablanca ; Yassine a son tiroir à Kénitra.
        $this->maria = $this->caissiere($this->casablanca);
        $this->yassine = $this->caissiere($this->kenitra);
    }

    private function caissiere(Etablissement $centre): Employee
    {
        $user = User::factory()->create()->assignRole('super-admin');

        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $centre->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
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

    private function transferer(Employee $de, Employee $vers, float $montant, ?int $centreId): CaisseTransfer
    {
        $this->contexte($de, $centreId);

        $transfert = app(DemanderTransfertCaisse::class)->handle([
            'caisse_source_id' => $de->till()->firstOrFail()->id,
            'caisse_destination_id' => $vers->till()->firstOrFail()->id,
            'montant' => $montant,
        ], $de);

        app(ValiderTransfertCaisse::class)->handle($transfert, $vers);

        return $transfert->fresh();
    }

    // ------------------------------------------------------------------ //

    /**
     * Le cas TRF-032 : Casablanca verse à un tiroir rattaché à Kénitra.
     * L'argent reste du Casablanca — il a changé de tiroir, pas de centre.
     */
    public function test_l_argent_recu_garde_le_centre_d_ou_il_sort(): void
    {
        $this->encaisser($this->maria, $this->casablanca, 69440);

        $this->transferer($this->maria, $this->yassine, 69440, $this->casablanca->id);

        $ventilation = app(VentilationCentre::class);
        $tiroirYassine = $this->yassine->till()->firstOrFail()->fresh();

        $this->assertSame(
            69440.0,
            $ventilation->soldeDuCentre($tiroirYassine, $this->casablanca->id),
            "l'argent reçu doit rester imputé à Casablanca"
        );

        $this->assertSame(
            0.0,
            $ventilation->soldeDuCentre($tiroirYassine, $this->kenitra->id),
            'Kénitra ne doit rien recevoir : ce tiroir n\'y a rien encaissé'
        );
    }

    /** Et le centre d'origine retombe bien à zéro : l'argent est parti. */
    public function test_le_centre_source_ne_garde_rien_apres_le_transfert(): void
    {
        $this->encaisser($this->maria, $this->casablanca, 69440);

        $this->transferer($this->maria, $this->yassine, 69440, $this->casablanca->id);

        $ventilation = app(VentilationCentre::class);
        $tiroirMaria = $this->maria->till()->firstOrFail()->fresh();

        $this->assertSame(0.0, $ventilation->soldeDuCentre($tiroirMaria, $this->casablanca->id));
    }

    /**
     * L'invariant qui prime sur tout : la somme des parts retombe sur le
     * solde stocké, qui reste l'autorité (CaisseLedger, §11).
     */
    public function test_la_somme_des_parts_retombe_sur_le_solde_des_deux_cotes(): void
    {
        $this->encaisser($this->maria, $this->casablanca, 69440);
        $this->encaisser($this->yassine, $this->kenitra, 2600);

        $this->transferer($this->maria, $this->yassine, 69440, $this->casablanca->id);

        $ventilation = app(VentilationCentre::class);

        foreach ([$this->maria, $this->yassine] as $agent) {
            $tiroir = $agent->till()->firstOrFail()->fresh();
            $somme = 0.0;

            foreach (Etablissement::pluck('id') as $centreId) {
                $somme += $ventilation->soldeDuCentre($tiroir, (int) $centreId);
            }

            $this->assertSame(
                round((float) $tiroir->solde, 2),
                round($somme, 2),
                "la somme des parts doit retomber sur le solde de {$tiroir->nom}"
            );
        }
    }

    /**
     * Un transfert ANTÉRIEUR à la colonne (etablissement_id NULL) se replie
     * sur le centre de la caisse SOURCE — jamais sur celui du tiroir qui
     * reçoit, sinon le bug corrigé ici reviendrait par la porte de derrière.
     */
    public function test_le_repli_d_une_entree_suit_la_caisse_source(): void
    {
        $this->encaisser($this->maria, $this->casablanca, 69440);

        $transfert = $this->transferer($this->maria, $this->yassine, 69440, $this->casablanca->id);

        // On efface le centre pour rejouer un transfert d'avant le 09/09/2026.
        $transfert->forceFill(['etablissement_id' => null])->saveQuietly();

        $ventilation = app(VentilationCentre::class);
        $tiroirYassine = $this->yassine->till()->firstOrFail()->fresh();

        $this->assertSame(
            69440.0,
            $ventilation->soldeDuCentre($tiroirYassine, $this->casablanca->id),
            'le repli doit suivre la caisse SOURCE, pas le tiroir qui reçoit'
        );
    }

    /**
     * « Caisse globale » AVEC une date rembobine les soldes depuis les tables
     * source (GetCaisseGlobale::soldesAt) et doit imputer chaque jambe par la
     * MÊME règle que la vue courante — centreDeLaJambe(). Jusqu'au 11/09/2026
     * l'entrée y retombait sur le centre du tiroir qui reçoit : le même écran
     * montrait 69 440 DH à Casablanca sans date et à Kénitra avec une date.
     */
    public function test_caisse_globale_datee_impute_l_entree_au_centre_source(): void
    {
        $this->encaisser($this->maria, $this->casablanca, 69440);
        $this->transferer($this->maria, $this->yassine, 69440, $this->casablanca->id);

        $demain = now()->addDay()->toDateString();
        $user = $this->maria->user->fresh();

        foreach ([$this->casablanca, $this->kenitra] as $centre) {
            $this->contexte($this->maria, $centre->id);

            $sansDate = app(GetCaisseGlobale::class)($user, []);
            $avecDate = app(GetCaisseGlobale::class)($user, ['dateTo' => $demain]);

            $this->assertSame(
                $sansDate['total'],
                $avecDate['total'],
                "sur {$centre->nom_centre}, la vue datée doit annoncer le même total que la vue courante"
            );
        }

        $this->contexte($this->maria, $this->casablanca->id);
        $this->assertSame('69440.00', app(GetCaisseGlobale::class)($user, ['dateTo' => $demain])['total']);

        $this->contexte($this->maria, $this->kenitra->id);
        $this->assertSame('0.00', app(GetCaisseGlobale::class)($user, ['dateTo' => $demain])['total']);
    }
}
