<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Concerns\ResourcePolicy;

final class CaissePolicy extends ResourcePolicy
{
    protected string $module = 'cash-registers';
}
