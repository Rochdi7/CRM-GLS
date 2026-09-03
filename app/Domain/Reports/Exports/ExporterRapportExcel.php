<?php

declare(strict_types=1);

namespace App\Domain\Reports\Exports;

use App\Models\Etablissement;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderStyle;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rapports → .xlsx. Écrit EXACTEMENT les lignes que le PDF imprime et que
 * l'aperçu affiche (le même Collection sort de la même requête Domain), donc
 * le classeur ne peut pas contredire le document ni l'écran — c'est la règle
 * qu'applique déjà ExporterMatriceAbsences.
 *
 * Générique par construction : titre + bloc d'en-tête + colonnes + lignes.
 * Un futur rapport passe ses propres colonnes, il n'écrit pas un second
 * exporteur.
 *
 * OpenSpout est déjà une dépendance (côté lecture pour l'import) ; il STREAME,
 * donc une longue période ne charge jamais le classeur entier en mémoire. Le
 * fichier est bâti dans un fichier temporaire puis streamé, plutôt qu'écrit
 * directement sur php://output : une exception en cours d'écriture rend alors
 * une page d'erreur propre au lieu d'un téléchargement corrompu.
 */
final class ExporterRapportExcel
{
    /**
     * @param  Collection<int, array<string, mixed>>  $lignes
     * @param  list<array{key: string, label: string, width?: float}>  $colonnes
     * @param  list<string>  $sousTitres  Lignes de contexte (période, groupe, statut…)
     */
    public function __invoke(
        string $titre,
        ?Etablissement $centre,
        Collection $lignes,
        array $colonnes,
        array $sousTitres,
        string $filename,
    ): StreamedResponse {
        // OpenSpout assemble le .xlsx comme un zip dans son dossier temporaire,
        // et ZipArchive::close() finit par un rename. Sous Windows ce rename
        // est refusé à la racine du TEMP utilisateur (le défaut) : le dossier
        // de travail est donc un dossier à nous sous storage/. Dossier et
        // fichier fini y vivent, et sont nettoyés plus bas.
        $temp = storage_path('app/exports');

        if (! is_dir($temp)) {
            mkdir($temp, 0775, true);
        }

        $path = $temp.DIRECTORY_SEPARATOR.'rapport_'.bin2hex(random_bytes(8)).'.xlsx';

        // Les styles OpenSpout sont immuables — chaque with*() rend un NOUVEAU
        // Style ; on les construit donc une fois, hors de la boucle.
        $titreStyle = (new Style())->withFontBold(true)->withFontSize(14);
        $centreStyle = (new Style())->withFontBold(true);
        $contexteStyle = (new Style())->withFontBold(true);

        $enteteStyle = (new Style())
            ->withFontBold(true)
            ->withBackgroundColor('D9E1F2')
            ->withBorder($this->border());

        $celluleStyle = (new Style())->withBorder($this->border());

        $options = new Options(tempFolder: $temp);

        // Largeurs de colonnes : sans elles le classeur sort en colonnes
        // étroites et chaque nom est tronqué à l'ouverture.
        foreach ($colonnes as $index => $colonne) {
            $options->setColumnWidth($colonne['width'] ?? 18.0, $index + 1);
        }

        $writer = new Writer($options);
        $writer->openToFile($path);

        // --- Bloc d'en-tête, comme le classeur de référence : titre, centre,
        // adresse, date d'édition, puis les lignes de contexte. ---
        $writer->addRow(Row::fromValuesWithStyle([$titre], $titreStyle));
        $writer->addRow(Row::fromValuesWithStyle([$centre?->nom_centre ?? 'GLS'], $centreStyle));

        if ($centre?->adresse) {
            $writer->addRow(Row::fromValues([$centre->adresse]));
        }

        if ($centre?->telephone) {
            $writer->addRow(Row::fromValues(['Tél. '.$centre->telephone]));
        }

        $writer->addRow(Row::fromValues(['Date : '.now()->format('d/m/Y H:i')]));
        $writer->addRow(Row::fromValues(['']));

        foreach ($sousTitres as $sousTitre) {
            $writer->addRow(Row::fromValuesWithStyle([$sousTitre], $contexteStyle));
        }

        $writer->addRow(Row::fromValues(['']));

        // --- Ligne d'en-tête des colonnes ---
        $writer->addRow(Row::fromValuesWithStyle(
            array_map(static fn (array $c): string => $c['label'], $colonnes),
            $enteteStyle,
        ));

        // --- Les lignes du rapport ---
        foreach ($lignes as $ligne) {
            $cells = [];

            foreach ($colonnes as $colonne) {
                $valeur = $ligne[$colonne['key']] ?? '';

                // Les nombres restent des nombres (le N° doit se trier comme
                // un nombre) ; tout le reste part en chaîne — un téléphone
                // « 212705674602 » écrit en numérique repartirait en notation
                // scientifique, et une référence « 382SL126 » n'est pas un
                // nombre de toute façon.
                $cells[] = is_int($valeur) || is_float($valeur)
                    ? Cell::fromValue($valeur, $celluleStyle)
                    : Cell::fromValue((string) $valeur, $celluleStyle);
            }

            $writer->addRow(new Row($cells));
        }

        $writer->close();

        return response()->streamDownload(
            function () use ($path): void {
                $handle = fopen($path, 'rb');

                if ($handle !== false) {
                    fpassthru($handle);
                    fclose($handle);
                }

                @unlink($path);
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        );
    }

    /** Grille fine grise autour de chaque cellule, comme le classeur de référence. */
    private function border(): Border
    {
        return new Border(
            new BorderPart(BorderName::TOP, '808080', BorderWidth::THIN, BorderStyle::SOLID),
            new BorderPart(BorderName::RIGHT, '808080', BorderWidth::THIN, BorderStyle::SOLID),
            new BorderPart(BorderName::BOTTOM, '808080', BorderWidth::THIN, BorderStyle::SOLID),
            new BorderPart(BorderName::LEFT, '808080', BorderWidth::THIN, BorderStyle::SOLID),
        );
    }
}
