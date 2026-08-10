<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class BanquePolicy extends ResourcePolicy
{
    protected string $module = 'banks';

    // The bank catalog is global reference data — no center scoping.
    protected function centerId(Model $model): ?int
    {
        return null;
    }
}
