{{--
    « Liste des inscriptions » — gabarit PDF (mPDF), servi par
    RapportPdfRenderer.

    ⚠ Le gabarit est rendu en TROIS sections ($section = debut | lignes | fin),
    et non d'une seule pièce : mPDF analyse le HTML avec PCRE et refuse au-delà
    de `pcre.backtrack_limit` (« The HTML code size is larger than… »), ce qui
    fait échouer net un rapport d'année pleine. Le renderer écrit donc
    l'ouverture, puis les lignes par tranches, puis la fermeture. Le tableau
    reste ouvert entre les appels — d'où les balises volontairement non
    refermées à la fin de « debut ».

    ⚠ L'identité du centre (nom, adresse, téléphone, logo) et le pied
    (signature, cachet, pagination) ne sont PAS dans ce fichier : ce sont
    RapportPdfRenderer::entetePage() / ::pied(), posés par
    SetHTMLHeader()/SetHTMLFooter() pour être répétés sur CHAQUE page. Écrits
    ici, ils ne s'imprimeraient qu'en page 1 — une page détachée du lot ne
    dirait plus de quel centre elle vient.

    Construit en tableaux pour la grille, jamais en flexbox : mPDF ne supporte
    pas flexbox (même contrainte que recu-pdf.blade.php).
--}}

@if ($section === 'debut')
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $titre }}</title>
    <style>
        body {
            font-family: sans-serif;
            color: #111;
            font-size: 9pt;
            line-height: 1.4;
        }

        table { width: 100%; border-collapse: collapse; }

        /* --- Titre encadré gris, centré, comme le rapport de référence --- */
        .titre {
            background: {{ $couleurFond }};
            text-align: center;
            font-size: 13pt;
            padding: 6pt 0;
            margin: 4pt auto 12pt;
            width: 46%;
        }

        /* --- Rappel des filtres appliqués : le document dit toujours de
               quel périmètre il parle (période, groupe, statut). --- */
        .filtres { margin-bottom: 8pt; font-size: 8.5pt; }
        .filtres div { padding: 1pt 0; }

        /* --- Tableau du rapport --- */
        .rapport { font-size: 8.5pt; }
        .rapport th,
        .rapport td {
            border: 0.6pt solid #333;
            padding: 4pt 3pt;
            text-align: center;
            vertical-align: middle;
        }
        .rapport thead th {
            background: {{ $couleurFond }};
            font-weight: normal;
            font-size: 9pt;
        }
        /* Le nom se lit aligné au fil du texte, comme sur le document de
           référence ; les autres colonnes restent centrées. */
        .rapport td.txt { text-align: left; }

        .vide { padding: 14pt 0; text-align: center; font-style: italic; }
    </style>
</head>
<body>
    <div class="titre">{{ $titre }}</div>

    <div class="filtres">
        <div>Période : Du {{ $periodeDebut }} au {{ $periodeFin }}</div>
        @if ($groupeLabel !== null)
            <div>Groupe : {{ $groupeLabel }}</div>
        @endif
        @if ($statutLabel !== null)
            <div>Statut : {{ $statutLabel }}</div>
        @endif
    </div>

    @if ($lignes->isEmpty())
        <div class="vide">Aucune inscription sur cette période.</div>
    @else
        {{-- Tableau volontairement laissé OUVERT : les tranches de lignes
             suivantes viennent s'y empiler (voir l'entête du fichier). --}}
        <table class="rapport">
            <thead>
                {{-- repeat_header : mPDF redessine cette ligne en haut de
                     chaque page, comme le document de référence. --}}
                <tr repeat_header="1">
                    <th style="width:5%;">N°</th>
                    <th style="width:10%;">Réf</th>
                    <th style="width:20%;">Étudiant</th>
                    <th style="width:13%;">Téléphone</th>
                    <th style="width:14%;">Groupe</th>
                    <th style="width:9%;">Statut</th>
                    <th style="width:10%;">Date d'inscription</th>
                    <th style="width:9%;">Date de début</th>
                    <th style="width:10%;">Date de fin</th>
                </tr>
            </thead>
            <tbody>
    @endif
@endif

@if ($section === 'lignes')
                @foreach ($lignes as $ligne)
                    {{-- Une ligne sur deux est légèrement teintée : sur neuf
                         colonnes serrées, c'est ce qui empêche l'œil de sauter
                         d'une ligne à l'autre en lisant la date de fin.
                         Alternée sur le N° de la ligne et NON sur $loop : les
                         lignes arrivent par tranches (LIGNES_PAR_TRANCHE), et
                         $loop repartirait de zéro à chaque tranche — deux
                         lignes de même teinte se seraient touchées à la
                         jointure. --}}
                    <tr @if ((int) $ligne['numero'] % 2 === 0) style="background:#f4f4f4;" @endif>
                        <td>{{ $ligne['numero'] }}</td>
                        <td>{{ $ligne['reference'] }}</td>
                        {{-- Les noms sortent en CAPITALES comme à l'écran
                             (app.css uppercase les cellules de tableau) : le
                             document doit se lire comme la page. La donnée
                             stockée garde sa casse — CLAUDE.md §5. --}}
                        <td class="txt">{{ mb_strtoupper($ligne['etudiant']) }}</td>
                        <td>{{ $ligne['telephone'] }}</td>
                        <td>{{ mb_strtoupper($ligne['groupe']) }}</td>
                        <td>{{ $ligne['statut'] }}</td>
                        <td>{{ $ligne['dateInscription'] }}</td>
                        <td>{{ $ligne['dateDebut'] }}</td>
                        <td>{{ $ligne['dateFin'] }}</td>
                    </tr>
                @endforeach
@endif

@if ($section === 'fin')
    @if ($lignes->isNotEmpty())
            </tbody>
        </table>
    @endif
</body>
</html>
@endif
