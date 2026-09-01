<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Models\Group;
use App\Models\Inscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Modification du groupe" — corrects the group of THIS inscription in
 * place, unlike "Changement de groupe" (ChangerGroupeInscription) which
 * archives the current inscription and creates a new Active one.
 *
 * Use case: the registration was filed into the wrong group (or the group
 * was renamed/recreated) — nothing about the enrollment itself changes, so
 * no historique snapshot and no second row. Every fee line (paid or not),
 * every Encaissement (they reference the fee, not the group) and the
 * assigned livres stay attached to the SAME inscription and therefore
 * follow the student into the new group automatically — no money is
 * rewritten, unlinked or re-allocated by this action.
 *
 * The inscription's centre/année are re-inherited from the new group (an
 * inscription always mirrors its group's scoping, same as store() and
 * ChangerGroupeInscription::createNewInscription()). The group_id change is
 * journalled by Auditable like any other column edit.
 *
 * ⚠ STRICT « En inscription » RULE (01/09/2026): the in-place move exists
 * for ONE scenario — the student enrolled (and may already have paid) but
 * classes have not started, and they switch groups before studying. So the
 * CURRENT group must still be « En inscription »: once it is « En
 * formation » there is teaching history (séances, presences) an in-place
 * swap would silently disown — that correction is « Changement de
 * groupe »'s job (archive + successor row). The TARGET group is
 * deliberately NOT restricted: joining a group already running is a normal
 * enrollment (store() and ChangerGroupeInscription allow it too).
 */
final class ModifierGroupeInscription
{
    public function handle(Inscription $inscription, Group $newGroup): Inscription
    {
        if ($inscription->statut !== Inscription::STATUT_ACTIVE) {
            throw ValidationException::withMessages([
                'inscription' => __('Only an active registration can change group.'),
            ]);
        }

        if ($newGroup->id === $inscription->group_id) {
            throw ValidationException::withMessages([
                'new_group_id' => __('The registration is already in this group.'),
            ]);
        }

        if ($inscription->group?->statut !== Group::STATUT_EN_INSCRIPTION) {
            throw ValidationException::withMessages([
                'new_group_id' => __('The current group has already started — use "Changement de groupe" instead.'),
            ]);
        }

        return DB::transaction(function () use ($inscription, $newGroup): Inscription {
            $inscription->update([
                'group_id' => $newGroup->id,
                'etablissement_id' => $newGroup->etablissement_id,
                'annee_scolaire_id' => $newGroup->annee_scolaire_id ?? $inscription->annee_scolaire_id,
            ]);

            return $inscription;
        });
    }
}
