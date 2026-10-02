<?php

declare(strict_types=1);

namespace App\Domain\Payments\Support;

use App\Models\Encaissement;
use Illuminate\Support\Collection;

/**
 * Règle UNIQUE du reçu groupé, partagée par l'impression, le PDF WhatsApp et
 * le lien signé du frontoffice.
 *
 * Un reçu groupé couvre les paiements d'UN SEUL ÉTUDIANT — il peut mêler
 * plusieurs de ses inscriptions (02/10/2026 : « Frais d'inscription B2 » d'un
 * dossier 2026/2027 avec « Frais d'inscription A1/A2/B1 » de 2025/2026 était
 * refusé alors que c'est le même étudiant au même guichet). L'en-tête liste
 * alors chaque année scolaire et chaque groupe. Une avance (sans frais, donc
 * sans inscription) n'entre jamais dans un reçu groupé.
 */
final class RecuGroupeLot
{
    /** @param  Collection<int, Encaissement>  $encaissements */
    public static function estValide(Collection $encaissements): bool
    {
        if ($encaissements->isEmpty()) {
            return false;
        }

        if ($encaissements->contains(fn (Encaissement $e): bool => $e->fee?->inscription_id === null)) {
            return false;
        }

        return $encaissements->pluck('student_id')->unique()->count() === 1;
    }

    /** @param  Collection<int, Encaissement>  $encaissements */
    public static function anneeScolaire(Collection $encaissements): ?string
    {
        return self::joindre($encaissements->map(fn (Encaissement $e) => $e->fee?->inscription?->anneeScolaire?->nom));
    }

    /** @param  Collection<int, Encaissement>  $encaissements */
    public static function niveau(Collection $encaissements): ?string
    {
        return self::joindre($encaissements->map(fn (Encaissement $e) => $e->fee?->inscription?->group?->nom))
            ?? $encaissements->first()?->student?->niveau;
    }

    /** @param  Collection<int, Encaissement>  $encaissements */
    public static function centre(Collection $encaissements): mixed
    {
        $first = $encaissements->first();

        return $first?->fee?->inscription?->etablissement ?? $first?->student?->etablissement;
    }

    /** @param  Collection<int, mixed>  $valeurs */
    private static function joindre(Collection $valeurs): ?string
    {
        $uniques = $valeurs->filter(fn ($v) => $v !== null && $v !== '')->unique()->values();

        return $uniques->isEmpty() ? null : $uniques->implode(' / ');
    }
}
