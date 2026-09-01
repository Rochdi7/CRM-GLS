<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Finance\Support\CaisseLedger;
use App\Models\Caisse;
use App\Models\Encaissement;
use App\Services\CaisseProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off reconciliation after the payment-method accounts refactor
 * (docs/caisse-comptes-methode-architecture.md §5).
 *
 * Before it, EVERY payment credited the cashier's physical till, whatever
 * its `methode`. This command re-homes each historical non-cash row (TPE /
 * Chèque / Virement) still sitting in a cash account into its centre's
 * method account:
 *
 *  - the balance moves through CaisseLedger, BOTH legs journaled
 *    (« Reclassement par méthode de paiement ») — never a raw update;
 *  - the row's `caisse_id` is updated (Auditable records before/after);
 *  - nothing is ever deleted.
 *
 * ⚠ ENCAISSEMENTS ONLY. Dépenses and remboursements are NEVER re-homed:
 * they always settle from the employee's physical till whatever their
 * `methode_paiement` label says (CLAUDE.md §11, accounting rule confirmed
 * 24/08/2026). An earlier version of this command moved approved non-cash
 * dépenses into the centre's method account — that contradicted the rule
 * (the till was credited back for cash that had really left it) and was
 * removed on the 24/08/2026 audit.
 *
 * Idempotent: a row already in a method account is skipped, so a second run
 * is a no-op. DRY-RUN BY DEFAULT — pass --apply to execute.
 *
 * ⚠ The target CENTRE of a historical row is stored nowhere. The rule is
 * the centre of the till the row sits in (where the card terminal / cheque
 * was physically handled). When the STUDENT's centre disagrees (typically a
 * legacy import done by another branch's operator) the row is listed as
 * AMBIGUOUS and --apply refuses to run until an explicit --ambiguous=caisse
 * or --ambiguous=student decides for those rows. Rows with no resolvable
 * centre at all are always refused — fix the data first.
 */
final class RecalculerSoldesCaisses extends Command
{
    protected $signature = 'caisse:recalculer-soldes
        {--apply : Execute (default is a dry-run that changes nothing)}
        {--ambiguous= : caisse|student — how to resolve rows whose student centre differs from the till centre}';

    protected $description = 'Re-home historical TPE/Chèque/Virement rows from cash tills into the centres\' method accounts (dry-run by default)';

    private const MOTIF = 'Reclassement par méthode de paiement';

    public function __construct(
        private readonly CaisseLedger $ledger,
        private readonly CaisseProvisioner $provisioner,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $ambiguousRule = $this->option('ambiguous');

        if ($ambiguousRule !== null && ! in_array($ambiguousRule, ['caisse', 'student'], true)) {
            $this->error('--ambiguous must be "caisse" or "student".');

            return self::INVALID;
        }

        $plan = $this->plan($ambiguousRule);

        $this->renderPlan($plan);

        if ($plan['unresolvable'] !== []) {
            $this->error(sprintf('%d row(s) have no resolvable centre — nothing applied. Fix the data first.', count($plan['unresolvable'])));

            return self::FAILURE;
        }

        if ($plan['ambiguous'] !== [] && $ambiguousRule === null) {
            $this->error(sprintf(
                '%d ambiguous row(s) (student centre ≠ till centre) — nothing applied. Re-run with --ambiguous=caisse or --ambiguous=student.',
                count($plan['ambiguous']),
            ));

            return self::FAILURE;
        }

        if (! $apply) {
            $this->info('Dry-run: nothing was changed. Re-run with --apply to execute.');

            return self::SUCCESS;
        }

        $moved = 0;

        foreach ($plan['moves'] as $move) {
            DB::transaction(function () use ($move, &$moved): void {
                $this->applyMove($move);
                $moved++;
            });
        }

        $this->info("Done: {$moved} row(s) re-homed. Balances after:");
        $this->renderBalances();

        return self::SUCCESS;
    }

    /**
     * @return array{moves: list<array<string, mixed>>, ambiguous: list<array<string, mixed>>, unresolvable: list<array<string, mixed>>}
     */
    private function plan(?string $ambiguousRule): array
    {
        $moves = [];
        $ambiguous = [];
        $unresolvable = [];

        // 1. Encaissements (parents only — apply rows follow their avance).
        $encaissements = Encaissement::query()
            ->with(['caisse', 'student'])
            ->whereNull('applied_from_encaissement_id')
            ->where('methode', '!=', Encaissement::METHODE_ESPECES)
            ->whereHas('caisse', fn ($q) => $q->whereIn('type', Caisse::TYPES_ESPECES))
            ->orderBy('id')
            ->get();

        foreach ($encaissements as $row) {
            $tillCentre = $row->caisse?->etablissement_id;
            $studentCentre = $row->student?->etablissement_id;

            $entry = [
                'table' => 'encaissements',
                'model' => $row,
                'reference' => $row->reference,
                'methode' => $row->methode,
                'montant' => (float) $row->montant,
                'from' => $row->caisse,
                'tillCentre' => $tillCentre,
                'studentCentre' => $studentCentre,
                'legacy' => $row->legacy_source !== null,
                'moveMoney' => true,
            ];

            if ($tillCentre === null && $studentCentre === null) {
                $unresolvable[] = $entry;

                continue;
            }

            $isAmbiguous = $tillCentre !== null && $studentCentre !== null && (int) $tillCentre !== (int) $studentCentre;

            if ($isAmbiguous) {
                $ambiguous[] = $entry;

                if ($ambiguousRule === null) {
                    continue;
                }

                $entry['centre'] = (int) ($ambiguousRule === 'student' ? $studentCentre : $tillCentre);
            } else {
                $entry['centre'] = (int) ($tillCentre ?? $studentCentre);
            }

            $moves[] = $entry;
        }

        return ['moves' => $moves, 'ambiguous' => $ambiguous, 'unresolvable' => $unresolvable];
    }

