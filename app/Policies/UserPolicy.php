<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;

/**
 * Who may administer a LOGIN (edit identity, regenerate its password,
 * deactivate it, change its roles). Super-admins bypass this via
 * Gate::before; for everyone else, holding `users.assign-roles` is
 * necessary but not sufficient (audit 27/08/2026, SEC-01/03/04):
 *
 *  - a super-admin account can only be administered by a super-admin —
 *    otherwise any director could regenerate the CEO's password and take
 *    over every Gate;
 *  - nobody administers their OWN account through this screen (the profile
 *    page exists for that) — it is the self-escalation hole;
 *  - the target must belong to a centre the actor reaches (« Centres
 *    affectés », CLAUDE.md §16); logins with no employee profile are
 *    global records, like everywhere else in CenterAccessService.
 */
final class UserPolicy
{
    public function __construct(private readonly CenterAccessService $centers) {}

    public function update(User $actor, User $target): bool
    {
        return $this->manage($actor, $target);
    }

    public function manageAuthorization(User $actor, User $target): bool
    {
        return $this->manage($actor, $target);
    }

    private function manage(User $actor, User $target): bool
    {
        if (! $actor->can('users.assign-roles')) {
            return false;
        }

        if ($actor->is($target)) {
            return false;
        }

        if ($target->hasRole(Role::SUPER_ADMIN) && ! $actor->hasRole(Role::SUPER_ADMIN)) {
            return false;
        }

        $employee = $target->employee;

        if ($employee === null) {
            return true;
        }

        $centreIds = $employee->etablissements()->pluck('etablissements.id')->all();

        if ($employee->etablissement_id !== null) {
            $centreIds[] = $employee->etablissement_id;
        }

        $centreIds = array_values(array_unique(array_map('intval', $centreIds)));

        if ($centreIds === []) {
            return true;
        }

        foreach ($centreIds as $centreId) {
            if ($this->centers->canAccessCenter($actor, $centreId)) {
                return true;
            }
        }

        return false;
    }
}
