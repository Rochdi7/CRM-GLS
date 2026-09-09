<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Console\Commands\AnnulerDepenseDoublon;
use App\Domain\Expenses\Actions\AnnulerDepense;
use App\Domain\Expenses\Actions\ApprouverDepense;
use App\Domain\Expenses\Queries\GetDepenseDetails;
use App\Domain\Expenses\Queries\GetDepensesList;
use App\Domain\Finance\Support\CaisseLedger;
use App\Models\Activity;
use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Replays the 09/09/2026 production state (El Mehdi Bakhach's till at
 * -7 250,00 DH after DEP-014 — a copy of DEP-012 — was approved) and proves
 * the correction: ONE compensating credit of 7 650 DH journaled through
 * CaisseLedger, DEP-014 kept and marked « Annulée », balance back to
 * +400,00 DH, and a second run writing nothing.
 */
final class AnnulerDepenseDoublonTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    private Employee $mehdi;

    private Caisse $till;

    private Employee $rafik;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);

        $this->mehdi = Employee::factory()->create(['etablissement_id' => $this->rabat->id]);
        $this->till = $this->mehdi->till()->firstOrFail();

        $rafikUser = User::factory()->create();
        $rafikUser->givePermissionTo(['expenses.view', 'expenses.approve', 'centers.access-all']);
        $this->rafik = Employee::factory()->create(['user_id' => $rafikUser->id, 'etablissement_id' => $this->rabat->id]);
    }

    /**
     * The production ledger, line for line: five transfers in (15 250), then
     * five dépenses debited (22 500) — written through CaisseLedger directly,
     * since ApprouverDepense now refuses the fifth one.
     *
     * @return array{Depense, Depense} [DEP-012, DEP-014]
     */
    private function replayProduction(): array
    {
        $ledger = app(CaisseLedger::class);
        foreach (['400.00', '1000.00', '7650.00', '100.00', '6100.00'] as $i => $in) {
            $ledger->credit($this->till->id, (float) $in, 'Transfert TRF-'.$i, null, ['etablissement_id' => $this->rabat->id]);
        }
        $this->assertSame('15250.00', (string) $this->till->fresh()->solde);

        $rows = [
            ['DEP-012', '7650.00', '2026-08-29', '*', '2026-09-04 18:07:03'],
            ['DEP-013', '1000.00', '2026-09-04', 'Avance', '2026-09-04 18:09:03'],
            ['DEP-014', '7650.00', '2026-08-29', '*', '2026-09-04 18:18:06'],
            ['DEP-024', '100.00', '2026-09-08', 'abdellah concierge', '2026-09-08 20:32:13'],
            ['DEP-025', '6100.00', '2026-09-08', '14', '2026-09-08 20:32:57'],
        ];

        $byRef = [];
        foreach ($rows as [$ref, $montant, $date, $description, $createdAt]) {
            $d = Depense::create([
                'reference' => $ref,
                'type_depense_id' => $this->type()->id,
                'caisse_id' => $this->till->id,
                'agent_id' => $this->mehdi->id,
                'montant' => $montant,
                'methode_paiement' => 'Espèces',
                'date_depense' => $date,
                'statut' => Depense::STATUT_APPROUVEE,
                'approved_by' => $this->rafik->id,
                'approved_at' => '2026-09-08 22:50:41',
                'description' => $description,
            ]);
            $d->created_at = $createdAt;
            $d->save();

            $ledger->debit($this->till->id, (float) $montant, "Dépense {$ref}", $d, ['etablissement_id' => $this->rabat->id]);
            $byRef[$ref] = $d;
        }

        $this->assertSame('-7250.00', (string) $this->till->fresh()->solde);

        return [$byRef['DEP-012'], $byRef['DEP-014']];
    }

    private function type(): TypeDepense
    {
        return TypeDepense::firstOrCreate(
            ['nom' => 'Fournitures'],
            ['is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF],
        );
    }

    private function corrections(Depense $depense, string $correction): int
    {
        return Activity::query()
            ->where('log_name', 'caisse')
            ->where('event', 'solde_movement')
            ->where('properties->origine_type', Depense::class)
            ->whereRaw("(properties->'origine_id')::text = ?", [(string) $depense->id])
            ->where('properties->sens', 'Entrée')
            ->where('properties->correction', $correction)
            ->count();
    }

    // ── Test 7 · the correction, and its idempotency ────────────────────

    public function test_the_command_reverses_dep_014_once_and_only_once(): void
    {
        [$dep012, $dep014] = $this->replayProduction();
        $correction = AnnulerDepenseDoublon::referenceCorrection('DEP-014');
        $this->assertSame('CORRECTION-DEP-014-DUPLICATE', $correction);

        // Dry run: reports, writes nothing.
        $this->artisan('depenses:annuler-doublon', ['reference' => 'DEP-014', 'conservee' => 'DEP-012'])
            ->expectsOutputToContain('SIMULATION')
            ->assertExitCode(0);
        $this->assertSame('-7250.00', (string) $this->till->fresh()->solde);
        $this->assertSame(Depense::STATUT_APPROUVEE, $dep014->fresh()->statut);

        // First application: -7 250 + 7 650 = +400.
        $this->artisan('depenses:annuler-doublon', ['reference' => 'DEP-014', 'conservee' => 'DEP-012', '--apply' => true])
            ->expectsOutputToContain('Correction appliquée')
            ->assertExitCode(0);

        $this->assertSame('400.00', (string) $this->till->fresh()->solde);
        $this->assertSame(1, $this->corrections($dep014, $correction));

        $dep014 = $dep014->fresh();
        $this->assertSame(Depense::STATUT_ANNULEE, $dep014->statut);
        $this->assertStringContainsString(Depense::MARQUEUR_ANNULE, (string) $dep014->note);
        $this->assertStringContainsString($correction, (string) $dep014->note);
        $this->assertStringContainsString('doublon de DEP-012', (string) $dep014->note);
        $this->assertStringContainsString('7 650,00 DH', (string) $dep014->note);

        // DEP-012 — the kept twin — is untouched.
        $this->assertSame(Depense::STATUT_APPROUVEE, $dep012->fresh()->statut);
        $this->assertNull($dep012->fresh()->note);
        $this->assertSame(0, $this->corrections($dep012, $correction));

        // The ledger explains itself: the compensating entry names the
        // dépense, the correction, the reason, the centre and the amount.
        $entry = Activity::query()
            ->where('properties->correction', $correction)
            ->firstOrFail();
        $this->assertSame('Entrée', $entry->properties['sens']);
        $this->assertSame('7650.00', $entry->properties['montant']);
        $this->assertSame('-7250.00', $entry->properties['solde_avant']);
        $this->assertSame('400.00', $entry->properties['solde_apres']);
        $this->assertSame('DEP-014', $entry->properties['origine_reference']);
        $this->assertSame($this->rabat->id, (int) $entry->properties['etablissement_id']);
        $this->assertStringContainsString('doublon de DEP-012', $entry->properties['motif_correction']);
        $this->assertSame($this->till->id, (int) $entry->subject_id);

        // Second application: nothing written, exit 0, balance unchanged.
        $this->artisan('depenses:annuler-doublon', ['reference' => 'DEP-014', 'conservee' => 'DEP-012', '--apply' => true])
            ->expectsOutputToContain('déjà annulée')
            ->assertExitCode(0);

        $this->assertSame('400.00', (string) $this->till->fresh()->solde);
        $this->assertSame(1, $this->corrections($dep014, $correction));
        $this->assertSame(1, substr_count((string) $dep014->fresh()->note, Depense::MARQUEUR_ANNULE));

        // The action itself refuses too, even if called directly.
        try {
            app(AnnulerDepense::class)->handle($dep014, $correction, 'encore');
            $this->fail('A cancelled expense cannot be cancelled again.');
        } catch (ValidationException $e) {
            $this->assertSame('Cette dépense a déjà été annulée.', $e->errors()['statut'][0]);
        }
        $this->assertSame('400.00', (string) $this->till->fresh()->solde);
    }

    /**
     * The ledger stores `origine_id` as a jsonb NUMBER. Eloquent's
     * `where('properties->origine_id', '14')` compares jsonb to jsonb and
     * therefore matches NOTHING against such a row — verified by query on
     * `gls_crm` (0 rows as string, 1 as number). Writing the row through raw
     * SQL here reproduces the production shape exactly, independently of how
     * the model casts on insert, so a regression back to the Eloquent
     * predicate fails here instead of silently double-crediting a till in
     * production.
     */
    public function test_the_correction_is_found_when_the_ledger_stores_origine_id_as_a_number(): void
    {
        [, $dep014] = $this->replayProduction();
        $correction = AnnulerDepenseDoublon::referenceCorrection('DEP-014');

        DB::table('activity_log')->insert([
            'log_name' => 'caisse',
            'description' => 'Entrée en caisse : Annulation de la dépense DEP-014',
            'subject_type' => Caisse::class,
            'subject_id' => $this->till->id,
            'event' => 'solde_movement',
            'properties' => json_encode([
                'caisse' => $this->till->nom,
                'sens' => 'Entrée',
                'montant' => '7650.00',
                'origine_type' => Depense::class,
                // A real NUMBER, exactly as PostgreSQL holds it in production.
                'origine_id' => $dep014->id,
                'origine_reference' => 'DEP-014',
                'correction' => $correction,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame('number', DB::selectOne(
            "SELECT jsonb_typeof(properties->'origine_id') AS t FROM activity_log WHERE properties->>'correction' = ?",
            [$correction],
        )->t);

        // Found — so the correction is recognised as already written…
        $this->assertTrue(AnnulerDepense::correctionExiste($dep014, $correction));

        // …and the command refuses to credit the till a second time.
        $this->artisan('depenses:annuler-doublon', ['reference' => 'DEP-014', 'conservee' => 'DEP-012', '--apply' => true])
            ->assertExitCode(0);
        $this->assertSame('-7250.00', (string) $this->till->fresh()->solde);
    }

    /**
     * « Argent sorti » has ONE definition across the app: statut Approuvée.
     * The dashboard card and the yearly recap read `depenses` directly and
     * used to sum every row whatever its statut — so a pending, refused or
     * (since 09/09/2026) cancelled dépense inflated them, contradicting the
     * Dépenses list on the very same money.
     */
    public function test_the_dashboard_and_yearly_recap_count_approved_money_only(): void
    {
        [, $dep014] = $this->replayProduction();
        $annee = \App\Models\AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-08-01', 'date_fin' => '2027-07-31',
        ]);
        app(CurrentContext::class)->setEtablissement($this->rabat->id);
        app(CurrentContext::class)->setAnneeScolaire($annee->id);

        $recapAvant = app(\App\Domain\Reports\Actions\GetAnnualFraisSummary::class)();
        $totalAvant = array_sum(array_map('floatval', $recapAvant['depenses']));
        $this->assertSame(22500.0, round($totalAvant, 2));

        app(AnnulerDepense::class)->handle($dep014, 'CORRECTION-DEP-014-DUPLICATE', 'doublon', $this->rafik);

        // The cancelled 7 650 leaves the recap, exactly as it leaves the list.
        $recapApres = app(\App\Domain\Reports\Actions\GetAnnualFraisSummary::class)();
        $this->assertSame(14850.0, round(array_sum(array_map('floatval', $recapApres['depenses'])), 2));
    }

    // ── The evidence gate ───────────────────────────────────────────────

    public function test_the_command_refuses_when_the_two_rows_are_not_identical(): void
    {
        [$dep012, $dep014] = $this->replayProduction();

        // A single differing fact (description) and the whole correction stops.
        $dep014->update(['description' => 'Loyer septembre']);

        $this->artisan('depenses:annuler-doublon', ['reference' => 'DEP-014', 'conservee' => 'DEP-012', '--apply' => true])
            ->expectsOutputToContain('Les preuves ne concordent pas')
            ->assertExitCode(1);

        $this->assertSame('-7250.00', (string) $this->till->fresh()->solde);
        $this->assertSame(Depense::STATUT_APPROUVEE, $dep014->fresh()->statut);
        $this->assertSame(0, $this->corrections($dep014, AnnulerDepenseDoublon::referenceCorrection('DEP-014')));

        // Naming a different amount as the "twin" is refused the same way.
        $this->artisan('depenses:annuler-doublon', ['reference' => 'DEP-014', 'conservee' => 'DEP-025', '--apply' => true])
            ->assertExitCode(1);
        $this->assertSame('-7250.00', (string) $this->till->fresh()->solde);
    }

    public function test_a_pending_expense_cannot_be_cancelled_it_never_debited_anything(): void
    {
        $pending = Depense::create([
            'reference' => 'DEP-099',
            'type_depense_id' => $this->type()->id,
            'caisse_id' => $this->till->id,
            'agent_id' => $this->mehdi->id,
            'montant' => '100.00',
            'methode_paiement' => 'Espèces',
            'date_depense' => '2026-09-01',
            'statut' => Depense::STATUT_EN_ATTENTE,
            'description' => 'x',
        ]);

        $this->expectException(ValidationException::class);
        app(AnnulerDepense::class)->handle($pending, 'CORRECTION-DEP-099-DUPLICATE', 'test');
    }

    // ── What the rest of the app sees afterwards ────────────────────────

    public function test_a_cancelled_expense_leaves_the_totals_and_is_frozen_but_stays_visible(): void
    {
        [, $dep014] = $this->replayProduction();
        app(CurrentContext::class)->setEtablissement($this->rabat->id);
        $viewer = $this->rafik->user;

        $before = app(GetDepensesList::class)($viewer, '', '', '', '', '', 25, GetDepensesList::SCOPE_TOUS, '', false);
        $this->assertSame('22500.00', $before['montantTotal']);

        app(AnnulerDepense::class)->handle($dep014, 'CORRECTION-DEP-014-DUPLICATE', 'dépense saisie en double (doublon de DEP-012)', $this->rafik);

        // Money that actually LEFT the tills: 22 500 - 7 650.
        $after = app(GetDepensesList::class)($viewer, '', '', '', '', '', 25, GetDepensesList::SCOPE_TOUS, '', false);
        $this->assertSame('14850.00', $after['montantTotal']);

        // Still listed (never deleted), flagged for the UI.
        $row = collect($after['data']->items())->firstWhere('reference', 'DEP-014');
        $this->assertNotNull($row);
        $this->assertSame(Depense::STATUT_ANNULEE, $row['statut']);
        $this->assertTrue($row['isAnnulee']);

        // The detail page can show the correction, read from the journal.
        $details = app(GetDepenseDetails::class)($dep014->fresh());
        $this->assertTrue($details['isAnnulee']);
        $this->assertSame('CORRECTION-DEP-014-DUPLICATE', $details['annulation']['correction']);
        $this->assertSame('7650.00', $details['annulation']['montant']);
        $this->assertSame($this->rafik->nomComplet(), $details['annulation']['par']);

        // Frozen: no edit, no second decision.
        $this->assertFalse($viewer->can('update', $dep014->fresh()));
        $this->assertFalse($viewer->can('approve', $dep014->fresh()));
        try {
            app(ApprouverDepense::class)->handle($dep014->fresh(), $this->rafik);
            $this->fail('A cancelled expense is decided.');
        } catch (ValidationException) {
            $this->assertSame('400.00', (string) $this->till->fresh()->solde);
        }
    }
}
