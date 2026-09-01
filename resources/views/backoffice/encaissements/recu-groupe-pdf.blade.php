{{--
    Reçu GROUPÉ — variante PDF (mPDF) de recu-groupe.blade.php, servie par le
    lien WhatsApp d'un envoi groupé (Frontoffice\RecuGroupeController) et par
    l'email groupé. Même règle que le couple recu / recu-pdf : la version
    navigateur reste l'imprimable du guichet, celle-ci est construite en
    TABLEAUX parce que mPDF ne supporte pas flexbox, et l'arabe y est façonné
    nativement (autoScriptToLang / autoLangToFont).

    Ne pas fusionner avec recu-pdf.blade.php : le bloc central diffère (une
    ligne par frais + total au lieu d'un couple Frais/Montant unique), et
    l'étudiant qui reçoit son reçu groupé doit tenir EXACTEMENT le document
    qu'on lui a imprimé.
--}}
@php
    $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ' '), '0'), '.');
    $modes = $encaissements
        ->map(fn ($e) => $e->methode
            .($e->numero_cheque ? ' N° :'.$e->numero_cheque : '')
            .($e->banque ? ' - '.$e->banque : ''))
        ->unique()
        ->implode(' / ');
    $dates = $encaissements->map(fn ($e) => $e->date_paiement?->format('d/m/Y'))->filter()->unique();
    $dateAffichee = $dates->count() === 1 ? $dates->first() : $dates->first().' — '.$dates->last();
    $logoPath = public_path('assets/images/logo/gls-noir.png');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu groupé {{ $reference }}</title>
    <style>
        body {
            font-family: sans-serif;
            color: #111;
            font-size: 10pt;
            line-height: 1.45;
        }

        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }

        .centre-fr { width: 38%; text-align: left; }
        .logo-cell { width: 24%; text-align: center; vertical-align: middle; }
        .centre-ar { width: 38%; text-align: right; }

        /* ⚠ U+200E (LEFT-TO-RIGHT MARK) autour du numéro : posé après un
           libellé arabe, l'algorithme bidi l'inverse (« +212 80-86 639 83 »
           → « 83 639 86-80 212+ »). Invisible mais porteur de sens — ne pas
           le retirer en « nettoyant » le gabarit. */
        .tel-ltr { direction: ltr; }

        .ice-line {
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            padding: 3pt 0 5pt;
        }

        .recu-num-table {
            background: #eeecec;
            border: 1px solid #444;
            margin-bottom: 9pt;
        }
        .recu-num-table td { padding: 4pt 8pt; font-weight: bold; }
        .num-lbl-fr { width: 32%; text-align: right; }
        .num-val { text-align: center; }
        .num-lbl-ar { width: 32%; text-align: left; }

        .row-table td { padding: 2pt 0; }
        .row-fr { width: 30%; text-align: left; }
        .row-val { text-align: center; font-weight: bold; }
        .row-ar { width: 30%; text-align: right; }

        .frais-table {
            margin: 8pt 0;
            font-size: 9pt;
        }
        .frais-table th,
        .frais-table td {
            border: 1px solid #999;
            padding: 3pt 5pt;
        }
        .frais-table th {
            background: #eeecec;
            font-weight: bold;
            text-align: left;
        }
        .frais-table .num { text-align: right; }
        .frais-table tfoot td {
            font-weight: bold;
            background: #f6f6f6;
        }

        /* Mention légale : les frais réglés ne sont pas remboursables. */
        .mention-nr {
            margin-top: 10pt;
            font-size: 8.5pt;
            font-weight: bold;
        }
        .mention-nr .nr-fr { text-align: left; }
        .mention-nr .nr-ar { text-align: right; }

        .signature {
            border-top: 1px solid #333;
            margin-top: 14pt;
            padding-top: 4pt;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <td class="centre-fr">
                {{ $centre?->nom_centre ?? 'GLS' }}<br>
                @if ($centre?->adresse)
                    {{ $centre->adresse }}<br>
                @endif
                @if ($centre?->telephone)
                    Tél. :{{ $centre->telephone }}
                @endif
            </td>
            <td class="logo-cell">
                @if (is_file($logoPath))
                    <img src="{{ $logoPath }}" style="height: 13mm;" alt="GLS">
                @endif
            </td>
            <td class="centre-ar">
                @if ($centre?->ville)
                    {{ $centre->ville }}<br>
                @endif
                @if ($centre?->telephone)
                    الهاتف : <span class="tel-ltr">&#8206;{{ $centre->telephone }}&#8206;</span>
                @endif
            </td>
        </tr>
    </table>

    <div class="ice-line">ICE : {{ $centre?->ice ?? '—' }}</div>

    <table class="recu-num-table">
        <tr>
            <td class="num-lbl-fr">Reçu N° :</td>
            <td class="num-val">{{ $encaissements->pluck('reference')->implode(' / ') }}</td>
            <td class="num-lbl-ar">وصل رقم :</td>
        </tr>
    </table>

    <table class="row-table">
        <tr>
            <td class="row-fr">Année scolaire</td>
            <td class="row-val">{{ $anneeScolaire ?? '—' }}</td>
            <td class="row-ar">السنة الدراسية</td>
        </tr>
        <tr>
            <td class="row-fr">Prénom et nom</td>
            <td class="row-val">{{ $student?->nomComplet() ?? '—' }}</td>
            <td class="row-ar">اسم و نسب التلميذ(ة)</td>
        </tr>
        <tr>
            <td class="row-fr">Matricule</td>
            <td class="row-val">{{ $student?->reference ?? '—' }}</td>
            <td class="row-ar">رقــم التسجيل</td>
        </tr>
        <tr>
            <td class="row-fr">Niveau</td>
            <td class="row-val">{{ $niveau ?? '—' }}</td>
            <td class="row-ar">المستوى</td>
        </tr>
    </table>

    <table class="frais-table">
        <thead>
            <tr>
                <th>Frais / مصاريف التمدرس</th>
                <th class="num">Montant (DH)</th>
                <th class="num">Reste (DH)</th>
                <th class="num">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($encaissements as $e)
                @php
                    $fee = $e->fee;
                    $reste = $fee
                        ? max(0, (float) $fee->montant - (float) ($payeParFee[$fee->id] ?? 0))
                        : 0;
                @endphp
                <tr>
                    <td>{{ $e->libelleFrais() }}</td>
                    <td class="num">{{ $fmt($e->montant) }}</td>
                    <td class="num">{{ $fmt($reste) }}</td>
                    <td class="num">{{ $e->date_paiement?->format('d/m/Y') ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total / المجموع</td>
                <td class="num">{{ $fmt($montantTotal) }}</td>
                <td class="num"></td>
                <td class="num"></td>
            </tr>
        </tfoot>
    </table>

    <table class="row-table">
        <tr>
            <td class="row-fr">Mode de paiement</td>
            <td class="row-val">{{ $modes }}</td>
            <td class="row-ar">طريقة الأداء</td>
        </tr>
        <tr>
            <td class="row-fr">Date de paiement</td>
            <td class="row-val">{{ $dateAffichee ?: '—' }}</td>
            <td class="row-ar">تاريخ الأداء</td>
        </tr>
    </table>

    <table class="mention-nr">
        <tr>
            <td class="nr-fr">Les frais versés ne sont pas remboursables.</td>
            <td class="nr-ar">المبالغ المدفوعة غير قابلة للاسترجاع.</td>
        </tr>
    </table>

    <div class="signature">L'administration</div>
</body>
</html>
