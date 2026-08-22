<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Domain\Registrations\Queries\GetGroupInscriptionFees;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Changement de groupe" — archives the current inscription into a new
 * group instead of editing group_id in place, so the original enrollment
 * period (and what was owed/paid during it) stays a permanent, queryable
 * record (inscriptions_historique), mirroring Group::archiverCommeTermine()'s
 * snapshot-before-transition pattern (gls-crm-schema.md §7).
 *
 * Unpaid fee lines on the OLD inscription can optionally be removed
 * (overdue-only or all) — any Encaissement still linked to a removed fee is
 * NOT deleted (money records are append-only, CLAUDE.md §11): it is simply
 * unlinked (inscription_fee_id = null), turning it into an unallocated
 * "avance" the same way AppliquerAvance's counterpart flow does, so the
 * amount stays available for the student without being tied to a fee that
 * no longer exists.
 */
final class ChangerGroupeInscription
{
    public const SCOPE_OVERDUE_ONLY = 'overdue_only';

    public const SCOPE_ALL = 'all';

    public const SCOPES = [self::SCOPE_OVERDUE_ONLY, self::SCOPE_ALL];

    public function __construct(private readonly GetGroupInscriptionFees $getGroupInscriptionFees) {}

    public function handle(
        Inscription $inscription,
        Group $newGroup,
        string $dateFin,
        string $dateDebut,
        ?string $unpaidFeesScope,
        ?string $note,
        ?Employee $archivedBy,
        array $transferFeeIds = [],
    ): Inscription {
        if ($inscription->statut !== Inscription::STATUT_ACTIVE) {
            throw ValidationException::withMessages([
                'inscription' => __('Only an active registration can change group.'),
            ]);
        }

        return DB::transaction(function () use ($inscription, $newGroup, $dateFin, $dateDebut, $unpaidFeesScope, $note, $archivedBy, $transferFeeIds): Inscription {
            $transferred = $this->feesToTransfer($inscription, $transferFeeIds);

            // The historique snapshot records what was paid DURING the old
            // enrollment; money that follows the student to the new group
            // is not part of it.
            $montantPaye = (float) InscriptionFee::query()
                ->where('inscription_id', $inscription->id)
                ->whereNotIn('id', $transferred->pluck('id'))
                ->get()
                ->sum(fn (InscriptionFee $fee): float => $fee->montantPaye());

            if ($unpaidFeesScope !== null) {
                $this->removeUnpaidFees($inscription, $unpaidFeesScope, $dateFin, $transferred->pluck('id')->all());
            }

            $inscription->update([
                'statut' => Inscription::STATUT_CHANGEMENT,
                'date_fin' => $dateFin,
            ]);

            $inscription->historique()->create([
                'student_id' => $inscription->student_id,
                'group_id' => $inscription->group_id,
                'montant_paye' => $montantPaye,
                'date_fin' => $dateFin,
                'note' => $note,
                'archived_at' => now(),
                'archived_by' => $archivedBy?->id,
            ]);

            $newInscription = $this->createNewInscription($inscription, $newGroup, $dateDebut, $transferred);

            $inscription->historique->update(['new_inscription_id' => $newInscription->id]);

            // Moving the fee row keeps every Encaissement attached to it
            // (they reference the fee, not the inscription), so the money
            // follows the student without any payment being rewritten.
            if ($transferred->isNotEmpty()) {
                InscriptionFee::query()
                    ->whereIn('id', $transferred->pluck('id'))
                    ->update(['inscription_id' => $newInscription->id]);

                $newInscription->update([
                    'montant_total' => $newInscription->fees()->whereNull('masque_le')->sum('montant') ?: null,
                ]);
                $inscription->update([
                    'montant_total' => $inscription->fees()->whereNull('masque_le')->sum('montant') ?: null,
                ]);
            }

            return $newInscription;
        });
    }

    /**
     * Fees the user ticked to carry over — only rows of THIS inscription that
     * actually received money (fully or partially paid); an unpaid line has
     * nothing to transfer and is handled by the unpaid-fees scope instead.
     *
     * @param  array<int, int|string>  $ids
     * @return \Illuminate\Support\Collection<int, InscriptionFee>
     */
    private function feesToTransfer(Inscription $inscription, array $ids): \Illuminate\Support\Collection
    {
        if ($ids === []) {
            return collect();
        }

        return $inscription->fees()
            ->whereIn('id', $ids)
            ->whereNull('masque_le')
            ->get()
            ->filter(fn (InscriptionFee $fee): bool => $fee->montantPaye() > 0)
            ->values();
    }

    /**
     * @param  array<int, int>  $keepIds
     */
    private function removeUnpaidFees(Inscription $inscription, string $scope, string $dateFin, array $keepIds = []): void
    {
        $query = $inscription->fees()
            ->where('statut', '!=', InscriptionFee::STATUT_PAYE)
            ->whereNotIn('id', $keepIds);

        if ($scope === self::SCOPE_OVERDUE_ONLY) {
            $query->whereDate('date_echeance', '>', $dateFin);
        }

        $query->get()->each(function (InscriptionFee $fee): void {
            // Unlink rather than delete any payment recorded against this
            // fee — it becomes an unallocated avance instead of vanishing.
            Encaissement::query()->where('inscription_fee_id', $fee->id)->update(['inscription_fee_id' => null]);
            $fee->delete();
        });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, InscriptionFee>  $transferred
     */
    private function createNewInscription(Inscription $old, Group $newGroup, string $dateDebut, \Illuminate\Support\Collection $transferred): Inscription
    {
        // A transferred fee replaces the new group's own line for the same
        // catalog entry — a paid "Frais d'inscription" must not be charged
        // a second time.
        $transferredFraisIds = $transferred->pluck('frais_id')->filter()->all();

        $lines = $this->getGroupInscriptionFees->__invoke($newGroup)
            ->reject(fn (array $fee): bool => in_array($fee['fraisId'], $transferredFraisIds, true))
            ->map(fn (array $fee): array => [
            'frais_id' => $fee['fraisId'],
            'nom' => $fee['nom'],
            'montant_initial' => (float) $fee['montantInitial'],
            'remise_pct' => null,
            'remise_montant' => null,
            'montant' => (float) $fee['montantInitial'],
            'date_echeance' => $fee['dateEcheance'],
            'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $newInscription = Inscription::create([
            'reference' => ReferenceGenerator::make('INS', 'inscriptions'),
            'student_id' => $old->student_id,
            'group_id' => $newGroup->id,
            'etablissement_id' => $newGroup->etablissement_id,
            'annee_scolaire_id' => $newGroup->annee_scolaire_id ?? app(CurrentContext::class)->anneeScolaireId(),
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => now()->toDateString(),
            'date_debut' => $dateDebut,
            'date_fin' => $newGroup->date_fin_formation?->toDateString(),
            'montant_total' => $lines->sum('montant') ?: null,
            'created_by' => $old->created_by,
        ]);

        foreach ($lines as $line) {
            $newInscription->fees()->create($line);
        }

        return $newInscription;
    }
}
