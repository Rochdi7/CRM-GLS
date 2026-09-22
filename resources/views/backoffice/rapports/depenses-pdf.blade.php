{{--
    « Liste des Dépenses » — gabarit PDF (mPDF), servi par RapportPdfRenderer.

    ⚠ Rendu en TROIS sections ($section = debut | lignes | fin), comme les
    autres rapports : mPDF analyse le HTML avec PCRE et refuse au-delà de
    `pcre.backtrack_limit`. Le tableau reste donc OUVERT entre les appels —
    d'où les balises volontairement non refermées à la fin de « debut ».

    ⚠ L'identité du centre et le pied (signature, cachet, pagination) ne sont
    PAS ici : ce sont RapportPdfRenderer::entetePage() / ::pied(), répétés sur
    CHAQUE page.

    ⚠ La colonne « Statut » porte la distinction qui fait tout le sens de ce
    document : seule une dépense « Approuvée » a fait SORTIR de l'argent de la
    caisse. « En attente » retient l'argent dans le tiroir, « Refusée » ne l'a
    jamais bougé, « Annulée » l'a rendu par écriture compensatoire. Le total du
    bas ne compte donc QUE les approuvées, et il est volontairement INFÉRIEUR à
    la somme de la colonne « Montant » — c'est ce qui le rend égal à l'argent
    réellement payé par les caisses, et la colonne Statut est ce qui permet au
    lecteur de le vérifier ligne par ligne.

    Construit en tableaux, jamais en flexbox : mPDF ne le supporte pas.
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

        .titre {
            background: {{ $couleurFond }};
            text-align: center;
            font-size: 13pt;
            padding: 6pt 0;
            margin: 4pt auto 12pt;
            width: 46%;
        }

        .filtres { margin-bottom: 8pt; font-size: 8.5pt; }
        .filtres div { padding: 1pt 0; }

        .rapport { font-size: 8pt; }
        .rapport th,
        .rapport td {
            border: 0.6pt solid #333;
            /* Rembourrage serré : onze colonnes dont trois portent du texte
               long (type, description, caisse). Même contrainte que le relevé
               des encaissements — à 4pt de padding ces cellules passaient à la
               ligne et chaque ligne du tableau faisait deux lignes de haut. */
            padding: 2.5pt 2pt;
            text-align: center;
            vertical-align: middle;
        }
        /* L'en-tête d'une colonne étroite ne se coupe pas en deux. */
        .rapport th.nowrap { white-space: nowrap; }
        .rapport thead th {
            background: {{ $couleurFond }};
            font-weight: normal;
            font-size: 8.5pt;
        }
        /* Le type, la description, la caisse et le groupe se lisent alignés au
           fil du texte ; les autres colonnes restent centrées. */
        .rapport td.txt { text-align: left; }
        /* Un montant se lit aligné à droite : c'est ce qui permet de comparer
           les ordres de grandeur en descendant la colonne, et de contrôler le
           total du bas. */
        .rapport td.num { text-align: right; }

        .vide { padding: 14pt 0; text-align: center; font-style: italic; }
    </style>
</head>
<body>
    <div class="titre">{{ $titre }}</div>

    <div class="filtres">
        <div>Période : Du {{ $periodeDebut }} au {{ $periodeFin }}</div>
        {{-- Les filtres réellement appliqués, nommés par le contrôleur. --}}
        @foreach ($filtresAppliques as $libelle => $valeur)
            <div>{{ $libelle }} : {{ $valeur }}</div>
        @endforeach
    </div>

    @if ($lignes->isEmpty())
        <div class="vide">Aucune dépense sur cette période.</div>
    @else
        {{-- Tableau volontairement laissé OUVERT (voir l'entête du fichier). --}}
        <table class="rapport">
            <thead>
                {{-- ⚠ La somme de ces largeurs DOIT faire exactement 100 %.
                     Au-delà, mPDF élargit le tableau hors de la zone
                     imprimable : le tableau déborde à droite et l'en-tête de
                     page — donc le LOGO — se retrouve rogné au bord de la
                     feuille. Recompter la somme après tout ajout ou retrait de
                     colonne. --}}
                <tr repeat_header="1">
                    <th style="width:4%;" class="nowrap">N°&nbsp;d'ordre</th>
                    <th style="width:7%;">Réf.</th>
                    <th style="width:13%;">Type</th>
                    <th style="width:18%;">Description</th>
                    <th style="width:9%;">Montant</th>
                    <th style="width:7%;">Méthode</th>
                    <th style="width:8%;">Statut</th>
                    <th style="width:11%;">Caisse</th>
                    <th style="width:11%;">Groupe</th>
                    <th style="width:6%;">Date</th>
                    <th style="width:6%;">Opérateur</th>
                </tr>
            </thead>
            <tbody>
    @endif
@endif

@if ($section === 'lignes')
                @foreach ($lignes as $ligne)
                    {{-- Une ligne sur deux légèrement teintée, alternée sur le
                         N° et NON sur $loop : les lignes arrivent par tranches
                         et $loop repartirait de zéro à chaque tranche — deux
                         lignes de même teinte se toucheraient à la jointure. --}}
                    <tr @if ((int) $ligne['numero'] % 2 === 0) style="background:#f4f4f4;" @endif>
                        <td>{{ $ligne['numero'] }}</td>
                        <td>{{ $ligne['reference'] }}</td>
                        {{-- Les libellés sortent en CAPITALES comme à l'écran
                             (app.css uppercase les cellules de tableau) : le
                             document doit se lire comme la page. La donnée
                             stockée garde sa casse — CLAUDE.md §5. --}}
                        <td class="txt">{{ mb_strtoupper($ligne['type']) }}</td>
                        <td class="txt">{{ mb_strtoupper($ligne['description']) }}</td>
                        <td class="num">{{ $ligne['montant'] }}</td>
                        <td>{{ $ligne['methode'] }}</td>
                        {{-- « Approuvée » / « En attente » / « Refusée » /
                             « Annulée » : jamais abrégé, c'est la colonne qui
                             dit si cet argent est sorti de la caisse. --}}
                        <td>{{ $ligne['statut'] }}</td>
                        <td class="txt">{{ mb_strtoupper($ligne['caisse']) }}</td>
                        <td class="txt">{{ mb_strtoupper($ligne['groupe']) }}</td>
                        <td>{{ $ligne['date'] }}</td>
                        <td>{{ mb_strtoupper($ligne['operateur']) }}</td>
                    </tr>
                @endforeach
@endif

@if ($section === 'fin')
    @if ($lignes->isNotEmpty())
            {{-- Le total vient du SERVEUR, calculé en SQL sur tout l'ensemble
                 filtré (GetDepensesReport::total()), et il ne compte QUE les
                 dépenses approuvées : c'est l'argent réellement sorti des
                 caisses, la même définition que la liste Dépenses et que
                 toutes les lectures de caisse. Il n'est pas ré-additionné ici
                 à partir des lignes rendues — sur un document signé, un total
                 recalculé par le gabarit finirait par diverger de l'écran.

                 ⚠ Dernière ligne du <tbody>, JAMAIS un <tfoot> : mPDF met le
                 pied de tableau en tampon et rejoue l'en-tête de PAGE en le
                 posant, ce qui dessine le LOGO DEUX FOIS au même endroit
                 (mesuré sur le relevé des encaissements en comptant les
                 opérateurs « Do » du flux : 2 avec <tfoot>, 1 sans). --}}
            <tr>
                <td colspan="4" style="text-align:right;background:{{ $couleurFond }};font-weight:bold;">Total approuvé</td>
                <td class="num" style="background:{{ $couleurFond }};font-weight:bold;">{{ $totalMontant }}</td>
                <td colspan="6" style="background:{{ $couleurFond }};"></td>
            </tr>
            </tbody>
        </table>
    @endif
</body>
</html>
@endif
