{{--
    Corps de l'email accompagnant le reçu de paiement en pièce jointe PDF
    (App\Domain\Payments\Mail\EncaissementRecuMail). Le reçu détaillé est
    dans le PDF joint — ce corps reste un résumé court FR, mais mis en page
    en table HTML (compat. large des clients mail, incl. Outlook) plutôt
    qu'en texte brut, avec la couleur de marque du reçu imprimé (#1b5a90,
    voir recu.blade.php .toolbar button) et le logo GLS.

    ⚠ Le logo est EMBARQUÉ dans le message ($message->embed(), un cid:), pas
    référencé par une URL asset(). Une URL est chargée depuis les serveurs du
    client mail : Gmail ne peut pas atteindre un APP_URL local, les proxys
    d'images la bloquent, et beaucoup de clients n'affichent rien tant que le
    destinataire n'a pas cliqué « afficher les images » — l'en-tête tombait
    alors sur le rectangle d'image cassée. Embarqué, le logo voyage avec
    l'email et s'affiche partout, hors ligne compris.

    ⚠ Et c'est un PNG, pas le .webp du backoffice : Gmail, Outlook et Apple
    Mail ne savent pas décoder WebP dans un email. gls-blanc-email.png est le
    gls-blanc.webp détouré et réduit à 64px de haut (affiché en 32 → net en
    écran retina) pour rester léger en pièce jointe.
--}}
@php
    $centre = $encaissement->fee?->inscription?->etablissement ?? $encaissement->student?->etablissement;
    $montantAffiche = rtrim(rtrim(number_format((float) $encaissement->montant, 2, '.', ' '), '0'), '.').' DH';
    $fraisNom = $encaissement->fee?->nom ?? 'Avance';
    // Logo blanc sur fond bleu, embarqué en cid — voir l'en-tête du fichier.
    $logoPath = public_path('assets/images/logo/gls-blanc-email.png');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reçu {{ $encaissement->reference }}</title>
</head>
<body style="margin: 0; padding: 0; background: #eef2f6; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #eef2f6; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 520px; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(17, 17, 17, 0.08);">
                    {{-- En-tête --}}
                    <tr>
                        <td style="background: #1b5a90; padding: 28px 32px; text-align: center;">
                            @if (is_file($logoPath))
                                <img src="{{ $message->embed($logoPath) }}" alt="GLS" width="59" height="32" style="width: 59px; height: 32px; display: inline-block; border: 0;">
                            @else
                                <span style="font-size: 20px; font-weight: bold; color: #ffffff; letter-spacing: 1px;">GLS</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Corps --}}
                    <tr>
                        <td style="padding: 32px;">
                            <p style="margin: 0 0 16px; font-size: 15px; color: #111827;">
                                Bonjour {{ $encaissement->student?->nomComplet() ?? '' }},
                            </p>
                            <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #374151;">
                                Veuillez trouver ci-joint votre reçu de paiement au format PDF.
                                Voici un récapitulatif :
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Reçu N°</td>
                                    <td style="padding: 12px 16px; font-size: 13px; font-weight: 700; color: #111827; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ $encaissement->reference }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Frais</td>
                                    <td style="padding: 12px 16px; font-size: 13px; font-weight: 700; color: #111827; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ $fraisNom }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 16px; font-size: 13px; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Date de paiement</td>
                                    <td style="padding: 12px 16px; font-size: 13px; font-weight: 700; color: #111827; text-align: right; border-bottom: 1px solid #e5e7eb;">{{ $encaissement->date_paiement?->format('d/m/Y') ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 16px; font-size: 14px; color: #111827; font-weight: 700;">Montant réglé</td>
                                    <td style="padding: 14px 16px; font-size: 18px; font-weight: 700; color: #1b5a90; text-align: right;">{{ $montantAffiche }}</td>
                                </tr>
                            </table>

                            <p style="margin: 24px 0 0; font-size: 13px; line-height: 1.6; color: #6b7280;">
                                Le reçu détaillé (avec cachet et informations complètes) se trouve
                                dans le fichier PDF joint à cet email.
                            </p>
                        </td>
                    </tr>

                    {{-- Pied de page --}}
                    <tr>
                        <td style="padding: 20px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #9ca3af;">
                                {{ $centre?->nom_centre ?? 'GLS' }}
                                @if ($centre?->adresse)
                                    · {{ $centre->adresse }}
                                @endif
                                @if ($centre?->telephone)
                                    · Tél. {{ $centre->telephone }}
                                @endif
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
