{{--
    Corps de l'email accompagnant le reçu de paiement en pièce jointe PDF
    (App\Domain\Payments\Mail\EncaissementRecuMail). Le reçu détaillé est
    dans le PDF joint — ce corps reste un résumé court FR, mais mis en page
    en table HTML (compat. large des clients mail, incl. Outlook) plutôt
    qu'en texte brut, avec la couleur de marque du reçu imprimé (#1b5a90,
    voir recu.blade.php .toolbar button) et le logo GLS.
--}}
@php
    $centre = $encaissement->fee?->inscription?->etablissement ?? $encaissement->student?->etablissement;
    $montantAffiche = rtrim(rtrim(number_format((float) $encaissement->montant, 2, '.', ' '), '0'), '.').' DH';
    $fraisNom = $encaissement->fee?->nom ?? 'Avance';
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
                            <img src="{{ asset('assets/images/logo/gls-blanc.webp') }}" alt="GLS" height="32" style="height: 32px; display: inline-block;">
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
