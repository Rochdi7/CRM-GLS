<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Inscriptions;

use App\Models\AnneeScolaire;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * « Ajouter un paiement » on the Inscriptions list: the row action and the
 * post-creation prompt.
 *
 * The modal itself posts to encaissements.store — the SAME endpoint the
 * Encaissements page uses — so the money invariants (till routing, the
 * per-fee remaining-balance re-check under lock, the fee->inscription
 * ownership guard, the active-context assertion) are covered by that
 * module's own tests and are deliberately not re-asserted here. What is
 * asserted here is the hand-off: the props that gate the menu entry, and the
 * one-time `nouvelleInscription` flash that opens the prompt exactly once.
 */
final class InscriptionPaiementPromptTest extends TestCase
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
        foreach ([...$permissions, 'centers.access-all'] as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user->fresh();
    }

    private function groupWithFees(): Group
    {
        $group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $frais = Frais::create(['nom' => "Frais d'inscription", 'statut' => 'Actif']);
        $group->frais()->attach([$frais->id => ['montant' => 300]]);

        return $group;
    }

    // --- props gating the row action ---------------------------------------

    public function test_index_exposes_can_create_payment_true_for_a_cashier(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'payments.create'))
            ->get(route('backoffice.inscriptions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Inscriptions/Index')
                ->where('canCreatePayment', true)
                // The per-row méthode dropdown reads the model's own list,
                // never a hard-coded copy in the React page.
                ->where('methodesPaiement', Encaissement::METHODES));
    }

    public function test_index_exposes_can_create_payment_false_without_payments_create(): void
    {
        $this->actingAs($this->userWith('registrations.view'))
            ->get(route('backoffice.inscriptions.index'))
            ->assertInertia(fn (Assert $page) => $page->where('canCreatePayment', false));
    }

    // --- the post-creation prompt -----------------------------------------

    public function test_creating_a_registration_flashes_it_for_the_payment_prompt(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create', 'payments.create'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2025-09-15',
            'fee_lines' => [
                ['frais_id' => null, 'nom' => "Frais d'inscription", 'montant_initial' => '300'],
            ],
        ])->assertRedirect(route('backoffice.inscriptions.index'));

        $inscription = Inscription::where('student_id', $student->id)->firstOrFail();

        $this->assertSame([
            'id' => $inscription->id,
            'reference' => $inscription->reference,
            'studentId' => $student->id,
            'studentLabel' => $student->reference.' | '.trim($student->prenom.' '.$student->nom),
        ], session('nouvelleInscription'));
    }

    public function test_the_flash_reaches_the_list_page_once_and_never_again(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create', 'payments.create'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2025-09-15',
            'fee_lines' => [
                ['frais_id' => null, 'nom' => "Frais d'inscription", 'montant_initial' => '300'],
            ],
        ]);

        $inscription = Inscription::where('student_id', $student->id)->firstOrFail();

        // First render: the prompt opens.
        $this->get(route('backoffice.inscriptions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('flash.nouvelleInscription.id', $inscription->id)
                ->where('flash.nouvelleInscription.reference', $inscription->reference));

        // Second render (a search/pagination reload, a refresh): the prompt
        // must NOT reappear — the flash is pulled, not merely read. Without
        // pull() Laravel keeps flash data for the whole next request, so the
        // modal would pop up again over an unrelated screen.
        $this->get(route('backoffice.inscriptions.index'))
            ->assertInertia(fn (Assert $page) => $page->where('flash.nouvelleInscription', null));
    }

    public function test_a_payment_recorded_from_the_inscriptions_page_hits_the_same_endpoint(): void
    {
        // The modal's contract: it posts the Encaissements payload. Proving it
        // lands on encaissements.store (and is refused without payments.create)
        // is what ties the two pages together — the money rules themselves are
        // asserted in the Finance suite.
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = $this->groupWithFees();

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2025-09-15',
            'fee_lines' => [
                ['frais_id' => null, 'nom' => "Frais d'inscription", 'montant_initial' => '300'],
            ],
        ]);

        $inscription = Inscription::where('student_id', $student->id)->firstOrFail();
        $fee = $inscription->fees()->firstOrFail();

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-15',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '100', 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-15'],
            ],
        ])->assertForbidden();

        $this->assertSame(0, Encaissement::query()->count());
    }
}
