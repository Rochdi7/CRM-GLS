<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Expenses\Actions\EnregistrerDepense;
use App\Domain\Finance\Actions\DemanderTransfertCaisse;
use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Domain\Finance\Queries\GetCaisseTransfersList;
use App\Domain\Finance\Support\VentilationCentre;
use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Models\AnneeScolaire;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Student;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Context\CurrentContext;
use App\Support\Settings\AppSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Un transfert « En attente » RÉSERVE son montant (11/09/2026).
 *
 * Une demande ne bouge pas `caisses.solde` — seule la validation par le
 * destinataire le fait. Entre les deux, le tiroir continuait de vivre : une
 * dépense, un remboursement ou une seconde demande pouvaient sortir l'argent
 * déjà promis, et la validation tombait ensuite sur un solde insuffisant.
 * Désormais tout ce qui fait sortir de l'argent se contrôle contre
 * `solde − réservé` (ReservationTransferts), et le plafond de transfert par
 * centre déduit lui aussi ce qui est déjà promis.
 *
 * Et la règle « un transfert change de tiroir, jamais de centre » rend
 * l'argent reçu transférable depuis le centre d'où il vient : un caissier de
 * Marrakech qui remet à un collègue dont le tiroir est rattaché à Kénitra
 * lui donne de l'argent MARRAKECH, que ce collègue voit et peut renvoyer
 * depuis Marrakech — pas depuis Kénitra.
 */
final class ReservationTransfertEnAttenteTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $marrakech;

    private Etablissement $kenitra;

    private Employee $expediteur;

    private Employee $destinataire;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);

        $this->marrakech = Etablissement::factory()->create(['nom_centre' => 'GLS Marrakech']);
        $this->kenitra = Etablissement::factory()->create(['nom_centre' => 'GLS Kénitra']);

        $this->expediteur = $this->caissiere($this->marrakech);
        // Le destinataire TRAVAILLE à Marrakech mais son tiroir est rattaché à Kénitra.
        $this->destinataire = $this->caissiere($this->kenitra);
    }

    private function caissiere(Etablissement $centre): Employee
    {
        $user = User::factory()->create()->assignRole('super-admin');

        return Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $centre->id,
            'categorie' => Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE,
        ])->fresh();
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

    private function demander(Employee $de, Employee $vers, float $montant, ?int $centreId): CaisseTransfer
    {
        $this->contexte($de, $centreId);

        return app(DemanderTransfertCaisse::class)->handle([
            'caisse_source_id' => $de->till()->firstOrFail()->id,
            'caisse_destination_id' => $vers->till()->firstOrFail()->id,
            'montant' => $montant,
        ], $de);
    }

    private function depenser(Employee $agent, float $montant): void
    {
        $type = TypeDepense::query()->firstOrCreate(['nom' => 'Loyer'], ['is_system' => false, 'statut' => 'Actif']);

        app(EnregistrerDepense::class)->handle([
            'type_depense_id' => $type->id,
            'caisse_id' => $agent->till()->firstOrFail()->id,
            'montant' => $montant,
            'date_depense' => '2026-09-10',
            'description' => 'Loyer',
        ], $agent);
    }

    private function plafond(Employee $agent, ?int $centreId): float
    {
        return app(VentilationCentre::class)->plafondTransfert($agent->till()->firstOrFail()->fresh(), $centreId);
    }

    // ------------------------------------------------------------------ //

    public function test_une_depense_ne_peut_pas_depenser_l_argent_promis_par_un_transfert_en_attente(): void
    {
        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, false);
        $this->encaisser($this->expediteur, $this->marrakech, 1000);
        $this->demander($this->expediteur, $this->destinataire, 600, $this->marrakech->id);

        try {
            $this->depenser($this->expediteur, 500);
            $this->fail('600 DH sont réservés : une dépense de 500 DH sur les 400 DH restants doit être refusée.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('400,00', $e->errors()['montant'][0]);
            $this->assertStringContainsString('600,00 DH réservés', $e->errors()['montant'][0]);
            $this->assertStringContainsString('TRF-', $e->errors()['montant'][0]);
        }

        // Ce qui n'est pas promis reste dépensable, jusqu'à 0,00 disponible.
        $this->depenser($this->expediteur, 400);
        $this->assertSame('600.00', (string) $this->expediteur->till()->firstOrFail()->fresh()->solde);

        // Et la validation trouve bien son argent.
        $transfert = CaisseTransfer::query()->firstOrFail();
        app(ValiderTransfertCaisse::class)->handle($transfert, $this->destinataire);
        $this->assertSame('0.00', (string) $this->expediteur->till()->firstOrFail()->fresh()->solde);
    }

    public function test_une_seconde_demande_ne_peut_pas_promettre_deux_fois_le_meme_argent(): void
    {
        $this->encaisser($this->expediteur, $this->marrakech, 1000);
        $this->demander($this->expediteur, $this->destinataire, 600, $this->marrakech->id);

        $this->assertSame(400.0, $this->plafond($this->expediteur, $this->marrakech->id));

        try {
            $this->demander($this->expediteur, $this->destinataire, 500, $this->marrakech->id);
            $this->fail('Le plafond est 400 DH une fois les 600 DH réservés.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('400,00', $e->errors()['montant'][0]);
        }

        $this->demander($this->expediteur, $this->destinataire, 400, $this->marrakech->id);
        $this->assertSame(0.0, $this->plafond($this->expediteur, $this->marrakech->id));
    }

    public function test_annuler_la_demande_libere_la_reservation(): void
    {
        $this->encaisser($this->expediteur, $this->marrakech, 1000);
        $transfert = $this->demander($this->expediteur, $this->destinataire, 600, $this->marrakech->id);

        $transfert->update(['statut' => CaisseTransfer::STATUT_ANNULE]);

        $this->assertSame(1000.0, $this->plafond($this->expediteur, $this->marrakech->id));
    }

    public function test_un_tiroir_vide_ne_peut_rien_transferer(): void
    {
        $this->expectException(ValidationException::class);

        $this->demander($this->expediteur, $this->destinataire, 1, $this->marrakech->id);
    }

    public function test_sur_tous_les_centres_le_tiroir_entier_moins_le_reserve_reste_la_borne(): void
    {
        $this->encaisser($this->expediteur, $this->marrakech, 1000);
        $this->demander($this->expediteur, $this->destinataire, 600, null);

        $this->assertSame(400.0, $this->plafond($this->expediteur, null));

        try {
            $this->demander($this->expediteur, $this->destinataire, 500, null);
            $this->fail('« Tous les centres » n\'était pas plafonné du tout : 600 DH promis, 500 DH redemandés sur 400 DH.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('400,00', $e->errors()['montant'][0]);
        }
    }

    /**
     * Le cas signalé : l'argent remis par Marrakech à un collègue dont le
     * tiroir est rattaché à Kénitra reste du MARRAKECH — c'est depuis
     * Marrakech qu'il le voit et peut le renvoyer, pas depuis Kénitra.
     */
    public function test_l_argent_recu_est_transferable_depuis_le_centre_d_ou_il_vient(): void
    {
        $this->encaisser($this->expediteur, $this->marrakech, 1000);
        $transfert = $this->demander($this->expediteur, $this->destinataire, 1000, $this->marrakech->id);
        app(ValiderTransfertCaisse::class)->handle($transfert, $this->destinataire);

        $this->assertSame(1000.0, $this->plafond($this->destinataire, $this->marrakech->id));
        $this->assertSame(0.0, $this->plafond($this->destinataire, $this->kenitra->id));

        // Et l'onglet « Validation de transfert » affiche ce même chiffre.
        $this->contexte($this->destinataire, $this->marrakech->id);
        $this->assertSame('1000.00', app(GetCaisseTransfersList::class)($this->destinataire->user->fresh())['soldeCaisse']);

        $this->contexte($this->destinataire, $this->kenitra->id);
        $this->assertSame('0.00', app(GetCaisseTransfersList::class)($this->destinataire->user->fresh())['soldeCaisse']);

        // Il peut le renvoyer depuis Marrakech…
        $this->demander($this->destinataire, $this->expediteur, 1000, $this->marrakech->id);

        // …mais rien depuis Kénitra : ce n'est pas l'argent de Kénitra.
        $this->expectException(ValidationException::class);
        $this->demander($this->destinataire, $this->expediteur, 1, $this->kenitra->id);
    }
}
