<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Remboursement;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class RemboursementPolicy extends ResourcePolicy
{
    protected string $module = 'refunds';

    /**
     * Le centre du remboursement est le SIEN — celui du paiement d'origine,
     * sinon celui de l'étudiant — jamais celui de la caisse (03/09/2026).
     * La caisse qui paie peut être rattachée à un tout autre centre : c'est
     * exactement ce qui rendait un remboursement invisible des deux côtés.
     * On retombe sur la caisse pour les lignes antérieures à la colonne.
     */
    protected function centerId(Model $model): ?int
    {
        $id = $model->getAttribute('etablissement_id') ?? $model->caisse?->etablissement_id;

        return $id === null ? null : (int) $id;
    }

    /**
     * Annuler un remboursement déjà payé : la caisse est recréditée par
     * écriture compensatoire, la ligne n'étant jamais supprimée (§11).
     *
     * C'est de l'argent qui REVIENT en caisse, pas une correction de
     * libellé : `refunds.cancel` est dans PermissionRegistry::superAdminOnly(),
     * donc aucun preset de rôle ne peut le porter (03/09/2026).
     *
     * Un remboursement déjà annulé ne se réannule pas — la caisse serait
     * recréditée deux fois pour une seule sortie.
     */
    public function cancel(User $user, Remboursement $remboursement): bool
    {
        if (Remboursement::estAnnule($remboursement)) {
            return false;
        }

        return $user->can('refunds.cancel') && $this->withinCenter($user, $remboursement);
    }
}
