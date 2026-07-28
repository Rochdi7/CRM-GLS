<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class TypeDepensePolicy extends ResourcePolicy
{
    protected string $module = 'expense-types';

    // Global catalogue — no center scoping.
    protected function centerId(Model $model): ?int
    {
        return null;
    }

    /**
     * System types are locked (schema §12) — payroll/transfer code depends
     * on them. Even permission holders cannot edit/delete them.
     */
    public function update(User $user, Model $model): bool
    {
        return ! $model->getAttribute('is_system') && parent::update($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return ! $model->getAttribute('is_system') && parent::delete($user, $model);
    }
}
