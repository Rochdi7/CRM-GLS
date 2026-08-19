<?php

declare(strict_types=1);

namespace App\Domain\Audit\Queries;

use App\Models\Activity;
use App\Models\User;
use App\Support\Audit\AuditLogRegistry;
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

        $entries->through(fn (Activity $a): array => $this->present($a));

        return ['data' => $entries];
    }

    /**
     * Shape one entry for the React page.
     *
     * @return array<string, mixed>
     */
    private function present(Activity $a): array
    {
        $changes = $a->attribute_changes?->toArray() ?? [];
        $old = $changes['old'] ?? [];
        $new = $changes['attributes'] ?? [];

        // One row per changed attribute — the journal's core question is
        // "what exactly changed, from what, to what".
        $diff = [];
        foreach (array_keys($new + $old) as $field) {
            $before = $old[$field] ?? null;
            $after = $new[$field] ?? null;

            if ($before === $after) {
                continue;
            }

            $diff[] = [
                'field' => (string) $field,
                'old' => $this->stringify($before),
                'new' => $this->stringify($after),
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
            'properties' => $a->properties?->toArray() ?? [],
        ];
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

        return (string) $value;
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
