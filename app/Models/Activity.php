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
            $this->url ??= $this->consoleOrigin();
        } else {
            $this->ip_address ??= Request::ip();
            $this->user_agent ??= mb_substr((string) Request::userAgent(), 0, 512) ?: null;
            $this->method ??= Request::method();
            $this->url ??= mb_substr((string) Request::fullUrl(), 0, 1024);
            $this->route_name ??= Request::route()?->getName();
        }

        $this->causer_label ??= $this->resolveCauserLabel();

        // Final belt-and-braces truncation. Everything above already trims,
        // but a caller may have set these itself (the ??= above leaves such a
        // value untouched), and an over-long string here would throw while
        // INSERTING an audit row — losing the very record that proves what
        // happened. Truncating a URL is always preferable to losing the entry.
        $this->url = $this->clamp($this->url, 1024);
        $this->user_agent = $this->clamp($this->user_agent, 512);
        $this->route_name = $this->clamp($this->route_name, 255);
        $this->causer_label = $this->clamp($this->causer_label, 255);
        $this->ip_address = $this->clamp($this->ip_address, 45);
        $this->method = $this->clamp($this->method, 10);
    }

    /** Trim a value to the column width, preserving null. */
    private function clamp(?string $value, int $length): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strlen($value) > $length ? mb_substr($value, 0, $length) : $value;
    }

    /**
     * What ran, when the entry came from the console.
     *
     * Only the command NAME is recorded — never its arguments. Joining the
     * whole of `$_SERVER['argv']` put the entire payload of
     * `tinker --execute=<script>` into this column, which is three problems at
     * once: a wall of PHP where a reader expects an origin, a leak of internal
     * code and absolute file paths into a page people read, and a value that
     * can exceed the column's 1024 chars and make the audit write itself fail.
     *
     * An option's VALUE can hold anything (a script, a password, a token), so
     * arguments are dropped wholesale rather than filtered: knowing which
     * command ran is what an investigation needs, and it is the part that is
     * always safe to keep.
     */
    private function consoleOrigin(): string
    {
        $argv = (array) ($_SERVER['argv'] ?? []);

        // argv[0] is the artisan script itself; argv[1] is the command name.
        $command = $argv[1] ?? null;

        if (! is_string($command) || $command === '' || str_starts_with($command, '-')) {
            return 'artisan';
        }

        return mb_substr('artisan:'.$command, 0, 255);
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
