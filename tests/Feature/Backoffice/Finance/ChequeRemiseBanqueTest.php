<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Remise à la banque d'un chèque et décision du comptable (30/09/2026).
 *
 *  - la remise (tous les rôles) exige un chèque « À déposer » en main,
 *    entièrement affecté, une date et le reçu de dépôt → « Déposé » ;
 *  - le comptable décide : « Encaissé » (la banque a accepté) ou « Rejeté » ;
 *    celui qui a déposé ne valide pas sa propre remise ;
 *  - AUCUNE de ces étapes ne bouge un solde de caisse (01/10/2026) :
 *    l'argent du chèque reste au compte « Chèque » du centre.
 */
final class ChequeRemiseBanqueTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('media');
        AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
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

    private function actingInCentre(User $user): self
    {
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        return $this;
    }

    private function compte(string $type, ?Etablissement $centre = null): Caisse
    {
        return Caisse::query()
            ->where('etablissement_id', ($centre ?? $this->centre)->id)
            ->where('type', $type)
            ->firstOrFail();
    }

    /** A cheque « À déposer » in hand whose $utilise has paid a fee (money sits in the Chèque account). */
    private function cheque(float $montant = 1000, ?float $utilise = null, array $attributes = []): Cheque
    {
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        $cheque = Cheque::create([
            'reference' => 'CHQ-'.random_int(10000, 99999),
            'source' => Cheque::SOURCE_ETUDIANT,
            'student_id' => $this->student->id,
            'numero_cheque' => 'N'.random_int(10000, 99999),
            'montant' => $montant,
            'banque' => 'CIH',
            'date_reception' => '2026-08-01',
            'type' => Cheque::TYPE_A_DEPOSER,
            'statut' => Cheque::STATUT_EN_POSSESSION,
            'etablissement_id' => $this->centre->id,
            'agent_id' => $agent->id,
            ...$attributes,
        ]);

        $utilise ??= $montant;

        if ($utilise > 0) {
            $compteCheque = $this->compte(Caisse::TYPE_CHEQUE);
            Encaissement::create([
                'reference' => 'ENC-'.random_int(100000, 999999),
                'student_id' => $this->student->id,
                'etablissement_id' => $this->centre->id,
                'cheque_id' => $cheque->id,
                'montant' => $utilise,
                'methode' => Encaissement::METHODE_CHEQUE,
                'date_paiement' => '2026-08-02',
                'caisse_id' => $compteCheque->id,
                'agent_id' => $agent->id,
            ]);
            $compteCheque->update(['solde' => (float) $compteCheque->solde + $utilise]);
        }

        return $cheque;
    }

    private function remettre(User $user, Cheque $cheque, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingInCentre($user)->post(route('backoffice.cheques.remise-banque', $cheque), [
            'date_remise' => '2026-08-10',
            'justificatif' => UploadedFile::fake()->image('recu.jpg'),
            ...$overrides,
        ]);
    }

    // --- Remise à la banque ---------------------------------------------

    public function test_a_fully_used_cheque_is_deposited_with_its_receipt_and_moves_no_money(): void
    {
        $guichet = $this->userWith('cheques.view', 'cheques.deposit');
        $cheque = $this->cheque();
        $avant = (float) $this->compte(Caisse::TYPE_CHEQUE)->fresh()->solde;

        $this->remettre($guichet, $cheque)->assertSessionHasNoErrors();

        $cheque->refresh();
        $this->assertSame(Cheque::STATUT_DEPOSE, $cheque->statut);
        $this->assertSame('2026-08-10', $cheque->date_remise->toDateString());
        $this->assertSame($guichet->employee->id, $cheque->depose_par_id);
        $this->assertNotSame('', $cheque->getFirstMediaUrl(Cheque::MEDIA_JUSTIFICATIF_DEPOT));
        $this->assertSame($avant, (float) $this->compte(Caisse::TYPE_CHEQUE)->fresh()->solde);
        $this->assertSame(0, CaisseTransfer::count());
    }

    public function test_the_deposit_receipt_is_required(): void
    {
        $guichet = $this->userWith('cheques.view', 'cheques.deposit');
        $cheque = $this->cheque();

        $this->remettre($guichet, $cheque, ['justificatif' => null])->assertSessionHasErrors('justificatif');

        $this->assertSame(Cheque::STATUT_EN_POSSESSION, $cheque->fresh()->statut);
    }

    public function test_a_guarantee_never_goes_to_the_bank(): void
    {
        $guichet = $this->userWith('cheques.view', 'cheques.deposit');
        $cheque = $this->cheque(1000, 0, ['type' => Cheque::TYPE_GARANTIE]);

        $this->remettre($guichet, $cheque)->assertSessionHasErrors('date_remise');

        $this->assertSame(Cheque::STATUT_EN_POSSESSION, $cheque->fresh()->statut);
    }

    public function test_a_cheque_with_an_unallocated_rest_cannot_be_deposited(): void
    {
        $guichet = $this->userWith('cheques.view', 'cheques.deposit');
        $cheque = $this->cheque(1000, 600);

        $this->remettre($guichet, $cheque)->assertSessionHasErrors('date_remise');

        $this->assertSame(Cheque::STATUT_EN_POSSESSION, $cheque->fresh()->statut);
    }

    public function test_a_deposited_cheque_can_no_longer_pay(): void
    {
        $guichet = $this->userWith('cheques.view', 'cheques.deposit');
        $cheque = $this->cheque(1000, 1000);
        $this->remettre($guichet, $cheque)->assertSessionHasNoErrors();

        $this->actingInCentre($guichet)
            ->getJson(route('backoffice.students.cheques', $this->student))
            ->assertOk()
            ->assertJsonCount(0, 'cheques');
    }

    // --- Décision du comptable ------------------------------------------

    public function test_accepting_marks_the_cheque_encaisse_and_moves_no_money(): void
    {
        $guichet = $this->userWith('cheques.view', 'cheques.deposit');
        $comptable = $this->userWith('cheques.view', 'cheques.validate-deposit');
        $cheque = $this->cheque(1000);
        $this->remettre($guichet, $cheque)->assertSessionHasNoErrors();
        $this->assertSame(Cheque::STATUT_DEPOSE, $cheque->fresh()->statut);
        [$soldes, $mouvements] = [$this->soldes(), $this->mouvementsDeCaisse()];

        $this->actingInCentre($comptable)
            ->patch(route('backoffice.cheques.update-statut', $cheque), ['statut' => Cheque::STATUT_ENCAISSE])
            ->assertSessionHasNoErrors();

        $cheque->refresh();
        $this->assertSame(Cheque::STATUT_ENCAISSE, $cheque->statut);
        $this->assertSame($comptable->employee->id, $cheque->depot_valide_par_id);
        $this->assertNotNull($cheque->depot_valide_le);

        // The money stays in the centre's Chèque account.
        $this->assertSame($soldes, $this->soldes());
        $this->assertSame($mouvements, $this->mouvementsDeCaisse());
        $this->assertSame(0, CaisseTransfer::count());
    }

    public function test_the_depositor_cannot_validate_their_own_deposit(): void
    {
        $lui = $this->userWith('cheques.view', 'cheques.deposit', 'cheques.validate-deposit');
        $cheque = $this->cheque();
        $this->remettre($lui, $cheque)->assertSessionHasNoErrors();

        $this->actingInCentre($lui)
            ->patch(route('backoffice.cheques.update-statut', $cheque), ['statut' => Cheque::STATUT_ENCAISSE])
            ->assertSessionHasErrors('statut');

        $this->assertSame(Cheque::STATUT_DEPOSE, $cheque->fresh()->statut);
        $this->assertSame(0, CaisseTransfer::count());
    }

    public function test_rejecting_a_deposit_moves_nothing(): void
    {
        $guichet = $this->userWith('cheques.view', 'cheques.deposit');
        $comptable = $this->userWith('cheques.view', 'cheques.validate-deposit');
        $cheque = $this->cheque();
        $this->remettre($guichet, $cheque)->assertSessionHasNoErrors();
        $avant = (float) $this->compte(Caisse::TYPE_CHEQUE)->fresh()->solde;

        $this->actingInCentre($comptable)
            ->patch(route('backoffice.cheques.update-statut', $cheque), ['statut' => Cheque::STATUT_REJETE])
            ->assertSessionHasNoErrors();

        $this->assertSame(Cheque::STATUT_REJETE, $cheque->fresh()->statut);
        $this->assertSame($avant, (float) $this->compte(Caisse::TYPE_CHEQUE)->fresh()->solde);
        $this->assertSame(0, CaisseTransfer::count());
    }

    public function test_an_accepted_cheque_can_still_be_rejected_later_without_moving_money(): void
    {
        $guichet = $this->userWith('cheques.view', 'cheques.deposit');
        $comptable = $this->userWith('cheques.view', 'cheques.validate-deposit');
        $cheque = $this->cheque();
        $this->remettre($guichet, $cheque)->assertSessionHasNoErrors();
        $this->actingInCentre($comptable)
            ->patch(route('backoffice.cheques.update-statut', $cheque), ['statut' => Cheque::STATUT_ENCAISSE])
            ->assertSessionHasNoErrors();
        $soldes = $this->soldes();

        $this->actingInCentre($comptable)
            ->patch(route('backoffice.cheques.update-statut', $cheque), ['statut' => Cheque::STATUT_REJETE])
            ->assertSessionHasNoErrors();

        $this->assertSame(Cheque::STATUT_REJETE, $cheque->fresh()->statut);
        $this->assertSame($soldes, $this->soldes());
    }

    /** @return array<int, string> every caisse balance, keyed by id */
    private function soldes(): array
    {
        return Caisse::query()->orderBy('id')->pluck('solde', 'id')->map(fn ($s) => (string) $s)->all();
    }

    private function mouvementsDeCaisse(): int
    {
        return \App\Models\Activity::query()->where('event', 'solde_movement')->count();
    }

    public function test_rejecting_a_deposit_moves_no_balance_anywhere(): void
    {
        $guichet = $this->userWith('cheques.view', 'cheques.deposit');
        $comptable = $this->userWith('cheques.view', 'cheques.validate-deposit');
        $cheque = $this->cheque(1300);
        $this->remettre($guichet, $cheque)->assertSessionHasNoErrors();
        [$soldes, $mouvements] = [$this->soldes(), $this->mouvementsDeCaisse()];

        $this->actingInCentre($comptable)
            ->patch(route('backoffice.cheques.update-statut', $cheque), ['statut' => Cheque::STATUT_REJETE])
            ->assertSessionHasNoErrors();

        $this->assertSame(Cheque::STATUT_REJETE, $cheque->fresh()->statut);
        $this->assertSame($soldes, $this->soldes());
        $this->assertSame($mouvements, $this->mouvementsDeCaisse());
        $this->assertSame('1300.00', (string) $cheque->encaissements()->sole()->montant);
    }

    /** Production shape: an imported « Encaissé » cheque whose payment sits in a cashier TILL. */
    public function test_a_legacy_cheque_paid_into_a_till_is_rejected_or_deleted_without_moving_money(): void
    {
        $comptable = $this->userWith('cheques.view', 'cheques.validate-deposit');
        $till = Caisse::factory()->create(['etablissement_id' => $this->centre->id, 'solde' => 1300]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        $legacy = [];
        foreach ([1, 2] as $i) {
            $legacy[$i] = Cheque::create([
                'reference' => "IMPORT-P{$i}", 'source' => Cheque::SOURCE_ETUDIANT, 'student_id' => $this->student->id,
                'numero_cheque' => "IMPORT-P{$i}", 'montant' => 650, 'date_reception' => '2026-08-01',
                'type' => Cheque::TYPE_A_DEPOSER, 'statut' => Cheque::STATUT_ENCAISSE,
                'etablissement_id' => $this->centre->id, 'agent_id' => $agent->id,
            ]);
            Encaissement::create([
                'reference' => "ENC-LEG{$i}", 'student_id' => $this->student->id, 'etablissement_id' => $this->centre->id,
                'cheque_id' => $legacy[$i]->id, 'montant' => 650, 'methode' => Encaissement::METHODE_CHEQUE,
                'date_paiement' => '2026-08-02', 'caisse_id' => $till->id, 'agent_id' => $agent->id,
            ]);
        }
        // Created BEFORE the snapshot: a new employee gets its own till.
        $admin = User::factory()->create()->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $admin->id, 'etablissement_id' => $this->centre->id]);
        [$soldes, $mouvements] = [$this->soldes(), $this->mouvementsDeCaisse()];

        // Rejected: nothing moves.
        $this->actingInCentre($comptable)
            ->patch(route('backoffice.cheques.update-statut', $legacy[1]), ['statut' => Cheque::STATUT_REJETE])
            ->assertSessionHasNoErrors();

        // Deleted: the payment stays whole, only its link goes.
        $this->actingInCentre($admin->fresh())
            ->delete(route('backoffice.cheques.destroy', $legacy[2]))
            ->assertSessionHasNoErrors();

        $this->assertSame($soldes, $this->soldes());
        $this->assertSame($mouvements, $this->mouvementsDeCaisse());
        $paiement = Encaissement::query()->where('reference', 'ENC-LEG2')->sole();
        $this->assertNull($paiement->cheque_id);
        $this->assertSame('650.00', (string) $paiement->montant);
        $this->assertSame($till->id, $paiement->caisse_id);
        $this->assertSame(Encaissement::METHODE_CHEQUE, $paiement->methode);
    }

    /** Production shape: a « Déposé » cheque from before this flow, payment in a cashier till. */
    public function test_a_legacy_deposit_is_accepted_without_moving_money(): void
    {
        $comptable = $this->userWith('cheques.view', 'cheques.validate-deposit');
        $till = Caisse::factory()->create(['etablissement_id' => $this->centre->id, 'solde' => 500]);
        $agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $cheque = Cheque::create([
            'reference' => 'IMPORT-P9', 'source' => Cheque::SOURCE_ETUDIANT, 'student_id' => $this->student->id,
            'numero_cheque' => 'IMPORT-P9', 'montant' => 500, 'date_reception' => '2026-08-01',
            'type' => Cheque::TYPE_A_DEPOSER, 'statut' => Cheque::STATUT_DEPOSE,
            'etablissement_id' => $this->centre->id, 'agent_id' => $agent->id,
        ]);
        Encaissement::create([
            'reference' => 'ENC-LEG9', 'student_id' => $this->student->id, 'etablissement_id' => $this->centre->id,
            'cheque_id' => $cheque->id, 'montant' => 500, 'methode' => Encaissement::METHODE_CHEQUE,
            'date_paiement' => '2026-08-02', 'caisse_id' => $till->id, 'agent_id' => $agent->id,
        ]);
        [$soldes, $mouvements] = [$this->soldes(), $this->mouvementsDeCaisse()];

        $this->actingInCentre($comptable)
            ->patch(route('backoffice.cheques.update-statut', $cheque), ['statut' => Cheque::STATUT_ENCAISSE])
            ->assertSessionHasNoErrors();

        $this->assertSame(Cheque::STATUT_ENCAISSE, $cheque->fresh()->statut);
        $this->assertSame($soldes, $this->soldes());
        $this->assertSame($mouvements, $this->mouvementsDeCaisse());
        $this->assertSame(0, CaisseTransfer::count());
    }

    // --- Annuler un chèque (comptable) — statut « Annulé » -----------------

    public function test_the_accountant_cancels_an_unused_cheque_and_no_money_moves(): void
    {
        $comptable = $this->userWith('cheques.view', 'cheques.cancel');
        $cheque = $this->cheque(1000, 0);
        [$soldes, $mouvements] = [$this->soldes(), $this->mouvementsDeCaisse()];

        $this->actingInCentre($comptable)
            ->patch(route('backoffice.cheques.annuler', $cheque), ['motif' => 'Saisi deux fois'])
            ->assertSessionHasNoErrors();

        $cheque->refresh();
        $this->assertSame(Cheque::STATUT_ANNULE, $cheque->statut);
        $this->assertStringContainsString('[ANNULÉ]', $cheque->note);
        $this->assertStringContainsString('Saisi deux fois', $cheque->note);
        $this->assertSame($soldes, $this->soldes());
        $this->assertSame($mouvements, $this->mouvementsDeCaisse());
        $this->assertTrue(\App\Models\Activity::query()->where('event', 'cheque_annule')->exists());
    }

    public function test_cancelling_needs_a_reason_and_the_permission(): void
    {
        $cheque = $this->cheque(1000, 0);

        $this->actingInCentre($this->userWith('cheques.view', 'cheques.cancel'))
            ->patch(route('backoffice.cheques.annuler', $cheque), ['motif' => '  '])
            ->assertSessionHasErrors('motif');

        // Depositing or validating is not cancelling.
        $this->actingInCentre($this->userWith('cheques.view', 'cheques.deposit', 'cheques.update'))
            ->patch(route('backoffice.cheques.annuler', $cheque), ['motif' => 'Erreur'])
            ->assertForbidden();

        $this->assertSame(Cheque::STATUT_EN_POSSESSION, $cheque->fresh()->statut);
    }

    public function test_a_cheque_that_paid_cannot_be_cancelled(): void
    {
        $comptable = $this->userWith('cheques.view', 'cheques.cancel');
        $cheque = $this->cheque(1000, 400);

        $this->actingInCentre($comptable)
            ->patch(route('backoffice.cheques.annuler', $cheque), ['motif' => 'Erreur'])
            ->assertSessionHasErrors('motif');

        $this->assertSame(Cheque::STATUT_EN_POSSESSION, $cheque->fresh()->statut);
    }

    public function test_a_cancelled_cheque_is_closed_but_stays_listed_out_of_the_total(): void
    {
        $user = $this->userWith('cheques.view', 'cheques.cancel', 'cheques.deposit', 'cheques.update');
        $vivant = $this->cheque(700, 0);
        $annule = $this->cheque(1000, 0);
        $this->actingInCentre($user)
            ->patch(route('backoffice.cheques.annuler', $annule), ['motif' => 'Erreur'])
            ->assertSessionHasNoErrors();

        // No longer offered to pay, to deposit, to edit, nor to cancel twice.
        $this->getJson(route('backoffice.students.cheques', $this->student))->assertJsonCount(1, 'cheques');
        $this->remettre($user, $annule)->assertSessionHasErrors('date_remise');
        $this->put(route('backoffice.cheques.update', $annule), [
            'source' => Cheque::SOURCE_ETUDIANT, 'student_id' => $this->student->id,
            'numero_cheque' => 'X1', 'montant' => '1000', 'date_reception' => '2026-08-01',
            'type' => Cheque::TYPE_A_DEPOSER,
        ])->assertSessionHasErrors('montant');
        $this->patch(route('backoffice.cheques.annuler', $annule), ['motif' => 'Encore'])
            ->assertSessionHasErrors('motif');

        // Still listed, flagged, and out of the « en main » total.
        $props = null;
        $this->get(route('backoffice.cheques.index', ['dateEcheanceFrom' => '']))
            ->assertOk()
            ->assertInertia(function (\Inertia\Testing\AssertableInertia $page) use (&$props): void {
                $props = $page->toArray()['props'];
            });
        $rows = collect($props['cheques']['data'])->keyBy('id');
        $this->assertSame(Cheque::STATUT_ANNULE, $rows[$annule->id]['statut']);
        $this->assertNotNull($rows[$annule->id]['annulationBlocker']);
        $this->assertNull($rows[$vivant->id]['annulationBlocker']);
        $this->assertSame('700.00', $props['montantTotal']);
        $this->assertContains(Cheque::STATUT_ANNULE, $props['statuts']);
    }

    // --- Annuler un rejet (comptable) --------------------------------------

    public function test_a_rejection_made_by_mistake_is_undone_back_to_depose(): void
    {
        $guichet = $this->userWith('cheques.view', 'cheques.deposit');
        $comptable = $this->userWith('cheques.view', 'cheques.validate-deposit');
        $cheque = $this->cheque();
        $this->remettre($guichet, $cheque)->assertSessionHasNoErrors();
        $this->actingInCentre($comptable)
            ->patch(route('backoffice.cheques.update-statut', $cheque), ['statut' => Cheque::STATUT_REJETE])
            ->assertSessionHasNoErrors();
        $soldes = $this->soldes();

        $this->actingInCentre($comptable)
            ->patch(route('backoffice.cheques.update-statut', $cheque), ['statut' => Cheque::STATUT_DEPOSE])
            ->assertSessionHasNoErrors();

        $this->assertSame(Cheque::STATUT_DEPOSE, $cheque->fresh()->statut);
        $this->assertSame($soldes, $this->soldes());

        // The decision is open again.
        $this->actingInCentre($comptable)
            ->patch(route('backoffice.cheques.update-statut', $cheque), ['statut' => Cheque::STATUT_ENCAISSE])
            ->assertSessionHasNoErrors();
        $this->assertSame(Cheque::STATUT_ENCAISSE, $cheque->fresh()->statut);
    }

    public function test_a_rejection_with_consequences_cannot_be_undone(): void
    {
        $comptable = $this->userWith('cheques.view', 'cheques.validate-deposit');

        // 1. The paper already went back to its owner.
        $rendu = $this->cheque();
        $rendu->update(['statut' => Cheque::STATUT_REJETE, 'retourne_le' => now(), 'retourne_par_id' => $comptable->employee->id]);

        // 2. A payment it funded was refunded because of the rejection.
        $rembourse = $this->cheque();
        $rembourse->update(['statut' => Cheque::STATUT_REJETE]);
        $paiement = $rembourse->encaissements()->sole();
        \App\Models\Remboursement::create([
            'reference' => 'RMB-T1', 'beneficiaire_id' => $this->student->id, 'encaissement_id' => $paiement->id,
            'caisse_id' => $paiement->caisse_id, 'etablissement_id' => $this->centre->id, 'montant' => 100,
            'date_remboursement' => '2026-08-12', 'motif' => 'Chèque rejeté', 'agent_id' => $comptable->employee->id,
        ]);

        foreach ([$rendu, $rembourse] as $cheque) {
            $this->actingInCentre($comptable)
                ->patch(route('backoffice.cheques.update-statut', $cheque), ['statut' => Cheque::STATUT_DEPOSE])
                ->assertSessionHasErrors('statut');

            $this->assertSame(Cheque::STATUT_REJETE, $cheque->fresh()->statut);
        }
    }

    public function test_only_the_accountant_role_decides_a_deposit(): void
    {
        $this->assertTrue(Role::findByName('accountant')->hasPermissionTo('cheques.validate-deposit'));
        $this->assertTrue(Role::findByName('accountant')->hasPermissionTo('cheques.cancel'));
        $this->assertFalse(Role::findByName('administrative-assistant')->hasPermissionTo('cheques.cancel'));
        $this->assertFalse(Role::findByName('director')->hasPermissionTo('cheques.cancel'));
        $this->assertFalse(Role::findByName('administrative-assistant')->hasPermissionTo('cheques.validate-deposit'));
        // Everyone still deposits.
        $this->assertTrue(Role::findByName('administrative-assistant')->hasPermissionTo('cheques.deposit'));
    }
}
