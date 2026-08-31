{{--
    Reçu de paiement imprimable — page Blade autonome (pas Inertia) ouverte
    dans un nouvel onglet, reproduisant le reçu wimschool historique
    (en-tête texte FR/AR, ligne ICE, bande « Reçu N° », valeurs centrées en
    gras, mention « Copie du reçu » sur le second exemplaire).
    Trois formats via ?format= :
      a6   → ticket A6 portrait (imprimante ticket)
      a5   → A5 paysage, un exemplaire
      a5x2 → deux exemplaires A5 paysage empilés sur une feuille A4 portrait
    Libellés bilingues FR/AR fixes (document officiel, indépendant de la
    locale UI) — rendu navigateur pour préserver la ligature arabe.
--}}
@php
    $montantAffiche = rtrim(rtrim(number_format((float) $encaissement->montant, 2, '.', ' '), '0'), '.').' DH';
    $modePaiement = $encaissement->methode
        .($encaissement->numero_cheque ? ' N° :'.$encaissement->numero_cheque : '')
        .($encaissement->banque ? ' - '.$encaissement->banque : '');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reçu {{ $encaissement->reference }}</title>
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
            size: A4 portrait;
            @else
            size: A5 landscape;
            @endif
            margin: 5mm;
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
            width: 210mm; min-height: 297mm;
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
            padding: 3mm 2mm 3mm 6mm;
            font-size: {{ $format === 'a6' ? '7.5pt' : '10pt' }};
            line-height: 1.45;
            page-break-inside: avoid;
        }

        .recu + .recu { border-top: 1px dashed #999; }

        .copie-label {
            position: absolute;
            left: 1mm;
            top: 42%;
            transform: rotate(180deg);
            writing-mode: vertical-rl;
            font-size: {{ $format === 'a6' ? '6pt' : '8pt' }};
            font-weight: 700;
            color: #333;
        }

        .recu-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 4mm;
        }

        .centre-fr { max-width: 42%; }
        .centre-fr .nom { font-weight: 400; }
        .logo { align-self: center; }
        .logo img { height: {{ $format === 'a6' ? '9mm' : '13mm' }}; }
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
            max-width: 34%;
        }

        /* Écriture arabe en Naskh (polices système, comme le reçu d'origine) —
           légèrement agrandie, le Naskh paraissant plus petit à taille égale. */
        .centre-ar, .lbl-ar, .row-line .ar {
            font-family: 'Traditional Arabic', 'Amiri', 'Noto Naskh Arabic', 'Sakkal Majalla', 'Times New Roman', serif;
            font-size: 1.18em;
        }

        .ice-line {
            text-align: center;
            font-weight: 700;
            font-size: {{ $format === 'a6' ? '7pt' : '9pt' }};
            margin: 1mm 0 1.5mm;
        }

        .recu-num {
            display: flex;
            align-items: center;
            background: #eeecec;
            border: 1px solid #444;
            padding: 1.4mm 3mm;
            font-weight: 700;
            margin-bottom: 3mm;
        }
        .recu-num .lbl-fr { width: 32%; text-align: right; padding-right: 4mm; }
        .recu-num .num { flex: 1; text-align: center; }
        .recu-num .lbl-ar { width: 32%; direction: rtl; text-align: left; }

        .rows { flex: 1 0 auto; }

        .row-line {
            display: flex;
            align-items: baseline;
            gap: 3mm;
            padding: 0.7mm 0;
        }
        .row-line.spaced { margin-top: {{ $format === 'a6' ? '2mm' : '3.5mm' }}; }

        .row-line .fr { width: 30%; }
        .row-line .val { flex: 1; text-align: center; font-weight: 700; }
        .row-line .ar { width: 30%; direction: rtl; text-align: right; }

        .signature {
            border-top: 1px solid #333;
            margin-top: {{ $format === 'a6' ? '3mm' : '5mm' }};
            padding-top: 1.5mm;
        }
        .signature .sig-space { min-height: {{ $format === 'a6' ? '6mm' : '10mm' }}; }

        @media print {
            html, body { background: #fff; }
            .sheet { margin: 0; padding: 0; width: auto; min-height: auto; box-shadow: none; }
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
                    <span class="num">{{ $encaissement->reference }}</span>
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
                        <span class="val">{{ $encaissement->student?->nomComplet() ?? '—' }}</span>
                        <span class="ar">اسم و نسب التلميذ(ة)</span>
                    </div>
                    <div class="row-line">
                        <span class="fr">Matricule</span>
                        <span class="val">{{ $encaissement->student?->reference ?? '—' }}</span>
                        <span class="ar">رقــم التسجيل</span>
                    </div>
                    <div class="row-line">
                        <span class="fr">Niveau</span>
                        <span class="val">{{ $niveau ?? '—' }}</span>
                        <span class="ar">المستوى</span>
                    </div>
                    <div class="row-line spaced">
                        <span class="fr">Frais scolaires</span>
                        <span class="val">{{ $fraisNom }}</span>
                        <span class="ar">مصاريف التمدرس</span>
                    </div>
                    <div class="row-line spaced">
                        <span class="fr">Montant</span>
                        <span class="val">{{ $montantAffiche }}</span>
                        <span class="ar">المبلغ</span>
                    </div>
                    <div class="row-line">
                        <span class="fr">Mode de paiement</span>
                        <span class="val">{{ $modePaiement }}</span>
                        <span class="ar">طريقة الأداء</span>
                    </div>
                    <div class="row-line">
                        <span class="fr">Date de paiement</span>
                        <span class="val">{{ $encaissement->date_paiement?->format('d/m/Y') ?? '—' }}</span>
                        <span class="ar">تاريخ الأداء</span>
                    </div>
                </div>

                <div class="signature">
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
