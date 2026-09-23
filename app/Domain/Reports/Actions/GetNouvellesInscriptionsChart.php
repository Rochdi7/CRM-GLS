<?php

declare(strict_types=1);

namespace App\Domain\Reports\Actions;

use App\Models\Inscription;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * « Nouvelles inscriptions » dashboard bar chart (every signed-in user,
 * no permission) — how many NEW registrations were taken,
 * bucketed over a chosen DURATION:
 *
 *  - `jour`  — today only (default), one bar per HOUR of the day. `date_inscription`
 *              carries no time, so the hour is read from `created_at` (when
 *              the row was keyed); the day itself is still `date_inscription`;
 *  - `7j`    — last 7 days, one bar per day;
 *  - `30j`   — last 30 days, one bar per day;
 *  - `12s`   — last 12 weeks, one bar per ISO week (Monday start);
 *  - `12m`   — last 12 months, one bar per month;
 *  - `annee` — the active année scolaire window (top-bar switcher), one bar
 *              per month.
 *
 * The date is `date_inscription` — the day the student signed up. Like the
 * « Résumé des frais annuels » chart, the année only supplies the WINDOW:
 * an inscription is not additionally filtered on `annee_scolaire_id`, the
 * date alone decides its bar (années never overlap, CLAUDE.md §11).
 *
 * ⚠ ONLY NEW inscriptions. Two things create an inscription row that is NOT
 * a new registration, and both are excluded:
 *
 *  1. « Changement de groupe » (ChangerGroupeInscription) closes the old row
 *     as `Changement` and creates a SUCCESSOR row for the same student. The
 *     app links the pair in `inscriptions_historique.new_inscription_id`.
 *  2. The legacy import brought the old CRM's group changes WITHOUT that
 *     link: the predecessor is a `Changement` row and the successor an
 *     ordinary row of the same student and centre whose `date_inscription`
 *     sits on (measured locally: 803 of 804 exact matches) or next to the
 *     predecessor's `date_fin`. A row is therefore a legacy successor when
 *     the same student + centre has an EARLIER `Changement` inscription whose
 *     `date_fin` is within LEGACY_TOLERANCE_DAYS of this `date_inscription`.
 *
 * « Modification du groupe » (ModifierGroupeInscription) rewrites group_id IN
 * PLACE and creates no row, so it can never be counted. Every statut is kept
 * — an inscription later cancelled was still a new registration the day it
 * was taken.
 *
 * Centre-scoped via CurrentContext (NULL on « Tous les centres »). One
 * GROUP BY query whatever the volume.
 */
final class GetNouvellesInscriptionsChart
{
    public const DUREES = ['jour', '7j', '30j', '12s', '12m', 'annee'];

    public const DUREE_DEFAUT = 'jour';

    /** Gap tolerated between a legacy `Changement` row's date_fin and its successor's date_inscription. */
    public const LEGACY_TOLERANCE_DAYS = 31;

    public function __construct(private readonly CurrentContext $context) {}

    /**
     * @return array{duree: string, labels: list<string>, counts: list<int>, total: int, periode: string}
     */
    public function __invoke(string $duree): array
    {
        if (! in_array($duree, self::DUREES, true)) {
            $duree = self::DUREE_DEFAUT;
        }

        [$start, $end, $unit] = $this->window($duree);
        $centreId = $this->context->etablissementId();

        $bucketSql = match ($unit) {
            'hour' => "to_char(i.created_at, 'HH24')",
            'day' => "to_char(i.date_inscription, 'YYYY-MM-DD')",
            'week' => "to_char(date_trunc('week', i.date_inscription), 'YYYY-MM-DD')",
            default => "to_char(i.date_inscription, 'YYYY-MM')",
        };

        $rows = DB::table('inscriptions as i')
            ->whereBetween('i.date_inscription', [$start->toDateString(), $end->toDateString()])
            ->when($centreId, fn (Builder $q) => $q->where('i.etablissement_id', $centreId))
            ->tap(fn (Builder $q) => $this->excludeSuccessors($q))
            ->selectRaw("{$bucketSql} AS bucket, COUNT(*) AS total")
            ->groupByRaw($bucketSql)
            ->pluck('total', 'bucket');

        $labels = [];
        $counts = [];
        if ($unit === 'hour') {
            for ($h = 0; $h < 24; $h++) {
                $labels[] = sprintf('%02dh', $h);
                $counts[] = (int) ($rows[sprintf('%02d', $h)] ?? 0);
            }

            return [
                'duree' => $duree,
                'labels' => $labels,
                'counts' => $counts,
                'total' => array_sum($counts),
                'periode' => $start->format('d/m/Y'),
            ];
        }

        $cursor = match ($unit) {
            'day' => $start->copy(),
            'week' => $start->copy()->startOfWeek(Carbon::MONDAY),
            default => $start->copy()->startOfMonth(),
        };

        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $unit === 'month' ? $cursor->format('Y-m') : $cursor->format('Y-m-d');
            $labels[] = match ($unit) {
                'day' => $cursor->format('d/m'),
                'week' => $cursor->format('d/m'),
                default => $cursor->format('m/Y'),
            };
            $counts[] = (int) ($rows[$key] ?? 0);

            match ($unit) {
                'day' => $cursor->addDay(),
                'week' => $cursor->addWeek(),
                default => $cursor->addMonth(),
            };
        }

        return [
            'duree' => $duree,
            'labels' => $labels,
            'counts' => $counts,
            'total' => array_sum($counts),
            'periode' => $start->format('d/m/Y').' – '.$end->format('d/m/Y'),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: 'hour'|'day'|'week'|'month'}
     */
    private function window(string $duree): array
    {
        $today = now()->startOfDay();

        return match ($duree) {
            'jour' => [$today, $today->copy(), 'hour'],
            '7j' => [$today->copy()->subDays(6), $today, 'day'],
            '30j' => [$today->copy()->subDays(29), $today, 'day'],
            '12s' => [$today->copy()->startOfWeek(Carbon::MONDAY)->subWeeks(11), $today, 'week'],
            '12m' => [$today->copy()->startOfMonth()->subMonths(11), $today, 'month'],
            default => $this->anneeWindow(),
        };
    }

    /** @return array{0: Carbon, 1: Carbon, 2: 'month'} */
    private function anneeWindow(): array
    {
        $annee = $this->context->anneeScolaire();

        if ($annee !== null) {
            return [Carbon::parse($annee->date_debut)->startOfDay(), Carbon::parse($annee->date_fin)->startOfDay(), 'month'];
        }

        return [now()->startOfYear(), now()->endOfYear()->startOfDay(), 'month'];
    }

    /** The two « this row is a group change, not a registration » rules (class docblock). */
    private function excludeSuccessors(Builder $query): void
    {
        $query
            ->whereNotExists(fn (Builder $sub) => $sub->selectRaw('1')
                ->from('inscriptions_historique as h')
                ->whereColumn('h.new_inscription_id', 'i.id'))
            ->whereNotExists(fn (Builder $sub) => $sub->selectRaw('1')
                ->from('inscriptions as p')
                ->whereColumn('p.student_id', 'i.student_id')
                ->whereColumn('p.id', '<>', 'i.id')
                ->where('p.statut', Inscription::STATUT_CHANGEMENT)
                ->whereRaw('(p.etablissement_id IS NOT DISTINCT FROM i.etablissement_id)')
                ->whereColumn('p.date_inscription', '<', 'i.date_inscription')
                ->whereNotNull('p.date_fin')
                ->whereRaw('abs(i.date_inscription - p.date_fin) <= ?', [self::LEGACY_TOLERANCE_DAYS]));
    }
}
