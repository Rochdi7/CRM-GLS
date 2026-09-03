<?php

declare(strict_types=1);

namespace App\Domain\Reports\Exports;

use App\Models\Etablissement;
use Illuminate\Support\Collection;

/**
 * Rendu PDF des rapports (mPDF) — la SEULE fabrique du document, comme
 * RecuPdfRenderer l'est pour les reçus. Le gabarit est paramétré par la clé du
 * rapport, donc un futur rapport (Finance, Caisse, Employés…) n'ajoute qu'une
 * vue Blade et son entrée dans RapportCatalogue : jamais une seconde
 * configuration mPDF, qui dériverait de celle-ci.
 *
 * mPDF et non dompdf : c'est déjà le moteur PDF de l'app (RecuPdfRenderer), et
 * son façonnage natif des glyphes (autoScriptToLang / autoLangToFont) garde la
 * porte ouverte à un rapport arabe — le gabarit sait le rendre, seul le
 * sélecteur de langue a été retiré de l'écran.
 *
 * Format A4 PAYSAGE : le rapport de référence porte neuf colonnes, qui ne
 * tiennent pas en portrait sans écraser les noms.
 *
 * ⚠ Coûteux (quelques secondes sur un gros lot) : à n'appeler que dans la
 * requête qui sert le fichier, jamais dans une boucle de liste.
 */
final class RapportPdfRenderer
{
    /**
     * Nombre de lignes rendues par appel à WriteHTML().
     *
     * ⚠ Ne PAS supprimer ce découpage. mPDF analyse le HTML avec PCRE, et
     * au-delà de `pcre.backtrack_limit` (1 000 000 par défaut) il lève
     * « The HTML code size is larger than pcre.backtrack_limit ». Un rapport
     * d'année pleine dépasse largement ce seuil : le premier essai a échoué
     * net sur 2 241 inscriptions. On écrit donc le document en tranches — la
     * limite reste PHP, pas un plafond arbitraire du métier, et un rapport
     * complet doit sortir quelle que soit sa taille.
     */
    private const LIGNES_PAR_TRANCHE = 250;

    /**
     * Le document est volontairement NOIR ET GRIS, comme le rapport de
     * référence : un document administratif qu'on signe et qu'on tamponne, pas
     * une page web. La seule couleur est le très léger gris d'une ligne sur
     * deux (voir le gabarit), qui aide l'œil à suivre neuf colonnes serrées.
     * Une version colorée aux teintes de la marque a été essayée puis retirée.
     */
    private const COULEUR_FOND = '#eeecec';

