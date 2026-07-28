<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Concerns;

use App\Models\Employee;
use Illuminate\Http\Request;

/**
 * Money-moving operations (encaissements, depenses, remboursements,
 * transfers) require an Employee identity for the accountability trail
 * (agent_id / requested_by / validated_by). A login without a linked
 * employees row cannot perform them.
 */
trait ResolvesActingEmployee
{
    protected function actingEmployee(Request $request): Employee
    {
        $employee = $request->user()?->employee;

        abort_unless($employee !== null, 403, __("Votre compte n'est lié à aucune fiche employé."));

        return $employee;
    }
}
