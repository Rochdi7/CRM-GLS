<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Domain\Payments\Actions\AppliquerAvance;
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
use App\Models\Virement;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une demande de virement ne retient plus son inscription (30/10/2026).
 *
 * INS-8146 (Rabat) : VIR-201/VIR-202 validés, leurs encaissements convertis
 * en avance — le dossier ne portait plus un dirham, mais la contrainte
 * RESTRICT de `virements.inscription_id` refusait la suppression. Une demande
 * DÉCIDÉE est désormais détachée ; une demande EN ATTENTE bloque toujours,
 * avec un message qui la nomme.
 */
final class InscriptionDeleteVirementTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private Group $group;

    private AnneeScolaire $annee;

    private Employee $agent;

    private Student $student;

    private Inscription $inscription;

    private InscriptionFee $fee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->centre = Etablissement::factory()->create();
        $this->annee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        [$this->inscription, $this->fee] = $this->inscrire('INS-VIR');

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);
    }

    /**
     * @return array{0: Inscription, 1: InscriptionFee}
     */
    private function inscrire(string $reference): array
    {
        $inscription = Inscription::create([
            'reference' => $reference,
            'student_id' => $this->student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2026-10-01',
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'nom' => "Frais d'inscription A1/A2/B1",
            'montant_initial' => 300,
            'montant' => 300,
            'date_echeance' => '2026-10-05',
            'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$inscription, $fee];
    }

    private function virement(string $statut, ?Encaissement $encaissement = null): Virement
    {
        return Virement::create([
            'reference' => 'VIR-'.fake()->unique()->numberBetween(100, 999),
            'student_id' => $this->student->id,
            'inscription_id' => $this->inscription->id,
            'inscription_fee_id' => $this->fee->id,
            'encaissement_id' => $encaissement?->id,
            'etablissement_id' => $this->centre->id,
            'montant' => 300,
            'nom_payeur' => 'Parent',
            'reference_virement' => '048505540',
            'date_operation' => now()->toDateString(),
            'statut' => $statut,
            'demande_par_id' => $this->agent->id,
        ]);
    }

    /** The payment a validated request created, already converted to an avance. */
    private function avanceDuVirement(): Encaissement
    {
        $compte = Caisse::query()->where('type', Caisse::TYPE_VIREMENT)
            ->where('etablissement_id', $this->centre->id)->firstOrFail();

        return Encaissement::create([
            'reference' => 'ENC-VIR',
            'etablissement_id' => $this->centre->id,
            'student_id' => $this->student->id,
            'inscription_fee_id' => null,
            'montant' => 300,
            'methode' => Encaissement::METHODE_VIREMENT,
            'date_paiement' => now()->toDateString(),
            'caisse_id' => $compte->id,
            'agent_id' => $this->agent->id,
        ]);
    }

    public function test_a_validated_transfer_no_longer_blocks_deleting_its_registration(): void
    {
        $avance = $this->avanceDuVirement();
        $virement = $this->virement(Virement::STATUT_VALIDE, $avance);
        $soldeAvant = (float) $avance->caisse->fresh()->solde;

        $this->delete(route('backoffice.inscriptions.destroy', $this->inscription))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertNull(Inscription::find($this->inscription->id));

        $virement->refresh();
        $this->assertNull($virement->inscription_id);
        $this->assertNull($virement->inscription_fee_id);
        $this->assertSame(Virement::STATUT_VALIDE, $virement->statut);
        $this->assertSame($avance->id, $virement->encaissement_id, 'La demande garde son encaissement.');
        $this->assertSame($this->student->id, $virement->student_id);

        $avance->refresh();
        $this->assertSame('300.00', (string) $avance->montant);
        $this->assertSame($soldeAvant, (float) $avance->caisse->fresh()->solde, 'Aucune caisse ne bouge.');
    }

    public function test_the_freed_money_is_reapplied_to_a_new_registration_and_the_page_still_lists_the_transfer(): void
    {
        $avance = $this->avanceDuVirement();
        $this->virement(Virement::STATUT_VALIDE, $avance);

        $this->delete(route('backoffice.inscriptions.destroy', $this->inscription))
            ->assertSessionHasNoErrors();

        [, $nouveauFrais] = $this->inscrire('INS-VIR-2');
        $application = app(AppliquerAvance::class)->handle($avance->fresh(), $nouveauFrais, 300);

        $this->assertSame($nouveauFrais->id, $application->inscription_fee_id);
        $this->assertSame(0.0, round((float) $nouveauFrais->montant - $nouveauFrais->fresh()->montantPaye(), 2));

        $this->get(route('backoffice.virements.index', ['statutFilter' => Virement::STATUT_VALIDE]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('virements.data.0.inscriptionId', null)
                ->where('virements.data.0.inscriptionReference', null));
    }

    public function test_a_refused_transfer_does_not_block_the_deletion(): void
    {
        $virement = $this->virement(Virement::STATUT_REFUSE);

        $this->delete(route('backoffice.inscriptions.destroy', $this->inscription))
            ->assertSessionHasNoErrors();

        $this->assertNull(Inscription::find($this->inscription->id));
        $this->assertNull($virement->fresh()->inscription_id);
    }

    public function test_a_pending_transfer_still_blocks_the_deletion_and_is_named(): void
    {
        $virement = $this->virement(Virement::STATUT_EN_ATTENTE);

        $this->delete(route('backoffice.inscriptions.destroy', $this->inscription))
            ->assertSessionHasErrors(['delete' => __('This registration has bank transfer requests awaiting verification (:refs). Validate or refuse them first.', [
                'refs' => $virement->reference,
            ])]);

        $this->assertNotNull(Inscription::find($this->inscription->id));
        $this->assertSame($this->inscription->id, $virement->fresh()->inscription_id);
    }
}
