<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Chèques en main — off-ledger inventory of physical checks received.
 * A Cheque row never touches caisses.solde by itself (see
 * ChequesInertiaCrudTest's sibling, EncaissementsInertiaCrudTest, for the
 * "pay with a tracked cheque" money-side coverage).
 */
final class ChequesInertiaCrudTest extends TestCase
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

    public function test_index_requires_cheques_view(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.cheques.index'))
            ->assertForbidden();

        $this->actingAs($this->userWith('cheques.view'))
            ->get(route('backoffice.cheques.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Cheques/Index', false)
                ->has('cheques')
                ->has('sources')
                ->has('types')
                ->has('statuts')
            );
    }

    public function test_a_cheque_can_be_recorded_for_a_student(): void
    {
        $this->actingAs($this->userWith('cheques.view', 'cheques.create'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.cheques.store'), [
            'source' => Cheque::SOURCE_ETUDIANT,
            'student_id' => $student->id,
            'numero_cheque' => 'A12445',
            'montant' => '500',
            'banque' => 'Attijariwafa Bank',
            'date_reception' => '2026-08-11',
            'type' => Cheque::TYPE_GARANTIE,
            'date_echeance' => '2026-12-01',
        ])->assertRedirect(route('backoffice.cheques.index'));

        $cheque = Cheque::firstOrFail();
        $this->assertStringStartsWith('CHQ-', $cheque->reference);
        $this->assertSame($student->id, $cheque->student_id);
        $this->assertSame(Cheque::STATUT_EN_POSSESSION, $cheque->statut);
        $this->assertSame('500.00', (string) $cheque->montant);
    }

    public function test_a_cheque_can_be_recorded_for_a_non_student_owner(): void
    {
        $this->actingAs($this->userWith('cheques.view', 'cheques.create'));

        $this->post(route('backoffice.cheques.store'), [
            'source' => Cheque::SOURCE_PARENTS,
            'proprietaire_nom' => 'Mme Bennani',
            'numero_cheque' => 'B999',
            'montant' => '300',
            'date_reception' => '2026-08-11',
            'type' => Cheque::TYPE_A_DEPOSER,
        ])->assertSessionDoesntHaveErrors();

        $cheque = Cheque::firstOrFail();
        $this->assertNull($cheque->student_id);
        $this->assertSame('Mme Bennani', $cheque->proprietaire_nom);
        $this->assertSame('Mme Bennani', $cheque->proprietaireLabel());
    }

    public function test_student_is_required_when_source_is_etudiant(): void
    {
        $this->actingAs($this->userWith('cheques.view', 'cheques.create'));

        $this->post(route('backoffice.cheques.store'), [
            'source' => Cheque::SOURCE_ETUDIANT,
            'numero_cheque' => 'A1',
            'montant' => '100',
            'date_reception' => '2026-08-11',
            'type' => Cheque::TYPE_GARANTIE,
        ])->assertSessionHasErrors('student_id');
    }

    public function test_proprietaire_nom_is_required_when_source_is_not_etudiant(): void
    {
        $this->actingAs($this->userWith('cheques.view', 'cheques.create'));

        $this->post(route('backoffice.cheques.store'), [
            'source' => Cheque::SOURCE_PARENTS,
            'numero_cheque' => 'A1',
            'montant' => '100',
            'date_reception' => '2026-08-11',
            'type' => Cheque::TYPE_GARANTIE,
        ])->assertSessionHasErrors('proprietaire_nom');
    }

    public function test_user_without_create_permission_cannot_store(): void
    {
        $this->actingAs($this->userWith('cheques.view'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->post(route('backoffice.cheques.store'), [
            'source' => Cheque::SOURCE_ETUDIANT, 'student_id' => $student->id,
            'numero_cheque' => 'A1', 'montant' => '100',
            'date_reception' => '2026-08-11', 'type' => Cheque::TYPE_GARANTIE,
        ])->assertForbidden();
    }

    // --- statut lifecycle ------------------------------------------------

    private function makeCheque(string $statut = Cheque::STATUT_EN_POSSESSION): Cheque
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::first() ?? Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        return Cheque::create([
            'reference' => 'CHQ-'.fake()->unique()->numerify('###'),
            'source' => Cheque::SOURCE_ETUDIANT,
            'student_id' => $student->id,
            'numero_cheque' => fake()->unique()->numerify('CHQ###'),
            'montant' => 1000,
            'date_reception' => '2026-08-01',
            'type' => Cheque::TYPE_A_DEPOSER,
            'statut' => $statut,
            'etablissement_id' => $this->centre->id,
            'agent_id' => $agent->id,
        ]);
    }

    public function test_remise_a_la_banque_moves_en_possession_to_depose(): void
    {
        $this->actingAs($this->userWith('cheques.view', 'cheques.update'));
        $cheque = $this->makeCheque(Cheque::STATUT_EN_POSSESSION);

        $this->patch(route('backoffice.cheques.update-statut', $cheque), [
            'statut' => Cheque::STATUT_DEPOSE,
        ])->assertRedirect(route('backoffice.cheques.index'));

        $this->assertSame(Cheque::STATUT_DEPOSE, $cheque->fresh()->statut);
    }

    public function test_deposited_cheque_can_be_marked_encaisse_or_rejete(): void
    {
        $this->actingAs($this->userWith('cheques.view', 'cheques.update'));
        $cheque = $this->makeCheque(Cheque::STATUT_DEPOSE);

        $this->patch(route('backoffice.cheques.update-statut', $cheque), [
            'statut' => Cheque::STATUT_ENCAISSE,
        ])->assertRedirect(route('backoffice.cheques.index'));

        $this->assertSame(Cheque::STATUT_ENCAISSE, $cheque->fresh()->statut);
    }

    public function test_a_direct_en_possession_to_encaisse_move_is_refused(): void
    {
        $this->actingAs($this->userWith('cheques.view', 'cheques.update'));
        $cheque = $this->makeCheque(Cheque::STATUT_EN_POSSESSION);

        $this->patch(route('backoffice.cheques.update-statut', $cheque), [
            'statut' => Cheque::STATUT_ENCAISSE,
        ])->assertSessionHasErrors('statut');

        $this->assertSame(Cheque::STATUT_EN_POSSESSION, $cheque->fresh()->statut);
    }

    public function test_a_rejete_cheque_cannot_be_transitioned_further(): void
    {
        $this->actingAs($this->userWith('cheques.view', 'cheques.update'));
        $cheque = $this->makeCheque(Cheque::STATUT_REJETE);

        $this->patch(route('backoffice.cheques.update-statut', $cheque), [
            'statut' => Cheque::STATUT_DEPOSE,
        ])->assertSessionHasErrors('statut');
    }

    // --- retour (returned to owner) tracking -------------------------------

    public function test_a_rejete_cheque_can_be_marked_as_returned(): void
    {
        $user = $this->userWith('cheques.view', 'cheques.update');
        $this->actingAs($user);
        $cheque = $this->makeCheque(Cheque::STATUT_REJETE);

        $this->patch(route('backoffice.cheques.retour', $cheque))
            ->assertRedirect(route('backoffice.cheques.index'));

        $cheque->refresh();
        $this->assertNotNull($cheque->retourne_le);
        $this->assertSame($user->employee->id, $cheque->retourne_par_id);
        $this->assertTrue($cheque->estRetourne());
    }

    public function test_a_non_rejete_cheque_cannot_be_marked_as_returned(): void
    {
        $this->actingAs($this->userWith('cheques.view', 'cheques.update'));
        $cheque = $this->makeCheque(Cheque::STATUT_EN_POSSESSION);

        $this->patch(route('backoffice.cheques.retour', $cheque))
            ->assertSessionHasErrors('statut');

        $this->assertNull($cheque->fresh()->retourne_le);
    }

    public function test_a_cheque_cannot_be_marked_as_returned_twice(): void
    {
        $this->actingAs($this->userWith('cheques.view', 'cheques.update'));
        $cheque = $this->makeCheque(Cheque::STATUT_REJETE);

        $this->patch(route('backoffice.cheques.retour', $cheque))
            ->assertRedirect(route('backoffice.cheques.index'));

        $this->patch(route('backoffice.cheques.retour', $cheque))
            ->assertSessionHasErrors('statut');
    }

    public function test_marking_a_cheque_as_returned_requires_update_permission(): void
    {
        $this->actingAs($this->userWith('cheques.view'));
        $cheque = $this->makeCheque(Cheque::STATUT_REJETE);

        $this->patch(route('backoffice.cheques.retour', $cheque))
            ->assertForbidden();
    }

    public function test_marking_a_cheque_as_returned_does_not_touch_any_caisse_solde(): void
    {
        $this->actingAs($this->userWith('cheques.view', 'cheques.update'));
        $cheque = $this->makeCheque(Cheque::STATUT_REJETE);
        $caisse = \App\Models\Caisse::factory()->create(['etablissement_id' => $this->centre->id, 'solde' => 500]);

        $this->patch(route('backoffice.cheques.retour', $cheque))
            ->assertRedirect(route('backoffice.cheques.index'));

        $this->assertSame('500.00', (string) $caisse->fresh()->solde);
    }

    // --- montant editing guard --------------------------------------------

    public function test_montant_cannot_be_lowered_below_the_used_amount(): void
    {
        $this->actingAs($this->userWith('cheques.view', 'cheques.update'));
        $cheque = $this->makeCheque();
        $cheque->encaissements()->create([
            'reference' => 'ENC-USED', 'student_id' => $cheque->student_id,
            'caisse_id' => \App\Models\Caisse::factory()->create(['etablissement_id' => $this->centre->id])->id,
            'agent_id' => Employee::first()->id, 'montant' => 700, 'methode' => 'Chèque', 'date_paiement' => '2026-08-05',
        ]);

        $this->put(route('backoffice.cheques.update', $cheque), [
            'source' => Cheque::SOURCE_ETUDIANT, 'student_id' => $cheque->student_id,
            'numero_cheque' => $cheque->numero_cheque, 'montant' => '500',
            'date_reception' => '2026-08-01', 'type' => Cheque::TYPE_A_DEPOSER,
        ])->assertSessionHasErrors('montant');

        $this->assertSame('1000.00', (string) $cheque->fresh()->montant);
    }

    // --- student cheques lookup -------------------------------------------

    public function test_student_cheques_lookup_only_returns_cheques_with_remaining_value(): void
    {
        $this->actingAs($this->userWith('cheques.view', 'cheques.create'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $agent = Employee::first();

        $available = Cheque::create([
            'reference' => 'CHQ-AVAIL', 'source' => Cheque::SOURCE_ETUDIANT, 'student_id' => $student->id,
            'numero_cheque' => 'CHQ-AVAIL', 'montant' => 500, 'date_reception' => '2026-08-01',
            'type' => Cheque::TYPE_A_DEPOSER, 'statut' => Cheque::STATUT_EN_POSSESSION,
            'etablissement_id' => $this->centre->id, 'agent_id' => $agent->id,
        ]);
        $rejected = Cheque::create([
            'reference' => 'CHQ-REJ', 'source' => Cheque::SOURCE_ETUDIANT, 'student_id' => $student->id,
            'numero_cheque' => 'CHQ-REJ', 'montant' => 200, 'date_reception' => '2026-08-01',
            'type' => Cheque::TYPE_A_DEPOSER, 'statut' => Cheque::STATUT_REJETE,
            'etablissement_id' => $this->centre->id, 'agent_id' => $agent->id,
        ]);

        $response = $this->get(route('backoffice.students.cheques', $student))->json();

        $ids = collect($response['cheques'])->pluck('id');
        $this->assertTrue($ids->contains($available->id));
        $this->assertFalse($ids->contains($rejected->id));
    }
}
