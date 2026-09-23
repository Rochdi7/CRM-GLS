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
 * ⚠ TWO MORE GUARDS, same day, after HIBA YOUSSEF at Salé: her imported
 * dossier was DELETED the day a new one was keyed for her (a group change done
 * as create + delete instead of « Changement de groupe »), her 07/08 payment
 * moved onto the new row, and the chart counted her as new — the
 * `inscriptions` table alone could no longer tell. So a student is NOT new
 * when (a) any payment of theirs is dated BEFORE the dossier — a first
 * enrolment is never preceded by money; or (b) the append-only journal holds
 * a `deleted` inscription of theirs with an EARLIER date (a dossier keyed by
 * mistake and re-keyed the same day stays new). Both live in
 * nouvellesInscriptions(), shared by the bars AND the per-bar student list.
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
     * @return array{duree: string, labels: list<string>, keys: list<string>, counts: list<int>, total: int, periode: string}
     */
    public function __invoke(string $duree): array
    {
        if (! in_array($duree, self::DUREES, true)) {
            $duree = self::DUREE_DEFAUT;
        }

        [$start, $end, $unit] = $this->window($duree);
        $bucketSql = self::bucketSql($unit);

        $rows = $this->nouvellesInscriptions($start, $end)
            ->selectRaw("{$bucketSql} AS bucket, COUNT(*) AS total")
            ->groupByRaw($bucketSql)
            ->pluck('total', 'bucket');

        $labels = [];
        $keys = [];
        $counts = [];
        if ($unit === 'hour') {
            for ($h = 0; $h < 24; $h++) {
                $labels[] = sprintf('%02dh', $h);
                $keys[] = sprintf('%02d', $h);
                $counts[] = (int) ($rows[sprintf('%02d', $h)] ?? 0);
            }

            return [
                'duree' => $duree,
                'labels' => $labels,
                'keys' => $keys,
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
            $keys[] = $key;
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
            'keys' => $keys,
            'counts' => $counts,
            'total' => array_sum($counts),
            'periode' => $start->format('d/m/Y').' – '.$end->format('d/m/Y'),
        ];
    }

    /**
     * The students behind ONE bar (click on the chart). Same base query and
     * the same bucket expression as the count, so the list can never hold a
     * row the bar does not count — or miss one it does. An unknown duration
     * or a key that is not a bucket of the window returns an empty list
     * instead of widening to something the chart never showed.
     *
     * @return list<array{inscriptionId: int, studentId: int, reference: string, nom: string, prenom: string, telephone: ?string, groupe: ?string, centre: ?string, statut: string, dateInscription: string, heure: string}>
     */
    public function students(string $duree, string $key): array
    {
        if (! in_array($duree, self::DUREES, true)) {
            return [];
        }

        [$start, $end, $unit] = $this->window($duree);

        $pattern = match ($unit) {
            'hour' => '/^([01]\d|2[0-3])$/',
            'day', 'week' => '/^\d{4}-\d{2}-\d{2}$/',
            default => '/^\d{4}-\d{2}$/',
        };
        if (preg_match($pattern, $key) !== 1) {
            return [];
        }

        return $this->nouvellesInscriptions($start, $end)
            ->whereRaw(self::bucketSql($unit).' = ?', [$key])
            ->join('students as s', 's.id', '=', 'i.student_id')
            ->leftJoin('groups as g', 'g.id', '=', 'i.group_id')
            ->leftJoin('etablissements as e', 'e.id', '=', 'i.etablissement_id')
            ->orderBy('i.date_inscription')
            ->orderBy('i.created_at')
            ->orderBy('i.id')
            ->get([
                'i.id', 'i.student_id', 'i.statut', 'i.date_inscription', 'i.created_at',
                's.reference', 's.nom', 's.prenom', 's.telephone',
                'g.nom as groupe', 'e.nom_centre as centre',
            ])
            ->map(fn (object $r): array => [
                'inscriptionId' => (int) $r->id,
                'studentId' => (int) $r->student_id,
                'reference' => (string) $r->reference,
                'nom' => (string) $r->nom,
                'prenom' => (string) $r->prenom,
                'telephone' => $r->telephone,
                'groupe' => $r->groupe,
                'centre' => $r->centre,
                'statut' => (string) $r->statut,
                'dateInscription' => Carbon::parse($r->date_inscription)->format('d/m/Y'),
                'heure' => $r->created_at !== null ? Carbon::parse($r->created_at)->format('H:i') : '',
            ])
            ->all();
    }

    /**
     * NEW registrations dated inside the window, on the active centre: the
     * student's FIRST dossier only (class docblock). Shared by the count and
     * the per-bar list.
     */
    private function nouvellesInscriptions(Carbon $start, Carbon $end): Builder
    {
        $centreId = $this->context->etablissementId();

        return DB::table('inscriptions as i')
            ->whereBetween('i.date_inscription', [$start->toDateString(), $end->toDateString()])
            ->when($centreId, fn (Builder $q) => $q->where('i.etablissement_id', $centreId))
            ->whereNotExists(fn (Builder $sub) => $sub->selectRaw('1')
                ->from('inscriptions as p')
                ->whereColumn('p.student_id', 'i.student_id')
                ->where(fn (Builder $w) => $w
                    ->whereColumn('p.date_inscription', '<', 'i.date_inscription')
                    ->orWhere(fn (Builder $t) => $t
                        ->whereColumn('p.date_inscription', 'i.date_inscription')
                        ->whereColumn('p.id', '<', 'i.id'))))
            // A student who PAID before this dossier was opened is not new,
            // whatever the inscriptions table says now (23/09/2026, HIBA
            // YOUSSEF at Salé: her imported dossier was deleted the day a new
            // one was created, her 07/08 payment moved onto the new one).
            ->whereNotExists(fn (Builder $sub) => $sub->selectRaw('1')
                ->from('encaissements as en')
                ->whereColumn('en.student_id', 'i.student_id')
                ->whereColumn('en.date_paiement', '<', 'i.date_inscription'))
            // …nor a student whose EARLIER dossier was deleted: the journal is
            // append-only, so the deletion survives the row (same case, for a
            // student who had not paid yet).
            ->whereNotExists(fn (Builder $sub) => $sub->selectRaw('1')
                ->from('activity_log as a')
                ->where('a.log_name', 'inscription')
                ->where('a.event', 'deleted')
                ->whereRaw("(a.attribute_changes->'old'->>'student_id') = i.student_id::text")
                ->whereRaw("(a.attribute_changes->'old'->>'date_inscription')::date < i.date_inscription"));
    }

    /** @param 'hour'|'day'|'week'|'month' $unit */
    private static function bucketSql(string $unit): string
    {
        return match ($unit) {
            'hour' => "to_char(i.created_at, 'HH24')",
            'day' => "to_char(i.date_inscription, 'YYYY-MM-DD')",
            'week' => "to_char(date_trunc('week', i.date_inscription), 'YYYY-MM-DD')",
            default => "to_char(i.date_inscription, 'YYYY-MM')",
        };
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
