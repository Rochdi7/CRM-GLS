<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Context;

use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Seance;
use App\Models\StockArticle;
use App\Models\StockMouvement;
use App\Models\StockType;
use App\Models\Student;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every write whose centre/année is inherited from a client-chosen parent
 * (or that mutates a record carrying its own centre/année) must sit inside
 * the ACTIVE working context — the top-bar year + centre (CLAUDE.md §11
 * « Context scoping is MANDATORY »). A stale dropdown loaded before the
 * switch, or a forged id, must never file data into a year/centre the
 * user is not working in (AssertsContextScope, 27/08/2026).
 *
 * The user here is a global (centers.access-all) user on purpose: centre
 * REACH is not the question — CenterAccessService already covers it — the
 * active CONTEXT is.
 */
final class ContextScopeWriteGuardTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $anneeActive;

    private AnneeScolaire $anneeAutre;

    private Etablissement $centreActif;

    private Etablissement $centreAutre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->anneeActive = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->anneeAutre = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => false, 'inscription_ouverte' => false,
        ]);
        $this->centreActif = Etablissement::factory()->create();
        $this->centreAutre = Etablissement::factory()->create();
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centreActif->id]);

        return $user->fresh();
    }

    /** Active context = $anneeActive + $centreActif (the top-bar switcher). */
    private function activateContext(): void
    {
        session([
            'context.annee_scolaire_id' => $this->anneeActive->id,
            'context.etablissement_id' => $this->centreActif->id,
        ]);
    }

    private function group(AnneeScolaire $annee, Etablissement $centre): Group
    {
        return Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $centre->id,
            'annee_scolaire_id' => $annee->id,
        ]);
    }

    /** @return array{0: Student, 1: Inscription, 2: InscriptionFee} */
    private function enrolled(Group $group): array
    {
        $student = Student::factory()->create(['etablissement_id' => $group->etablissement_id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $group->etablissement_id, 'annee_scolaire_id' => $group->annee_scolaire_id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => 1000,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Juillet',
            'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2025-10-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        return [$student, $inscription, $fee];
    }

    // --- Inscriptions ------------------------------------------------------

    public function test_enrolling_into_a_group_of_another_year_is_refused(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $this->activateContext();
        $student = Student::factory()->create(['etablissement_id' => $this->centreActif->id]);
        $group = $this->group($this->anneeAutre, $this->centreActif);

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2026-09-15',
            'fee_lines' => [['frais_id' => null, 'nom' => 'Frais', 'montant_initial' => '300']],
        ])->assertSessionHasErrors('group_id');

        $this->assertSame(0, Inscription::count());
    }

    public function test_enrolling_into_a_group_of_another_centre_is_refused(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $this->activateContext();
        $student = Student::factory()->create(['etablissement_id' => $this->centreAutre->id]);
        $group = $this->group($this->anneeActive, $this->centreAutre);

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2026-09-15',
            'fee_lines' => [['frais_id' => null, 'nom' => 'Frais', 'montant_initial' => '300']],
        ])->assertSessionHasErrors('group_id');

        $this->assertSame(0, Inscription::count());
    }

    public function test_enrolling_into_a_group_of_the_active_context_still_works(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.create'));
        $this->activateContext();
        $student = Student::factory()->create(['etablissement_id' => $this->centreActif->id]);
        $group = $this->group($this->anneeActive, $this->centreActif);

        $this->post(route('backoffice.inscriptions.store'), [
            'inscription_mode' => 'existing',
            'student_id' => $student->id,
            'group_id' => $group->id,
            'date_inscription' => '2026-09-15',
            'fee_lines' => [['frais_id' => null, 'nom' => 'Frais', 'montant_initial' => '300']],
        ])->assertSessionHasNoErrors()->assertRedirect(route('backoffice.inscriptions.index'));

        $inscription = Inscription::sole();
        $this->assertSame($this->anneeActive->id, $inscription->annee_scolaire_id);
        $this->assertSame($this->centreActif->id, $inscription->etablissement_id);
    }

    /**
     * « Changement de groupe » is the ONE deliberate exception to the année
     * half of the guard (02/09/2026, CLAUDE.md §11 « Deliberate exceptions »):
     * a student whose course is interrupted must be movable into NEXT year's
     * group. The successor row inherits the TARGET group's année, so nothing
     * is filed into the active year by accident — it simply becomes visible
     * only once the top-bar year switcher follows.
     *
     * The CENTRE half stays enforced — see the next test.
     */
    public function test_changing_group_towards_another_year_is_allowed(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.change-group'));
        $this->activateContext();
        [, $inscription] = $this->enrolled($this->group($this->anneeActive, $this->centreActif));
        $target = $this->group($this->anneeAutre, $this->centreActif);

        $this->post(route('backoffice.inscriptions.change-group', $inscription), [
            'new_group_id' => $target->id,
            'date_fin' => '2026-10-01',
            'date_debut' => '2026-10-02',
        ])->assertSessionHasNoErrors();

        $this->assertSame(Inscription::STATUT_CHANGEMENT, $inscription->fresh()->statut);

        $successor = Inscription::where('group_id', $target->id)->sole();
        $this->assertSame($this->anneeAutre->id, $successor->annee_scolaire_id);
    }

    public function test_changing_group_towards_another_centre_is_still_refused(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.change-group'));
        $this->activateContext();
        [, $inscription] = $this->enrolled($this->group($this->anneeActive, $this->centreActif));
        $target = $this->group($this->anneeActive, $this->centreAutre);

        $this->post(route('backoffice.inscriptions.change-group', $inscription), [
            'new_group_id' => $target->id,
            'date_fin' => '2026-10-01',
            'date_debut' => '2026-10-02',
        ])->assertSessionHasErrors('new_group_id');

        $this->assertSame(Inscription::STATUT_ACTIVE, $inscription->fresh()->statut);
    }

    public function test_editing_an_inscription_of_another_year_is_refused(): void
    {
        $this->actingAs($this->userWith('registrations.view', 'registrations.update'));
        $this->activateContext();
        [$student, $inscription] = $this->enrolled($this->group($this->anneeAutre, $this->centreActif));

        $this->put(route('backoffice.inscriptions.update', $inscription), [
            'student_id' => $student->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-09-15',
            'note' => 'tampered',
        ])->assertSessionHasErrors('student_id');

        $this->assertNull($inscription->fresh()->note);
    }

    // --- Encaissements -----------------------------------------------------

    public function test_paying_an_inscription_of_another_year_is_refused(): void
    {
        $this->actingAs($this->userWith('payments.view', 'payments.create'));
        $this->activateContext();
        [$student, $inscription, $fee] = $this->enrolled($this->group($this->anneeAutre, $this->centreActif));

        $this->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'date_paiement' => '2026-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '500', 'methode' => 'Espèces', 'date_paiement' => '2026-09-20'],
            ],
        ])->assertSessionHasErrors('inscription_id');

        $this->assertSame(0, Encaissement::count());
    }

    public function test_recording_an_advance_for_a_student_of_another_centre_is_refused(): void
    {
        $this->actingAs($this->userWith('payments.view', 'payments.create'));
        $this->activateContext();
        $student = Student::factory()->create(['etablissement_id' => $this->centreAutre->id]);

        $this->post(route('backoffice.avances.store'), [
            'student_id' => $student->id,
            'montant' => '400',
            'methode' => 'Espèces',
            'date_paiement' => '2026-09-21',
        ])->assertSessionHasErrors('student_id');

        $this->assertSame(0, Encaissement::count());
    }

    // --- Stock -------------------------------------------------------------

    public function test_a_stock_movement_on_an_article_of_another_centre_is_refused(): void
    {
        $this->actingAs($this->userWith('stock.view', 'stock.move'));
        $this->activateContext();
        $type = StockType::create(['nom' => StockType::SYSTEM_LIVRE, 'is_system' => true, 'statut' => StockType::STATUT_ACTIF]);
        $article = StockArticle::create([
            'reference' => 'ART-000001', 'nom' => 'Manuel A1', 'stock_type_id' => $type->id,
            'quantite' => 0, 'statut' => StockArticle::STATUT_ACTIF, 'etablissement_id' => $this->centreAutre->id,
        ]);

        $this->post(route('backoffice.stock-mouvements.store'), [
            'stock_article_id' => $article->id,
            'type' => StockMouvement::TYPE_ENTREE,
            'quantite' => 20,
        ])->assertSessionHasErrors('stock_article_id');

        $this->assertSame(0, $article->fresh()->quantite);
        $this->assertSame(0, StockMouvement::count());
    }

    // --- Séances / Groupes -------------------------------------------------

    public function test_editing_a_session_of_another_year_is_refused(): void
    {
        $this->actingAs($this->userWith('attendance.view', 'attendance.update'));
        $this->activateContext();
        $group = $this->group($this->anneeAutre, $this->centreActif);
        $seance = Seance::create([
            'group_id' => $group->id, 'date_seance' => '2026-03-02',
            'etablissement_id' => $this->centreActif->id, 'annee_scolaire_id' => $this->anneeAutre->id,
            'statut' => Seance::STATUT_PREVUE,
        ]);

        $this->put(route('backoffice.seances.update', $seance), [
            'date_seance' => '2026-03-09',
            'statut' => Seance::STATUT_ANNULEE,
        ])->assertSessionHasErrors('statut');

        $this->assertSame(Seance::STATUT_PREVUE, $seance->fresh()->statut);
    }

    public function test_archiving_a_group_of_another_year_is_refused(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.archive'));
        $this->activateContext();
        $group = $this->group($this->anneeAutre, $this->centreActif);

        $this->post(route('backoffice.groups.archive', $group))
            ->assertSessionHasErrors('statut');

        $this->assertSame(Group::STATUT_EN_FORMATION, $group->fresh()->statut);
    }

    /** « Tous les centres » (super-admin) never conflicts on the centre — only the year still binds. */
    public function test_all_centres_context_only_binds_the_year(): void
    {
        $this->actingAs($this->userWith('groups.view', 'groups.archive'));
        session(['context.annee_scolaire_id' => $this->anneeActive->id]);
        session()->forget('context.etablissement_id');
        $this->assertTrue(app(CurrentContext::class)->isAllCenters());

        $ok = $this->group($this->anneeActive, $this->centreAutre);
        $this->post(route('backoffice.groups.archive', $ok))->assertSessionHasNoErrors();
        $this->assertSame(Group::STATUT_FIN_FORMATION, $ok->fresh()->statut);

        $ko = $this->group($this->anneeAutre, $this->centreAutre);
        $this->post(route('backoffice.groups.archive', $ko))->assertSessionHasErrors('statut');
        $this->assertSame(Group::STATUT_EN_FORMATION, $ko->fresh()->statut);
    }
}
