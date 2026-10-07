<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Demandes de virement (07/10/2026).
 *
 * Déclarer et consulter une demande est un geste de PAIEMENT — même
 * permission que l'encaissement qu'elle deviendra (`payments.view` /
 * `payments.create`), puisque c'est depuis le modal « Enregistrer un
 * paiement » qu'elle naît. La DÉCISION, elle, est le travail du comptable :
 * `virements.validate`, séparée, parce que valider crée l'encaissement et
 * crédite le compte « Virement » du centre.
 *
 * Portée centre inchangée : une demande se lit et se décide dans les
 * centres affectés de l'utilisateur (`etablissement_id` de la ligne).
 */
final class VirementPolicy extends ResourcePolicy
{
    protected string $module = 'payments';

    public function validate(User $user, Model $model): bool
    {
        return $user->can('virements.validate') && $this->withinCenter($user, $model);
    }
}
