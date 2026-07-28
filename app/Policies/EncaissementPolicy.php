<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class EncaissementPolicy extends ResourcePolicy
{
    protected string $module = 'payments';

    // A payment reaches its center through the till it went into.
    protected function centerId(Model $model): ?int
    {
        $id = $model->caisse?->etablissement_id;

        return $id === null ? null : (int) $id;
    }
}
