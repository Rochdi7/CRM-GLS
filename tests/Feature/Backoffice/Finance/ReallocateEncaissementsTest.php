<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Payments\Actions\ReaffecterEncaissements;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Support\Authorization\PermissionRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReallocateEncaissementsTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

    private AnneeScolaire $anneeSuivante;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->centre = Etablissement::factory()->create();
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->anneeSuivante = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);
    }

    /** @return array{0: Inscription, 1: InscriptionFee} */
    private function inscriptionWithFee(Student $student, AnneeScolaire $annee, string $feeNom, float $montant, ?Group $group = null): array
    {
        $group ??= Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $annee->id,
        ]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => $feeNom,
            'montant_initial' => $montant, 'montant' => $montant,
            'date_echeance' => '2025-10-01', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$inscription, $fee];
    }

    private function superAdminAgent(): Employee
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);

        return Employee::factory()->create([
            'user_id' => $user->id, 'etablissement_id' => $this->centre->id,
        ]);
    }

    /**
     * The core promise of the screen: money moves to the other année's
     * registration, the payment DATE is untouched, and caisses.solde does not
     * budge — the cash never left the till, only its allocation changed.
     */
    public function test_it_moves_payments_across_years_without_touching_the_date_or_the_till(): void
    {
        $agent = $this->superAdminAgent();
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $soldeAvant = (float) $caisse->solde;

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [, $feeSource] = $this->inscriptionWithFee($student, $this->annee, 'Frais de Mars', 1300);
        $groupeCible = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->anneeSuivante->id,
        ]);
        [$cibleInscription] = $this->inscriptionWithFee($student, $this->anneeSuivante, 'Frais de Mars', 1300, $groupeCible);

        $paiement = Encaissement::create([
            'reference' => 'ENC-MV1', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => $feeSource->id, 'caisse_id' => $caisse->id,
            'montant' => 1300, 'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-03-10', 'etablissement_id' => $this->centre->id,
        ]);

        $result = app(ReaffecterEncaissements::class)->handle([$paiement->id], $groupeCible);

        $this->assertSame(1, $result['deplaces']);
        $this->assertSame('1300.00', $result['montant']);

        // The original row survives, detached: money records are append-only.
        $this->assertNull($paiement->fresh()->inscription_fee_id);

        // The allocation landed on the target year's fee, with the SAME date.
        $applied = Encaissement::query()->where('applied_from_encaissement_id', $paiement->id)->firstOrFail();
        $this->assertSame(
            InscriptionFee::query()->where('inscription_id', $cibleInscription->id)->value('id'),
            $applied->inscription_fee_id
        );
        $this->assertSame('2026-03-10', $applied->date_paiement->toDateString());

        // The till never moved.
        $this->assertSame($soldeAvant, (float) $caisse->fresh()->solde);
    }

    /**
     * THE FRAIS FOLLOWS THE MONEY. When the target registration has no fee
     * of that name, the line is recreated there from the source fee - same
     * nom, same montant, same echeance - instead of the payment being left as
     * an avance with no frais at all, which is what the operator sees as
     * "le frais a disparu de l'encaissement".
     */
    public function test_a_missing_fee_is_recreated_on_the_target_registration(): void
    {
        $agent = $this->superAdminAgent();
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [, $feeSource] = $this->inscriptionWithFee($student, $this->annee, 'Frais de Mars', 1300);
        $groupeCible = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->anneeSuivante->id,
        ]);
        [$cibleInscription] = $this->inscriptionWithFee($student, $this->anneeSuivante, 'Frais de Juin', 1300, $groupeCible);

        $paiement = Encaissement::create([
            'reference' => 'ENC-MV2', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => $feeSource->id, 'caisse_id' => $caisse->id,
            'montant' => 1300, 'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-03-10', 'etablissement_id' => $this->centre->id,
        ]);

        $result = app(ReaffecterEncaissements::class)->handle([$paiement->id], $groupeCible);

        $this->assertSame(1, $result['deplaces']);
        $this->assertSame(0, $result['avances']);
        $this->assertSame(1, $result['fraisCrees']);

        // "Frais de Mars" now exists on the target registration, copied from
        // the source line - and the money sits on it.
        $recreee = InscriptionFee::query()
            ->where('inscription_id', $cibleInscription->id)
            ->where('nom', 'Frais de Mars')
            ->firstOrFail();

        $this->assertSame('1300.00', $recreee->montant);
        $this->assertSame($feeSource->date_echeance->toDateString(), $recreee->date_echeance->toDateString());

        $applied = Encaissement::query()->where('applied_from_encaissement_id', $paiement->id)->firstOrFail();
        $this->assertSame($recreee->id, $applied->inscription_fee_id);
        // The date the money was received is untouched by the move.
        $this->assertSame('2026-03-10', $applied->date_paiement->toDateString());
        $this->assertSame(InscriptionFee::STATUT_PAYE, $recreee->fresh()->statut);

        // The untouched "Frais de Juin" line is not disturbed.
        $this->assertSame(
            InscriptionFee::STATUT_NON_PAYE,
            InscriptionFee::query()->where('inscription_id', $cibleInscription->id)
                ->where('nom', 'Frais de Juin')->value('statut')
        );
    }

    /**
     * A student with no registration in the target group has nowhere to carry
     * the fee - so the payment is left ENTIRELY alone, still attached to its
     * original frais. Detaching it first and only then discovering the
     * problem is what stripped the frais off the encaissement.
     */
    public function test_an_unplaceable_payment_keeps_its_original_fee(): void
    {
        $agent = $this->superAdminAgent();
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);

        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id, 'prenom' => 'SANS', 'nom' => 'GROUPE',
        ]);
        [, $feeSource] = $this->inscriptionWithFee($student, $this->annee, 'Frais de Juillet', 1300);

        $groupeCible = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->anneeSuivante->id,
        ]);

        $paiement = Encaissement::create([
            'reference' => 'ENC-KEEP', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => $feeSource->id, 'caisse_id' => $caisse->id,
            'montant' => 1300, 'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-07-10', 'etablissement_id' => $this->centre->id,
        ]);

        $result = app(ReaffecterEncaissements::class)->handle([$paiement->id], $groupeCible);

        $this->assertSame(0, $result['deplaces']);
        $this->assertContains('SANS GROUPE', $result['sansInscription']);
        $this->assertSame($feeSource->id, $paiement->fresh()->inscription_fee_id);
        $this->assertSame('2026-07-10', $paiement->fresh()->date_paiement->toDateString());
    }

    /**
     * A masked line of the same name is brought back rather than duplicated -
     * the student's statement keeps ONE "Frais de Juillet", not two.
     */
    public function test_a_masked_fee_of_the_same_name_is_unmasked_not_duplicated(): void
    {
        $agent = $this->superAdminAgent();
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);

        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [, $feeSource] = $this->inscriptionWithFee($student, $this->annee, 'Frais de Juillet', 1300);
        $groupeCible = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->anneeSuivante->id,
        ]);
        [$cibleInscription, $feeCible] = $this->inscriptionWithFee(
            $student, $this->anneeSuivante, 'Frais de Juillet', 1300, $groupeCible
        );
        $feeCible->update(['masque_le' => now()]);

        $paiement = Encaissement::create([
            'reference' => 'ENC-MASK', 'agent_id' => $agent->id, 'student_id' => $student->id,
            'inscription_fee_id' => $feeSource->id, 'caisse_id' => $caisse->id,
            'montant' => 1300, 'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-07-10', 'etablissement_id' => $this->centre->id,
        ]);

        $result = app(ReaffecterEncaissements::class)->handle([$paiement->id], $groupeCible);

        $this->assertSame(1, $result['deplaces']);
        $this->assertSame(0, $result['fraisCrees'], 'No duplicate line was created.');
        $this->assertNull($feeCible->fresh()->masque_le);
        $this->assertSame(
            1,
            InscriptionFee::query()->where('inscription_id', $cibleInscription->id)
                ->where('nom', 'Frais de Juillet')->count()
        );
    }

    /** payments.reallocate is super-admin only — no role preset may hold it. */
    public function test_the_permission_is_reserved_to_super_admins(): void
    {
        $this->assertContains('payments.reallocate', PermissionRegistry::superAdminOnly());

        // NOTE: it stays in grantable() on purpose — like payments.delete and
        // groups.move-year, a super-admin may still hand it to one named user
        // on the Autorisations screen. What superAdminOnly() forbids is a
        // ROLE PRESET carrying it, which matrix() filters below.

        foreach (PermissionRegistry::matrix() as $role => $permissions) {
            $this->assertNotContains(
                'payments.reallocate',
                $permissions,
                "Role {$role} must not carry payments.reallocate."
            );
        }
    }

    /** The screen itself is closed to anyone without the permission. */
    public function test_a_non_super_admin_cannot_reach_the_screen(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('payments.view', 'payments.create');
        Employee::factory()->create([
            'user_id' => $user->id, 'etablissement_id' => $this->centre->id,
        ]);

        $this->actingAs($user->fresh())
            ->get(route('backoffice.encaissements.reaffecter.index'))
            ->assertForbidden();
    }

    /**
     * ⚠ The reason the target is a GROUP and not a registration: a selection
     * normally spans several students, and each one's money may only ever
     * land on THAT student's own inscription. Aiming a dozen students'
     * payments at one registration was possible in the first version of the
     * screen (26/08/2026) — AppliquerAvance would have refused it mid-
     * transaction, so it could only ever have failed with a raw error.
     */
    public function test_each_students_money_lands_on_his_own_registration_never_another(): void
    {
        $agent = $this->superAdminAgent();
        $caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);

        $groupeCible = Group::factory()->create([
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->anneeSuivante->id,
        ]);

        $paiements = [];
        $ciblesParStudent = [];

        foreach (['A', 'B'] as $index => $suffix) {
            $student = Student::factory()->create([
                'etablissement_id' => $this->centre->id, 'nom' => "ELEVE{$suffix}",
            ]);
            [, $feeSource] = $this->inscriptionWithFee($student, $this->annee, 'Frais de Juillet', 1300);
            [$cible] = $this->inscriptionWithFee($student, $this->anneeSuivante, 'Frais de Juillet', 1300, $groupeCible);
            $ciblesParStudent[$student->id] = $cible->id;

            $paiements[] = Encaissement::create([
                'reference' => "ENC-MULTI{$index}", 'agent_id' => $agent->id, 'student_id' => $student->id,
                'inscription_fee_id' => $feeSource->id, 'caisse_id' => $caisse->id,
                'montant' => 1300, 'methode' => Encaissement::METHODE_ESPECES,
                'date_paiement' => '2026-07-10', 'etablissement_id' => $this->centre->id,
            ]);
        }

        $result = app(ReaffecterEncaissements::class)->handle(
            array_map(static fn (Encaissement $e): int => $e->id, $paiements),
            $groupeCible
        );

        $this->assertSame(2, $result['deplaces']);

        // Each allocation sits on its OWN student's registration.
        foreach ($paiements as $paiement) {
            $applied = Encaissement::query()->where('applied_from_encaissement_id', $paiement->id)->firstOrFail();
            $fee = InscriptionFee::findOrFail($applied->inscription_fee_id);

            $this->assertSame($paiement->student_id, $applied->student_id);
            $this->assertSame($ciblesParStudent[$paiement->student_id], $fee->inscription_id);
        }
    }

}