    /** @param array<string, mixed> $move */
    private function applyMove(array $move): void
    {
        $target = $this->provisioner->compteMethodeFor((int) $move['centre'], (string) $move['methode']);
        /** @var Caisse $from */
        $from = $move['from'];
        $model = $move['model'];
        $extra = [
            'reclassement' => true,
            'methode' => $move['methode'],
            'caisse_origine' => $from->nom,
            'caisse_cible' => $target->nom,
            // Centre dimension: the re-homed payment's own centre (both legs
            // reverse/apply the same underlying operation).
            'etablissement_id' => $model->etablissement_id,
        ];

        if ($move['table'] !== 'encaissements') {
            // Defensive: only payments are ever re-homed (see class docblock).
            throw new \LogicException("Seuls les encaissements peuvent être reclassés (reçu : {$move['table']}).");
        }

        if ($move['moveMoney']) {
            $this->ledger->debit($from->id, $move['montant'], self::MOTIF." — {$move['reference']}", $model, $extra);
            $this->ledger->credit($target->id, $move['montant'], self::MOTIF." — {$move['reference']}", $model, $extra);
        }

        $model->update(['caisse_id' => $target->id]);

        // Apply rows of this avance never moved money; they just follow.
        Encaissement::query()
            ->where('applied_from_encaissement_id', $model->id)
            ->get()
            ->each(fn (Encaissement $apply) => $apply->update(['caisse_id' => $target->id]));
    }

    /** @param array{moves: list<array<string, mixed>>, ambiguous: list<array<string, mixed>>, unresolvable: list<array<string, mixed>>} $plan */
    private function renderPlan(array $plan): void
    {
        $this->line('Balances before:');
        $this->renderBalances();

        $this->newLine();
        $this->line(sprintf('Rows to re-home: %d', count($plan['moves'])));

        if ($plan['moves'] !== []) {
            $this->table(
                ['Table', 'Référence', 'Méthode', 'Montant', 'Depuis', 'Centre cible', 'Argent bouge ?', 'Legacy'],
                array_map(fn (array $m) => [
                    $m['table'], $m['reference'], $m['methode'], number_format($m['montant'], 2, '.', ''),
                    $m['from']?->nom, $this->centreName($m['centre']), $m['moveMoney'] ? 'oui' : 'non', $m['legacy'] ? 'oui' : '',
                ], $plan['moves']),
            );
        }

        if ($plan['ambiguous'] !== []) {
            $this->newLine();
            $this->warn(sprintf('Ambiguous rows (student centre ≠ till centre): %d', count($plan['ambiguous'])));
            $this->table(
                ['Référence', 'Méthode', 'Montant', 'Centre de la caisse', 'Centre de l\'étudiant', 'Legacy'],
                array_map(fn (array $m) => [
                    $m['reference'], $m['methode'], number_format($m['montant'], 2, '.', ''),
                    $this->centreName($m['tillCentre']), $this->centreName($m['studentCentre']), $m['legacy'] ? 'oui' : '',
                ], $plan['ambiguous']),
            );
        }

        if ($plan['unresolvable'] !== []) {
            $this->newLine();
            $this->error(sprintf('Rows with NO resolvable centre: %d', count($plan['unresolvable'])));
            $this->table(
                ['Table', 'Référence', 'Méthode', 'Montant', 'Depuis'],
                array_map(fn (array $m) => [$m['table'], $m['reference'], $m['methode'], number_format($m['montant'], 2, '.', ''), $m['from']?->nom], $plan['unresolvable']),
            );
        }
    }

    private function renderBalances(): void
    {
        $this->table(
            ['Compte', 'Type', 'Centre', 'Solde'],
            Caisse::query()->with('etablissement')->orderBy('etablissement_id')->orderBy('type')->orderBy('nom')->get()
                ->filter(fn (Caisse $c) => (float) $c->solde !== 0.0 || $c->isCompteMethode())
                ->map(fn (Caisse $c) => [$c->nom, $c->type, $c->etablissement?->nom_centre ?? '—', number_format((float) $c->solde, 2, '.', '')])
                ->values()
                ->all(),
        );
    }

    private function centreName(?int $id): string
    {
        if ($id === null) {
            return '—';
        }

        return (string) (DB::table('etablissements')->where('id', $id)->value('nom_centre') ?? "#{$id}");
    }
}
