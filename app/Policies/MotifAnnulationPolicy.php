<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class MotifAnnulationPolicy extends ResourcePolicy
{
    protected string $module = 'cancellation-reasons';

    // Global reference data — no center scoping.
    protected function centerId(Model $model): ?int
    {
        return null;
    }

    /**
     * System reasons ("Changement de groupe") are written by application
     * flows — even permission holders cannot edit/delete them. Super-admins
     * bypass policies (Gate::before), so the controller repeats this guard
     * with abort_if, same as TypeDepenseController.
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
