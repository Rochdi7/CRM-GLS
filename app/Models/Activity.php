<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Audit\AuditLogRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Request;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * The audit-journal entry (CLAUDE.md §11 "Audit log"), wired in as
 * `activity_model` in config/activitylog.php.
 *
 * Why a custom model instead of the packaged one: the fraud trail has to
 * answer "from where" and "through which endpoint", not just "who and what".
 * Stamping that in `creating` — rather than at each call site — is what makes
 * the guarantee unconditional: every entry gets the context no matter which
 * model, Domain action or console command produced it, and there is no code
 * path that can log an entry while quietly skipping it.
 *
 * The same reasoning drives `causer_label`: causer_id is a foreign key, and a
 * User row can later be renamed or deactivated. Freezing the human-readable
 * identity at write time means the journal still names the actor as they were
 * AT THAT MOMENT, which is the only version an investigation can rely on.
 *
 * ⚠ Append-only by design. The journal is the evidence, so it must not be
 * editable from application code — including by a super-admin, who bypasses
 * every Gate but cannot bypass a model that refuses to update or delete.
 * Retention pruning is the one legitimate removal path and runs through the
 * package's own `activitylog:clean` command against the query builder, not
 * through this model's delete().
 */
class Activity extends SpatieActivity
{
    /** Guard against tampering through Eloquent (see class docblock). */
    public const IMMUTABLE_MESSAGE = 'Les entrées du journal d\'audit sont en lecture seule.';

    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            $activity->fillForensicContext();
        });

        // An audit entry is evidence: once written it is never amended or
        // removed by the application. Anything that tries is a bug (or an
        // attempt to cover tracks) and must fail loudly rather than silently
        // mutate the trail.
        static::updating(function (): bool {
            throw new \RuntimeException(self::IMMUTABLE_MESSAGE);
        });

        static::deleting(function (): bool {
            throw new \RuntimeException(self::IMMUTABLE_MESSAGE);
        });
    }

    /**
     * Capture request/actor context at write time.
     *
     * Values already set by the caller win — a Domain action that knows the
     * real origin better than the current request (e.g. replaying an import)
     * can pass its own and this will not overwrite it.
     */
    public function fillForensicContext(): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            $this->method ??= 'CLI';
            $this->url ??= 'artisan:'.implode(' ', array_slice((array) ($_SERVER['argv'] ?? []), 1));
        } else {
            $this->ip_address ??= Request::ip();
            $this->user_agent ??= mb_substr((string) Request::userAgent(), 0, 512) ?: null;
            $this->method ??= Request::method();
            $this->url ??= mb_substr((string) Request::fullUrl(), 0, 1024);
            $this->route_name ??= Request::route()?->getName();
        }

        $this->causer_label ??= $this->resolveCauserLabel();
    }

    /**
     * Human-readable identity of the actor, frozen at write time.
     */
    private function resolveCauserLabel(): ?string
    {
        $causer = $this->causer;

        if ($causer === null) {
            return null;
        }

        if ($causer instanceof User) {
            $name = trim((string) $causer->name);
            $handle = $causer->username ?: $causer->email;

            return $name !== ''
                ? mb_substr($handle ? "{$name} ({$handle})" : $name, 0, 255)
                : (mb_substr((string) $handle, 0, 255) ?: null);
        }

        return mb_substr(class_basename($causer).'#'.$causer->getKey(), 0, 255);
    }

    /**
     * Entries whose subject is one of the money-touching models — the
     * "suivi des encaissements" view of the journal.
     *
     * @param  Builder<self>  $query
     */
    public function scopeFinance(Builder $query): void
    {
        $query->whereIn('log_name', AuditLogRegistry::financeLogNames());
    }
}
