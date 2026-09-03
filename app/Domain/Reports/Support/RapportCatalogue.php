<?php

declare(strict_types=1);

namespace App\Domain\Reports\Support;

use App\Domain\Reports\Queries\GetInscriptionsReport;

/**
 * Le catalogue des rapports — source de vérité UNIQUE des onglets de « Gestion
 * des rapports » et des rapports que chaque onglet propose.
 *
 * La page React lit ce catalogue tel quel (prop `onglets`) : les onglets ne
 * sont donc pas écrits en dur dans le composant, et un rapport ne peut pas
 * être offert par le sélecteur sans exister côté serveur. Ajouter un rapport =
 * une entrée ici + sa requête Domain + sa vue Blade, jamais une modification
 * du composant.
 *
 * Les domaines sans rapport implémenté restent listés ici avec `rapports: []` :
 * ils décrivent le périmètre visé et servent de point d'accroche au prochain
 * rapport. La PAGE, elle, ne dessine plus de barre d'onglets — six onglets qui
 * ne mènent nulle part sont du décor (demande utilisateur) : elle aplatit ce
 * catalogue en un seul sélecteur « Rapport », qui ne montre donc que les
 * domaines réellement servis.
 */
final class RapportCatalogue
{
    /**
     * Les onglets de la page, dans l'ordre de l'écran de référence.
     *
     * @return list<array{key: string, label: string, rapports: list<array{value: string, label: string}>}>
     */
    public static function onglets(): array
    {
        return [
            [
                'key' => 'inscriptions',
                'label' => 'Inscriptions',
                'rapports' => [
                    [
                        'value' => GetInscriptionsReport::KEY,
                        'label' => 'Liste des inscriptions',
                    ],
                ],
            ],
            ['key' => 'admissions', 'label' => 'Admissions & CRM', 'rapports' => []],
            ['key' => 'finance', 'label' => 'Finance & Paiements', 'rapports' => []],
            ['key' => 'caisse', 'label' => 'Caisse', 'rapports' => []],
            ['key' => 'depenses', 'label' => 'Dépenses', 'rapports' => []],
            ['key' => 'vie-scolaire', 'label' => 'Vie scolaire', 'rapports' => []],
            ['key' => 'employes', 'label' => 'Employés', 'rapports' => []],
        ];
    }

    /** Les clés de rapport réellement servies — ce que le contrôleur accepte. */
    public static function clesImplementees(): array
    {
        return [GetInscriptionsReport::KEY];
    }

    /** Le titre imprimé en tête du PDF et du classeur. */
    public static function titre(string $cle): string
    {
        return match ($cle) {
            GetInscriptionsReport::KEY => "Liste d'inscriptions",
            default => 'Rapport',
        };
    }

    /** La vue Blade du PDF. */
    public static function vuePdf(string $cle): string
    {
        return match ($cle) {
            GetInscriptionsReport::KEY => 'backoffice.rapports.inscriptions-pdf',
            default => throw new \InvalidArgumentException("Rapport inconnu : {$cle}"),
        };
    }

    /**
     * Les colonnes du classeur Excel — mêmes libellés et même ORDRE que les
     * colonnes du PDF, sinon le classeur et le document ne se liraient pas
     * pareil. `key` désigne une clé des lignes rendues par la requête Domain.
     *
     * @return list<array{key: string, label: string, width?: float}>
     */
    public static function colonnesExcel(string $cle): array
    {
        return match ($cle) {
            GetInscriptionsReport::KEY => [
                ['key' => 'numero', 'label' => 'N°', 'width' => 6.0],
                ['key' => 'reference', 'label' => 'Réf', 'width' => 14.0],
                ['key' => 'etudiant', 'label' => 'Étudiant', 'width' => 28.0],
                ['key' => 'telephone', 'label' => 'Téléphone', 'width' => 16.0],
                ['key' => 'groupe', 'label' => 'Groupe', 'width' => 24.0],
                ['key' => 'statut', 'label' => 'Statut', 'width' => 12.0],
                ['key' => 'dateInscription', 'label' => "Date d'inscription", 'width' => 16.0],
                ['key' => 'dateDebut', 'label' => 'Date de début', 'width' => 14.0],
                ['key' => 'dateFin', 'label' => 'Date de fin', 'width' => 14.0],
            ],
            default => throw new \InvalidArgumentException("Rapport inconnu : {$cle}"),
        };
    }

    /**
     * Le nom de fichier téléchargé — préfixe du rapport + fenêtre de dates,
     * pour que deux exports de périodes différentes ne s'écrasent pas dans le
     * dossier Téléchargements de l'utilisateur.
     */
    public static function filename(string $cle, string $extension, string $dateFrom, string $dateTo): string
    {
        $prefixe = match ($cle) {
            GetInscriptionsReport::KEY => 'liste-inscriptions',
            default => 'rapport',
        };

        $fenetre = trim(($dateFrom !== '' ? $dateFrom : '').'_'.($dateTo !== '' ? $dateTo : ''), '_');

        return trim($prefixe.($fenetre !== '' ? "_{$fenetre}" : ''), '-_').'.'.$extension;
    }
}
