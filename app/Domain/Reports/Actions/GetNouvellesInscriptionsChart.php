<?php

declare(strict_types=1);

namespace App\Domain\Reports\Actions;

use App\Services\Context\CurrentContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * « Nouvelles inscriptions » dashboard bar chart (every signed-in user,
 * no permission) — how many NEW STUDENTS registered, bucketed over a chosen
 * DURATION:
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
 * ⚠ ONLY NEW STUDENTS (23/09/2026). The date is `inscriptions.date_inscription`,
 * but a dossier is counted only when it is the student's FIRST one — no other
 * inscription of the same student is older (earlier date, or same date and
 * lower id). A group change, a manual re-enrolment in another group, a legacy
 * successor: each is a later dossier of a student who already exists, so none
 * of them can reach this chart. The previous version tried to recognise group
 * changes after the fact, and every manual re-enrolment slipped through.
 * « Modification du groupe » rewrites group_id IN PLACE and creates no row.
 * Every statut is kept — a registration later cancelled was still taken.
 *
 * Like « Résumé des frais annuels », the année only supplies the WINDOW.
 * Centre-scoped on the dossier's centre via CurrentContext (NULL on « Tous
 * les centres »). One GROUP BY query whatever the volume.
 */
final class GetNouvellesInscriptionsChart
{
    public const DUREES = ['jour', '7j', '30j', '12s', '12m', 'annee'];

    public const DUREE_DEFAUT = 'jour';

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
            // The student's FIRST dossier only (class docblock).
            ->whereNotExists(fn (Builder $sub) => $sub->selectRaw('1')
                ->from('inscriptions as p')
                ->whereColumn('p.student_id', 'i.student_id')
                ->where(fn (Builder $w) => $w
                    ->whereColumn('p.date_inscription', '<', 'i.date_inscription')
                    ->orWhere(fn (Builder $t) => $t
                        ->whereColumn('p.date_inscription', 'i.date_inscription')
                        ->whereColumn('p.id', '<', 'i.id'))))
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
}
