<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class EtablissementPolicy extends ResourcePolicy
{
    protected string $module = 'centers';

    /**
     * The centers list is shared reference data (selects, filters) — no
     * center-scoping on the catalogue itself; mutations stay permission-gated.
     */
    protected function centerId(Model $model): ?int
    {
        return null;
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can('centers.delete');
    }
}
