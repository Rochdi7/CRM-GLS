<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class GroupPolicy extends ResourcePolicy
{
    protected string $module = 'groups';

    /**
     * ⚠ L'EXCEPTION à « un groupe ne se supprime jamais » (CLAUDE.md §11,
     * schema §6). Depuis le 31/08/2026 un groupe créé par erreur (import
     * raté, doublon, groupe de test) peut être détruit DÉFINITIVEMENT avec
     * ses inscriptions — mais `groups.delete` est dans
     * PermissionRegistry::superAdminOnly() : aucun preset de rôle ne le
     * porte, donc en pratique seul un super-admin (Gate::before) l'atteint.
     *
     * L'argent n'est JAMAIS supprimé : Domain\Groups\Actions\SupprimerGroupe
     * reconvertit d'abord les encaissements rattachés en avances (les
     * enregistrements monétaires restent append-only, caisses.solde ne bouge
     * pas). Un groupe qui porte des séances est refusé — l'historique de
     * présence n'est pas destructible par ce chemin.
     */
    public function delete(User $user, Model $model): bool
    {
        return $user->can('groups.delete') && $this->withinCenter($user, $model);
    }

    /**
     * Transition to "Fin de formation" (Group::archiverCommeTermine).
     */
    public function archive(User $user, Group $group): bool
    {
        return $user->can('groups.archive') && $this->withinCenter($user, $group);
    }

    /**
     * Changing or clearing a group's teacher is its OWN ability, not part of
     * `groups.update` (31/08/2026): every role holds `groups.change-teacher`
     * so anyone can fix a wrong or departed enseignant, without that also
     * granting the right to rename the group, move its salle or touch its
     * frais. Centre reach still applies — a group you cannot reach is still
     * out of bounds.
     */
    public function changeTeacher(User $user, Group $group): bool
    {
        return $user->can('groups.change-teacher') && $this->withinCenter($user, $group);
    }

    /**
     * `groups.move-year` sits in PermissionRegistry::superAdminOnly(): no
     * role preset carries it, so in practice only a super-admin (Gate::before)
     * or a hand-granted account reaches this.
     */
    public function moveYear(User $user, Group $group): bool
    {
        return $user->can('groups.move-year') && $this->withinCenter($user, $group);
    }
}
