<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DemandeRemboursement;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;

/**
 * Demandes de remboursement (02/11/2026).
 *
 * Demander et consulter restent des gestes de remboursement
 * (`refunds.create` / `refunds.view`). DÉCIDER est `refunds.approve` —
 * preset `director` seul (+ le bypass super-admin) — et TOUJOURS borné aux
 * centres affectés : un directeur n'approuve que les demandes de SES centres
 * (`etablissement_id` de la demande, CenterAccessService).
 */
final class DemandeRemboursementPolicy extends ResourcePolicy
{
    protected string $module = 'refunds';

    public function approve(User $user, DemandeRemboursement $demande): bool
    {
        return $demande->isEnAttente()
            && $user->can('refunds.approve')
            && $this->withinCenter($user, $demande);
    }
}
