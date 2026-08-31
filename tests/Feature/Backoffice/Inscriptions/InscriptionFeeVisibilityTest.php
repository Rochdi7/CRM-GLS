<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

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
 * "Hide" a fee line instead of deleting it — the edit modal's trash icon
 * (BasculerVisibiliteFraisInscription) now sets masque_le instead of
 * hard-deleting via MettreAJourFraisInscription's old "omitted from the
 * payload = delete" sweep. A hidden fee keeps its row and can be restored.
 *
 * ⚠ Masquer un frais DÉJÀ PAYÉ libère son argent en avance (31/08/2026) :
 * l'encaissement n'est jamais supprimé, mais il est DÉTACHÉ du frais, sinon
 * l'argent reste accroché à une ligne invisible et l'étudiant ne peut plus
 * le réutiliser.
 */
final class InscriptionFeeVisibilityTest extends TestCase
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

        return $user->fresh();
    }

    private static int $inscriptionCounter = 0;

    private function inscriptionWithFee(float $montant = 1300.0): array
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-V'.(++self::$inscriptionCounter), 'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => 'Active', 'date_inscription' => '2025-09-15', 'montant_total' => $montant,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Juillet',
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-10-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$inscription, $fee];
    }

    public function test_hiding_a_fee_removes_it_from_the_fees_endpoint_and_lists_it_as_hidden(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->postJson(route('backoffice.inscriptions.fees.hide', [$inscription, $fee]))
            // JSON, not an Inertia redirect: the action fires from inside the
            // open edit modal, which updates its own table from local state.
            // Returning a redirect made Inertia re-run index() and rebuild the
            // whole page payload for every click.
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertNotNull($fee->fresh()->masque_le);

        $response = $this->get(route('backoffice.inscriptions.fees', $inscription))->assertOk()->json();
        $this->assertCount(0, $response['fees']);
        $this->assertCount(1, $response['hiddenFees']);
        $this->assertSame($fee->id, $response['hiddenFees'][0]['id']);
    }

    public function test_hiding_recomputes_montant_total_from_remaining_visible_fees(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(1300.0);
        InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais restant',
            'montant_initial' => 200, 'montant' => 200,
            'date_echeance' => '2025-10-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->postJson(route('backoffice.inscriptions.fees.hide', [$inscription, $fee]))
            // JSON, not an Inertia redirect: the action fires from inside the
            // open edit modal, which updates its own table from local state.
            // Returning a redirect made Inertia re-run index() and rebuild the
            // whole page payload for every click.
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame('200.00', (string) $inscription->fresh()->montant_total);
    }

    public function test_a_fee_with_payments_can_be_hidden_unlike_the_old_hard_delete(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        Encaissement::create([
            'reference' => 'ENC-VIS1', 'student_id' => $inscription->student_id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => 500, 'methode' => 'Espèces', 'date_paiement' => '2025-10-01',
        ]);

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->postJson(route('backoffice.inscriptions.fees.hide', [$inscription, $fee]))
            // JSON, not an Inertia redirect: the action fires from inside the
            // open edit modal, which updates its own table from local state.
            // Returning a redirect made Inertia re-run index() and rebuild the
            // whole page payload for every click.
            ->assertOk()
            ->assertJson(['ok' => true]);

        $fresh = $fee->fresh();
        $this->assertNotNull($fresh->masque_le);
        $this->assertNotNull(InscriptionFee::find($fee->id));

        // ⚠ L'argent est LIBÉRÉ en avance, il ne reste pas accroché à une
        // ligne invisible (31/08/2026). L'encaissement lui-même n'est jamais
        // supprimé — les enregistrements monétaires sont append-only (§11) —
        // seul son rattachement au frais disparaît.
        $this->assertSame(0, Encaissement::where('inscription_fee_id', $fee->id)->count());
        $this->assertSame(1, Encaissement::where('student_id', $inscription->student_id)
            ->whereNull('inscription_fee_id')->count());
    }

    /**
     * LE bug signalé : masquer un frais déjà payé laissait 500 DH accrochés à
     * une ligne invisible — absents du dû, absents de l'onglet Avances,
     * impossibles à réutiliser. Le retrait au niveau du GROUPE
     * (RetirerFraisGroupe) et le retrait d'une ligne
     * (MettreAJourFraisInscription) libéraient déjà l'argent : les trois
     * chemins doivent se comporter pareil.
     */
    public function test_hiding_a_paid_fee_releases_its_money_as_a_reusable_advance(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(300.0);
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-VIS2', 'student_id' => $inscription->student_id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => 300, 'methode' => 'Espèces', 'date_paiement' => '2025-10-01',
        ]);
        $fee->update(['statut' => InscriptionFee::STATUT_PAYE]);

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->postJson(route('backoffice.inscriptions.fees.hide', [$inscription, $fee]))
            ->assertOk()
            // Le modal ANNONCE le montant libéré : sinon l'utilisateur voit
            // 300 DH disparaître de l'écran sans savoir où ils sont partis.
            ->assertJson(['ok' => true, 'montantLibere' => 300.0]);

        $encaissement->refresh();
        $this->assertNull($encaissement->inscription_fee_id);
        // C'est désormais une avance : montant restant réapplicable.
        $this->assertTrue($encaissement->isAvance());
        $this->assertSame(300.0, $encaissement->montantRestant());
        // Et le frais masqué n'est plus compté comme payé.
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fee->fresh()->statut);
    }

    public function test_hiding_an_unpaid_fee_releases_nothing(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->postJson(route('backoffice.inscriptions.fees.hide', [$inscription, $fee]))
            ->assertOk()
            ->assertJson(['ok' => true, 'montantLibere' => 0]);
    }

    /**
     * Restaurer ne « re-colle » PAS l'avance au frais — même règle que
     * RetirerFraisGroupe::restore. Entre-temps l'argent a pu être appliqué
     * ailleurs ; le frais revient donc dû, et l'avance reste disponible.
     */
    public function test_restoring_leaves_the_released_money_as_an_advance(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee(300.0);
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $encaissement = Encaissement::create([
            'reference' => 'ENC-VIS3', 'student_id' => $inscription->student_id,
            'inscription_fee_id' => $fee->id, 'caisse_id' => $caisse->id, 'agent_id' => $agent->id,
            'montant' => 300, 'methode' => 'Espèces', 'date_paiement' => '2025-10-01',
        ]);

        $user = $this->userWith('registrations.view', 'registrations.manage-fees');
        $this->actingAs($user)
            ->postJson(route('backoffice.inscriptions.fees.hide', [$inscription, $fee]))->assertOk();
        $this->actingAs($user)
            ->postJson(route('backoffice.inscriptions.fees.restore', [$inscription, $fee]))->assertOk();

        $this->assertNull($fee->fresh()->masque_le);
        $this->assertNull($encaissement->fresh()->inscription_fee_id);
        $this->assertSame('300.00', (string) $inscription->fresh()->montant_total);
    }

    public function test_restoring_a_hidden_fee_brings_it_back_to_the_visible_list(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();
        $fee->update(['masque_le' => now()]);
        $inscription->update(['montant_total' => null]);

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->postJson(route('backoffice.inscriptions.fees.restore', [$inscription, $fee]))
            // JSON — see the hide tests. The restored line's full shape comes
            // back in the payload so the modal can splice it straight into its
            // table without a second request.
            ->assertOk()
            ->assertJson(['ok' => true, 'fee' => ['id' => $fee->id]]);

        $this->assertNull($fee->fresh()->masque_le);
        $this->assertSame('1300.00', (string) $inscription->fresh()->montant_total);

        $response = $this->get(route('backoffice.inscriptions.fees', $inscription))->assertOk()->json();
        $this->assertCount(1, $response['fees']);
        $this->assertCount(0, $response['hiddenFees']);
    }

    public function test_hiding_requires_manage_fees_not_just_update(): void
    {
        [$inscription, $fee] = $this->inscriptionWithFee();

        $this->actingAs($this->userWith('registrations.view', 'registrations.update'))
            ->postJson(route('backoffice.inscriptions.fees.hide', [$inscription, $fee]))
            ->assertForbidden();

        $this->assertNull($fee->fresh()->masque_le);
    }

    public function test_hiding_a_fee_that_does_not_belong_to_the_inscription_is_refused(): void
    {
        [$inscriptionA] = $this->inscriptionWithFee();
        [, $feeB] = $this->inscriptionWithFee();

        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            // A fee that is not this inscription's is a tampered request, not
            // a correctable form error — the controller aborts with 404
            // (see hideFee(): these JSON endpoints must not raise a
            // ValidationException, which bootstrap/app.php would try to
            // render down the HTML/Inertia redirect path).
            ->postJson(route('backoffice.inscriptions.fees.hide', [$inscriptionA, $feeB]))
            ->assertNotFound();

        $this->assertNull($feeB->fresh()->masque_le);
    }

    public function test_updating_fees_never_hard_deletes_a_hidden_fee(): void
    {
        [$inscription, $visibleFee] = $this->inscriptionWithFee(300.0);
        $hiddenFee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais masqué',
            'montant_initial' => 100, 'montant' => 100,
            'date_echeance' => '2025-10-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
            'masque_le' => now(),
        ]);

        // Submitting the visible-only payload (as the client now does, since
        // fees() never returns hidden rows) must not sweep up the hidden one.
        $this->actingAs($this->userWith('registrations.view', 'registrations.manage-fees'))
            ->put(route('backoffice.inscriptions.fees.update', $inscription), [
                'fee_lines' => [['id' => $visibleFee->id, 'nom' => $visibleFee->nom, 'montant_initial' => '300']],
            ])
            ->assertRedirect(route('backoffice.inscriptions.index'));

        $this->assertNotNull(InscriptionFee::find($hiddenFee->id));
        $this->assertNotNull($hiddenFee->fresh()->masque_le);
    }
}
