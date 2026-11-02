<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Caisse;
use App\Models\DemandeRemboursement;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Remboursement;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Support\Settings\AppSettings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * ⚠ Un remboursement est une DEMANDE quand « Validation des remboursements »
 * est active (02/11/2026, défaut).
 *
 *  - la demande ne débite RIEN et ne crée aucune ligne `remboursements` ;
 *  - seuls un directeur (dans SES centres) et un super-admin l'approuvent ;
 *  - à l'approbation l'argent sort en Espèces de la caisse du DEMANDEUR ;
 *    seul un super-admin peut choisir TPE / Virement, et l'argent sort alors
 *    du compte de cette méthode du centre de la demande ;
 *  - un refus ne bouge rien et garde la ligne avec son motif.
 */
final class DemandeRemboursementTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private Etablissement $autreCentre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        AppSettings::setBool(AppSettings::REFUND_APPROVAL, true);
        $this->centre = Etablissement::factory()->create();
        $this->autreCentre = Etablissement::factory()->create();
    }

    private function employe(Etablissement $centre, ?string $role = null, array $permissions = []): User
    {
        $user = User::factory()->create();

        if ($role !== null) {
            $user->assignRole($role);
        }

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $centre->id]);

        return $user->fresh();
    }

    private function guichet(): User
    {
        $user = $this->employe($this->centre, null, ['refunds.view', 'refunds.create']);
        $user->employee->till()->firstOrFail()->update(['solde' => 1000]);

        return $user->fresh();
    }

    private function demander(User $guichet, Student $student, string $montant = '300', ?int $encaissementId = null): DemandeRemboursement
    {
        $this->actingAs($guichet)->post(route('backoffice.remboursements.store'), array_filter([
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $encaissementId,
            'montant' => $montant,
            'motif' => 'Désistement',
        ]))->assertSessionDoesntHaveErrors();

        return DemandeRemboursement::query()->latest('id')->firstOrFail();
    }

    private function compte(string $type, Etablissement $centre): Caisse
    {
        return Caisse::query()->where('etablissement_id', $centre->id)->where('type', $type)->firstOrFail();
    }

    public function test_a_request_moves_no_money_and_creates_no_refund(): void
    {
        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $demande = $this->demander($guichet, $student);

        $this->assertSame(DemandeRemboursement::STATUT_EN_ATTENTE, $demande->statut);
        $this->assertSame('300.00', (string) $demande->montant);
        $this->assertSame($this->centre->id, $demande->etablissement_id);
        $this->assertSame($guichet->employee->id, $demande->demande_par_id);
        $this->assertNull($demande->methode);
        $this->assertSame(0, Remboursement::query()->count());
        $this->assertSame('1000.00', (string) $guichet->employee->till()->firstOrFail()->solde);
    }

    public function test_a_request_naming_a_till_is_refused_not_ignored(): void
    {
        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->actingAs($guichet)->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'caisse_id' => $guichet->employee->till()->firstOrFail()->id,
            'montant' => '300',
        ])->assertSessionHasErrors('caisse_id');

        $this->assertSame(0, DemandeRemboursement::query()->count());
    }

    public function test_the_centres_director_approves_in_cash_from_the_requesters_till(): void
    {
        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $demande = $this->demander($guichet, $student);

        $directeur = $this->employe($this->centre, 'director');
        $tiroirDirecteur = $directeur->employee->till()->firstOrFail();
        $tiroirDirecteur->update(['solde' => 5000]);

        $this->actingAs($directeur)
            ->patch(route('backoffice.demandes-remboursement.approuver', $demande))
            ->assertSessionDoesntHaveErrors();

        $demande->refresh();
        $remboursement = Remboursement::query()->firstOrFail();

        $this->assertSame(DemandeRemboursement::STATUT_APPROUVEE, $demande->statut);
        $this->assertSame(Encaissement::METHODE_ESPECES, $demande->methode);
        $this->assertSame($remboursement->id, $demande->remboursement_id);
        $this->assertSame($directeur->employee->id, $demande->decide_par_id);

        // L'argent sort du tiroir de CELUI QUI A DEMANDÉ, jamais de celui de l'approbateur.
        $this->assertSame($guichet->employee->till()->firstOrFail()->id, $remboursement->caisse_id);
        $this->assertSame($guichet->employee->id, $remboursement->agent_id);
        $this->assertSame('700.00', (string) $guichet->employee->till()->firstOrFail()->solde);
        $this->assertSame('5000.00', (string) $tiroirDirecteur->fresh()->solde);
        $this->assertSame(now()->toDateString(), $remboursement->date_remboursement->toDateString());
    }

    public function test_a_director_of_another_centre_cannot_approve(): void
    {
        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $demande = $this->demander($guichet, $student);

        $directeurAilleurs = $this->employe($this->autreCentre, 'director');

        $this->actingAs($directeurAilleurs)
            ->patch(route('backoffice.demandes-remboursement.approuver', $demande))
            ->assertForbidden();

        $this->assertSame(DemandeRemboursement::STATUT_EN_ATTENTE, $demande->fresh()->statut);
        $this->assertSame(0, Remboursement::query()->count());
    }

    public function test_the_front_office_cannot_approve(): void
    {
        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $demande = $this->demander($guichet, $student);

        $collegue = $this->employe($this->centre, 'administrative-assistant');

        $this->actingAs($collegue)
            ->patch(route('backoffice.demandes-remboursement.approuver', $demande))
            ->assertForbidden();

        $this->assertSame(0, Remboursement::query()->count());
    }

    public function test_a_director_cannot_choose_tpe_or_virement(): void
    {
        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $demande = $this->demander($guichet, $student);

        $tpe = $this->compte(Caisse::TYPE_TPE, $this->centre);
        $tpe->update(['solde' => 2000]);

        $directeur = $this->employe($this->centre, 'director');

        $this->actingAs($directeur)
            ->patch(route('backoffice.demandes-remboursement.approuver', $demande), ['methode' => Encaissement::METHODE_TPE])
            ->assertSessionHasErrors('methode');

        $this->assertSame(DemandeRemboursement::STATUT_EN_ATTENTE, $demande->fresh()->statut);
        $this->assertSame('2000.00', (string) $tpe->fresh()->solde);
        $this->assertSame('1000.00', (string) $guichet->employee->till()->firstOrFail()->solde);
    }

    public function test_a_super_admin_approving_by_tpe_debits_the_centres_tpe_account(): void
    {
        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $demande = $this->demander($guichet, $student);

        $tpe = $this->compte(Caisse::TYPE_TPE, $this->centre);
        $tpe->update(['solde' => 2000]);
        $tpeAilleurs = $this->compte(Caisse::TYPE_TPE, $this->autreCentre);
        $tpeAilleurs->update(['solde' => 2000]);

        $admin = $this->employe($this->autreCentre, Role::SUPER_ADMIN);

        $this->actingAs($admin)
            ->patch(route('backoffice.demandes-remboursement.approuver', $demande), ['methode' => Encaissement::METHODE_TPE])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('1700.00', (string) $tpe->fresh()->solde);
        // Le compte du centre de la DEMANDE, jamais celui de l'approbateur.
        $this->assertSame('2000.00', (string) $tpeAilleurs->fresh()->solde);
        $this->assertSame('1000.00', (string) $guichet->employee->till()->firstOrFail()->solde);
        $this->assertSame(Encaissement::METHODE_TPE, $demande->fresh()->methode);
        $this->assertSame($tpe->id, Remboursement::query()->firstOrFail()->caisse_id);
    }

    public function test_a_super_admin_approving_by_virement_debits_the_centres_virement_account(): void
    {
        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $demande = $this->demander($guichet, $student, '450');

        $virement = $this->compte(Caisse::TYPE_VIREMENT, $this->centre);
        $virement->update(['solde' => 1000]);

        $admin = $this->employe($this->centre, Role::SUPER_ADMIN);

        $this->actingAs($admin)
            ->patch(route('backoffice.demandes-remboursement.approuver', $demande), ['methode' => Encaissement::METHODE_VIREMENT])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('550.00', (string) $virement->fresh()->solde);
        $this->assertSame('1000.00', (string) $guichet->employee->till()->firstOrFail()->solde);
    }

    public function test_approval_is_refused_beyond_what_the_till_holds_and_the_request_stays_pending(): void
    {
        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $demande = $this->demander($guichet, $student, '1500');

        $directeur = $this->employe($this->centre, 'director');

        $this->actingAs($directeur)
            ->patch(route('backoffice.demandes-remboursement.approuver', $demande))
            ->assertSessionHasErrors('montant');

        $this->assertSame(DemandeRemboursement::STATUT_EN_ATTENTE, $demande->fresh()->statut);
        $this->assertSame('1000.00', (string) $guichet->employee->till()->firstOrFail()->solde);
        $this->assertSame(0, Remboursement::query()->count());
    }

    public function test_a_refusal_moves_nothing_and_keeps_its_reason(): void
    {
        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $demande = $this->demander($guichet, $student);

        $directeur = $this->employe($this->centre, 'director');

        $this->actingAs($directeur)
            ->patch(route('backoffice.demandes-remboursement.refuser', $demande), ['motif' => ''])
            ->assertSessionHasErrors('motif');

        $this->actingAs($directeur)
            ->patch(route('backoffice.demandes-remboursement.refuser', $demande), ['motif' => 'Pas de justificatif'])
            ->assertSessionDoesntHaveErrors();

        $demande->refresh();
        $this->assertSame(DemandeRemboursement::STATUT_REFUSEE, $demande->statut);
        $this->assertSame('Pas de justificatif', $demande->motif_refus);
        $this->assertSame(0, Remboursement::query()->count());
        $this->assertSame('1000.00', (string) $guichet->employee->till()->firstOrFail()->solde);

        // Une demande traitée ne se rejoue pas.
        $admin = $this->employe($this->centre, Role::SUPER_ADMIN);
        $this->actingAs($admin)
            ->patch(route('backoffice.demandes-remboursement.approuver', $demande))
            ->assertSessionHasErrors('demande');
        $this->assertSame(0, Remboursement::query()->count());
    }

    public function test_pending_requests_cannot_promise_the_same_payment_twice(): void
    {
        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        // Une avance de 500 DH (aucun frais) — remboursable jusqu'à son restant.
        $paiement = Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id,
            'etablissement_id' => $this->centre->id,
            'inscription_fee_id' => null,
            'montant' => 500,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => now()->toDateString(),
            'caisse_id' => $guichet->employee->till()->firstOrFail()->id,
            'agent_id' => $guichet->employee->id,
        ]);

        $this->demander($guichet, $student, '400', $paiement->id);

        $this->actingAs($guichet)->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $paiement->id,
            'montant' => '200',
        ])->assertSessionHasErrors('montant');

        $this->assertSame(1, DemandeRemboursement::query()->count());
    }

    /**
     * Signalé le 02/11/2026 : DRB-002 refusée « disparaissait » (la liste
     * s'ouvrait sur « En attente »). Une demande refusée reste dans
     * l'historique, et elle LIBÈRE le paiement : seule une demande APPROUVÉE
     * le consomme.
     */
    public function test_a_refused_request_stays_listed_and_frees_the_payment_for_a_new_request(): void
    {
        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $paiement = Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id,
            'etablissement_id' => $this->centre->id,
            'inscription_fee_id' => null,
            'montant' => 500,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => now()->toDateString(),
            'caisse_id' => $guichet->employee->till()->firstOrFail()->id,
            'agent_id' => $guichet->employee->id,
        ]);

        $refusee = $this->demander($guichet, $student, '500', $paiement->id);
        $directeur = $this->employe($this->centre, 'director');
        $this->actingAs($directeur)
            ->patch(route('backoffice.demandes-remboursement.refuser', $refusee), ['motif' => 'Mauvais montant'])
            ->assertSessionDoesntHaveErrors();

        // Listée par défaut, avec son motif — jamais escamotée.
        $this->actingAs($guichet)
            ->get(route('backoffice.depenses.index', ['tab' => 'demandes-remboursement']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('demandesRemboursement.total', 1)
                ->where('demandesRemboursement.data.0.statut', DemandeRemboursement::STATUT_REFUSEE)
                ->where('demandesRemboursement.data.0.motifRefus', 'Mauvais montant')
                ->where('demandesRemboursement.data.0.encaissementId', $paiement->id));

        // Le paiement est de nouveau entièrement remboursable…
        $this->actingAs($guichet)
            ->getJson(route('backoffice.students.payments-for-refund', $student))
            ->assertJsonPath('payments.0.montantRemboursable', '500.00');

        // …et une nouvelle demande du même montant passe.
        $nouvelle = $this->demander($guichet, $student, '500', $paiement->id);
        $this->assertTrue($nouvelle->isEnAttente());

        // Une fois APPROUVÉE, elle consomme le paiement : plus rien à demander.
        $this->actingAs($directeur)
            ->patch(route('backoffice.demandes-remboursement.approuver', $nouvelle))
            ->assertSessionDoesntHaveErrors();

        $this->actingAs($guichet)->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $paiement->id,
            'montant' => '10',
        ])->assertSessionHasErrors('montant');
    }

    public function test_with_the_switch_off_a_refund_is_recorded_at_once(): void
    {
        AppSettings::setBool(AppSettings::REFUND_APPROVAL, false);

        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $this->actingAs($guichet)->post(route('backoffice.remboursements.store'), [
            'beneficiaire_id' => $student->id,
            'montant' => '300',
            'date_remboursement' => now()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(0, DemandeRemboursement::query()->count());
        $this->assertSame(1, Remboursement::query()->count());
        $this->assertSame('700.00', (string) $guichet->employee->till()->firstOrFail()->solde);
    }

    public function test_the_page_lists_pending_requests_and_who_may_decide(): void
    {
        $guichet = $this->guichet();
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $demande = $this->demander($guichet, $student);

        $this->actingAs($guichet)
            ->get(route('backoffice.depenses.index', ['tab' => 'remboursements']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('refundApprovalEnabled', true)
                ->where('canApproveRemboursement', false)
                ->where('demandesRemboursementEnAttente.count', 1)
                ->where('demandesRemboursementEnAttente.montant', '300.00')
                ->where('demandesRemboursement.data.0.reference', $demande->reference)
                ->where('demandesRemboursement.data.0.canDecide', false));

        $directeur = $this->employe($this->centre, 'director');

        $this->actingAs($directeur)
            ->get(route('backoffice.depenses.index', ['tab' => 'remboursements']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canApproveRemboursement', true)
                ->where('canChooseRemboursementMethode', false)
                ->where('demandesRemboursement.data.0.canDecide', true));
    }

    public function test_choosing_the_method_is_a_super_admin_only_permission(): void
    {
        $this->assertContains('refunds.choose-method', \App\Support\Authorization\PermissionRegistry::superAdminOnly());
        $this->assertTrue(Role::findByName('director')->hasPermissionTo('refunds.approve'));
        $this->assertFalse(Role::findByName('director')->hasPermissionTo('refunds.choose-method'));
        $this->assertFalse(Role::findByName('administrative-assistant')->hasPermissionTo('refunds.approve'));
    }
}
