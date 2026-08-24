<?php

declare(strict_types=1);

namespace App\Policies;

use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class EncaissementPolicy extends ResourcePolicy
{
    protected string $module = 'payments';

    /**
     * A payment reaches its center through the STUDENT it is for — the same
     * definition the list query uses (GetEncaissementsList) and the one the
     * schema documents ("this table has no etablissement_id: the centre is
     * reached via student / inscription").
     *
     * It used to resolve through the till instead, which disagreed with the
     * list as soon as the money sat in an operator's till from another
     * centre (legacy import: CaisseProvisioner puts an employee's till in
     * their PRIMARY centre) — a row was listed for the centre but 403'd when
     * opened.
     */
    protected function centerId(Model $model): ?int
    {
        $id = $model->student?->etablissement_id;

        return $id === null ? null : (int) $id;
    }
}
