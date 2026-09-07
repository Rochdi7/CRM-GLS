<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use App\Support\Access\HiddenAccount;
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
     * Rouvrir un groupe « Fin de formation » ou « Annulée »
     * (Group::rouvrir). `groups.reopen` est dans
     * PermissionRegistry::superAdminOnly() : aucun preset de rôle ne le
     * porte, donc seul un super-admin (Gate::before) — ou un compte à qui
     * la permission a été accordée à la main — l'atteint.
     *
     * Sortir d'un statut terminal remet le groupe dans les listes actives
     * et le rend de nouveau inscriptible ; l'action ne détruit rien (ni
     * paiement, ni inscription, ni séance), mais elle réécrit ce que
     * l'établissement considérait comme un dossier clos.
     */
    public function reopen(User $user, Group $group): bool
    {
        return $user->can('groups.reopen') && $this->withinCenter($user, $group);
    }

    /**
     * ⚠ Modifier un groupe DÉJÀ CLOS (« Fin de formation » ou « Annulée »).
     *
     * L'onglet Historique est en lecture seule pour tout le monde : un
     * dossier clos ne se retouche pas, sinon la ligne vivante et le snapshot
     * `groups_historique` divergent. Le MAINTENEUR seul y échappe
     * (`HiddenAccount::EMAIL`, demandé le 07/09/2026) : il doit pouvoir
     * corriger un nom, un niveau ou une date saisis de travers par l'import
     * legacy sans passer par `reopen`, qui remettrait le groupe dans les
     * listes actives et le rendrait de nouveau inscriptible.
     *
     * Ce n'est PAS une permission : `groups.update-closed` n'existe pas et ne
     * s'accorde pas. Un super-admin ordinaire (le CEO compris) ne l'obtient
     * pas — sinon « un dossier clos est clos » ne tiendrait plus que par
     * convention. `EMAIL` seul, jamais `emails()` : l'autre compte du
     * mainteneur est un compte de STAFF, pas l'identité de maintenance.
     *
     * Le STATUT reste verrouillé dans GroupController::update() pour tout le
     * monde, mainteneur inclus : sortir d'un statut terminal passe uniquement
     * par `reopen`.
     */
    public function updateClosed(User $user, Group $group): bool
    {
        return $user->email === HiddenAccount::EMAIL && $this->update($user, $group);
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