    /**
     * Contenu binaire du PDF, prêt à streamer.
     *
     * @param  Collection<int, array<string, mixed>>  $lignes
     * @param  array<string, mixed>  $entete
     */
    public function render(string $vue, Collection $lignes, array $entete): string
    {
        $this->relacherLesLimites($lignes->count());

        $mpdf = $this->mpdf();

        // Le gris des fonds voyage jusqu'au gabarit : une seule définition,
        // partagée par le titre et l'en-tête des colonnes.
        $donnees = [
            ...$entete,
            'couleurFond' => self::COULEUR_FOND,
        ];

        // En-tête ET pied sont posés par l'API, PAS par des balises dans le
        // gabarit : le document est écrit en plusieurs WriteHTML() (voir
        // LIGNES_PAR_TRANCHE), et une balise <htmlpageheader>/<htmlpagefooter>
        // rencontrée dans le premier fragment n'était plus appliquée aux pages
        // suivantes. Surtout, un en-tête écrit dans le CORPS du document ne
        // s'imprime qu'une fois, en haut de la page 1 : l'identité du centre et
        // le logo manquaient donc sur toutes les pages suivantes, alors qu'un
        // document signé et tamponné doit s'identifier sur chacune de ses
        // feuilles (une page détachée du lot ne dirait plus de quel centre elle
        // vient). SetHTMLHeader()/SetHTMLFooter() valent pour le document
        // entier, quel que soit le découpage.
        $mpdf->SetHTMLHeader($this->entetePage($entete['centre'] ?? null));
        $mpdf->SetHTMLFooter($this->pied($entete['editeLe'] ?? ''));

        // 1) L'ouverture du document : styles, en-tête du centre, titre, rappel
        // des filtres, et l'ouverture du tableau (ou le message « aucune
        // ligne » quand il n'y en a pas).
        $mpdf->WriteHTML(view($vue, [
            ...$donnees,
            'section' => 'debut',
            'lignes' => $lignes,
        ])->render());

        // 2) Les lignes, par tranches — chacune est un fragment <tr> autonome,
        // que mPDF empile dans le tableau resté ouvert.
        if ($lignes->isNotEmpty()) {
            foreach ($lignes->chunk(self::LIGNES_PAR_TRANCHE) as $tranche) {
                $mpdf->WriteHTML(view($vue, [
                    ...$donnees,
                    'section' => 'lignes',
                    'lignes' => $tranche,
                ])->render());
            }
        }

        // 3) La fermeture du tableau et du document.
        $mpdf->WriteHTML(view($vue, [
            ...$donnees,
            'section' => 'fin',
            'lignes' => $lignes,
        ])->render());

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /**
     * L'en-tête répété sur CHAQUE page : identité du centre à gauche, logo à
     * droite.
     *
     * ⚠ Répété sur toutes les pages, et non écrit une fois en tête du corps :
     * une page détachée du lot doit continuer à dire de quel centre elle vient.
     * C'est aussi ce que fait le rapport de référence.
     */
    private function entetePage(?Etablissement $centre): string
    {
        $nom = htmlspecialchars(mb_strtoupper($centre?->nom_centre ?? 'GLS'), ENT_QUOTES);
        $adresse = $centre?->adresse ? htmlspecialchars($centre->adresse, ENT_QUOTES).'<br>' : '';
        $tel = $centre?->telephone ? 'Tél. '.htmlspecialchars($centre->telephone, ENT_QUOTES) : '';

        $logoPath = public_path('assets/images/logo/gls-noir.png');
        $logo = is_file($logoPath)
            ? '<img src="'.$logoPath.'" style="height:34pt;">'
            : '';

        return <<<HTML
            <table style="width:100%;border-collapse:collapse;font-size:8.5pt;">
                <tr>
                    <td style="border:none;padding:0;vertical-align:top;">
                        <span style="font-weight:bold;font-size:11pt;">{$nom}</span><br>
                        {$adresse}{$tel}
                    </td>
                    <td style="border:none;padding:0;width:22%;text-align:right;vertical-align:top;">{$logo}</td>
                </tr>
            </table>
            HTML;
    }

    /**
     * Le pied répété sur CHAQUE page : les deux cadres à signer, puis le numéro
     * de page et la date d'édition. {PAGENO}/{nbpg} sont des jetons mPDF,
     * remplacés à la fermeture du document.
     *
     * ⚠ Dessiné en <table> à cellules SANS bordure, jamais en div flottantes :
     * dans un pied mPDF, `float` n'est pas appliqué et les deux blocs
     * s'empilaient l'un sur l'autre. Les cadres eux-mêmes sont des div à
     * bordure explicite, que `simpleTables` (activé pour la mémoire) ne touche
     * pas — une bordure de <td> aurait, elle, été uniformisée.
     */
    private function pied(string $editeLe): string
    {
        return <<<HTML
            <table style="width:100%;border-collapse:collapse;font-size:8pt;">
                <tr>
                    <td style="width:45%;border:none;padding:0;vertical-align:top;">
                        Signature de la direction
                        <div style="border:0.6pt solid #333;height:38pt;margin-top:2pt;"></div>
                    </td>
                    <td style="width:10%;border:none;padding:0;"></td>
                    <td style="width:45%;border:none;padding:0;vertical-align:top;">
                        Cachet de l'établissement
                        <div style="border:0.6pt solid #333;height:38pt;margin-top:2pt;"></div>
                    </td>
                </tr>
            </table>
            <table style="width:100%;border-collapse:collapse;font-size:8pt;border-top:0.8pt solid #333;">
                <tr>
                    <td style="border:none;padding:3pt 0 0;text-align:left;">Page : {PAGENO} / {nbpg}</td>
                    <td style="border:none;padding:3pt 0 0;text-align:center;">{$editeLe}</td>
                    <td style="border:none;padding:3pt 0 0;width:25%;"></td>
                </tr>
            </table>
            HTML;
    }

    /**
     * Desserre mémoire et temps d'exécution le temps de fabriquer le document.
     *
     * Mesuré sur les données réelles (2 241 inscriptions d'une année) : mPDF
     * tamponne le tableau entier pour calculer la largeur des colonnes, donc le
     * coût monte plus vite que le nombre de lignes — ~114 Mo / 9 s à 500
     * lignes, ~240 Mo / 30 s à 2 000. Les valeurs par défaut de PHP (128 Mo,
     * 30 s) tombent donc vers ~700 lignes, c'est-à-dire sur un rapport d'année
     * pleine parfaitement légitime.
     *
     * On desserre plutôt que de TRONQUER : un rapport amputé de ses dernières
     * lignes sans le dire est bien pire qu'un rapport lent — il serait signé et
     * tamponné comme s'il était complet. Le volume reste borné en amont par la
     * fenêtre de dates, qui est obligatoire.
     *
     * Rien n'est desserré sous PHPUnit : `set_time_limit()` y casse une
     * exécution complète de la suite sous Windows, et aucun test ne rend un
     * document assez gros pour en avoir besoin.
     */
    private function relacherLesLimites(int $lignes): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        // 60 s couvrent largement le plus gros rapport mesuré (30 s) ; au-delà,
        // c'est une requête qui a dérapé et il vaut mieux qu'elle meure.
        set_time_limit(120);

        // Palier, et non « illimité » : une fuite ou un filtre absurde doit
        // toujours finir par heurter un plafond.
        $requis = $lignes > 1500 ? '512M' : '256M';

        if ($this->limiteActuelleEnOctets() < $this->enOctets($requis)) {
            ini_set('memory_limit', $requis);
        }
    }

