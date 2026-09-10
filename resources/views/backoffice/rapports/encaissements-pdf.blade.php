{{--
    « Relevé des Encaissements » — gabarit PDF (mPDF), servi par
    RapportPdfRenderer.

    ⚠ Rendu en TROIS sections ($section = debut | lignes | fin), comme les
    autres rapports : mPDF analyse le HTML avec PCRE et refuse au-delà de
    `pcre.backtrack_limit`. Le tableau reste donc OUVERT entre les appels —
    d'où les balises volontairement non refermées à la fin de « debut ».

    ⚠ L'identité du centre et le pied (signature, cachet, pagination) ne sont
    PAS ici : ce sont RapportPdfRenderer::entetePage() / ::pied(), répétés sur
    CHAQUE page.

    ⚠ La colonne « Type » porte la distinction qui fait tout le sens de ce
    document : « Avance » = argent reçu et pas encore affecté à un frais,
    « Règlement » = argent posé sur un frais. Les lignes d'application d'avance
    ne sont pas dans $lignes (voir GetEncaissementsReport), donc la somme de la
    colonne « Montant » est exactement l'argent entré en caisse.

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
            /* Rembourrage serré : dix colonnes dont trois portent du texte
               long (« Frais d'inscription A1/A2/B1 », 28 caractères mesurés
               sur les données réelles). À 4pt de padding et 8.5pt de police,
               ces cellules passaient à la ligne et chaque ligne du tableau
               faisait DEUX lignes de haut — le document débordait sur une
               deuxième page dès 15 lignes, là où le rapport des inscriptions
               en tient 25. */
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
        /* Le nom et le frais se lisent alignés au fil du texte, comme sur le
           document de référence ; les autres colonnes restent centrées. */
        .rapport td.txt { text-align: left; }
        /* Un montant se lit aligné à droite : c'est ce qui permet de comparer
           les ordres de grandeur d'une ligne à l'autre en descendant la
           colonne, et de contrôler le total du bas. */
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
        <div class="vide">Aucun encaissement sur cette période.</div>
    @else
        {{-- Tableau volontairement laissé OUVERT (voir l'entête du fichier). --}}
        <table class="rapport">
            <thead>
                {{-- ⚠ La somme de ces largeurs DOIT faire exactement 100 %.
                     À 106 % (dix colonnes posées à vue), mPDF élargit le
                     tableau au-delà de la zone imprimable : le tableau déborde
                     à droite et l'en-tête de page — donc le LOGO — se retrouve
                     rogné au bord de la feuille. Le rapport des inscriptions,
                     lui, totalise bien 100 % sur neuf colonnes. Recompter la
                     somme après tout ajout ou retrait de colonne. --}}
                <tr repeat_header="1">
                    <th style="width:4%;" class="nowrap">N°&nbsp;d'ordre</th>
                    <th style="width:7%;">Réf.</th>
                    <th style="width:16%;">Élève / Payeur</th>
                    <th style="width:7%;">Type</th>
                    <th style="width:9%;">Montant</th>
                    <th style="width:7%;">Méthode</th>
                    <th style="width:17%;">Frais</th>
                    <th style="width:14%;">Groupe</th>
                    <th style="width:7%;">Date</th>
                    <th style="width:12%;">Opérateur</th>
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
                        {{-- Les noms sortent en CAPITALES comme à l'écran
                             (app.css uppercase les cellules de tableau) : le
                             document doit se lire comme la page. La donnée
                             stockée garde sa casse — CLAUDE.md §5. --}}
                        <td class="txt">{{ mb_strtoupper($ligne['etudiant']) }}</td>
                        {{-- « Avance » / « Règlement » : jamais abrégé, c'est
                             la colonne qui dit si cet argent est déjà affecté
                             à un frais. --}}
                        <td>{{ $ligne['type'] }}</td>
                        <td class="num">{{ $ligne['montant'] }}</td>
                        <td>{{ $ligne['methode'] }}</td>
                        <td class="txt">{{ mb_strtoupper($ligne['frais']) }}</td>
                        <td class="txt">{{ mb_strtoupper($ligne['groupe']) }}</td>
                        <td>{{ $ligne['date'] }}</td>
                        <td>{{ mb_strtoupper($ligne['operateur']) }}</td>
                    </tr>
                @endforeach
@endif

@if ($section === 'fin')
    @if ($lignes->isNotEmpty())
            {{-- Le total vient du SERVEUR, calculé en SQL sur tout l'ensemble
                 filtré (GetEncaissementsReport::total()) : il n'est pas
                 ré-additionné ici à partir des lignes rendues. Sur un document
                 signé, un total recalculé par le gabarit finirait par diverger
                 de celui qu'affiche l'écran.

                 ⚠ Dernière ligne du <tbody>, JAMAIS un <tfoot> : mPDF met le
                 pied de tableau en tampon et rejoue l'en-tête de PAGE en le
                 posant, ce qui dessinait le LOGO DEUX FOIS au même endroit
                 (vérifié en comptant les opérateurs « Do » du flux : 2 avec
                 <tfoot>, 1 sans — le rapport des inscriptions, qui n'a pas de
                 pied de tableau, n'en a jamais eu qu'un). Le total n'a pas
                 besoin d'être répété en bas de chaque page : c'est un total
                 de DOCUMENT, il se lit une fois, à la fin. --}}
            <tr>
                <td colspan="4" style="text-align:right;background:{{ $couleurFond }};font-weight:bold;">Total encaissé</td>
                <td class="num" style="background:{{ $couleurFond }};font-weight:bold;">{{ $totalMontant }}</td>
                <td colspan="5" style="background:{{ $couleurFond }};"></td>
            </tr>
            </tbody>
        </table>
    @endif
</body>
</html>
@endif
