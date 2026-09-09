<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Clôture d'un groupe (« Fin de formation » / « Annulée ») et sa cascade sur
 * les inscriptions — Domain\Groups\Actions\CloturerInscriptionsGroupe
 * (09/09/2026).
 *
 * Ce qui est asserté ici tient en une phrase : un groupe clos ne doit plus
 * rien réclamer, et n'a pas le droit d'effacer une créance sur laquelle de
 * l'argent est arrivé.
 *
 *  - les inscriptions « Active » passent « Annulée » avec le motif système ;
 *  - une ligne de frais SANS aucun encaissement est masquée, jamais
 *    supprimée, et `montant_total` suit ;
 *  - une ligne PAYÉE ou PARTIELLEMENT payée reste visible et due, et son
 *    encaissement reste accroché (aucune avance créée, `caisses.solde`
 *    immobile) ;
 *  - un frais du catalogue du groupe n'est détaché que si AUCUN étudiant du
 *    groupe ne l'a payé ;
 *  - un dossier déjà clos (Annulée / Changement) garde son motif d'origine.
 */
final class GroupClotureCascadeTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

    private Group $group;

    private Caisse $caisse;

    private Employee $agent;

    private Frais $fraisAvril;

    private Frais $fraisMai;

    private Frais $fraisInscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->centre = Etablissement::factory()->create();
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Group::STATUT_EN_FORMATION,
            'date_fin_formation' => '2026-06-30',
        ]);

        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->caisse = $this->agent->till()->firstOrFail();

        $this->fraisAvril = Frais::create(['nom' => "Frais d'Avril", 'montant_defaut' => 1300]);
        $this->fraisMai = Frais::create(['nom' => 'Frais de Mai', 'montant_defaut' => 1300]);
        $this->fraisInscription = Frais::create(['nom' => "Frais d'inscription", 'montant_defaut' => 300]);

        $this->group->frais()->sync([
            $this->fraisAvril->id => ['montant' => 1300, 'date_echeance' => '2026-04-01'],
            $this->fraisMai->id => ['montant' => 1300, 'date_echeance' => '2026-05-01'],
            $this->fraisInscription->id => ['montant' => 300, 'date_echeance' => '2026-01-01'],
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function enrol(string $nom, string $statut = Inscription::STATUT_ACTIVE): Inscription
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id, 'nom' => $nom]);

        return Inscription::create([
            'reference' => 'INS-CLO-'.$student->id,
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => $statut,
            'motif_annulation' => $statut === Inscription::STATUT_ACTIVE ? null : 'Non-paiement',
            'date_inscription' => '2026-01-10',
            'montant_total' => 2900,
        ]);
    }

    private function addFee(Inscription $inscription, Frais $frais, float $montant, float $paye = 0): InscriptionFee
    {
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'frais_id' => $frais->id,
            'nom' => $frais->nom,
            'montant_initial' => $montant,
            'montant' => $montant,
            'date_echeance' => '2026-03-01',
        ]);

        if ($paye > 0) {
            Encaissement::create([
                'reference' => 'ENC-CLO-'.$fee->id,
                'student_id' => $inscription->student_id,
                'etablissement_id' => $this->centre->id,
                'inscription_fee_id' => $fee->id,
                'montant' => $paye,
                'methode' => Encaissement::METHODE_ESPECES,
                'date_paiement' => '2026-01-15',
                'caisse_id' => $this->caisse->id,
                'agent_id' => $this->agent->id,
            ]);
        }

        return $fee->refresh();
    }

    public function test_archiving_a_group_cancels_active_registrations_and_removes_only_unpaid_fees(): void
    {
        $inscription = $this->enrol('BENALI');
        $feePaye = $this->addFee($inscription, $this->fraisInscription, 300, paye: 300);
        $feePartiel = $this->addFee($inscription, $this->fraisAvril, 1300, paye: 500);
        $feeNonPaye = $this->addFee($inscription, $this->fraisMai, 1300);

        $soldeAvant = $this->caisse->fresh()->solde;

        $this->actingAs($this->superAdmin())
            ->post(route('backoffice.groups.archive', $this->group))
            ->assertRedirect();

        $this->assertSame(Group::STATUT_FIN_FORMATION, $this->group->fresh()->statut);

        $inscription->refresh();
        $this->assertSame(Inscription::STATUT_ANNULEE, $inscription->statut);
        $this->assertSame('Clôture du groupe', $inscription->motif_annulation);

        // Une ligne qui a reçu de l'argent — même partiellement — reste due.
        $this->assertNull($feePaye->fresh()->masque_le);
        $this->assertNull($feePartiel->fresh()->masque_le);
        // Celle qui n'a jamais rien reçu est masquée, jamais supprimée.
        $this->assertNotNull($feeNonPaye->fresh()->masque_le);
        $this->assertDatabaseHas('inscription_fees', ['id' => $feeNonPaye->id]);

        // Le total suit les lignes VISIBLES : 300 + 1300.
        $this->assertSame('1600.00', $inscription->fresh()->montant_total);

        // Aucun argent n'a bougé : rien n'est devenu avance, la caisse est
        // immobile.
        $this->assertSame($feePaye->id, Encaissement::where('reference', 'ENC-CLO-'.$feePaye->id)->value('inscription_fee_id'));
        $this->assertSame($soldeAvant, $this->caisse->fresh()->solde);
    }

    public function test_cancelling_a_group_applies_the_same_cascade(): void
    {
        $inscription = $this->enrol('CHAKIR');
        $fee = $this->addFee($inscription, $this->fraisMai, 1300);

        $this->actingAs($this->superAdmin())
            ->post(route('backoffice.groups.annuler', $this->group))
            ->assertRedirect();

        $this->assertSame(Group::STATUT_ANNULEE, $this->group->fresh()->statut);
        $this->assertSame(Inscription::STATUT_ANNULEE, $inscription->fresh()->statut);
        $this->assertNotNull($fee->fresh()->masque_le);
    }

    public function test_a_group_fee_paid_by_any_student_stays_attached_to_the_group(): void
    {
        // Un seul étudiant a payé les frais d'inscription ; personne n'a
        // payé Avril ni Mai.
        $payeur = $this->enrol('DRISSI');
        $this->addFee($payeur, $this->fraisInscription, 300, paye: 300);
        $this->addFee($payeur, $this->fraisAvril, 1300);

        $autre = $this->enrol('EL AMRANI');
        $this->addFee($autre, $this->fraisInscription, 300);
        $this->addFee($autre, $this->fraisMai, 1300);

        $this->actingAs($this->superAdmin())
            ->post(route('backoffice.groups.archive', $this->group))
            ->assertRedirect();

        $restants = $this->group->fresh()->frais()->pluck('frais.id');

        $this->assertTrue($restants->contains($this->fraisInscription->id));
        $this->assertFalse($restants->contains($this->fraisAvril->id));
        $this->assertFalse($restants->contains($this->fraisMai->id));
    }

    public function test_a_fee_paid_on_an_already_cancelled_registration_still_keeps_it_attached(): void
    {
        // L'argent d'un dossier clos compte autant : détacher le frais
        // rendrait son montant de référence introuvable.
        $ancien = $this->enrol('FASSI', Inscription::STATUT_ANNULEE);
        $this->addFee($ancien, $this->fraisAvril, 1300, paye: 1300);

        $this->actingAs($this->superAdmin())
            ->post(route('backoffice.groups.archive', $this->group))
            ->assertRedirect();

        $this->assertTrue($this->group->fresh()->frais()->pluck('frais.id')->contains($this->fraisAvril->id));
    }

    public function test_an_already_closed_registration_keeps_its_own_reason(): void
    {
        $ancien = $this->enrol('GHALI', Inscription::STATUT_ANNULEE);
        $feeNonPaye = $this->addFee($ancien, $this->fraisMai, 1300);

        $this->actingAs($this->superAdmin())
            ->post(route('backoffice.groups.archive', $this->group))
            ->assertRedirect();

        $ancien->refresh();
        $this->assertSame(Inscription::STATUT_ANNULEE, $ancien->statut);
        $this->assertSame('Non-paiement', $ancien->motif_annulation);
        // Ses lignes ne sont pas retouchées : le dossier était déjà clos.
        $this->assertNull($feeNonPaye->fresh()->masque_le);
    }
}
