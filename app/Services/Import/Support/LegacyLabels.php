<?php

declare(strict_types=1);

namespace App\Services\Import\Support;

use App\Console\Commands\ReconcilierPaiementsLegacy;
use App\Models\Encaissement;

/**
 * The legacy export's own vocabulary: how its « Frais » and « Méthode »
 * cells map onto the CRM's catalogue and payment methods.
 *
 * These lived as private constants inside EncaissementImporter until
 * `paiements:reconcilier` needed to read the SAME files with the SAME
 * meaning. A second copy would drift the day a new alias is added, and the
 * reconciler would then report phantom écarts on rows the importer handled
 * correctly — so the two share this one source.
 *
 * @see EncaissementImporter for the import path
 * @see ReconcilierPaiementsLegacy for the audit path
 */
final class LegacyLabels
{
    /** Source label (lowercased) => catalogue fee name. */
    public const array FRAIS_ALIASES = [
        "frais d'inscription" => "Frais d'inscription A1/A2/B1",
        "frais d'inscription 1" => "Frais d'inscription A1/A2/B1",
        "frais d'inscription a1" => "Frais d'inscription A1/A2/B1",
        "frais d'inscription a2" => "Frais d'inscription A1/A2/B1",
        "frais d'inscription b1" => "Frais d'inscription A1/A2/B1",
        "frais d'inscription 2" => "Frais d'inscription B2",
    ];

    /** File label => Encaissement::METHODE_* — "Virement bancaire" is NOT a literal match. */
    public const array METHODE_MAP = [
        'Espèces' => Encaissement::METHODE_ESPECES,
        'TPE' => Encaissement::METHODE_TPE,
        'Virement bancaire' => Encaissement::METHODE_VIREMENT,
        'Chèque' => Encaissement::METHODE_CHEQUE,
        // The legacy CRM writes "Chéque" (e-acute) — a misspelling of
        // "Chèque". Both map to the same method.
        'Chéque' => Encaissement::METHODE_CHEQUE,
    ];

    /**
     * The export writes a literal "-" for a missing value in EVERY column,
     * so an empty Frais cell means « no fee » — the payment is an avance.
     */
    public static function estVide(string $valeur): bool
    {
        $valeur = trim($valeur);

        return $valeur === '' || $valeur === '-';
    }

    /**
     * Canonical catalogue name for a source label, alias applied. A
     * comma-separated cell is handled part by part by the caller.
     */
    public static function fraisCanonique(string $label): string
    {
        $label = CellNormalizer::text($label);

        return self::FRAIS_ALIASES[mb_strtolower($label)] ?? $label;
    }

    /** Comparison key: accents/case/punctuation folded away. */
    public static function cle(string $nom): string
    {
        $nom = mb_strtolower(CellNormalizer::text($nom));
        $nom = strtr($nom, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e',
            'ê' => 'e', 'ë' => 'e', 'î' => 'i', 'ï' => 'i', 'ô' => 'o',
            'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
        ]);

        return (string) preg_replace('/[^a-z0-9]/', '', $nom);
    }

    public static function methode(string $label): ?string
    {
        return self::METHODE_MAP[CellNormalizer::text($label)] ?? null;
    }
}
