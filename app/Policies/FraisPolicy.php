<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class FraisPolicy extends ResourcePolicy
{
    protected string $module = 'fees';

    // The fee catalog is global reference data — no center scoping.
    protected function centerId(Model $model): ?int
    {
        return null;
    }
}
