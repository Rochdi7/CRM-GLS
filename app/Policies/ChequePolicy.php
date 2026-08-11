<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Concerns\ResourcePolicy;

/**
 * Chèques in hand — same permission + center-scope combination as every
 * other resource. centerId() reads etablissement_id straight off the row
 * (the base default), set from CurrentContext at creation.
 */
final class ChequePolicy extends ResourcePolicy
{
    protected string $module = 'cheques';
}
