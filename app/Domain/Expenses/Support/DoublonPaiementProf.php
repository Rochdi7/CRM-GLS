<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Support;

use App\Models\Depense;
use App\Models\TypeDepense;
use Carbon\CarbonImmutable;

/**
 * Un groupe ne se paie qu'UNE fois par période (21/10/2026).
 *
 * Une période « du 24/09 au 24/10 » est le mois de SEPTEMBRE : le mois imputé
 * d'un paiement prof est celui de `periode_debut`. Une seconde saisie pour le
 * même groupe (et le même enseignant) est refusée dès qu'elle tombe sur le
 * même mois imputé OU que ses dates chevauchent une période déjà payée —
 * c'est par là que les doublons passaient.
 *
 * Seule une ligne « Refusée » ou « Annulée » libère la période : l'argent
 * n'est alors jamais sorti, ou il est revenu à la caisse. « En attente »
 * bloque aussi — la demande est déjà faite, l'approuver suffit.
 */
final class DoublonPaiementProf
{
    /** Statuts qui ne libèrent PAS la période. */
    private const STATUTS_BLOQUANTS = [Depense::STATUT_EN_ATTENTE, Depense::STATUT_APPROUVEE];

    public static function trouver(
        int $groupId,
        ?int $enseignantId,
        string $debut,
        string $fin,
        ?int $exceptId = null,
    ): ?Depense {
        $moisDebut = CarbonImmutable::parse($debut)->startOfMonth();

        return Depense::query()
            ->where('group_id', $groupId)
            ->whereIn('statut', self::STATUTS_BLOQUANTS)
            ->whereHas('typeDepense', fn ($q) => $q->where('nom', TypeDepense::SYSTEM_PAIEMENT_PROF))
            ->whereNotNull('periode_debut')
            ->whereNotNull('periode_fin')
            // Un changement d'enseignant en cours de mois paie légitimement
            // les DEUX profs ; une ligne sans enseignant (antérieure à la
            // colonne) reste comptée.
            ->when($enseignantId !== null, fn ($q) => $q->where(
                fn ($w) => $w->whereNull('enseignant_id')->orWhere('enseignant_id', $enseignantId),
            ))
            ->when($exceptId !== null, fn ($q) => $q->whereKeyNot($exceptId))
            ->where(fn ($q) => $q
                // même mois imputé…
                ->whereBetween('periode_debut', [
                    $moisDebut->toDateString(),
                    $moisDebut->endOfMonth()->toDateString(),
                ])
                // …ou dates qui se chevauchent.
                ->orWhere(fn ($w) => $w
                    ->where('periode_debut', '<=', $fin)
                    ->where('periode_fin', '>=', $debut)))
            ->orderBy('periode_debut')
            ->first();
    }

    /** Le message de refus — nomme la ligne qui occupe déjà la période. */
    public static function message(Depense $existante): string
    {
        return __('This group already has a teacher payment for :mois (:reference, from :debut to :fin, :statut). Cancel or refuse it before entering another one.', [
            'mois' => CarbonImmutable::parse($existante->periode_debut)->locale('fr')->translatedFormat('F Y'),
            'reference' => $existante->reference,
            'debut' => $existante->periode_debut->format('d/m/Y'),
            'fin' => $existante->periode_fin->format('d/m/Y'),
            'statut' => $existante->statut,
        ]);
    }
}
