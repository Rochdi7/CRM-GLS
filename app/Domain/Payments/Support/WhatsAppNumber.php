<?php

declare(strict_types=1);

namespace App\Domain\Payments\Support;

use App\Models\Student;

/**
 * Normalise un numéro marocain (ou étranger) vers la forme que le lien
 * click-to-chat exige : CHIFFRES SEULS, indicatif pays inclus, sans « + »,
 * sans espace ni séparateur (api.whatsapp.com/send?phone=212648430612).
 *
 * Le CRM stocke des formats hétérogènes — l'import de l'ancien CRM a laissé
 * des `+212…`, des `0…` nationaux et des numéros étrangers bruts (`33…`).
 * Un « + » ou une espace laissés dans l'URL cassent l'ouverture du chat, donc
 * tout passe par ici : ne jamais construire ce paramètre à la main.
 *
 * ⚠ On normalise la REPRÉSENTATION, jamais la donnée stockée : la colonne
 * garde le format saisi par l'utilisateur (même règle que l'affichage en
 * majuscules des tableaux, CLAUDE.md §5).
 */
final class WhatsAppNumber
{
    /** Indicatif par défaut quand le numéro est saisi en national (0XXXXXXXXX). */
    private const DEFAULT_COUNTRY = '212';

    /**
     * Le numéro joignable d'un étudiant : `whatsapp` d'abord, `telephone`
     * en repli.
     *
     * Le repli n'est pas un raccourci : au 31/08/2026 la colonne `whatsapp`
     * est vide pour les 4 995 étudiants de production alors que 4 913 ont un
     * `telephone`. Sans lui, l'action serait masquée partout.
     */
    public static function forStudent(?Student $student): ?string
    {
        if ($student === null) {
            return null;
        }

        return self::normalize($student->whatsapp) ?? self::normalize($student->telephone);
    }

    /** Chiffres seuls avec indicatif, ou null si rien d'exploitable. */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        // 00212… : forme internationale à l'ancienne, le 00 remplace le « + ».
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            // 0648430612 → national marocain : on préfixe l'indicatif.
            $digits = self::DEFAULT_COUNTRY.substr($digits, 1);
        }

        // Trop court pour être un numéro joignable (saisie partielle, « - »
        // laissé par l'ancien CRM, extension interne…).
        if (strlen($digits) < 11 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }
}
