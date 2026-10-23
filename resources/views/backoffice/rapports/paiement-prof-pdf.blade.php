{{--
    « Calcul paiement prof » - gabarit PDF (mPDF), servi par RapportPdfRenderer.

    ⚠ Rendu en TROIS sections ($section = debut | lignes | fin), comme les
    autres rapports : mPDF analyse le HTML avec PCRE et refuse au-delà de
    `pcre.backtrack_limit`. Le tableau reste donc OUVERT entre les appels -
    d'où les balises volontairement non refermées à la fin de « debut ».

    ⚠ L'identité du centre et le pied (signature, cachet, pagination) ne sont
    PAS ici : ce sont RapportPdfRenderer::entetePage() / ::pied(), répétés sur
    CHAQUE page.

    ⚠ Ce document imprime un CALCUL, pas un paiement : rien n'a été écrit ni
    débité. Les montants viennent de GetPaiementProfCalcul (paliers de
    présences, mode et taux de la fiche employé) - le gabarit n'additionne
    rien, le total du bas est celui du calcul.

    Mode horaire : aucune ligne par étudiant (la paie y est taux × heures) -
    le résumé et le total suffisent.

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
            padding: 2.5pt 2pt;
            text-align: center;
            vertical-align: middle;
        }
        .rapport th.nowrap { white-space: nowrap; }
        .rapport thead th {
            background: {{ $couleurFond }};
            font-weight: normal;
            font-size: 8.5pt;
        }
        .rapport td.txt { text-align: left; }
        .rapport td.num { text-align: right; }

        .vide { padding: 14pt 0; text-align: center; font-style: italic; }
        .note { margin-bottom: 8pt; font-size: 8pt; font-style: italic; }
    </style>
</head>
<body>
    <div class="titre">{{ $titre }}</div>

    <div class="filtres">
        <div>Période : Du {{ $periodeDebut }} au {{ $periodeFin }}</div>
        @foreach ($filtresAppliques as $libelle => $valeur)
            <div>{{ $libelle }} : {{ $valeur }}</div>
        @endforeach
        {{-- Le résumé du calcul : enseignant, mode, taux, séances… - ce qui
             permet de refaire le total à la main. --}}
        @foreach ($resume as $libelle => $valeur)
            <div>{{ $libelle }} : {{ $valeur }}</div>
        @endforeach
    </div>

    <div class="note">Calcul proposé à partir des appels saisis - ce document n'est pas un paiement.</div>

    @if ($lignes->isEmpty())
        @if ($avertissement !== '')
            <div class="vide">{{ $avertissement }}</div>
        @elseif ($totalMontant !== '')
            {{-- Mode horaire : pas de détail par étudiant, le total seul. --}}
            <table class="rapport" style="width:40%;">
                <tr>
                    <td style="text-align:right;background:{{ $couleurFond }};font-weight:bold;">Total proposé</td>
                    <td class="num" style="background:{{ $couleurFond }};font-weight:bold;">{{ $totalMontant }}</td>
                </tr>
            </table>
        @else
            <div class="vide">Aucun calcul pour ces filtres.</div>
        @endif
    @else
        {{-- Tableau volontairement laissé OUVERT (voir l'entête du fichier). --}}
        <table class="rapport">
            <thead>
                {{-- ⚠ La somme de ces largeurs DOIT faire exactement 100 %
                     (sinon le tableau déborde et rogne le logo). --}}
                <tr repeat_header="1">
                    <th style="width:7%;" class="nowrap">N°&nbsp;d'ordre</th>
                    <th style="width:33%;">Étudiant</th>
                    <th style="width:15%;">Statut d'inscription</th>
                    <th style="width:10%;">Présences</th>
                    <th style="width:10%;">Absences</th>
                    <th style="width:12%;">Palier payé</th>
                    <th style="width:13%;">Montant</th>
                </tr>
            </thead>
            <tbody>
    @endif
@endif

@if ($section === 'lignes')
                @foreach ($lignes as $ligne)
                    <tr @if ((int) $ligne['numero'] % 2 === 0) style="background:#f4f4f4;" @endif>
                        <td>{{ $ligne['numero'] }}</td>
                        <td class="txt">{{ mb_strtoupper($ligne['etudiant']) }}</td>
                        <td>{{ $ligne['statut'] }}</td>
                        <td>{{ $ligne['presences'] }}</td>
                        <td>{{ $ligne['absences'] }}</td>
                        <td>{{ $ligne['palier'] }}</td>
                        <td class="num">{{ $ligne['montant'] }}</td>
                    </tr>
                @endforeach
@endif

@if ($section === 'fin')
    @if ($lignes->isNotEmpty())
            {{-- ⚠ Dernière ligne du <tbody>, JAMAIS un <tfoot> : mPDF rejoue
                 l'en-tête de PAGE en posant un pied de tableau, ce qui dessine
                 le LOGO DEUX FOIS. --}}
            <tr>
                <td colspan="6" style="text-align:right;background:{{ $couleurFond }};font-weight:bold;">Total proposé</td>
                <td class="num" style="background:{{ $couleurFond }};font-weight:bold;">{{ $totalMontant }}</td>
            </tr>
            </tbody>
        </table>
    @endif
</body>
</html>
@endif
