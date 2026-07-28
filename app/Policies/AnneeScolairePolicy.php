<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class AnneeScolairePolicy extends ResourcePolicy
{
    protected string $module = 'academic-years';

    // Academic years are global reference data — no center scoping.
    protected function centerId(Model $model): ?int
    {
        return null;
    }
}
