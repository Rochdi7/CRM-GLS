<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared CRUD policy behavior: `<module>.<action>` permission + center scope.
 * Subclasses set $module and override centerId() when the record reaches its
 * center indirectly (e.g. encaissement → caisse → etablissement_id).
 * Super-admin bypasses all of this via Gate::before.
 */
abstract class ResourcePolicy
{
    /** Permission module prefix, e.g. "students". */
    protected string $module;

    public function viewAny(User $user): bool
    {
        return $user->can("{$this->module}.view");
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can("{$this->module}.view") && $this->withinCenter($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can("{$this->module}.create");
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can("{$this->module}.update") && $this->withinCenter($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can("{$this->module}.delete") && $this->withinCenter($user, $model);
    }

    /**
     * The center this record belongs to (null = global record).
     */
    protected function centerId(Model $model): ?int
    {
        $id = $model->getAttribute('etablissement_id');

        return $id === null ? null : (int) $id;
    }

    protected function withinCenter(User $user, Model $model): bool
    {
        return app(CenterAccessService::class)->canAccessCenter($user, $this->centerId($model));
    }
}
