{{--
    Reçu de paiement GROUPÉ — plusieurs encaissements de la MÊME inscription
    réunis sur un seul document. Reprend à l'identique le modèle du reçu
    unitaire (`recu.blade.php`) : en-tête FR/AR du centre, ligne ICE, bande
    « Reçu N° », libellés bilingues, signature. Seul le bloc central change :
    au lieu d'un couple Frais/Montant unique, un tableau d'une ligne par frais
    réglé, clos par une ligne Total.
    Trois formats via ?format= : a6 (ticket), a5 (paysage), a5x2 (original +
    copie sur UNE demi-feuille A4 = une A5 paysage coupée en deux).
    Rendu navigateur (pas mPDF) pour préserver la ligature arabe.
--}}
@php
    // a5x2 = deux reçus sur une demi-feuille A4 (A5 paysage) : chaque
    // exemplaire occupe ~A6 paysage, donc la même échelle typographique
    // compacte que le ticket A6.
    $compact = $format === 'a6';
    // a5x2 : deux exemplaires dans la moitié droite d'une A4 paysage, soit
    // ~137 x 100mm chacun. Trop étroit pour le 10pt de la A5 pleine page,
    // bien plus large que le ticket A6 : d'où une échelle intermédiaire.
    $mid = $format === 'a5x2';
    $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ' '), '0'), '.');
    $modes = $encaissements
        ->map(fn ($e) => $e->methode
            .($e->numero_cheque ? ' N° :'.$e->numero_cheque : '')
            .($e->banque ? ' - '.$e->banque : ''))
        ->unique()
        ->implode(' / ');
    $dates = $encaissements->map(fn ($e) => $e->date_paiement?->format('d/m/Y'))->filter()->unique();
    $dateAffichee = $dates->count() === 1 ? $dates->first() : $dates->first().' — '.$dates->last();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reçu groupé {{ $reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            font-family: 'Helvetica Neue', Helvetica, Arial, 'Segoe UI', sans-serif;
            color: #111;
            background: #f0f0f0;
        }

        @page {
            @if ($format === 'a6')
            size: A6 portrait;
            @elseif ($format === 'a5x2')
            size: A4 landscape;
            @else
            size: A5 landscape;
            @endif
            margin: {{ $format === 'a5x2' ? '0' : '5mm' }};
        }

        .sheet {
            background: #fff;
            margin: 10px auto;
            padding: 4mm 5mm;
            display: flex;
            flex-direction: column;
            @if ($format === 'a6')
            width: 105mm; min-height: 148mm;
            @elseif ($format === 'a5x2')
            /* A4 PAYSAGE (297 x 210mm), les deux exemplaires empilés dans la
               MOITIÉ DROITE : la gauche reste vierge, c'est le talon que l'on
               garde après découpe. Géométrie relevée sur le modèle de
               référence (colonne de ~134mm de large à partir de x=153mm).
               La marge @page vaut 0 : c'est .sheet qui place la colonne, sinon
               le décalage de gauche dépendrait de la marge de l'imprimante. */
            width: 297mm; min-height: 210mm;
            padding-left: 152mm; padding-right: 8mm;
            @else
            width: 210mm; min-height: 148mm;
            @endif
        }

        .recu {
            flex: 1 1 0;
            position: relative;
            display: flex;
            flex-direction: column;
            border-left: 1px dashed #999;
            padding: {{ $mid ? '1.5mm 2mm 1.5mm 6mm' : '3mm 2mm 3mm 6mm' }};
            font-size: {{ $compact ? '7.5pt' : ($mid ? '7.4pt' : '10pt') }};
            line-height: {{ $mid ? '1.3' : '1.45' }};
            page-break-inside: avoid;
        }

        .recu + .recu { border-top: 1px dashed #999; }

        .copie-label {
            position: absolute;
            left: 1mm;
            top: 42%;
            transform: rotate(180deg);
            writing-mode: vertical-rl;
            font-size: {{ $compact ? '6pt' : ($mid ? '7pt' : '8pt') }};
            font-weight: 700;
            color: #333;
        }

        .recu-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 4mm;
        }

        /* Le logo doit être CENTRÉ sur la largeur du reçu, pas simplement
           posé entre les deux blocs : avec un `space-between`, sa position
           dépendait de la longueur de l'adresse FR et du nom de ville AR, donc
           elle changeait d'un centre à l'autre. Les deux blocs latéraux
           prennent la même part (`flex: 1 1 0`), ce qui fixe le logo au milieu
           quel que soit leur contenu. */
        .centre-fr { flex: 1 1 0; }
        .centre-fr .nom { font-weight: 400; }
        .logo { flex: 0 0 auto; align-self: center; }
        .logo img { height: {{ $compact ? '9mm' : ($mid ? '8mm' : '13mm') }}; }
        /* ⚠ Un numéro de téléphone dans un bloc RTL est réordonné par
           l'algorithme bidi : « +212 80-86 639 83 » s'affiche « 83 639 86-80
           212+ », car chaque groupe de chiffres est un run neutre placé de
           droite à gauche. `direction: ltr` + `unicode-bidi: embed` isole le
           numéro dans son propre contexte LTR, à l'intérieur du bloc arabe.
           Le libellé arabe reste, lui, en RTL. */
        .tel-ltr {
            direction: ltr;
            unicode-bidi: embed;
            display: inline-block;
        }

        .centre-ar {
            direction: rtl;
            text-align: right;
            flex: 1 1 0;
        }

        /* Écriture arabe en Naskh (polices système, comme le reçu d'origine) —
           légèrement agrandie, le Naskh paraissant plus petit à taille égale,
           et en GRAS : à graisse égale le Naskh rend un trait bien plus fin que
           l'Helvetica du côté français, si bien que la colonne arabe paraissait
           délavée en face de son libellé FR. */
        .centre-ar, .lbl-ar, .row-line .ar, .frais-table .ar {
            font-family: 'Traditional Arabic', 'Amiri', 'Noto Naskh Arabic', 'Sakkal Majalla', 'Times New Roman', serif;
            font-size: 1.18em;
            font-weight: 700;
        }

        .ice-line {
            text-align: center;
            font-weight: 700;
            font-size: {{ $compact ? '7pt' : ($mid ? '7.5pt' : '9pt') }};
            margin: 1mm 0 1.5mm;
        }

        .recu-num {
            display: flex;
            align-items: center;
            background: #eeecec;
            border: 1px solid #444;
            padding: 1.4mm 3mm;
            font-weight: 700;
            margin-bottom: {{ $format === 'a5x2' ? '2mm' : '3mm' }};
        }
        .recu-num .lbl-fr { width: 32%; text-align: right; padding-right: 4mm; }
        .recu-num .num { flex: 1; text-align: center; }
        .recu-num .lbl-ar { width: 32%; direction: rtl; text-align: left; }

        .rows { flex: 1 0 auto; }

        .row-line {
            display: flex;
            align-items: baseline;
            gap: 3mm;
            padding: {{ $mid ? '0.25mm 0' : '0.7mm 0' }};
        }
        .row-line.spaced { margin-top: {{ $compact ? '2mm' : ($format === 'a5x2' ? '2mm' : '3.5mm') }}; }

        /* Le reste à payer est la ligne que l'étudiant cherche. */
        .row-line.reste .fr, .row-line.reste .val, .row-line.reste .ar { font-weight: 700; }
        .row-line.reste { border-top: 1px solid #999; margin-top: 0.8mm; padding-top: 1mm; }

        .row-line .fr { width: 30%; }
        .row-line .val { flex: 1; text-align: center; font-weight: 700; }
        .row-line .ar { width: 30%; direction: rtl; text-align: right; }

        /* Bloc propre au reçu groupé : une ligne par frais réglé. */
        .frais-table {
            width: 100%;
            border-collapse: collapse;
            margin: {{ $compact ? '2mm' : ($mid ? '1.5mm' : '3mm') }} 0 1mm;
            font-size: {{ $compact ? '7pt' : ($mid ? '7.5pt' : '9.5pt') }};
        }
        .frais-table th,
        .frais-table td {
            border: 1px solid #333;
            padding: {{ $compact ? '0.8mm 1.2mm' : ($mid ? '0.7mm 1.5mm' : '1.2mm 2mm') }};
        }
        .frais-table thead th { background: #eeecec; font-weight: 700; text-align: center; }
        .frais-table td.num { text-align: center; font-weight: 700; white-space: nowrap; }
        .frais-table tfoot td { font-weight: 700; background: #f7f7f7; }

        /* Mention légale : les frais réglés ne sont pas remboursables. */
        .mention-nr {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 3mm;
            font-size: {{ $compact ? '6.5pt' : ($mid ? '7pt' : '8.5pt') }};
            font-weight: 700;
        }
        .mention-nr .ar {
            direction: rtl;
            text-align: right;
            font-family: 'Traditional Arabic', 'Amiri', 'Noto Naskh Arabic', 'Sakkal Majalla', 'Times New Roman', serif;
            font-size: 1.18em;
            font-weight: 700;
        }

        .signature {
            border-top: 1px solid #333;
            margin-top: {{ $compact ? '3mm' : ($mid ? '1.5mm' : '5mm') }};
            padding-top: 1.5mm;
        }
        .signature .mention-nr + div { margin-top: {{ $compact ? '2mm' : '3mm' }}; }
        .signature .sig-space { min-height: {{ $compact ? '6mm' : ($mid ? '2mm' : '10mm') }}; }

        @media print {
            html, body { background: #fff; }
@if ($format === 'a5x2')
            /* ⚠ NE PAS remettre padding/width à zéro ici pour le a5x2 :
               c'est justement ce padding-left qui pousse les deux reçus dans
               la moitié droite de la feuille. Un `padding: 0` à l'impression
               les ramènerait à gauche, alors que l'aperçu écran, lui,
               resterait correct — le pire des bugs à diagnostiquer. */
            .sheet { margin: 0; box-shadow: none; }
@else
            .sheet { margin: 0; padding: 0; width: auto; min-height: auto; box-shadow: none; }
@endif
            .no-print { display: none !important; }
        }

        @media screen {
            .sheet { box-shadow: 0 1px 6px rgba(0,0,0,.25); }
            .toolbar {
                text-align: center;
                padding: 10px;
            }
            .toolbar button {
                padding: 8px 22px;
                border: 0;
                border-radius: 4px;
                background: #1b5a90;
                color: #fff;
                font-size: 14px;
                cursor: pointer;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Imprimer / Télécharger PDF</button>
    </div>

    <div class="sheet">
        @foreach (range(1, $format === 'a5x2' ? 2 : 1) as $copie)
            <div class="recu">
                @if ($copie === 2)
                    <span class="copie-label">Copie du reçu</span>
                @endif

                <div class="recu-header">
                    <div class="centre-fr">
                        <div class="nom">{{ $centre?->nom_centre ?? 'GLS' }}</div>
                        @if ($centre?->adresse)
                            <div>{{ $centre->adresse }}</div>
                        @endif
                        @if ($centre?->telephone)
                            <div>Tél. :{{ $centre->telephone }}</div>
                        @endif
                    </div>
                    <div class="logo">
                        <img src="{{ asset('assets/images/logo/gls-noir.png') }}" alt="GLS">
                    </div>
                    <div class="centre-ar">
                        @if ($centre?->ville)
                            <div>{{ $centre->ville }}</div>
                        @endif
                        @if ($centre?->telephone)
                            <div>الهاتف : <span class="tel-ltr">{{ $centre->telephone }}</span></div>
                        @endif
                    </div>
                </div>

                <div class="ice-line">ICE : {{ $centre?->ice ?? '—' }}</div>

                <div class="recu-num">
                    <span class="lbl-fr">Reçu N° :</span>
                    <span class="num">{{ $encaissements->pluck('reference')->implode(' / ') }}</span>
                    <span class="lbl-ar">وصل رقم :</span>
                </div>

                <div class="rows">
                    <div class="row-line">
                        <span class="fr">Année scolaire</span>
                        <span class="val">{{ $anneeScolaire ?? '—' }}</span>
                        <span class="ar">السنة الدراسية</span>
                    </div>
                    <div class="row-line">
                        <span class="fr">Prénom et nom</span>
                        <span class="val">{{ $student?->nomComplet() ?? '—' }}</span>
                        <span class="ar">اسم و نسب التلميذ(ة)</span>
                    </div>
                    <div class="row-line">
                        <span class="fr">Matricule</span>
                        <span class="val">{{ $student?->reference ?? '—' }}</span>
                        <span class="ar">رقــم التسجيل</span>
                    </div>
                    <div class="row-line">
                        <span class="fr">Groupe</span>
                        <span class="val">{{ $niveau ?? '—' }}</span>
                        <span class="ar">المجموعة</span>
                    </div>
                    <table class="frais-table">
                        <thead>
                            <tr>
                                <th>Frais <span class="ar">/ مصاريف التمدرس</span></th>
                                <th>Montant (DH)</th>
                                <th>Reste (DH)</th>
                                <th>Date</th>
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
                                <td>Total <span class="ar">/ المجموع</span></td>
                                <td class="num">{{ $fmt($montantTotal) }}</td>
                                {{-- Le reste GLOBAL du lot, pas une cellule vide :
                                     la colonne Reste détaille frais par frais, son
                                     total est ce qui décide si l'étudiant est quitte. --}}
                                <td class="num">{{ $situation->disponible ? $fmt($situation->reste) : '' }}</td>
                                <td class="num"></td>
                            </tr>
                        </tfoot>
                    </table>

                    @if ($situation->disponible)
                        {{-- Même récapitulatif que le reçu unitaire : le dû, le cumul
                             payé (TOUS les versements de ces frais, ce lot compris)
                             et le solde restant. --}}
                        <div class="row-line">
                            <span class="fr">Total des frais</span>
                            <span class="val">{{ $fmt($situation->totalFrais) }} DH</span>
                            <span class="ar">مجموع المصاريف</span>
                        </div>
                        <div class="row-line">
                            <span class="fr">Total payé</span>
                            <span class="val">{{ $fmt($situation->totalPaye) }} DH</span>
                            <span class="ar">المبلغ المؤدى</span>
                        </div>
                        <div class="row-line reste">
                            <span class="fr">Reste à payer</span>
                            <span class="val">{{ $fmt($situation->reste) }} DH</span>
                            <span class="ar">المبلغ المتبقي</span>
                        </div>
                    @endif

                    <div class="row-line">
                        <span class="fr">Mode de paiement</span>
                        <span class="val">{{ $modes }}</span>
                        <span class="ar">طريقة الأداء</span>
                    </div>
                    <div class="row-line">
                        <span class="fr">Date de paiement</span>
                        <span class="val">{{ $dateAffichee ?: '—' }}</span>
                        <span class="ar">تاريخ الأداء</span>
                    </div>
                </div>

                <div class="signature">
                    <div class="mention-nr">
                        <span class="fr">Les frais versés ne sont pas remboursables.</span>
                        <span class="ar">المبالغ المدفوعة غير قابلة للاسترجاع.</span>
                    </div>
                    <div>L'administration</div>
                    <div class="sig-space"></div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
