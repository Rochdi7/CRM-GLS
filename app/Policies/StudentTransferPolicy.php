<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StudentTransfer;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use App\Services\Authorization\CenterAccessService;
use Illuminate\Database\Eloquent\Model;

/**
 * Transfert d'un étudiant entre centres (25/09/2026).
 *
 * Une demande a DEUX bouts : le centre qui la fait et le centre qui reçoit.
 * Chacun doit pouvoir la voir — le front office d'arrivée prépare l'accueil
 * de l'étudiant. La DÉCISION (valider / refuser) est `student-transfers.
 * validate`, réservée au super-admin (PermissionRegistry::superAdminOnly()) ;
 * la garde « déjà traitée » vit dans les actions Domain, sous verrou.
 */
final class StudentTransferPolicy extends ResourcePolicy
{
    protected string $module = 'student-transfers';

    protected function centerId(Model $model): ?int
    {
        $id = $model->getAttribute('etablissement_source_id');

        return $id === null ? null : (int) $id;
    }

    private function withinEitherCenter(User $user, StudentTransfer $transfert): bool
    {
        if ($this->withinCenter($user, $transfert)) {
            return true;
        }

        return app(CenterAccessService::class)->canAccessCenter($user, (int) $transfert->etablissement_cible_id);
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can('student-transfers.view') && $this->withinEitherCenter($user, $model);
    }

    public function validate(User $user, StudentTransfer $transfert): bool
    {
        return $user->can('student-transfers.validate');
    }

    public function refuse(User $user, StudentTransfer $transfert): bool
    {
        return $user->can('student-transfers.validate');
    }

    /**
     * Le DEMANDEUR retire sa propre demande tant qu'elle attend ; le
     * décideur peut aussi la clore. Un tiers qui tient seulement
     * `student-transfers.create` ne retire pas la demande d'un collègue.
     */
    public function cancel(User $user, StudentTransfer $transfert): bool
    {
        if ($user->can('student-transfers.validate')) {
            return true;
        }

        $employeeId = $user->employee?->id;

        return $employeeId !== null
            && (int) $transfert->requested_by === (int) $employeeId
            && $user->can('student-transfers.create');
    }
}
