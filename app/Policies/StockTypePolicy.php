<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class StockTypePolicy extends ResourcePolicy
{
    protected string $module = 'stock-types';

    // Global catalogue — no center scoping.
    protected function centerId(Model $model): ?int
    {
        return null;
    }

    /**
     * System types (the original 6 categories) are locked — even permission
     * holders cannot edit/delete them, same guard as TypeDepensePolicy.
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
