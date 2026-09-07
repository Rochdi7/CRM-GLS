<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Models\InscriptionFee;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Applique UNE date d'échéance à plusieurs lignes de frais d'un coup —
 * l'écriture derrière « Échéances en masse ».
 *
 * Ce que cette action fait, et RIEN d'autre : elle écrit
 * `inscription_fees.date_echeance`. Elle ne touche ni `montant`, ni
 * `statut`, ni un encaissement, ni une caisse. Une échéance est une date de
 * rappel : la déplacer ne rend pas un frais payé et ne fait bouger aucun
 * dirham, ce qui est exactement pourquoi le droit
 * `fee-due-dates.bulk-update` peut être accordé à tous les rôles
 * (`PermissionRegistry::defaultForEveryRole()`) alors que
 * `registrations.update` ne l'est pas.
 *
 * ⚠ **La portée est revérifiée ICI, ligne par ligne, pas seulement dans le
 * read-model.** Le contrôleur reçoit une liste d'ids cochés dans le
 * navigateur : rien n'empêche d'en forger un. La sélection affichée a beau
 * être scopée (GetGroupFeeDueDates), c'est cette requête-ci qui décide ce
 * qui est réellement écrit — même règle qu'AssertsContextScope, appliquée en
 * masse. Un id hors portée n'est pas ignoré silencieusement : le lot entier
 * est refusé, sinon l'utilisateur croit avoir modifié 30 lignes alors que
 * 28 seulement ont bougé (CLAUDE.md §11, « signaler plutôt que masquer »).
 *
 * Les lignes MASQUÉES sont refusées de la même façon : un frais retiré n'est
 * plus dû et son argent a été libéré en avance — le redater écrirait sur une
 * ligne qu'aucun écran ne montre.
 *
 * Chaque ligne est sauvegardée par `save()` sur un modèle Eloquent, jamais
 * par un `update()` de masse : le trait `Auditable` doit journaliser chaque
 * changement avec son avant/après (§11 « Audit log »). Un UPDATE ... WHERE
 * IN unique serait plus rapide d'une requête et laisserait trente
 * modifications sans aucune trace.
 */
final class ModifierEcheancesFraisEnMasse
{
    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @param  list<int>  $feeIds  les lignes cochées
     * @param  string  $dateEcheance  la date à appliquer (Y-m-d)
     * @return int le nombre de lignes réellement modifiées (les lignes qui
     *             portaient déjà cette date ne comptent pas)
     */
    public function __invoke(User $user, array $feeIds, string $dateEcheance): int
    {
        $feeIds = array_values(array_unique(array_map('intval', $feeIds)));

        if ($feeIds === []) {
            throw ValidationException::withMessages([
                'fee_ids' => __('Select at least one line to update.'),
            ]);
        }

        return DB::transaction(function () use ($user, $feeIds, $dateEcheance): int {
            $fees = InscriptionFee::query()
                ->whereIn('id', $feeIds)
                ->whereNull('masque_le')
                ->whereHas('inscription', function (Builder $q) use ($user): void {
                    $this->centerAccess->scopeAccessibleCenters($q, $user);

                    if (($annee = $this->context->anneeScolaireId()) !== null) {
                        $q->where('annee_scolaire_id', $annee);
                    }

                    if (($centre = $this->context->etablissementId()) !== null) {
                        $q->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $centre));
                    }
                })
                ->lockForUpdate()
                ->get();

            // Refus GLOBAL, pas un filtrage : voir l'en-tête de classe.
            if ($fees->count() !== count($feeIds)) {
                throw ValidationException::withMessages([
                    'fee_ids' => __('Some selected lines are outside your scope or no longer exist. Nothing was changed.'),
                ]);
            }

            $modifiees = 0;

            foreach ($fees as $fee) {
                if ($fee->date_echeance?->toDateString() === $dateEcheance) {
                    continue;
                }

                $fee->date_echeance = $dateEcheance;
                $fee->save();
                $modifiees++;
            }

            return $modifiees;
        });
    }
}
