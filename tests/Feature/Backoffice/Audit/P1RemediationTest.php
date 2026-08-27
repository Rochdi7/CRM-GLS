<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Audit;

use App\Domain\Groups\Actions\RetirerFraisGroupe;
use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Registrations\Actions\MettreAJourFraisInscription;
use App\Domain\Registrations\Queries\GetInscriptionDetails;
use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Role;
use App\Models\StockArticle;
use App\Models\StockMouvement;
use App\Models\StockType;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Audit 27/08/2026 — P1 remediation (data integrity): safe deletes
 * (CRUD-F1/F2/F3, DB-02), group fee lifecycle (CRUD-F6/F14/F7, DB-10,
 * R-04, R-07, DB-06), totals ignoring hidden lines (R-03), rejected-cheque
 * avances (DB-05).
 */
final class P1RemediationTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $this->admin->id, 'etablissement_id' => $this->centre->id]);
        $this->admin = $this->admin->fresh();
    }

    private function group(): Group
    {
        return Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
    }

    private function enrol(Group $group, string $statut = Inscription::STATUT_ACTIVE): Inscription
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        return Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => $statut, 'date_inscription' => '2025-09-15',
        ]);
    }

    private function fee(Inscription $inscription, ?Frais $frais, float $montant): InscriptionFee
    {
        return InscriptionFee::create([
            'inscription_id' => $inscription->id, 'frais_id' => $frais?->id, 'nom' => $frais?->nom ?? 'Manuel',
            'montant_initial' => $montant, 'montant' => $montant, 'date_echeance' => '2026-03-01',
        ]);
    }

    private function pay(InscriptionFee $fee, float $montant): Encaissement
    {
        return Encaissement::create([
            'reference' => 'ENC-'.fake()->unique()->numerify('#####'),
            'student_id' => $fee->inscription->student_id, 'inscription_fee_id' => $fee->id,
            'etablissement_id' => $this->centre->id,
            'montant' => $montant, 'methode' => 'Espèces', 'date_paiement' => '2025-09-20',
            'caisse_id' => $this->admin->employee->caisses()->first()->id, 'agent_id' => $this->admin->employee->id,
        ]);
    }

    // --- CRUD-F1 centre deletion --------------------------------------

    public function test_a_centre_holding_a_group_cannot_be_deleted_and_a_pristine_one_can(): void
    {
        $this->actingAs($this->admin);
        $used = Etablissement::factory()->create();
        Group::factory()->create(['etablissement_id' => $used->id, 'annee_scolaire_id' => $this->annee->id]);

        $this->from(route('backoffice.settings'))
            ->delete(route('backoffice.etablissements.destroy', $used))
            ->assertSessionHasErrors('delete');
        $this->assertNotNull($used->fresh());

        $pristine = Etablissement::factory()->create();
        $this->assertGreaterThan(0, $pristine->caisses()->count(), 'method accounts are provisioned by the observer');

        $this->delete(route('backoffice.etablissements.destroy', $pristine))->assertRedirect();
        $this->assertNull(Etablissement::find($pristine->id));
        $this->assertSame(0, Caisse::query()->whereNull('etablissement_id')->whereIn('type', Caisse::TYPES_METHODE)->count());
    }

    // --- CRUD-F2 / F3 employees -----------------------------------------

    public function test_employee_creation_with_a_login_email_already_taken_is_refused_before_insert(): void
    {
        $this->actingAs($this->admin);
        $taken = User::factory()->create(['email' => 'taken@gls.test']);

        $this->from(route('backoffice.employees.index'))->post(route('backoffice.employees.store'), [
            'nom' => 'Dup', 'prenom' => 'Licate', 'sexe' => 'Femme',
            'categorie' => Employee::CATEGORIE_ENSEIGNANT, 'statut' => Employee::STATUT_ACTIF,
            'email' => 'taken@gls.test', 'etablissement_ids' => [$this->centre->id],
        ])->assertSessionHasErrors('email');

        $this->assertNull(Employee::query()->where('nom', 'Dup')->first());
        $this->assertNotNull($taken);
    }

    public function test_deleting_an_employee_disables_its_login_and_removes_its_empty_till(): void
    {
        $this->actingAs($this->admin);
        $employee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $user = $employee->fresh()->user;
        $this->assertNotNull($user);
        $this->assertTrue($employee->fresh()->caisses()->exists());

        $this->delete(route('backoffice.employees.destroy', $employee))->assertRedirect();

        $this->assertNull(Employee::find($employee->id));
        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame(0, Caisse::query()->where('responsable_employee_id', $employee->id)->count());
    }

    public function test_deleting_an_employee_whose_till_holds_money_is_refused(): void
    {
        $this->actingAs($this->admin);
        $employee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $employee->caisses()->first()->forceFill(['solde' => 10])->saveQuietly();

        $this->from(route('backoffice.employees.index'))
            ->delete(route('backoffice.employees.destroy', $employee))
            ->assertSessionHasErrors('delete');
        $this->assertNotNull(Employee::find($employee->id));
    }

    // --- DB-02 inscription deletion returns books ------------------------

    public function test_deleting_a_registration_returns_its_books_to_stock(): void
    {
        $this->actingAs($this->admin);
        $type = StockType::create(['nom' => StockType::SYSTEM_LIVRE, 'is_system' => true, 'statut' => StockType::STATUT_ACTIF]);
        $book = StockArticle::create([
            'reference' => 'ART-000001', 'nom' => 'Menschen A1', 'stock_type_id' => $type->id,
            'quantite' => 5, 'etablissement_id' => $this->centre->id, 'statut' => StockArticle::STATUT_ACTIF,
        ]);
        $inscription = $this->enrol($this->group());
        app(\App\Domain\Registrations\Actions\AssignerLivresInscription::class)->handle($inscription, [$book->id], $this->admin->employee);
        $this->assertSame(4, $book->fresh()->quantite);

        $this->delete(route('backoffice.inscriptions.destroy', $inscription))->assertRedirect();

        $this->assertNull(Inscription::find($inscription->id));
        $this->assertSame(5, $book->fresh()->quantite);
        $this->assertSame(1, StockMouvement::query()->where('stock_article_id', $book->id)->where('type', StockMouvement::TYPE_ENTREE)->count());
    }

    // --- CRUD-F6 / F14 group edit -----------------------------------------

    public function test_editing_a_group_keeps_a_fee_that_became_inactive_in_the_catalog(): void
    {
        $this->actingAs($this->admin);
        $group = $this->group();
        $frais = Frais::create(['nom' => 'Frais ancien', 'statut' => 'Actif', 'montant_defaut' => 100]);
        $group->frais()->attach($frais->id, ['montant' => 100, 'date_echeance' => '2026-01-01']);
        $frais->update(['statut' => 'Inactif']);

        $this->put(route('backoffice.groups.update', $group), [
            'nom' => 'Renamed', 'niveau' => $group->niveau, 'statut' => $group->statut,
            'date_debut_formation' => '2025-09-01', 'date_fin_formation' => '2026-06-30',
        ])->assertSessionDoesntHaveErrors();

        $this->assertTrue($group->fresh()->frais()->where('frais.id', $frais->id)->exists());
    }

    public function test_a_finished_group_cannot_be_reopened_from_the_edit_modal(): void
    {
        $this->actingAs($this->admin);
        $group = $this->group();
        $group->archiverCommeTermine($this->admin->employee);
        $this->assertSame(Group::STATUT_FIN_FORMATION, $group->fresh()->statut);

        $this->put(route('backoffice.groups.update', $group), [
            'nom' => $group->nom, 'niveau' => $group->niveau, 'statut' => Group::STATUT_EN_FORMATION,
            'date_debut_formation' => '2025-09-01', 'date_fin_formation' => '2026-06-30',
        ]);

        $this->assertSame(Group::STATUT_FIN_FORMATION, $group->fresh()->statut);
    }

    public function test_restore_fee_validates_its_payload(): void
    {
        $this->actingAs($this->admin);
        $group = $this->group();
        $frais = Frais::create(['nom' => 'Frais X', 'statut' => 'Actif', 'montant_defaut' => 100]);

        $this->postJson(route('backoffice.groups.frais.restore', [$group, $frais]), ['montant' => -5, 'date_echeance' => 'abc'])
            ->assertStatus(422)->assertJsonValidationErrors(['montant', 'date_echeance']);
    }

    // --- DB-10 / R-04 remove & restore scope ---------------------------------

    public function test_removing_a_group_fee_leaves_cancelled_registrations_alone_and_restore_keeps_manual_exemptions(): void
    {
        $group = $this->group();
        $frais = Frais::create(['nom' => 'Frais Avril', 'statut' => 'Actif', 'montant_defaut' => 1300]);
        $group->frais()->attach($frais->id, ['montant' => 1300, 'date_echeance' => '2026-04-01']);

        $active = $this->enrol($group);
        $cancelled = $this->enrol($group, Inscription::STATUT_ANNULEE);
        $exempted = $this->enrol($group);
        $feeActive = $this->fee($active, $frais, 1300);
        $feeCancelled = $this->fee($cancelled, $frais, 1300);
        $feeExempted = $this->fee($exempted, $frais, 1300);
        app(\App\Domain\Registrations\Actions\BasculerVisibiliteFraisInscription::class)->hide($exempted, $feeExempted);

        $action = app(RetirerFraisGroupe::class);
        $action->handle($group, $frais->id);

        $this->assertNotNull($feeActive->fresh()->masque_le);
        $this->assertNull($feeCancelled->fresh()->masque_le, 'a cancelled registration is frozen history');

        $action->restore($group, $frais->id, 1300, '2026-04-01', null);

        $this->assertNull($feeActive->fresh()->masque_le);
        $this->assertNotNull($feeExempted->fresh()->masque_le, 'a manual exemption survives a group restore');
    }

    // --- R-07 / DB-06 fee update --------------------------------------------

    public function test_fee_update_refuses_re_adding_a_hidden_catalog_fee_and_pricing_below_paid(): void
    {
        $group = $this->group();
        $frais = Frais::create(['nom' => 'Frais Mai', 'statut' => 'Actif', 'montant_defaut' => 1300]);
        $inscription = $this->enrol($group);
        $hidden = $this->fee($inscription, $frais, 1300);
        $hidden->update(['masque_le' => now()]);
        $paid = $this->fee($inscription, null, 1000);
        $this->pay($paid, 800);
        $action = app(MettreAJourFraisInscription::class);

        try {
            $action->handle($inscription, [
                ['id' => $paid->id, 'nom' => 'Manuel', 'montant_initial' => 1000],
                ['frais_id' => $frais->id, 'nom' => 'Frais Mai', 'montant_initial' => 1300],
            ]);
            $this->fail('re-adding a hidden fee must be refused');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fee_lines', $e->errors());
        }

        try {
            $action->handle($inscription, [['id' => $paid->id, 'nom' => 'Manuel', 'montant_initial' => 1000, 'remise_pct' => 50]]);
            $this->fail('pricing below the paid amount must be refused');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fee_lines', $e->errors());
        }

        $this->assertSame('1000.00', (string) $paid->fresh()->montant);
        $this->assertSame(1, $inscription->fees()->where('frais_id', $frais->id)->count());
    }

    // --- R-03 details ignore hidden lines ------------------------------------

    public function test_registration_details_ignore_hidden_lines(): void
    {
        $inscription = $this->enrol($this->group());
        $this->fee($inscription, null, 300);
        $this->fee($inscription, null, 1300)->update(['masque_le' => now()]);

        $details = app(GetInscriptionDetails::class)($inscription);

        $this->assertSame('300.00', $details['totalDu']);
        $this->assertCount(1, $details['fees']);
    }

    // --- DB-05 rejected cheque avance -----------------------------------------

    public function test_an_advance_funded_by_a_rejected_cheque_cannot_be_applied(): void
    {
        $inscription = $this->enrol($this->group());
        $fee = $this->fee($inscription, null, 500);
        $cheque = Cheque::create([
            'reference' => 'CHQ-REJ', 'source' => Cheque::SOURCE_ETUDIANT, 'student_id' => $inscription->student_id,
            'numero_cheque' => 'CHQ-9', 'banque' => 'BMCE', 'date_reception' => '2025-09-01', 'date_echeance' => '2025-10-01',
            'type' => Cheque::TYPE_A_DEPOSER, 'statut' => Cheque::STATUT_REJETE, 'montant' => 500,
            'etablissement_id' => $this->centre->id, 'agent_id' => $this->admin->employee->id,
        ]);
        $avance = Encaissement::create([
            'reference' => 'ENC-AV', 'student_id' => $inscription->student_id, 'inscription_fee_id' => null,
            'etablissement_id' => $this->centre->id, 'cheque_id' => $cheque->id,
            'montant' => 500, 'methode' => 'Chèque', 'date_paiement' => '2025-09-20',
            'caisse_id' => $this->admin->employee->caisses()->first()->id, 'agent_id' => $this->admin->employee->id,
        ]);

        $this->expectException(ValidationException::class);
        app(AppliquerAvance::class)->handle($avance, $fee, 500);
    }
}
