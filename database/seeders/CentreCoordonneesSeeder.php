<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Etablissement;
use Illuminate\Database\Seeder;

/**
 * Coordonnées légales et postales des centres GLS (idempotent).
 *
 * Pourquoi ce seeder existe : `ReferentialDataSeeder` crée les 7 centres avec
 * seulement `ville` + `siege_social`, laissant `adresse`, `ice`, `telephone`
 * et `email` à NULL. Or les reçus les impriment déjà
 * (`resources/views/backoffice/encaissements/recu-groupe.blade.php` — en-tête
 * FR/AR + la ligne « ICE : … », `recu.blade.php`, `recu-pdf.blade.php`), donc
 * chaque reçu sortait sans adresse, sans téléphone et avec « ICE : — ».
 *
 * ⚠ L'ICE est le MÊME pour tous les centres : GLS est une seule entité
 * juridique, l'Identifiant Commun de l'Entreprise est attaché à la société,
 * pas à l'établissement. La colonne reste par centre parce que le reçu la lit
 * depuis le centre du paiement.
 *
 * Source des adresses/téléphones : l'ancien projet GLS
 * (`Projects\Gls`) — `database/seeders/SitesTableSeeder.php` pour la liste,
 * `resources/views/backoffice/attestations/pdf.blade.php` et
 * `resources/views/frontoffice/sites.blade.php` (site public) pour la forme
 * « papier à en-tête ». Là où les deux divergent (Marrakech, Kénitra, Agadir)
 * on retient la version en-tête/site public : c'est celle qui est déjà
 * imprimée sur les documents officiels et elle est corroborée par deux
 * sources contre une.
 *
 * ⚠ Ce seeder ne REMPLACE jamais une valeur déjà saisie : Paramètres →
 * Établissements permet de corriger une adresse, et un re-run (chaque deploy
 * lance `db:seed`) ne doit pas écraser cette correction. Seuls les champs
 * vides sont remplis. Seule exception : l'ICE, qui est une donnée légale
 * unique et est donc toujours réaligné.
 *
 * Lancer seul :  php artisan db:seed --class=CentreCoordonneesSeeder
 */
final class CentreCoordonneesSeeder extends Seeder
{
    /** Identifiant Commun de l'Entreprise — société GLS, identique partout. */
    public const ICE = '002331724000041';

    /** Adresse e-mail de contact commune à tous les centres. */
    private const EMAIL = 'info@gls-sprachzentrum.ma';

    /**
     * nom_centre => [adresse, téléphone]
     *
     * « GLS Online » est volontairement absent : aucune adresse postale ni
     * téléphone propre n'existe pour ce centre dans aucune source. Il reçoit
     * quand même l'ICE et l'e-mail ci-dessous (voir run()) — inventer une
     * adresse serait pire que de la laisser vide.
     *
     * @var array<string, array{string, string}>
     */
    private const COORDONNEES = [
        'GLS Marrakech' => [
            '3ème étage Bureau 28, Immeuble Espace, Av. Yacoub El Mansour, Marrakech 40000',
            '+212 80-86 639 83',
        ],
        'GLS Rabat' => [
            'Avenue Fal Ould Oumeir, Immeuble 77, 1er étage N°1, Agdal, Rabat',
            '+212 80-85 735 09',
        ],
        'GLS Casablanca' => [
            '14 Bd de Paris, 1er étage N°8, Casablanca 20000',
            '+212 80-85 497 17',
        ],
        'GLS Kénitra' => [
            '4ème étage, résidence Nezha, Av. Mohamed V, Kenitra 14000',
            '+212 80-86 514 50',
        ],
        'GLS Agadir' => [
            '2ème étage, Av. Massoude Al Wafkaoui, Agadir 80000',
            '+212 606-48 40 51',
        ],
        'GLS Salé' => [
            'Avenue Mohamed V, Rue Halima N°12 Diyar, Salé',
            '+212 80-85 40 625',
        ],
    ];

    public function run(): void
    {
        Etablissement::query()->each(function (Etablissement $centre): void {
            // L'ICE est légal et unique : on le réaligne toujours, y compris
            // pour GLS Online qui n'a pas d'adresse propre.
            $centre->ice = self::ICE;

            // Les autres champs ne comblent qu'un vide (cf. en-tête de classe).
            if (blank($centre->email)) {
                $centre->email = self::EMAIL;
            }

            [$adresse, $telephone] = self::COORDONNEES[$centre->nom_centre] ?? [null, null];

            if ($adresse !== null && blank($centre->adresse)) {
                $centre->adresse = $adresse;
            }

            if ($telephone !== null && blank($centre->telephone)) {
                $centre->telephone = $telephone;
            }

            $centre->save();
        });
    }
}
