<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Groups;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Détails paiement" — the Groups list kebab menu's students × frais matrix
 * (GetGroupPaymentMatrix), the in-app successor to the legacy CRM's
 * Statistiques_Prof Excel export.
 *
 * The four cell states and the three row colours ARE the feature — a cashier
 * reads recouvrement off them — so each one is asserted explicitly here.
 */
final class GroupPaymentMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

    private Group $group;

    private Caisse $caisse;

    private Employee $agent;

    /** @var array<string, Frais> */
    private array $frais = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->centre = Etablissement::factory()->create();
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->caisse = $this->agent->till()->firstOrFail();

        // Deliberately attached out of chronological order: the query must
        // sort the COLUMNS by due date, earliest first (Inscription → Mars →
        // Avril), not by attach order or by id.
        $this->frais = [
            'avril' => Frais::create(['nom' => 'Frais d\'Avril', 'montant_defaut' => 1300]),
            'inscription' => Frais::create(['nom' => 'Frais d\'inscription', 'montant_defaut' => 300]),
            'mars' => Frais::create(['nom' => 'Frais de Mars', 'montant_defaut' => 1300]),
        ];

        $this->group->frais()->sync([
            $this->frais['avril']->id => ['montant' => 1300, 'date_echeance' => '2026-04-01'],
            $this->frais['inscription']->id => ['montant' => 300, 'date_echeance' => '2026-01-01'],
            $this->frais['mars']->id => ['montant' => 1300, 'date_echeance' => '2026-03-01'],
        ]);
    }

    private function userWithPayments(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function enrol(string $prenom, string $nom, string $statut = Inscription::STATUT_ACTIVE): Inscription
    {
        $student = Student::factory()->create([
            'etablissement_id' => $this->centre->id,
            'prenom' => $prenom,
            'nom' => $nom,
        ]);

        return Inscription::create([
            'reference' => 'INS-MTX-'.$student->id,
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => $statut,
            'date_inscription' => '2026-01-10',
        ]);
    }

    private function addFee(Inscription $inscription, string $fraisKey, float $montant, float $paye = 0): InscriptionFee
    {
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'frais_id' => $this->frais[$fraisKey]->id,
            'nom' => $this->frais[$fraisKey]->nom,
            'montant_initial' => $montant,
            'montant' => $montant,
            'date_echeance' => '2026-03-01',
            'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        if ($paye > 0) {
            Encaissement::create([
                'reference' => 'ENC-MTX-'.$fee->id,
                'student_id' => $inscription->student_id,
                'inscription_fee_id' => $fee->id,
                'montant' => $paye,
                'methode' => Encaissement::METHODE_ESPECES,
                'date_paiement' => '2026-01-15',
                'caisse_id' => $this->caisse->id,
                'agent_id' => $this->agent->id,
            ]);
        }

        return $fee;
    }

    /** @return array<string, mixed> */
    private function matrix(string $sort = 'nom'): array
    {
        $response = $this->actingAs($this->userWithPayments())
            ->getJson(route('backoffice.groups.payment-matrix', $this->group).'?sort='.$sort)
            ->assertOk();

        return $response->json('matrix');
    }

    public function test_columns_are_the_groups_fees_ordered_by_due_date(): void
    {
        $this->enrol('Chaimae', 'Bammadi');

        $columns = $this->matrix()['columns'];

        $this->assertSame(
            ["Frais d'inscription", 'Frais de Mars', "Frais d'Avril"],
            array_column($columns, 'nom'),
        );
        $this->assertSame(['01/01/2026', '01/03/2026', '01/04/2026'], array_column($columns, 'dateEcheance'));
        $this->assertSame((string) $this->frais['inscription']->id, $columns[0]['key']);
    }

    public function test_each_cell_state_reflects_what_was_paid_on_that_fee(): void
    {
        $inscription = $this->enrol('Chaimae', 'Bammadi');

        // Payé in full → green.
        $this->addFee($inscription, 'inscription', 300, 300);
        // Partiellement payé → orange, with the remainder for the tooltip.
        $this->addFee($inscription, 'mars', 1300, 1000);
        // Affecté mais rien payé → red: still in recouvrement.
        $this->addFee($inscription, 'avril', 1300, 0);

        $cells = $this->matrix()['rows'][0]['cells'];

        $this->assertSame('paye', $cells[(string) $this->frais['inscription']->id]['state']);
        $this->assertSame('300.00', $cells[(string) $this->frais['inscription']->id]['montant']);

        $this->assertSame('partiel', $cells[(string) $this->frais['mars']->id]['state']);
        $this->assertSame('1000.00', $cells[(string) $this->frais['mars']->id]['montant']);
        $this->assertSame('300.00', $cells[(string) $this->frais['mars']->id]['reste']);

        $this->assertSame('impaye', $cells[(string) $this->frais['avril']->id]['state']);
        $this->assertSame('0.00', $cells[(string) $this->frais['avril']->id]['montant']);
        $this->assertSame('1300.00', $cells[(string) $this->frais['avril']->id]['reste']);
    }

    public function test_a_fee_not_on_the_inscription_has_no_cell_at_all(): void
    {
        $inscription = $this->enrol('Chaimae', 'Bammadi');
        $this->addFee($inscription, 'inscription', 300, 300);

        $cells = $this->matrix()['rows'][0]['cells'];

        // Grey/empty in the UI — the fee was never added (or was removed on a
        // group change), so nothing is owed and nothing must be displayed.
        // This is the one case that must NOT read as an unpaid 0 DH debt.
        $this->assertArrayNotHasKey((string) $this->frais['mars']->id, $cells);
        $this->assertArrayNotHasKey((string) $this->frais['avril']->id, $cells);
        $this->assertSame('0.00', $this->matrix()['rows'][0]['reste']);
    }

    public function test_a_masked_fee_reads_as_absent_not_as_a_debt(): void
    {
        $inscription = $this->enrol('Chaimae', 'Bammadi');
        $fee = $this->addFee($inscription, 'mars', 1300, 0);
        $fee->update(['masque_le' => now()]);

        $row = $this->matrix()['rows'][0];

        $this->assertArrayNotHasKey((string) $this->frais['mars']->id, $row['cells']);
        $this->assertSame('0.00', $row['reste']);
    }

    public function test_rows_carry_the_inscription_statut_that_colours_them(): void
    {
        $this->enrol('Aya', 'Active');
        $this->enrol('Sara', 'Bannulee', Inscription::STATUT_ANNULEE);
        $this->enrol('Wijdane', 'Changement', Inscription::STATUT_CHANGEMENT);

        $rows = $this->matrix()['rows'];

        $this->assertCount(3, $rows);
        // Grouped by statut block, NOT by name: Active, then Changement, then
        // Annulée (see GetGroupPaymentMatrix::STATUT_ORDRE).
        $this->assertSame(
            [Inscription::STATUT_ACTIVE, Inscription::STATUT_CHANGEMENT, Inscription::STATUT_ANNULEE],
            array_column($rows, 'statut'),
        );
    }

    /**
     * The status block always wins over the chosen sort, whichever it is: the
     * list reads as every active student, then archived, then cancelled — a
     * cashier scanning for who still owes money never steps over a cancelled
     * inscription to reach the next active one.
     */
    public function test_rows_are_grouped_by_statut_before_the_chosen_sort_is_applied(): void
    {
        // Names chosen so alphabetical order alone would interleave the
        // blocks (Aaziz annulée would come first, Zahiri active last).
        $this->enrol('Sara', 'Aaziz', Inscription::STATUT_ANNULEE);
        $this->enrol('Nada', 'Bakkali', Inscription::STATUT_ARCHIVEE);
        $this->enrol('Younes', 'Zahiri');
        $this->enrol('Amine', 'Mansouri');

        $rows = $this->matrix()['rows'];

        $this->assertSame(
            ['Amine Mansouri', 'Younes Zahiri', 'Nada Bakkali', 'Sara Aaziz'],
            array_column($rows, 'student'),
        );
        // Numbering follows the final visible order.
        $this->assertSame(['#1', '#2', '#3', '#4'], array_column($rows, 'numero'));
    }

    public function test_statut_grouping_also_wins_over_the_date_sort(): void
    {
        // The cancelled student registered FIRST, so a pure date sort would
        // put them at the top.
        $annulee = $this->enrol('Sara', 'Aaziz', Inscription::STATUT_ANNULEE);
        $annulee->update(['date_inscription' => '2026-01-01']);
        $this->enrol('Younes', 'Zahiri')->update(['date_inscription' => '2026-05-01']);
        $this->enrol('Amine', 'Mansouri')->update(['date_inscription' => '2026-06-01']);

        $rows = $this->matrix('date')['rows'];

        $this->assertSame(
            ['Younes Zahiri', 'Amine Mansouri', 'Sara Aaziz'],
            array_column($rows, 'student'),
        );
    }

    public function test_rows_sort_alphabetically_and_are_numbered_after_sorting(): void
    {
        $this->enrol('Younes', 'Zahiri');
        $this->enrol('Amine', 'Alaoui');
        $this->enrol('Mehdi', 'Mansouri');

        $rows = $this->matrix('nom')['rows'];

        $this->assertSame(['Amine Alaoui', 'Mehdi Mansouri', 'Younes Zahiri'], array_column($rows, 'student'));
        $this->assertSame(['#1', '#2', '#3'], array_column($rows, 'numero'));
    }

    public function test_rows_sort_reverse_alphabetically(): void
    {
        $this->enrol('Younes', 'Zahiri');
        $this->enrol('Amine', 'Alaoui');
        $this->enrol('Mehdi', 'Mansouri');

        $rows = $this->matrix('nom_desc')['rows'];

        $this->assertSame(['Younes Zahiri', 'Mehdi Mansouri', 'Amine Alaoui'], array_column($rows, 'student'));
        $this->assertSame(['#1', '#2', '#3'], array_column($rows, 'numero'));
    }

    public function test_totals_sum_what_was_collected_per_column_and_overall(): void
    {
        $first = $this->enrol('Amine', 'Alaoui');
        $this->addFee($first, 'inscription', 300, 300);
        $this->addFee($first, 'mars', 1300, 1000);

        $second = $this->enrol('Younes', 'Zahiri');
        $this->addFee($second, 'inscription', 300, 300);
        $this->addFee($second, 'mars', 1300, 0);

        $matrix = $this->matrix();
        $columns = collect($matrix['columns'])->keyBy('nom');

        $this->assertSame('600.00', $columns["Frais d'inscription"]['total']);
        $this->assertSame('1000.00', $columns['Frais de Mars']['total']);
        $this->assertSame('0.00', $columns["Frais d'Avril"]['total']);
        $this->assertSame('1600.00', $matrix['totals']['general']);
    }

    public function test_a_fee_line_outside_the_groups_catalog_still_counts_in_the_row_total(): void
    {
        $inscription = $this->enrol('Chaimae', 'Bammadi');
        $this->addFee($inscription, 'inscription', 300, 300);

        // Carried over from another group — no column to render it in.
        $hors = Frais::create(['nom' => 'Frais hors barème', 'montant_defaut' => 500]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'frais_id' => $hors->id,
            'nom' => $hors->nom,
            'montant_initial' => 500,
            'montant' => 500,
            'date_echeance' => '2026-05-01',
            'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
        Encaissement::create([
            'reference' => 'ENC-MTX-HORS-'.$fee->id,
            'student_id' => $inscription->student_id,
            'inscription_fee_id' => $fee->id,
            'montant' => 200,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-01-20',
            'caisse_id' => $this->caisse->id,
            'agent_id' => $this->agent->id,
        ]);

        $row = $this->matrix()['rows'][0];

        $this->assertArrayNotHasKey((string) $hors->id, $row['cells']);
        $this->assertSame('500.00', $row['total']);
        $this->assertSame('300.00', $row['reste']);
    }

    public function test_the_endpoint_is_gated_by_payments_view(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'sans-paiements', 'label' => 'Sans paiements', 'guard_name' => 'web']);
        $role->givePermissionTo('groups.view');
        $user->assignRole($role);
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        $this->actingAs($user->fresh())
            ->getJson(route('backoffice.groups.payment-matrix', $this->group))
            ->assertForbidden();
    }

    public function test_the_matrix_does_not_run_one_query_per_fee_line(): void
    {
        foreach (range(1, 6) as $i) {
            $inscription = $this->enrol('Etudiant'.$i, 'Test'.$i);
            $this->addFee($inscription, 'inscription', 300, 300);
            $this->addFee($inscription, 'mars', 1300, 500);
            $this->addFee($inscription, 'avril', 1300, 0);
        }

        $user = $this->userWithPayments();
        $this->actingAs($user);

        \DB::enableQueryLog();
        app(\App\Domain\Groups\Queries\GetGroupPaymentMatrix::class)($this->group->fresh());
        $queries = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        // group->frais + inscriptions + students + fee lines (one withSum
        // pass) — a constant, whatever the 18 fee lines above become.
        $this->assertLessThanOrEqual(6, $queries, 'The payment matrix must not scale its query count with the row count.');
    }
}
