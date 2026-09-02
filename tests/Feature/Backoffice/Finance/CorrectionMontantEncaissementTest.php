<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

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
use App\Services\Context\CurrentContext;
use App\Support\Authorization\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Correction du MONTANT d'un encaissement enregistré (02/09/2026).
 *
 * Deux règles demandées, testées ici :
 *  1. seul un SUPER-ADMIN peut corriger un montant (`payments.update-amount`
 *     est dans PermissionRegistry::superAdminOnly(), donc aucun preset de
 *     rôle ne le porte) ;
 *  2. l'écart bouge sur la caisse de l'EMPLOYÉ QUI A ENCAISSÉ — la caisse
 *     stockée sur la ligne — et JAMAIS sur celle du super-admin qui corrige.
 *     Sinon la correction déplacerait silencieusement de l'argent d'un till à
 *     l'autre et les deux soldes deviendraient faux.
 */
final class CorrectionMontantEncaissementTest extends TestCase
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

    private function superAdmin(): User
    {
        $user = User::factory()->create()->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    /** @return array{0: Student, 1: Inscription, 2: InscriptionFee} */
    private function enrolledStudentWithFee(float $montant = 1500): array
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => $montant,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais',
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-07-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$student, $inscription, $fee];
    }

    private function payLine(User $user, Student $student, Inscription $inscription, InscriptionFee $fee, string $montant): void
    {
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($this->centre->id);

        $this->actingAs($user)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => $montant, 'methode' => Encaissement::METHODE_ESPECES, 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionHasNoErrors();
    }

    // ── 1. Super-admin only ─────────────────────────────────────────────

    public function test_the_amount_permission_is_in_no_role_preset(): void
    {
        foreach (PermissionRegistry::matrix() as $role => $permissions) {
            $this->assertNotContains(
                'payments.update-amount',
                $permissions,
                "Le preset « {$role} » ne doit jamais porter payments.update-amount.",
            );
        }

        $this->assertContains('payments.update-amount', PermissionRegistry::superAdminOnly());
    }

    public function test_a_non_super_admin_cannot_change_the_amount(): void
    {
        $cashier = $this->userWith('payments.view', 'payments.create', 'payments.update');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $this->payLine($cashier, $student, $inscription, $fee, '900');

        $encaissement = Encaissement::query()->firstOrFail();
        $till = $cashier->employee->till;

        $this->actingAs($cashier)->put(route('backoffice.encaissements.update', $encaissement), [
            'montant' => '1200',
            'methode' => $encaissement->methode,
            'date_paiement' => '2025-09-20',
        ])->assertSessionHasErrors('montant');

        $this->assertSame('900.00', (string) $encaissement->fresh()->montant);
        $this->assertSame('900.00', (string) $till->fresh()->solde, 'Un refus ne doit toucher aucun solde.');
    }

    public function test_echoing_the_stored_amount_back_is_accepted(): void
    {
        // Le modal renvoie toujours le champ : une valeur IDENTIQUE ne doit
        // pas être traitée comme une tentative de correction.
        $cashier = $this->userWith('payments.view', 'payments.create', 'payments.update');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $this->payLine($cashier, $student, $inscription, $fee, '900');

        $encaissement = Encaissement::query()->firstOrFail();

        $this->actingAs($cashier)->put(route('backoffice.encaissements.update', $encaissement), [
            'montant' => '900.00',
            'methode' => $encaissement->methode,
            'date_paiement' => '2025-09-20',
            'note' => 'Correction de note',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Correction de note', $encaissement->fresh()->note);
    }

    // ── 2. The ORIGINAL cashier's till moves, never the corrector's ─────

    public function test_raising_the_amount_credits_the_original_cashiers_till(): void
    {
        $cashier = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $this->payLine($cashier, $student, $inscription, $fee, '900');

        $encaissement = Encaissement::query()->firstOrFail();
        $cashierTill = $cashier->employee->till;
        $admin = $this->superAdmin();
        $adminTill = $admin->employee->till;

        $this->assertSame('900.00', (string) $cashierTill->fresh()->solde);

        $this->actingAs($admin);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->actingAs($admin)->put(route('backoffice.encaissements.update', $encaissement), [
            'montant' => '1200',
            'methode' => $encaissement->methode,
            'date_paiement' => '2025-09-20',
        ])->assertSessionHasNoErrors();

        $this->assertSame('1200.00', (string) $encaissement->fresh()->montant);
        // +300 sur la caisse de la CAISSIÈRE…
        $this->assertSame('1200.00', (string) $cashierTill->fresh()->solde);
        // …et rien du tout sur celle du super-admin.
        $this->assertSame('0.00', (string) $adminTill->fresh()->solde);
        // La ligne reste rattachée à la même caisse.
        $this->assertSame($cashierTill->id, $encaissement->fresh()->caisse_id);
    }

    public function test_lowering_the_amount_debits_the_original_cashiers_till(): void
    {
        $cashier = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $this->payLine($cashier, $student, $inscription, $fee, '900');

        $encaissement = Encaissement::query()->firstOrFail();
        $cashierTill = $cashier->employee->till;
        $admin = $this->superAdmin();
        $adminTill = $admin->employee->till;

        $this->actingAs($admin);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->actingAs($admin)->put(route('backoffice.encaissements.update', $encaissement), [
            'montant' => '400',
            'methode' => $encaissement->methode,
            'date_paiement' => '2025-09-20',
        ])->assertSessionHasNoErrors();

        $this->assertSame('400.00', (string) $encaissement->fresh()->montant);
        $this->assertSame('400.00', (string) $cashierTill->fresh()->solde);
        $this->assertSame('0.00', (string) $adminTill->fresh()->solde);
    }

    public function test_a_non_cash_payment_moves_its_method_account_not_a_till(): void
    {
        $cashier = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);

        $this->actingAs($cashier);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->actingAs($cashier)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '900', 'methode' => Encaissement::METHODE_TPE, 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionHasNoErrors();

        $encaissement = Encaissement::query()->firstOrFail();
        $compteTpe = Caisse::query()
            ->where('etablissement_id', $this->centre->id)
            ->where('type', Encaissement::METHODE_TPE)
            ->firstOrFail();
        $cashierTill = $cashier->employee->till;

        $admin = $this->superAdmin();
        $this->actingAs($admin);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->actingAs($admin)->put(route('backoffice.encaissements.update', $encaissement), [
            'montant' => '1200',
            'methode' => $encaissement->methode,
            'date_paiement' => '2025-09-20',
        ])->assertSessionHasNoErrors();

        // L'écart suit la caisse STOCKÉE : le compte TPE du centre.
        $this->assertSame('1200.00', (string) $compteTpe->fresh()->solde);
        $this->assertSame('0.00', (string) $cashierTill->fresh()->solde);
    }

    // ── Garde-fous ──────────────────────────────────────────────────────

    public function test_the_correction_is_journaled_on_the_original_till(): void
    {
        $cashier = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $this->payLine($cashier, $student, $inscription, $fee, '900');

        $encaissement = Encaissement::query()->firstOrFail();
        $cashierTill = $cashier->employee->till;

        $admin = $this->superAdmin();
        $this->actingAs($admin);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->actingAs($admin)->put(route('backoffice.encaissements.update', $encaissement), [
            'montant' => '1200',
            'methode' => $encaissement->methode,
            'date_paiement' => '2025-09-20',
        ])->assertSessionHasNoErrors();

        // Le mouvement doit être visible dans le journal d'audit, sur la
        // caisse de la caissière — sinon 300 DH bougeraient sans trace
        // (CLAUDE.md §11, CaisseLedger).
        $entry = \App\Models\Activity::query()
            ->where('event', 'solde_movement')
            ->where('subject_type', Caisse::class)
            ->where('subject_id', $cashierTill->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($entry, 'La correction doit être journalisée sur la caisse d’origine.');
        $this->assertSame($this->centre->id, (int) ($entry->properties['etablissement_id'] ?? 0));
    }

    public function test_the_amount_cannot_exceed_what_remains_due_on_the_fee(): void
    {
        $cashier = $this->userWith('payments.view', 'payments.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1000);
        $this->payLine($cashier, $student, $inscription, $fee, '400');

        $encaissement = Encaissement::query()->firstOrFail();
        $cashierTill = $cashier->employee->till;

        $admin = $this->superAdmin();
        $this->actingAs($admin);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->actingAs($admin)->put(route('backoffice.encaissements.update', $encaissement), [
            'montant' => '1500',
            'methode' => $encaissement->methode,
            'date_paiement' => '2025-09-20',
        ])->assertSessionHasErrors('montant');

        $this->assertSame('400.00', (string) $encaissement->fresh()->montant);
        $this->assertSame('400.00', (string) $cashierTill->fresh()->solde);
    }

    public function test_a_refunded_payment_cannot_have_its_amount_corrected(): void
    {
        $cashier = $this->userWith('payments.view', 'payments.create', 'refunds.view', 'refunds.create');
        [$student, $inscription, $fee] = $this->enrolledStudentWithFee(1500);
        $this->payLine($cashier, $student, $inscription, $fee, '900');
        $encaissement = Encaissement::query()->firstOrFail();

        $this->actingAs($cashier)->post(route('backoffice.remboursements.store'), [
            'student_id' => $student->id,
            'beneficiaire_id' => $student->id,
            'encaissement_id' => $encaissement->id,
            'montant' => '200',
            'date_remboursement' => '2025-09-25',
            'motif' => 'Test',
        ])->assertSessionHasNoErrors();

        $admin = $this->superAdmin();
        $this->actingAs($admin);
        app(CurrentContext::class)->setEtablissement($this->centre->id);
        $this->actingAs($admin)->put(route('backoffice.encaissements.update', $encaissement), [
            'montant' => '1200',
            'methode' => $encaissement->methode,
            'date_paiement' => '2025-09-20',
        ])->assertSessionHasErrors('montant');

        $this->assertSame('900.00', (string) $encaissement->fresh()->montant);
    }
}
