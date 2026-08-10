<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Creneau;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Créneaux (emploi du temps) reuse the "attendance" permission module —
 * they are part of the same scheduling workflow as séances, not a separate
 * resource. A créneau has no etablissement_id of its own; its center is its
 * group's.
 */
final class CreneauPolicy extends ResourcePolicy
{
    protected string $module = 'attendance';

    protected function centerId(Model $model): ?int
    {
        /** @var Creneau $model */
        $id = $model->group?->etablissement_id;

        return $id === null ? null : (int) $id;
    }
}
