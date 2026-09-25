<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Groups\Support\PorteeEnseignant;
use App\Models\Seance;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class SeancePolicy extends ResourcePolicy
{
    protected string $module = 'attendance';

    /** A teacher opens only the séances of his groups (PorteeEnseignant). */
    public function view(User $user, Model $model): bool
    {
        /** @var Seance $model */
        return parent::view($user, $model) && PorteeEnseignant::couvreSeance($user, $model);
    }

    /**
     * Saving/updating the roll call of a séance (fiche de présence).
     */
    public function mark(User $user, Seance $seance): bool
    {
        return $user->can('attendance.mark')
            && $this->withinCenter($user, $seance)
            && PorteeEnseignant::couvreSeance($user, $seance);
    }

    /**
     * Manually confirming ("Valider") or cancelling ("Annuler") a séance —
     * same audience as taking the roll call itself.
     */
    public function validate(User $user, Seance $seance): bool
    {
        return $this->mark($user, $seance);
    }

    /**
     * Cancelling needs `attendance.update` on top of the roll-call right
     * (24/09/2026): an enseignant takes the roll call and validates it, but
     * never cancels a séance — that decision belongs to the office. Every
     * other role holding `attendance.mark` also holds `attendance.update`, so
     * only `teacher` loses it.
     */
    public function cancel(User $user, Seance $seance): bool
    {
        return $this->mark($user, $seance) && $user->can('attendance.update');
    }
}
