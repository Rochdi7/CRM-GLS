<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class DepensePolicy extends ResourcePolicy
{
    protected string $module = 'expenses';

    // An expense reaches its center through the till it came out of.
    protected function centerId(Model $model): ?int
    {
        $id = $model->caisse?->etablissement_id;

        return $id === null ? null : (int) $id;
    }
}
