<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class EncaissementPolicy extends ResourcePolicy
{
    protected string $module = 'payments';

    /**
     * Déplacer un paiement vers le frais d'une autre inscription du même
     * étudiant (super-admin — `payments.move-fee` est dans
     * PermissionRegistry::superAdminOnly()).
     *
     * ⚠ Sans contrôle de centre, comme StudentPolicy@merge : l'argent à
     * rapatrier se trouve souvent sur un dossier d'un autre centre ou d'une
     * autre année, ce que les écrans ordinaires masquent justement.
     * L'identité du payeur, elle, reste vérifiée dans l'action
     * (DeplacerEncaissementVersFrais) : l'argent d'un étudiant ne solde
     * jamais le frais d'un autre.
     */
    public function movePayment(User $user): bool
    {
        return $user->can('payments.move-fee');
    }

    /**
     * Transférer un frais payé vers l'inscription d'un AUTRE étudiant — le
     * frère qui a payé sans jamais venir cède sa place à sa sœur
     * (10/09/2026, direction + super-admin).
     *
     * Permission DÉDIÉE, jamais `payments.update` : ce geste fait qu'une
     * somme encaissée au nom d'une personne solde le dossier d'une autre.
     * C'est l'exception assumée au garde-fou que movePayment() ci-dessus ne
     * relâche jamais.
     *
     * Aucun contrôle de centre ICI (l'ability ne reçoit pas de modèle : le
     * paiement et le frais cible arrivent du formulaire). Il est fait deux
     * fois là où il a du sens : le contrôleur borne le frais cible au
     * contexte actif, et l'action refuse deux inscriptions de centres
     * différents.
     */
    public function transferToStudent(User $user): bool
    {
        return $user->can('payments.transfer-student');
    }

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
