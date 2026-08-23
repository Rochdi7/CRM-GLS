<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Support\Access\HiddenAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Keeps the maintainer's staff record out of every `employees` query.
 *
 * Applied as a GLOBAL scope rather than added list-by-list on purpose: the
 * employee roster is read from a couple of dozen places (the Employés list,
 * the Utilisateurs page's center filter, the enseignant pickers on Groupes /
 * Créneaux / Séances, the dashboard head-count, every "agent" column…), and
 * a new page added next month would otherwise silently re-expose the account.
 * Hiding it at the model means a list has to opt OUT, not opt in.
 *
 * ⚠ Deliberately NOT applied to `User`: Laravel's EloquentUserProvider builds
 * its credential lookup with `newQuery()`, so a global scope there would
 * apply during authentication and lock the maintainer out of his own account.
 * The users list filters explicitly instead (GetUsersList).
 *
 * Opt out with `Employee::withoutGlobalScope(HiddenAccountScope::class)` —
 * or `withoutGlobalScopes()` — wherever the row genuinely must be reachable
 * (the seeder that provisions it, an integrity check, a data export).
 *
 * See App\Support\Access\HiddenAccount for what this is and is NOT allowed
 * to do (it is a display filter, never an audit or authorization bypass).
 */
final class HiddenAccountScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        /** @var Builder<\App\Models\Employee> $builder */
        HiddenAccount::hideEmployees($builder);
    }
}