    /** -1 (illimité) compte comme le plus haut plafond possible : on n'y touche pas. */
    private function limiteActuelleEnOctets(): int
    {
        $limite = (string) ini_get('memory_limit');

        return $limite === '-1' ? PHP_INT_MAX : $this->enOctets($limite);
    }

    /** « 256M » → octets. */
    private function enOctets(string $valeur): int
    {
        $valeur = trim($valeur);
        $unite = strtolower(substr($valeur, -1));
        $nombre = (int) $valeur;

        return match ($unite) {
            'g' => $nombre * 1024 * 1024 * 1024,
            'm' => $nombre * 1024 * 1024,
            'k' => $nombre * 1024,
            default => $nombre,
        };
    }

    /**
     * L'en-tête commun à tous les rapports : identité du centre, période,
     * filtres rappelés, date d'édition. Assemblé ici pour que deux rapports ne
     * puissent pas afficher leur périmètre différemment.
     *
     * @return array<string, mixed>
     */
    public function entete(
        string $titre,
        ?Etablissement $centre,
        string $periodeDebut,
        string $periodeFin,
        ?string $groupeLabel = null,
        ?string $statutLabel = null,
    ): array {
        return [
            'titre' => $titre,
            'centre' => $centre,
            'periodeDebut' => $periodeDebut,
            'periodeFin' => $periodeFin,
            'groupeLabel' => $groupeLabel,
            'statutLabel' => $statutLabel,
            'editeLe' => now()->format('d/m/Y H:i'),
        ];
    }

    /** UNE seule configuration mPDF pour tous les rapports. */
    private function mpdf(): \Mpdf\Mpdf
    {
        $tempDir = storage_path('app/mpdf');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        return new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            // Marge haute large : l'en-tête (centre + logo + filet) est un
            // htmlpageheader, il s'imprime DANS cette marge — sur CHAQUE page.
            'margin_top' => 28,
            // Marge basse large : le pied (signature + cachet + pagination) est
            // un htmlpagefooter, il s'imprime DANS cette marge.
            'margin_bottom' => 30,
            'margin_header' => 8,
            'margin_footer' => 5,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => $tempDir,
            // ⚠ Les deux options qui rendent un rapport d'année pleine
            // possible. mPDF met TOUT un <table> en mémoire avant de le
            // dessiner (il lui faut la largeur des colonnes) : sans elles,
            // 2 241 inscriptions épuisent les 128 Mo de PHP et la requête meurt
            // en « Allowed memory size exhausted ». `packTableData` compresse
            // les cellules tamponnées, `simpleTables` évite de mémoriser les
            // bordures cellule par cellule — la grille du rapport est
            // uniforme, elle n'en a pas besoin. Ne pas les retirer « pour
            // gagner en vitesse » : le rapport ne sortirait plus du tout.
            'packTableData' => true,
            'simpleTables' => true,
        ]);
    }
}
