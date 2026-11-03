<?php

declare(strict_types=1);

namespace App\Domain\Reports\Actions;

use App\Models\Inscription;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * « Nouvelles inscriptions » dashboard bar chart (every signed-in user,
 * no permission) — how many NEW STUDENTS registered, bucketed over a chosen
 * DURATION:
 *
 *  - `jour`  — today only (default), one bar per HOUR of the day. `date_inscription`
 *              carries no time, so the hour is read from `created_at` (when
 *              the row was keyed); the day itself is still `date_inscription`;
 *  - `7j`    — last 7 days, one bar per day (the ONLY day-count window —
 *              « 30 jours » and « 12 semaines » were replaced by month windows
 *              on 23/09/2026: the school plans by month, never by week);
 *  - `1m`    — the last month (today minus one month → today), one bar per day;
 *  - `12m`   — last 12 months, one bar per month (« 3 mois » / « 6 mois »
 *              removed 03/11/2026 at the owner's request — the old keys fall
 *              back to today like any unknown key);
 *  - `periode` — a FREE window chosen by the user (« Du » / « Au » date
 *              pickers, 19/10/2026 — replaces the old « Année scolaire »
 *              button: the school wanted to compare any two dates, not only
 *              the switcher's year). One bar per day up to
 *              PERIODE_JOURS_MAX days, one bar per month beyond. An invalid
 *              or inverted range falls back to today, like an unknown key.
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
 *
 * ⚠ « BILAN » (03/11/2026) — the second view behind the swap icon. Same
 * window, same buckets, and for each bucket WHAT HAPPENED to inscriptions:
 *  - `nouvelles` = the bars above (same query), `actives` = how many of those
 *    are still Active today;
 *  - `annulees` / `changements` / `archivees` = statut TRANSITIONS dated by
 *    the journal entry (`activity_log`, event `updated`, new statut ≠ old) —
 *    the moment the gesture was made, not the dossier's date. A legacy row
 *    IMPORTED already « Annulée » is an `created` entry and is not counted:
 *    nobody cancelled anything that day;
 *  - `supprimees` = `deleted` journal entries (append-only, so a deletion
 *    survives its row);
 *  - `solde` = nouvelles − annulées − archivées − supprimées. A group change
 *    is shown but NEUTRAL: the student stays, only the group moves.
 * Centre of a journal entry = the inscription's current centre, or — once
 * deleted — the centre frozen in the entry's `old` attributes. One GROUP BY
 * on the journal, whatever the volume.
 */
final class GetNouvellesInscriptionsChart
{
    public const DUREES = ['jour', '7j', '1m', '12m', 'periode'];

    public const DUREE_DEFAUT = 'jour';

    /** A free window longer than this is bucketed by MONTH (two months of days still read on screen). */
    public const PERIODE_JOURS_MAX = 62;

    public function __construct(private readonly CurrentContext $context) {}

    /**
     * @return array{duree: string, debut: ?string, fin: ?string, labels: list<string>, keys: list<string>, counts: list<int>, total: int, periode: string}
     */
    public function __invoke(string $duree, ?string $debut = null, ?string $fin = null): array
    {
        $duree = self::normaliser($duree, $debut, $fin);

        [$start, $end, $unit] = $this->window($duree, $debut, $fin);
        // Echoed back so the pickers show the window actually queried.
        $plage = $duree === 'periode'
            ? ['debut' => $start->toDateString(), 'fin' => $end->toDateString()]
            : ['debut' => null, 'fin' => null];
        $bucketSql = self::bucketSql($unit);

        $rows = $this->nouvellesInscriptions($start, $end)
            ->selectRaw("{$bucketSql} AS bucket, COUNT(*) AS total, COUNT(*) FILTER (WHERE i.statut = ?) AS actives", [Inscription::STATUT_ACTIVE])
            ->groupByRaw($bucketSql)
            ->get()
            ->keyBy('bucket');

        $events = $this->evenements($start, $end, $unit);

        $labels = [];
        $keys = [];
        if ($unit === 'hour') {
            for ($h = 0; $h < 24; $h++) {
                $labels[] = sprintf('%02dh', $h);
                $keys[] = sprintf('%02d', $h);
            }
        } else {
            $cursor = $unit === 'day' ? $start->copy() : $start->copy()->startOfMonth();

            while ($cursor->lessThanOrEqualTo($end)) {
                $keys[] = $unit === 'month' ? $cursor->format('Y-m') : $cursor->format('Y-m-d');
                $labels[] = $unit === 'day' ? $cursor->format('d/m') : $cursor->format('m/Y');

                $unit === 'day' ? $cursor->addDay() : $cursor->addMonth();
            }
        }

        $counts = [];
        $bilan = ['actives' => [], 'annulees' => [], 'changements' => [], 'archivees' => [], 'supprimees' => [], 'solde' => []];
        foreach ($keys as $key) {
            $nouvelles = (int) ($rows[$key]->total ?? 0);
            $ev = $events[$key] ?? null;
            $annulees = (int) ($ev->annulees ?? 0);
            $archivees = (int) ($ev->archivees ?? 0);
            $supprimees = (int) ($ev->supprimees ?? 0);

            $counts[] = $nouvelles;
            $bilan['actives'][] = (int) ($rows[$key]->actives ?? 0);
            $bilan['annulees'][] = $annulees;
            $bilan['changements'][] = (int) ($ev->changements ?? 0);
            $bilan['archivees'][] = $archivees;
            $bilan['supprimees'][] = $supprimees;
            $bilan['solde'][] = $nouvelles - $annulees - $archivees - $supprimees;
        }

        $bilan['totaux'] = [
            'nouvelles' => array_sum($counts),
            'actives' => array_sum($bilan['actives']),
            'annulees' => array_sum($bilan['annulees']),
            'changements' => array_sum($bilan['changements']),
            'archivees' => array_sum($bilan['archivees']),
            'supprimees' => array_sum($bilan['supprimees']),
            'solde' => array_sum($bilan['solde']),
        ];

        return [
            'duree' => $duree,
            ...$plage,
            'labels' => $labels,
            'keys' => $keys,
            'counts' => $counts,
            'total' => array_sum($counts),
            'periode' => $unit === 'hour'
                ? $start->format('d/m/Y')
                : $start->format('d/m/Y').' – '.$end->format('d/m/Y'),
            'bilan' => $bilan,
        ];
    }

    /**
     * Statut transitions and deletions journaled inside the window, per
     * bucket (class docblock « BILAN »). Keyed by the bucket string.
     *
     * @param  'hour'|'day'|'month'  $unit
     * @return Collection<string, object{annulees: int, changements: int, archivees: int, supprimees: int}>
     */
    private function evenements(Carbon $start, Carbon $end, string $unit): Collection
    {
        $centreId = $this->context->etablissementId();
        $bucket = match ($unit) {
            'hour' => "to_char(a.created_at, 'HH24')",
            'day' => "to_char(a.created_at, 'YYYY-MM-DD')",
            default => "to_char(a.created_at, 'YYYY-MM')",
        };
        $nouveau = "(a.attribute_changes->'attributes'->>'statut')";
        $ancien = "(a.attribute_changes->'old'->>'statut')";
        $transition = fn (string $statut): string => "a.event = 'updated' AND {$nouveau} = ".DB::getPdo()->quote($statut)." AND {$ancien} IS DISTINCT FROM {$nouveau}";

        return DB::table('activity_log as a')
            ->leftJoin('inscriptions as i', 'i.id', '=', 'a.subject_id')
            ->where('a.log_name', 'inscription')
            ->where('a.subject_type', (new Inscription)->getMorphClass())
            ->whereBetween('a.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->where(fn (Builder $w) => $w
                ->where('a.event', 'deleted')
                ->orWhere(fn (Builder $u) => $u
                    ->where('a.event', 'updated')
                    ->whereRaw("{$nouveau} IN (?, ?, ?)", [Inscription::STATUT_ANNULEE, Inscription::STATUT_CHANGEMENT, Inscription::STATUT_ARCHIVEE])))
            ->when($centreId, fn (Builder $q) => $q->whereRaw(
                "COALESCE(i.etablissement_id::text, a.attribute_changes->'old'->>'etablissement_id') = ?",
                [(string) $centreId],
            ))
            ->selectRaw("{$bucket} AS bucket,
                COUNT(*) FILTER (WHERE {$transition(Inscription::STATUT_ANNULEE)}) AS annulees,
                COUNT(*) FILTER (WHERE {$transition(Inscription::STATUT_CHANGEMENT)}) AS changements,
                COUNT(*) FILTER (WHERE {$transition(Inscription::STATUT_ARCHIVEE)}) AS archivees,
                COUNT(*) FILTER (WHERE a.event = 'deleted') AS supprimees")
            ->groupByRaw($bucket)
            ->get()
            ->keyBy('bucket');
    }

    /**
     * The duration actually served: an unknown key, or `periode` without a
     * valid `debut` ≤ `fin` pair (Y-m-d), falls back to the default — the
     * chart never shows a window the user did not finish choosing.
     */
    public static function normaliser(string $duree, ?string $debut, ?string $fin): string
    {
        if (! in_array($duree, self::DUREES, true)) {
            return self::DUREE_DEFAUT;
        }

        if ($duree === 'periode' && self::plage($debut, $fin) === null) {
            return self::DUREE_DEFAUT;
        }

        return $duree;
    }

    /** @return array{0: Carbon, 1: Carbon}|null */
    private static function plage(?string $debut, ?string $fin): ?array
    {
        if ($debut === null || $fin === null
            || preg_match('/^\d{4}-\d{2}-\d{2}$/', $debut) !== 1
            || preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin) !== 1) {
            return null;
        }

        $start = Carbon::createFromFormat('!Y-m-d', $debut);
        $end = Carbon::createFromFormat('!Y-m-d', $fin);

        // createFromFormat rolls « 2026-02-31 » over to March: refuse what
        // does not read back as typed.
        if ($start === null || $end === null || $start->format('Y-m-d') !== $debut || $end->format('Y-m-d') !== $fin) {
            return null;
        }

        if ($start->greaterThan($end)) {
            return null;
        }

        return [$start->startOfDay(), $end->startOfDay()];
    }

    /** Rows per page of the per-bar student list — a month of Marrakech is 100+ rows. */
    public const STUDENTS_PER_PAGE = 25;

    /**
     * The students behind ONE bar (click on the chart), PAGINATED server-side
     * (23/09/2026 — « Année scolaire » on a big centre listed 113 rows in one
     * modal). Same base query and the same bucket expression as the count, so
     * the list can never hold a row the bar does not count — or miss one it
     * does. An unknown duration or a key that is not a bucket of the window
     * returns an empty page instead of widening to something the chart never
     * showed; a page past the end returns empty rows with the real total.
     *
     * @return array{students: list<array{inscriptionId: int, studentId: int, reference: string, nom: string, prenom: string, telephone: ?string, groupe: ?string, centre: ?string, statut: string, dateInscription: string, heure: string}>, total: int, page: int, lastPage: int, perPage: int}
     */
    public function students(string $duree, string $key, int $page = 1, ?string $debut = null, ?string $fin = null): array
    {
        $page = max(1, $page);
        $empty = ['students' => [], 'total' => 0, 'page' => $page, 'lastPage' => 1, 'perPage' => self::STUDENTS_PER_PAGE];

        // Same normalisation as the bars: a `periode` without its dates is
        // not « today », it is nothing the chart showed.
        if (! in_array($duree, self::DUREES, true) || self::normaliser($duree, $debut, $fin) !== $duree) {
            return $empty;
        }

        [$start, $end, $unit] = $this->window($duree, $debut, $fin);

        $pattern = match ($unit) {
            'hour' => '/^([01]\d|2[0-3])$/',
            'day' => '/^\d{4}-\d{2}-\d{2}$/',
            default => '/^\d{4}-\d{2}$/',
        };
        if (preg_match($pattern, $key) !== 1) {
            return $empty;
        }

        $bar = $this->nouvellesInscriptions($start, $end)
            ->whereRaw(self::bucketSql($unit).' = ?', [$key]);

        $total = (clone $bar)->count();
        $lastPage = max(1, (int) ceil($total / self::STUDENTS_PER_PAGE));

        $students = $bar
            ->join('students as s', 's.id', '=', 'i.student_id')
            ->leftJoin('groups as g', 'g.id', '=', 'i.group_id')
            ->leftJoin('etablissements as e', 'e.id', '=', 'i.etablissement_id')
            ->orderBy('i.date_inscription')
            ->orderBy('i.created_at')
            ->orderBy('i.id')
            ->forPage($page, self::STUDENTS_PER_PAGE)
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

        return ['students' => $students, 'total' => $total, 'page' => $page, 'lastPage' => $lastPage, 'perPage' => self::STUDENTS_PER_PAGE];
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

    /** @param 'hour'|'day'|'month' $unit */
    private static function bucketSql(string $unit): string
    {
        return match ($unit) {
            'hour' => "to_char(i.created_at, 'HH24')",
            'day' => "to_char(i.date_inscription, 'YYYY-MM-DD')",
            default => "to_char(i.date_inscription, 'YYYY-MM')",
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: 'hour'|'day'|'month'}
     */
    private static function window(string $duree, ?string $debut = null, ?string $fin = null): array
    {
        $today = now()->startOfDay();

        return match ($duree) {
            '7j' => [$today->copy()->subDays(6), $today, 'day'],
            '1m' => [$today->copy()->subMonthNoOverflow()->addDay(), $today, 'day'],
            '12m' => [$today->copy()->startOfMonth()->subMonths(11), $today, 'month'],
            'periode' => self::periodeWindow($debut, $fin),
            default => [$today, $today->copy(), 'hour'],
        };
    }

    /**
     * The user's own window — callers went through normaliser() first, so
     * the pair is valid here. Days while the span fits on screen, months
     * beyond (a full year of days would be 365 unreadable bars).
     *
     * @return array{0: Carbon, 1: Carbon, 2: 'day'|'month'}
     */
    private static function periodeWindow(?string $debut, ?string $fin): array
    {
        [$start, $end] = self::plage($debut, $fin) ?? [now()->startOfDay(), now()->startOfDay()];
        $unit = (int) $start->diffInDays($end) + 1 > self::PERIODE_JOURS_MAX ? 'month' : 'day';

        return [$start, $end, $unit];
    }
}
