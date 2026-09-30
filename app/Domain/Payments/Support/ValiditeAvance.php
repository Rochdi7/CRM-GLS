<?php

declare(strict_types=1);

namespace App\Domain\Payments\Support;

use App\Models\Encaissement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

/**
 * La durée de validité d'une AVANCE — la SEULE définition (29/09/2026).
 *
 * Le problème : une avance restait applicable indéfiniment. De l'argent
 * vieux de plusieurs mois pouvait être posé sur un frais « quand on veut »,
 * sans que personne ne s'en aperçoive — c'est par là qu'une fraude passe
 * sans bruit. Désormais l'argent non affecté a 14 jours pour être appliqué ;
 * passé ce délai, `AppliquerAvance` le refuse.
 *
 * Deux formes, une seule règle :
 *  - PHP (`expireLe()` / `estExpiree()` / `assertNonExpiree()`) pour les
 *    actions, sur la ligne verrouillée ;
 *  - SQL (`SQL_EXPIRE_LE`) pour les listes et leurs filtres.
 * Ne jamais recopier `date_paiement + 14` ailleurs : un écran finirait par
 * offrir ce que l'action refuse.
 *
 * D'OÙ PART LE DÉLAI :
 *  - une avance SAISIE comme telle → de sa `date_paiement` ;
 *  - un paiement DÉTACHÉ de son frais (conversion, changement de groupe,
 *    retrait d'un frais payé…) → du jour du DÉTACHEMENT. Partir de la date
 *    de paiement ferait naître l'avance déjà expirée : un changement de
 *    groupe deux mois après le paiement libérerait de l'argent inutilisable ;
 *  - une ligne SANS valeur stockée (antérieure à la colonne) → de sa
 *    `date_paiement`, à la lecture. Aucun backfill.
 * Le tampon est posé par `Encaissement::booted()`, donc aucun chemin de
 * détachement ne peut l'oublier.
 *
 * LES DEUX GESTES DU GUICHET QUE LE DÉLAI FERME (précision du propriétaire,
 * 29/09/2026 : « passé 2 semaines, restreint — seul le super-admin garde
 * l'accès ») :
 *  - APPLIQUER une avance de plus de 14 jours (`estExpiree()`) ;
 *  - CONVERTIR en avance un paiement de plus de 14 jours
 *    (`tropAncienPourConversion()`), mesuré sur sa `date_paiement`. Sans
 *    cette seconde borne la première ne protège rien : il suffirait de
 *    reconvertir un vieux paiement pour lui rendre 14 jours.
 * Le SUPER-ADMIN passe outre les deux (`PERMISSION`, `superAdminOnly()`), et
 * le journal le note. Les flux SYSTÈME qui détachent un paiement (changement
 * de groupe, retrait d'un frais payé, clôture) ne sont pas bornés : ils
 * libèrent de l'argent qui resterait sinon accroché à une ligne invisible
 * (§11), et ce ne sont pas des gestes libres du guichet.
 *
 * CE QUE L'EXPIRATION NE FAIT PAS : elle ne supprime rien, ne bouge aucune
 * caisse et ne touche pas au montant restant — l'argent reste reçu et reste
 * REMBOURSABLE (le remboursement a ses propres permissions et sa propre
 * borne de solde). Un super-admin peut aussi RENDRE une avance au guichet
 * pour 14 jours par `ProlongerValiditeAvance` (motif obligatoire, journalisé).
 *
 * Tests : tests/Feature/Backoffice/Finance/AvanceExpirationTest.php
 */
final class ValiditeAvance
{
    public const DUREE_JOURS = 14;

    /** Passer outre le délai — convertir, appliquer, prolonger. Super-admin uniquement. */
    public const PERMISSION = 'payments.override-advance-expiry';

    /**
     * La date d'expiration d'une avance, en SQL — valeur stockée, sinon
     * `date_paiement + 14 jours`. `date + integer` donne une `date` en
     * PostgreSQL (§17 : pas de fonction enveloppant une colonne indexée dans
     * un filtre de masse — ici la colonne n'est lue que sur des avances déjà
     * filtrées).
     */
    public const SQL_EXPIRE_LE = '(coalesce(encaissements.avance_expire_le, encaissements.date_paiement + '.self::DUREE_JOURS.'))';

    public static function echeanceDepuis(CarbonInterface $depuis): CarbonImmutable
    {
        return CarbonImmutable::parse($depuis->toDateString())->addDays(self::DUREE_JOURS);
    }

    /** Null seulement pour une ligne qui n'est pas une avance. */
    public static function expireLe(Encaissement $encaissement): ?CarbonImmutable
    {
        if (! $encaissement->isAvance()) {
            return null;
        }

        if ($encaissement->avance_expire_le !== null) {
            return CarbonImmutable::parse($encaissement->avance_expire_le->toDateString());
        }

        return $encaissement->date_paiement !== null
            ? self::echeanceDepuis($encaissement->date_paiement)
            : null;
    }

    /**
     * Expirée DÈS le jour d'expiration : payée le 01/10, l'avance s'applique
     * jusqu'au 14/10 inclus et est refusée à partir du 15/10.
     */
    public static function estExpiree(Encaissement $encaissement): bool
    {
        $expireLe = self::expireLe($encaissement);

        return $expireLe !== null && $expireLe->lessThanOrEqualTo(CarbonImmutable::today());
    }

    public static function assertNonExpiree(Encaissement $encaissement, string $champ = 'avance'): void
    {
        if (! self::estExpiree($encaissement)) {
            return;
        }

        throw ValidationException::withMessages([
            $champ => __('This advance expired on :date (:jours days after it was received or released) and can no longer be applied.', [
                'date' => self::expireLe($encaissement)?->format('d/m/Y'),
                'jours' => self::DUREE_JOURS,
            ]),
        ]);
    }

    /** Seul le titulaire de PERMISSION (super-admin) passe outre le délai. */
    public static function peutOutrepasser(?User $user): bool
    {
        return $user?->can(self::PERMISSION) ?? false;
    }

    /**
     * Un paiement posé sur un frais est trop ancien pour être converti en
     * avance par le guichet : 14 jours ou plus depuis sa `date_paiement`.
     * Même borne que `estExpiree()` (le jour J compte comme dépassé).
     */
    public static function tropAncienPourConversion(Encaissement $encaissement): bool
    {
        if ($encaissement->date_paiement === null) {
            return false;
        }

        return self::echeanceDepuis($encaissement->date_paiement)->lessThanOrEqualTo(CarbonImmutable::today());
    }

    /** Le motif affiché à l'écran ET renvoyé par l'action — une seule phrase. */
    public static function motifConversionRefusee(Encaissement $encaissement): string
    {
        return __('Payment :reference dates from :date, more than :jours days ago: it can no longer be converted into an advance.', [
            'reference' => $encaissement->reference,
            'date' => $encaissement->date_paiement?->format('d/m/Y'),
            'jours' => self::DUREE_JOURS,
        ]);
    }
}
