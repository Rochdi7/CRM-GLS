<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Etablissement;
use App\Services\CaisseProvisioner;

/**
 * A new centre gets its three payment-method accounts (TPE / Chèque /
 * Virement) the moment it exists — same "no manual creation screen" rule as
 * the employee till (EmployeeObserver). Idempotent through the provisioner.
 */
final class EtablissementObserver
{
    public function created(Etablissement $etablissement): void
    {
        app(CaisseProvisioner::class)->provisionComptesMethodeFor($etablissement);
    }
}
