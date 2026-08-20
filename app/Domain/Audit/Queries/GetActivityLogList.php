<?php

declare(strict_types=1);

namespace App\Domain\Audit\Queries;

use App\Models\Activity;
use App\Models\Caisse;
use App\Models\User;
use App\Support\Audit\AuditLogRegistry;
use App\Support\Audit\AuditValueResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-model for the audit journal (CLAUDE.md §11 "Audit log").
 *
 * Server-side pagination/search/filter throughout (CLAUDE.md §5) — the journal
 * is the biggest table in the app by row count, so it is never loaded
 * client-side.
 *
 * Deliberately NOT center-scoped, unlike every other list query. The journal
 * exists to investigate fraud, and scoping it to the active center would hide
 * exactly the cross-center activity an investigation is looking for. Access is
 * controlled at the door instead — `audit-logs.view`, held by no role except
 * director and the super-admin bypass.
 */
final class GetActivityLogList
{
    public const DEFAULT_PER_PAGE = 25;

    /**
     * @return array{data: LengthAwarePaginator}
     */
    public function __invoke(
        string $search = '',
        string $logName = '',
        string $event = '',
        string $causerId = '',
        string $subjectType = '',
        string $dateFrom = '',
        string $dateTo = '',
        string $ip = '',
        bool $financeOnly = false,
        string $caisseId = '',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): array {
        $query = Activity::query()
            ->when($financeOnly, fn (Builder $q) => $q->finance())
            ->when($logName !== '', fn (Builder $q) => $q->where('log_name', $logName))
            ->when($event !== '', fn (Builder $q) => $q->where('event', $event))
            ->when($subjectType !== '', fn (Builder $q) => $q->where('subject_type', $subjectType))
            ->when($causerId !== '', fn (Builder $q) => $q->where('causer_id', (int) $causerId)
                ->where('causer_type', (new User)->getMorphClass()))
            ->when($ip !== '', fn (Builder $q) => $q->where('ip_address', $ip))
            // Verifying ONE till: every movement it received, in order. Matches
            // the subject (the caisse row itself) so both the ledger movements
            // and direct edits to the caisse show up together.
            ->when($caisseId !== '', fn (Builder $q) => $q
                ->where('subject_type', (new Caisse)->getMorphClass())
                ->where('subject_id', (int) $caisseId))
            // Date filters are inclusive of the whole end day: the journal is
            // read by date, not by timestamp, and "au 19/08" must include
            // everything that happened during the 19th.
            ->when($dateFrom !== '', fn (Builder $q) => $q->where('created_at', '>=', $dateFrom.' 00:00:00'))
            ->when($dateTo !== '', fn (Builder $q) => $q->where('created_at', '<=', $dateTo.' 23:59:59'))
            ->when($search !== '', function (Builder $q) use ($search): void {
                $term = "%{$search}%";
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->where('description', 'ilike', $term)
                        ->orWhere('causer_label', 'ilike', $term)
                        ->orWhere('log_name', 'ilike', $term)
                        ->orWhere('ip_address', 'ilike', $term)
                        ->orWhere('url', 'ilike', $term)
                        // Free-text over the recorded diff/properties so a
                        // reference (ENC-2026-0042) or an amount can be found
                        // without knowing which column held it.
                        ->orWhereRaw('attribute_changes::text ilike ?', [$term])
                        ->orWhereRaw('properties::text ilike ?', [$term]);
                });
            });

        $entries = $query
            ->with(['causer', 'subject'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        // One resolver per page load: warm it with every FK value on the page
        // so the id -> name lookups cost a few queries, not one per field.
        $resolver = new AuditValueResolver();
        $resolver->warm($this->foreignKeyPairs($entries->getCollection()));

        $entries->through(fn (Activity $a): array => $this->present($a, $resolver));

        return ['data' => $entries];
    }

    /**
     * Shape one entry for the React page.
     *
     * @return array<string, mixed>
     */
    private function present(Activity $a, AuditValueResolver $resolver): array
    {
        $changes = $a->attribute_changes?->toArray() ?? [];
        $old = $changes['old'] ?? [];
        $new = $changes['attributes'] ?? [];

        // One row per changed attribute — the journal's core question is
        // "what exactly changed, from what, to what". Each side carries both
        // the raw stored value AND, for a foreign key, the name behind it, so
        // a reader never has to know that `enseignant_id: 11` means Karim.
        $diff = [];
        foreach (array_keys($new + $old) as $field) {
            $field = (string) $field;
            $before = $old[$field] ?? null;
            $after = $new[$field] ?? null;

            if ($before === $after) {
                continue;
            }

            // Plumbing columns (id/created_at on a creation) would push the
            // meaningful fields off the top of the list.
            if (AuditValueResolver::isNoise($field, $a->event)) {
                continue;
            }

            $diff[] = [
                'field' => $field,
                'label' => AuditValueResolver::label($field),
                'old' => $this->stringify($before),
                'oldLabel' => $resolver->resolve($field, $before),
                'new' => $this->stringify($after),
                'newLabel' => $resolver->resolve($field, $after),
            ];
        }

        return [
            'id' => $a->id,
            'logName' => $a->log_name,
            'logLabel' => AuditLogRegistry::labels()[$a->log_name] ?? $a->log_name,
            'description' => $a->description,
            'event' => $a->event,
            'eventLabel' => $this->eventLabel($a->event),
            'causerLabel' => $a->causer_label,
            'causerId' => $a->causer_id,
            'subjectType' => $a->subject_type,
            'subjectLabel' => AuditLogRegistry::labelForSubjectType($a->subject_type),
            'subjectId' => $a->subject_id,
            'subjectRef' => $this->subjectReference($a),
            'ipAddress' => $a->ip_address,
            'userAgent' => $a->user_agent,
            'method' => $a->method,
            'url' => $a->url,
            'routeName' => $a->route_name,
            // Second-precision timestamp: an investigation orders events
            // within the same minute, so the seconds are not decoration.
            'createdAt' => $a->created_at?->format('Y-m-d H:i:s'),
            'createdAtHuman' => $a->created_at?->diffForHumans(),
            'changes' => $diff,
            'properties' => $this->labelledProperties($a),
            // Money summary — present only when the entry actually moved cash,
            // so the page can lead with the arithmetic instead of burying it
            // among the raw properties (see GetActivityLogList::moneySummary).
            'money' => $this->moneySummary($a),
        ];
    }

    /**
     * Context properties, French-labelled and stripped of what the page already
     * shows elsewhere.
     *
     * The money block and the origin panel render `solde_avant`, `montant`,
     * `caisse` etc. in their own sections; repeating them here as raw
     * snake_case keys would just be noise under a heading called « Contexte ».
     *
     * @return list<array{key: string, label: string, value: string}>
     */
    private function labelledProperties(Activity $a): array
    {
        $raw = $a->properties?->toArray() ?? [];

        // Already rendered by the dedicated money/origin sections.
        $handledElsewhere = [
            'solde_avant', 'solde_apres', 'montant', 'caisse', 'sens', 'motif',
            'origine_type', 'origine_id', 'origine_reference',
        ];

        $out = [];

        foreach ($raw as $key => $value) {
            if (in_array($key, $handledElsewhere, true) || $value === null || $value === '') {
                continue;
            }

            if (is_bool($value)) {
                $display = $value ? 'Oui' : 'Non';
            } elseif (is_array($value)) {
                $display = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
            } else {
                $display = $this->humanizeTimestamp((string) $value);
            }

            $out[] = [
                'key' => (string) $key,
                'label' => AuditValueResolver::label((string) $key),
                'value' => $display,
            ];
        }

        return $out;
    }

    /**
     * The cash arithmetic behind an entry, when there is any.
     *
     * A till is verified by reading "balance before → movement → balance
     * after" in sequence. That arithmetic is recorded by CaisseLedger on
     * `solde_movement` entries, and it is the single most important thing on
     * the page for a finance check — so it is lifted out of `properties` into
     * its own typed block rather than left as anonymous key/value noise.
     *
     * Returns null for everything that did not move money, so the UI can
     * simply test for its presence.
     *
     * @return array<string, mixed>|null
     */
    private function moneySummary(Activity $a): ?array
    {
        $p = $a->properties?->toArray() ?? [];

        if (! isset($p['solde_avant'], $p['solde_apres'], $p['montant'])) {
            return null;
        }

        $avant = (float) $p['solde_avant'];
        $apres = (float) $p['solde_apres'];

        return [
            'caisse' => $p['caisse'] ?? null,
            'sens' => $p['sens'] ?? null,
            'isCredit' => ($p['sens'] ?? null) === 'Entrée',
            'montant' => number_format((float) $p['montant'], 2, ',', ' '),
            'soldeAvant' => number_format($avant, 2, ',', ' '),
            'soldeApres' => number_format($apres, 2, ',', ' '),
            // Recomputed here rather than trusted from the row: if the stored
            // before/after do not agree with the recorded amount, that
            // discrepancy is itself the finding an auditor is looking for.
            'delta' => number_format($apres - $avant, 2, ',', ' '),
            'coherent' => abs(abs($apres - $avant) - (float) $p['montant']) < 0.005,
            'motif' => $p['motif'] ?? null,
            'origineReference' => $p['origine_reference'] ?? null,
        ];
    }

    /**
     * Every [column, value] pair on a page that might be a foreign key, so the
     * resolver can batch-load the names in one pass.
     *
     * @param  Collection<int, Activity>  $entries
     * @return list<array{0: string, 1: mixed}>
     */
    private function foreignKeyPairs(Collection $entries): array
    {
        $pairs = [];

        foreach ($entries as $entry) {
            $changes = $entry->attribute_changes?->toArray() ?? [];

            foreach ([$changes['old'] ?? [], $changes['attributes'] ?? []] as $side) {
                foreach ($side as $column => $value) {
                    $pairs[] = [(string) $column, $value];
                }
            }
        }

        return $pairs;
    }

    /**
     * One entry, fully presented, for the detail page. Returns null when the
     * id does not exist so the controller can 404 rather than render an empty
     * shell.
     *
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $entry = Activity::query()->with(['causer', 'subject'])->find($id);

        if ($entry === null) {
            return null;
        }

        $resolver = new AuditValueResolver();
        $resolver->warm($this->foreignKeyPairs(collect([$entry])));

        return $this->present($entry, $resolver);
    }

    /**
     * A human handle for the touched record — its `reference` when the model
     * has one (ENC-…, DEP-…), so the journal points at something an
     * investigator can search for elsewhere in the app.
     */
    private function subjectReference(Activity $a): ?string
    {
        $subject = $a->subject;

        if ($subject === null) {
            // The record may have been deleted — the entry still stands, and
            // the id is what remains to identify it.
            return $a->subject_id !== null ? '#'.$a->subject_id : null;
        }

        foreach (['reference', 'nom_centre', 'nom'] as $attribute) {
            $value = $subject->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '#'.$a->subject_id;
    }

    private function eventLabel(?string $event): ?string
    {
        return match ($event) {
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            'restored' => 'Restauration',
            'login' => 'Connexion',
            'logout' => 'Déconnexion',
            'login_failed' => 'Échec de connexion',
            'lockout' => 'Blocage',
            'password_reset' => 'Réinitialisation du mot de passe',
            'solde_movement' => 'Mouvement de caisse',
            'avance_applied' => "Avance affectée à un frais",
            'cheque_statut' => 'Changement de statut du chèque',
            null => null,
            default => $event,
        };
    }

    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
        }

        return $this->humanizeTimestamp((string) $value);
    }

    /**
     * Eloquent serialises dates as `2026-08-19T00:00:00.000000Z`, which is
     * precise and unreadable. Rendered as `19/08/2026` (or with the time when
     * there is one), since the journal is read by people, not parsers.
     *
     * Anything that is not an ISO timestamp is returned untouched — this must
     * never reshape a reference, an amount or a free-text note.
     */
    private function humanizeTimestamp(string $value): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})/', $value, $m) !== 1) {
            return $value;
        }

        [, $year, $month, $day, $hour, $minute, $second] = $m;

        $date = "{$day}/{$month}/{$year}";

        // A pure date (midnight) reads better without a meaningless 00:00:00.
        if ($hour === '00' && $minute === '00' && $second === '00') {
            return $date;
        }

        return "{$date} {$hour}:{$minute}:{$second}";
    }

    /**
     * Users who actually appear in the journal — the filter only offers
     * actors that have something recorded, so it can never point at an empty
     * result set.
     *
     * @return Collection<int, array{id:int, nom:string}>
     */
    public function causerOptions(): Collection
    {
        return User::query()
            ->whereIn('id', Activity::query()
                ->where('causer_type', (new User)->getMorphClass())
                ->whereNotNull('causer_id')
                ->distinct()
                ->pluck('causer_id'))
            ->orderBy('name')
            ->get()
            ->map(fn (User $u): array => [
                'id' => $u->id,
                'nom' => $u->name.($u->username ? " ({$u->username})" : ''),
            ]);
    }

    /**
     * Tills that have something recorded — the « Caisse » filter, which is how
     * a finance check starts: pick the till, read its movements in order.
     *
     * @return list<array{value:string, label:string}>
     */
    public function caisseOptions(): array
    {
        $ids = Activity::query()
            ->where('subject_type', (new Caisse)->getMorphClass())
            ->whereNotNull('subject_id')
            ->distinct()
            ->pluck('subject_id');

        return Caisse::query()
            ->whereIn('id', $ids)
            ->orderBy('nom')
            ->get()
            ->map(fn (Caisse $c): array => [
                'value' => (string) $c->id,
                'label' => $c->nom,
            ])
            ->all();
    }

    /**
     * Log-name options, restricted to those present in the table so the
     * filter never lists a module that has never been touched.
     *
     * @return list<array{value:string, label:string}>
     */
    public function logNameOptions(): array
    {
        $labels = AuditLogRegistry::labels();

        $present = Activity::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->pluck('log_name')
            ->all();

        $options = [];
        foreach ($present as $name) {
            $options[] = ['value' => $name, 'label' => $labels[$name] ?? $name];
        }

        usort($options, fn (array $a, array $b): int => strcoll($a['label'], $b['label']));

        return $options;
    }

    /**
     * Distinct events present in the table, French-labelled.
     *
     * @return list<array{value:string, label:string}>
     */
    public function eventOptions(): array
    {
        $present = Activity::query()
            ->whereNotNull('event')
            ->distinct()
            ->pluck('event')
            ->all();

        $options = [];
        foreach ($present as $event) {
            $options[] = ['value' => $event, 'label' => $this->eventLabel($event) ?? $event];
        }

        usort($options, fn (array $a, array $b): int => strcoll($a['label'], $b['label']));

        return $options;
    }
}
