<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Support\Audit\AuditLogRegistry;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Full-detail audit logging for a model (CLAUDE.md §11 "Audit log").
 *
 * Wraps spatie/laravel-activitylog with the project's forensic defaults so an
 * audited model only has to `use Auditable;` — no per-model LogOptions to
 * write, and no way to accidentally ship a model that logs a narrower set of
 * fields than the journal promises.
 *
 * The defaults, and why they are what they are:
 *
 * - `logAll()` rather than an explicit field list. The previous per-model
 *   `logOnly([...])` recorded only a handful of columns, which meant an edit
 *   to anything else — a payment's date, its note, its student — vanished
 *   silently. For a fraud trail the question "what exactly changed" has to be
 *   answerable for EVERY column, so the allowlist is gone.
 * - `logOnlyDirty()` stays: unchanged columns are noise, and a diff of only
 *   what actually moved is what an investigation reads.
 * - `dontLogEmptyChanges()`: a save that changed nothing is not an event.
 * - Sensitive columns are excluded globally (see EXCLUDED) — a password hash
 *   or remember-token in a readable journal is a liability, and their VALUES
 *   are never what an audit needs. That a password changed is still recorded,
 *   because the event itself is logged even when the attribute is not.
 *
 * The `log_name` comes from AuditLogRegistry so the journal's filters and the
 * recorded rows can never disagree.
 */
trait Auditable
{
    use LogsActivity;

    /**
     * Never written to the journal, for any model.
     *
     * @var list<string>
     */
    protected static array $auditExcluded = [
        'password', 'remember_token', 'two_factor_secret',
        'two_factor_recovery_codes', 'api_token',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(static::$auditExcluded)
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName($this->auditLogName());
    }

    /**
     * Human-readable description of the event, in French, so the journal
     * reads as prose instead of "updated".
     */
    public function getDescriptionForEvent(string $eventName): string
    {
        $label = AuditLogRegistry::labelForSubjectType(static::class) ?? class_basename(static::class);

        $verb = match ($eventName) {
            'created' => 'créé',
            'updated' => 'modifié',
            'deleted' => 'supprimé',
            'restored' => 'restauré',
            default => $eventName,
        };

        return "{$label} {$verb}";
    }

    /**
     * Stable log name from the registry; falls back to the snake-cased class
     * name so a model that forgets to register still logs coherently.
     */
    protected function auditLogName(): string
    {
        return AuditLogRegistry::map()[static::class][0]
            ?? str(class_basename(static::class))->snake()->value();
    }
}
