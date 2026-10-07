<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Depense;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class DepensePolicy extends ResourcePolicy
{
    protected string $module = 'expenses';

    // An expense belongs to the centre it was KEYED in (group, else its own
    // column, else the till's for rows older than the column) — see
    // Depense::centreId(). Reaching it through the till alone tied every
    // expense to the employee's PRIMARY centre (18/09/2026).
    protected function centerId(Model $model): ?int
    {
        return $model instanceof Depense ? $model->centreId() : null;
    }

    /**
     * Approve / refuse a pending expense — the decision that debits the till
     * (Domain\Expenses\Actions\ApprouverDepense).
     *
     * Requires `expenses.approve`, which sits in no role preset: in practice
     * only super-admins hold it (via Gate::before) unless a super-admin
     * grants it by hand.
     */
    public function approve(User $user, Depense $depense): bool
    {
        // An already-decided expense has no decision left to take; the Domain
        // actions re-check this under lock (defense in depth).
        if ($depense->isDecided()) {
            return false;
        }

        return $user->can('expenses.approve') && $this->withinCenter($user, $depense);
    }

    /**
     * Annuler une dépense approuvée : la caisse est recréditée par écriture
     * compensatoire (AnnulerDepense), la ligne est conservée.
     *
     * Super-admin uniquement — `expenses.cancel` est dans
     * PermissionRegistry::superAdminOnly(), donc aucun preset de rôle ne
     * peut le porter. Seule une dépense APPROUVÉE est annulable : une
     * dépense en attente ou refusée n'a jamais débité la caisse (il n'y a
     * rien à rendre), et une dépense déjà annulée le serait deux fois.
     */
    public function cancel(User $user, Depense $depense): bool
    {
        if (! $depense->isApprouvee()) {
            return false;
        }

        return $user->can('expenses.cancel') && $this->withinCenter($user, $depense);
    }

    /**
     * « Modifier le calcul » d'un paiement prof EN ATTENTE (07/10/2026) :
     * refaire le calcul et en reporter le résultat (montant, CNSS, période,
     * enseignant) sur la MÊME dépense. Ouvert à qui peut la modifier ET à
     * l'employé qui l'a saisie — une demande en attente n'a débité aucune
     * caisse, la corriger ne déplace pas d'argent. Exige le calcul
     * (`prof-payments.calculate`). Exclu du bypass super-admin
     * (NO_SUPER_ADMIN_BYPASS) : une dépense décidée ne se recalcule pour
     * personne.
     */
    public function recalculer(User $user, Depense $depense): bool
    {
        if (! $depense->estRecalculable() || ! $user->can('prof-payments.calculate')) {
            return false;
        }

        if ($this->update($user, $depense)) {
            return true;
        }

        $employeeId = $user->employee?->id;

        return $employeeId !== null
            && (int) $depense->agent_id === (int) $employeeId
            && $user->can('expenses.create')
            && $this->withinCenter($user, $depense);
    }

    /**
     * A refused or cancelled expense is closed history — nothing about it may
     * be edited. (An approved one stays editable exactly as before: its money
     * already moved, and UpdateDepenseRequest structurally excludes
     * montant/caisse_id, so an edit can never change the amount that left
     * the till.)
     */
    public function update(User $user, Model $model): bool
    {
        if ($model instanceof Depense && ($model->isRefusee() || $model->isAnnulee())) {
            return false;
        }

        // Un « Paiement prof » ne se modifie QUE tant qu'il est EN ATTENTE
        // (07/10/2026) : une fois approuvé, la paie est actée et l'argent
        // est sorti de la caisse — plus personne ne la retouche, super-admin
        // compris (`update` est exclu du bypass, NO_SUPER_ADMIN_BYPASS).
        if ($model instanceof Depense && ! $model->isEnAttente() && $model->estPaiementProf()) {
            return false;
        }

        return parent::update($user, $model);
    }
}
