<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\AnneeScolaire;
use App\Models\Employee;
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
 * « Gestion des recouvrements » chases only LIVE dossiers (04/09/2026).
 *
 * An inscription Annulée / Changement / Expirée / Archivée is closed: its
 * fees are no longer due, so an overdue line on one is not a debt anybody
 * owes. Listing them sent the front office after money it could never
 * collect and inflated the header total — on the real database 32 M DH of
 * « retards » were reported, of which 30 M DH (96 %) hung off closed
 * inscriptions.
 *
 * The rule must hold on BOTH query paths of GetRetardsList — the paginated
 * rows plus their total AND the per-bucket badge counts of the « Retards
 * selon la durée » tab — or the badges promise rows the table refuses to
 * show.
 */
final class RecouvrementInscriptionActiveTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private AnneeScolaire $annee;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
    }

    public function test_only_fees_of_an_active_inscription_are_listed_as_overdue(): void
    {
        $user = $this->collectionsUser();

        $active = $this->overdueFee(Inscription::STATUT_ACTIVE, 1000);
        $closed = [
            'Annulée' => $this->overdueFee(Inscription::STATUT_ANNULEE, 2000),
            'Changement' => $this->overdueFee(Inscription::STATUT_CHANGEMENT, 3000),
            'Archivée' => $this->overdueFee(Inscription::STATUT_ARCHIVEE, 4000),
            'Expirée' => $this->overdueFee(Inscription::STATUT_EXPIREE, 5000),
        ];

        $ids = collect($this->recouvrementProps($user)['retards']['data'])->pluck('id');

        $this->assertTrue(
            $ids->contains($active->id),
            'An overdue fee of an Active inscription must be listed.',
        );

        foreach ($closed as $statut => $fee) {
            $this->assertFalse(
                $ids->contains($fee->id),
                "A fee of a {$statut} inscription is not a debt and must not appear in the recouvrement list.",
            );
        }
    }

    /**
     * The header total is the exposure the front office is asked to recover.
     * It must count the live dossier alone, never the 5 000 DH of closed
     * ones sitting beside it.
     */
    public function test_the_header_total_excludes_closed_inscriptions(): void
    {
        $user = $this->collectionsUser();

        $this->overdueFee(Inscription::STATUT_ACTIVE, 1000);
        $this->overdueFee(Inscription::STATUT_ANNULEE, 2000);
        $this->overdueFee(Inscription::STATUT_CHANGEMENT, 3000);

        $this->assertSame('1000.00', $this->recouvrementProps($user)['montantTotal']);
    }

    /**
     * The durée tab's badges come from bucketCounts(), a SECOND query
     * carrying its own copy of the scoping. A filter added to one path only
     * leaves the badge and the table disagreeing.
     */
    public function test_the_duration_bucket_counts_exclude_closed_inscriptions(): void
    {
        $user = $this->collectionsUser();

        // All three are 10 days late, so they share the 7j bucket.
        $this->overdueFee(Inscription::STATUT_ACTIVE, 1000, 10);
        $this->overdueFee(Inscription::STATUT_ANNULEE, 2000, 10);
        $this->overdueFee(Inscription::STATUT_CHANGEMENT, 3000, 10);

        $counts = $this->recouvrementProps($user)['bucketCounts'];

        $this->assertSame(1, $counts['7j']);
        $this->assertSame(1, array_sum($counts), 'Only the Active fee may be counted, in exactly one bucket.');
    }

    /**
     * The page is a live mirror that stores nothing of its own, so
     * cancelling an inscription must stop its fees being chased.
     */
    public function test_cancelling_an_inscription_removes_its_fees_from_the_list(): void
    {
        $user = $this->collectionsUser();
        $fee = $this->overdueFee(Inscription::STATUT_ACTIVE, 1000);

        $before = collect($this->recouvrementProps($user)['retards']['data'])->pluck('id');
        $this->assertTrue($before->contains($fee->id));

        $fee->inscription->update(['statut' => Inscription::STATUT_ANNULEE]);

        $after = collect($this->recouvrementProps($user)['retards']['data'])->pluck('id');
        $this->assertFalse($after->contains($fee->id));
    }

    /**
     * @return array<string, mixed>
     */
    private function recouvrementProps(User $user): array
    {
        // A bare visit redirects once to the canonical URL carrying the
        // default date window, so pass an explicit wide window instead.
        $response = $this->actingAs($user)->get(route('backoffice.recouvrement.index', [
            'dateFrom' => now()->subYears(2)->toDateString(),
            'dateTo' => now()->toDateString(),
        ]));

        $response->assertOk();

        return $response->viewData('page')['props'];
    }

    private function collectionsUser(): User
    {
        $user = User::factory()->create();

        foreach (['collections.view', 'centers.access-all'] as $permission) {
            $user->givePermissionTo($permission);
        }

        Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centre->id,
        ]);

        session([
            'context.annee_scolaire_id' => $this->annee->id,
            'context.etablissement_id' => $this->centre->id,
        ]);

        return $user->fresh();
    }

    /** An unpaid fee whose échéance is $daysLate days in the past. */
    private function overdueFee(string $statut, int $montant, int $daysLate = 10): InscriptionFee
    {
        $student = Student::factory()->create(['etablissement_id' => $this->centre->id]);

        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => $statut,
            'date_inscription' => now()->subMonths(2)->toDateString(),
            'montant_total' => $montant,
        ]);

        return InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'nom' => 'Frais de scolarité',
            'montant_initial' => $montant,
            'montant' => $montant,
            'date_echeance' => now()->subDays($daysLate)->toDateString(),
            'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
    }
}
