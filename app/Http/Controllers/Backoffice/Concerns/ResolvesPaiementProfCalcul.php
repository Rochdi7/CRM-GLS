<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Concerns;

use App\Domain\Payroll\Queries\GetPaiementProfCalcul;
use App\Domain\Payroll\Support\MoisDeGroupe;
use App\Models\Employee;
use App\Models\Group;
use Illuminate\Http\Request;

/**
 * « Calcul paiement prof » — résolution des paramètres de la query string
 * (groupe, enseignant, période début/fin, heures) en UN calcul, ou `null` tant que le
 * trio n'est pas complet.
 *
 * Partagé par l'écran dédié (`PaiementProfController`) et par l'onglet
 * « Paiements prof » de Gestion des dépenses (`DepenseController@index`,
 * 30/09/2026) : la MÊME lecture des paramètres, la MÊME garde de portée
 * (l'id du groupe vient du navigateur, §11) — jamais recopiée, sinon l'un
 * des deux écrans finirait par accepter un groupe que l'autre refuse.
 *
 * ⚠ Ne lit qu'en base et ne touche AUCUNE caisse : un calcul n'est pas un
 * paiement.
 *
 * @return array{calcul: ?array, filters: array<string, string>}
 */
trait ResolvesPaiementProfCalcul
{
    /**
     * @param  array<string, string>  $keys  nom du paramètre de requête pour
     *                                       chacune des 4 clés `groupFilter`,
     *                                       `enseignantFilter`, `mois`, `heures`.
     */
    private function resolvePaiementProfCalcul(Request $request, GetPaiementProfCalcul $query, array $keys = []): array
    {
        $keys = [
            'groupFilter' => 'groupFilter',
            'enseignantFilter' => 'enseignantFilter',
            'mois' => 'mois',
            'debut' => 'debut',
            'fin' => 'fin',
            'heures' => 'heures',
            ...$keys,
        ];

        $groupFilter = (string) $request->string($keys['groupFilter']);
        $enseignantFilter = (string) $request->string($keys['enseignantFilter']);
        $mois = (string) $request->string($keys['mois']);
        $debut = (string) $request->string($keys['debut']);
        $fin = (string) $request->string($keys['fin']);
        $heuresFilter = (string) $request->string($keys['heures']);

        $group = null;
        $enseignant = null;
        $calcul = null;

        if ($groupFilter !== '') {
            $group = Group::query()->with('enseignant')->find((int) $groupFilter);

            if ($group !== null) {
                // L'id vient de la query string, donc du navigateur : la
                // portée se rejoue là où il entre (§11).
                $this->assertGroupInContext($request, $group, $keys['groupFilter']);
            }
        }

        if ($group !== null && $enseignantFilter !== '') {
            $enseignant = Employee::query()->find((int) $enseignantFilter);
        }

        // Dates libres (début/fin) ; `mois` reste lu pour les anciens liens.
        // Une période invalide (inversée, trop longue) ne calcule rien.
        $periode = $group !== null ? MoisDeGroupe::resoudre($group, $mois, $debut, $fin) : null;

        if ($group !== null && $enseignant !== null && $periode !== null) {
            $calcul = $query(
                group: $group,
                enseignant: $enseignant,
                mois: $periode,
                heures: $heuresFilter !== '' ? (float) $heuresFilter : null,
            );
        }

        return [
            'calcul' => $calcul,
            'filters' => [
                'groupFilter' => $groupFilter,
                'enseignantFilter' => $enseignantFilter,
                'debut' => $periode?->debut->toDateString() ?? $debut,
                'fin' => $periode?->fin->toDateString() ?? $fin,
                'heures' => $heuresFilter,
                // Aide de saisie côté écran uniquement (durée d'une séance,
                // « 2h30 ») : le serveur ne s'en sert pas, mais la page
                // initialise son état depuis `filters`.
                'dureeSeance' => '',
            ],
        ];
    }
}
