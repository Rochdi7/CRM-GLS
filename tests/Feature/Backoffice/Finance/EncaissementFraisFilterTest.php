<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
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
 * Le filtre « Frais » de la page Encaissements (04/09/2026).
 *
 * Il passe par `inscription_fees.frais_id` — l'entrée du CATALOGUE — et non
 * par le libellé copié sur la ligne d'inscription, qui se désynchronise dès
 * qu'un frais est renommé. Une avance, qui n'a pas de frais à elle, est
 * rattachée par les frais auxquels son argent a été APPLIQUÉ (même logique
 * que le filtre Groupe, qui rattache l'avance aux inscriptions de l'étudiant).
 */
final class EncaissementFraisFilterTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

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
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function feeFor(Student $student, Frais $frais, float $montant = 1000): InscriptionFee
    {
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
        ]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => $montant,
        ]);

        return InscriptionFee::create([
            'inscription_id' => $inscription->id, 'frais_id' => $frais->id, 'nom' => $frais->nom,
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
    }

    public function test_le_filtre_frais_ne_garde_que_les_paiements_de_ce_frais(): void
    {
        $user = $this->userWith('payments.view');
        $agent = $user->employee;
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id, 'solde' => 0]);

        $inscriptionFrais = Frais::create(['nom' => "Frais d'inscription", 'montant_defaut' => 500]);
        $octobre = Frais::create(['nom' => "Frais d'Octobre", 'montant_defaut' => 1000]);

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $feeInscription = $this->feeFor($student, $inscriptionFrais, 500);
        $feeOctobre = $this->feeFor($student, $octobre, 1000);

        Encaissement::create([
            'reference' => 'ENC-INS', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => $feeInscription->id, 'caisse_id' => $caisse->id,
            'montant' => 500, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20',
        ]);
        Encaissement::create([
            'reference' => 'ENC-OCT', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => $feeOctobre->id, 'caisse_id' => $caisse->id,
            'montant' => 1000, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-10-20',
        ]);

        $this->actingAs($user)
            ->get(route('backoffice.encaissements.index', [
                'dateFrom' => '-', 'dateTo' => '-', 'fraisFilter' => $octobre->id,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('encaissements.total', 1)
                ->where('encaissements.data.0.reference', 'ENC-OCT')
                ->where('montantTotal', '1000.00')
                ->where('filters.fraisFilter', (string) $octobre->id));
    }

    public function test_le_filtre_suit_le_catalogue_meme_apres_un_renommage(): void
    {
        $user = $this->userWith('payments.view');
        $agent = $user->employee;
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id, 'solde' => 0]);

        $frais = Frais::create(['nom' => 'Frais de Mai', 'montant_defaut' => 800]);
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, $frais, 800);

        Encaissement::create([
            'reference' => 'ENC-MAI', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $caisse->id,
            'montant' => 800, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-05-20',
        ]);

        // Le catalogue est renommé : la ligne d'inscription garde son ancien
        // libellé (copie figée), le filtre doit malgré tout la retrouver.
        $frais->update(['nom' => 'Frais de Mai (2026)']);

        $this->actingAs($user)
            ->get(route('backoffice.encaissements.index', [
                'dateFrom' => '-', 'dateTo' => '-', 'fraisFilter' => $frais->id,
            ]))
            ->assertInertia(fn (Assert $page) => $page->where('encaissements.total', 1));
    }

    public function test_une_avance_est_rattachee_aux_frais_ou_son_argent_est_alle(): void
    {
        $user = $this->userWith('payments.view');
        $agent = $user->employee;
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id, 'solde' => 0]);

        $frais = Frais::create(['nom' => 'Frais de Novembre', 'montant_defaut' => 600]);
        $autre = Frais::create(['nom' => 'Frais de Decembre', 'montant_defaut' => 600]);

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $fee = $this->feeFor($student, $frais, 600);

        $avance = Encaissement::create([
            'reference' => 'ENC-AV', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => null, 'caisse_id' => $caisse->id,
            'montant' => 600, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-11-02',
        ]);
        // La ligne « application » : l'argent de l'avance atterrit sur le frais.
        Encaissement::create([
            'reference' => 'ENC-AV-APP', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $caisse->id,
            'applied_from_encaissement_id' => $avance->id,
            'montant' => 600, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-11-05',
        ]);

        // Onglet Avances, filtré sur le frais servi : l'avance est là.
        $this->actingAs($user)
            ->get(route('backoffice.encaissements.index', [
                'view' => 'avance', 'soldeFilter' => 'tous', 'dateFrom' => '-', 'dateTo' => '-',
                'fraisFilter' => $frais->id,
            ]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('encaissements.total', 1)
                ->where('encaissements.data.0.reference', 'ENC-AV'));

        // Filtré sur un AUTRE frais : elle n'y est pas.
        $this->actingAs($user)
            ->get(route('backoffice.encaissements.index', [
                'view' => 'avance', 'soldeFilter' => 'tous', 'dateFrom' => '-', 'dateTo' => '-',
                'fraisFilter' => $autre->id,
            ]))
            ->assertInertia(fn (Assert $page) => $page->where('encaissements.total', 0));
    }

    public function test_le_catalogue_des_frais_est_envoye_a_la_page(): void
    {
        $user = $this->userWith('payments.view');
        $actif = Frais::create(['nom' => 'Frais actif', 'montant_defaut' => 100]);
        // Un frais désactivé reste filtrable : les paiements qu'il a réglés
        // sont toujours à l'écran.
        $inactif = Frais::create(['nom' => 'Frais retire', 'montant_defaut' => 100, 'statut' => Frais::STATUT_INACTIF]);

        $this->actingAs($user)
            ->get(route('backoffice.encaissements.index', ['dateFrom' => '-', 'dateTo' => '-']))
            ->assertInertia(function (Assert $page) use ($actif, $inactif) {
                $ids = array_column($page->toArray()['props']['frais'], 'id');
                $this->assertContains($actif->id, $ids);
                $this->assertContains($inactif->id, $ids);
            });
    }
}
