{{--
    Reçu de paiement — variante PDF (mPDF) du reçu imprimable
    recu.blade.php, utilisée pour la pièce jointe email
    (App\Domain\Payments\Mail\EncaissementRecuMail). Même design (en-tête
    FR/logo/AR, ligne ICE, bande « Reçu N° », lignes libellé FR / valeur /
    libellé AR, signature) mais construit en tableaux : mPDF ne supporte pas
    flexbox. L'arabe est façonné par mPDF (autoScriptToLang/autoLangToFont)
    — c'est la raison du passage dompdf → mPDF, dompdf n'ayant aucun support
    RTL. Ne pas réutiliser cette vue pour l'impression navigateur : la page
    imprimable reste recu.blade.php.
--}}
@php
    $montantAffiche = rtrim(rtrim(number_format((float) $encaissement->montant, 2, '.', ' '), '0'), '.').' DH';
    $modePaiement = $encaissement->methode
        .($encaissement->numero_cheque ? ' N° :'.$encaissement->numero_cheque : '')
        .($encaissement->banque ? ' - '.$encaissement->banque : '');
    $logoPath = public_path('assets/images/logo/gls-noir.png');
    // Situation du frais — App\Domain\Payments\Support\SituationFraisRecu,
    // la MÊME que le reçu imprimé au guichet.
    $fmtDh = fn (float $v): string => rtrim(rtrim(number_format($v, 2, '.', ' '), '0'), '.').' DH';
    $montantRedondant = $situation->montantRedondant((float) $encaissement->montant);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu {{ $encaissement->reference }}</title>
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

        /* ⚠ Même correctif que les reçus HTML : un numéro de téléphone posé
           après un libellé arabe est réordonné par l'algorithme bidi
           (« +212 80-86 639 83 » → « 83 639 86-80 212+ »). Ici le moteur est
           DomPDF, qui ignore `unicode-bidi` — c'est le caractère U+200E
           (LEFT-TO-RIGHT MARK) inséré dans le gabarit qui force le sens de
           lecture du numéro. Ne pas le retirer en « nettoyant » le template :
           il est invisible mais porteur de sens. */
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
        .row-spacer td { padding-top: 10pt; }

        /* mPDF ne gère pas les bordures sur <tr> : elles vont sur les <td>. */
        .row-reste td {
            font-weight: bold;
            border-top: 0.4pt solid #999;
            padding-top: 3pt;
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
            <td class="num-val">{{ $encaissement->reference }}</td>
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
            <td class="row-val">{{ $encaissement->student?->nomComplet() ?? '—' }}</td>
            <td class="row-ar">اسم و نسب التلميذ(ة)</td>
        </tr>
        <tr>
            <td class="row-fr">Matricule</td>
            <td class="row-val">{{ $encaissement->student?->reference ?? '—' }}</td>
            <td class="row-ar">رقــم التسجيل</td>
        </tr>
        <tr>
            <td class="row-fr">Groupe</td>
            <td class="row-val">{{ $niveau ?? '—' }}</td>
            <td class="row-ar">المجموعة</td>
        </tr>
        <tr class="row-spacer">
            <td class="row-fr">Frais scolaires</td>
            <td class="row-val">{{ $fraisNom }}</td>
            <td class="row-ar">مصاريف التمدرس</td>
        </tr>
        @unless ($montantRedondant)
            {{-- Masquée quand elle répète « Total payé » — voir recu.blade.php. --}}
            <tr class="row-spacer">
                <td class="row-fr">Montant</td>
                <td class="row-val">{{ $montantAffiche }}</td>
                <td class="row-ar">المبلغ</td>
            </tr>
        @endunless
        @if ($situation->disponible)
            {{-- Reste dû après ce versement — voir recu.blade.php pour le
                 pourquoi. « Total payé » est cumulatif, pas le montant de ce reçu. --}}
            <tr class="{{ $montantRedondant ? 'row-spacer' : '' }}">
                <td class="row-fr">Total du frais</td>
                <td class="row-val">{{ $fmtDh($situation->totalFrais) }}</td>
                <td class="row-ar">مجموع المصاريف</td>
            </tr>
            <tr>
                <td class="row-fr">Total payé</td>
                <td class="row-val">{{ $fmtDh($situation->totalPaye) }}</td>
                <td class="row-ar">المبلغ المؤدى</td>
            </tr>
            <tr class="row-reste">
                <td class="row-fr">Reste à payer</td>
                <td class="row-val">{{ $fmtDh($situation->reste) }}</td>
                <td class="row-ar">المبلغ المتبقي</td>
            </tr>
        @endif
        <tr>
            <td class="row-fr">Mode de paiement</td>
            <td class="row-val">{{ $modePaiement }}</td>
            <td class="row-ar">طريقة الأداء</td>
        </tr>
        <tr>
            <td class="row-fr">Date de paiement</td>
            <td class="row-val">{{ $encaissement->date_paiement?->format('d/m/Y') ?? '—' }}</td>
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
