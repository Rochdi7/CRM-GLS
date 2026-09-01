{{--
    Page d'atterrissage du lien reçu envoyé par WhatsApp (01/09/2026).

    ⚠ POURQUOI cette page existe. Le lien servait directement les octets du
    PDF avec `Content-Disposition: attachment`. Sur un ORDINATEUR le fichier
    tombe dans les téléchargements ; sur un TÉLÉPHONE — le seul appareil qui
    reçoive réellement ces liens — iOS Safari ouvre le PDF dans une visionneuse
    en lecture seule, sans aucun bouton d'enregistrement visible. L'étudiant
    voit son reçu et ne peut pas le garder (constaté le 01/09/2026, capture
    iPhone). On sert donc une page qui porte un vrai bouton, et le PDF n'est
    servi qu'au clic (`?download=1` sur la MÊME URL signée).

    Aucune donnée d'un autre dossier, aucun lien vers le CRM : cette page est
    publique par nécessité et un message WhatsApp se transfère.

    ⚠ Deux comportements selon l'appareil (01/09/2026, vérifié sources WebKit) :
    - HORS iOS : le téléchargement démarre TOUT SEUL (navigation vers
      `?download=1`, servi en `attachment` — la page reste affichée) ; le
      bouton reste le plan B.
    - iOS (iPhone/iPad) : un téléchargement forcé N'EXISTE PAS — WebKit
      ignore `attachment` pour les types qu'il sait afficher, et la webview
      WhatsApp avale même le clic (bugs WebKit 216918/167341). Le bouton
      ouvre donc le PDF DANS la visionneuse (`inline`, décidé côté serveur
      sur le User-Agent) et le texte sous le bouton explique le seul chemin
      d'enregistrement qui marche : Partager → « Enregistrer dans Fichiers ».
    Ne pas « unifier » les deux : c'est la plateforme qui impose l'écart.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $titre }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 24px 16px 40px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f2f4f7;
            color: #1f2937;
            line-height: 1.5;
            -webkit-text-size-adjust: 100%;
        }

        .card {
            max-width: 460px;
            margin: 0 auto;
            background: #fff;
            border-radius: 14px;
            padding: 24px 20px;
            box-shadow: 0 2px 10px rgba(16, 24, 40, .08);
        }

        .logo { display: block; height: 44px; margin: 0 auto 18px; }

        h1 {
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            margin: 0 0 4px;
        }

        .centre {
            text-align: center;
            color: #667085;
            font-size: 14px;
            margin: 0 0 20px;
        }

        dl { margin: 0 0 22px; }

        .line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px solid #eaecf0;
            font-size: 14px;
        }
        .line:last-child { border-bottom: 0; }
        .line dt { color: #667085; margin: 0; }
        .line dd { margin: 0; font-weight: 600; text-align: right; }

        .total {
            border-top: 2px solid #1f2937;
            border-bottom: 0;
            margin-top: 4px;
            padding-top: 12px;
            font-size: 16px;
        }
        .total dt { color: #1f2937; font-weight: 600; }

        /* Le bouton EST la raison d'être de la page : plein cadre, taille
           tactile confortable, jamais réduit à un lien texte. */
        .btn {
            display: block;
            width: 100%;
            padding: 15px 18px;
            background: #16a34a;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border-radius: 10px;
        }

        .note {
            margin: 16px 0 0;
            font-size: 12px;
            color: #98a2b3;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="card">
        @if ($logoUrl)
            <img class="logo" src="{{ $logoUrl }}" alt="GLS">
        @endif

        <h1>{{ $titre }}</h1>
        <p class="centre">{{ $centre?->nom_centre ?? 'GLS' }}</p>

        <dl>
            <div class="line">
                <dt>Reçu N°</dt>
                <dd>{{ $reference }}</dd>
            </div>
            <div class="line">
                <dt>Prénom et nom</dt>
                <dd>{{ $student?->nomComplet() ?? '—' }}</dd>
            </div>
            <div class="line">
                <dt>Date de paiement</dt>
                <dd>{{ $date ?? '—' }}</dd>
            </div>
            @foreach ($lignes as $ligne)
                <div class="line">
                    <dt>{{ $ligne['libelle'] }}</dt>
                    <dd>{{ $ligne['montant'] }}</dd>
                </div>
            @endforeach
            <div class="line total">
                <dt>Total</dt>
                <dd>{{ $montantTotal }}</dd>
            </div>
        </dl>

        <a class="btn" id="dl" href="{{ $downloadUrl }}" @unless ($isIos) download @endunless>
            {{ $isIos ? 'Ouvrir le reçu en PDF' : 'Télécharger le reçu en PDF' }}
        </a>

        @if ($isIos)
            {{-- Le seul chemin d'enregistrement qui fonctionne sur iOS. --}}
            <p class="note">
                Le reçu s'ouvre à l'écran : touchez ensuite
                <strong>Partager&nbsp;<span aria-hidden="true">⬆︎</span></strong>
                puis «&nbsp;Enregistrer dans Fichiers&nbsp;» pour le garder.
            </p>
        @else
            <p class="note" id="auto-note">Le téléchargement démarre automatiquement…</p>
        @endif

        <p class="note">Lien valable {{ $ttlJours }} jours.</p>
    </div>

    @unless ($isIos)
        <script>
            // Démarrage automatique HORS iOS uniquement : la réponse est en
            // `attachment`, donc la navigation dépose le fichier sans quitter
            // la page. Sur iOS la même navigation REMPLACERAIT la page par la
            // visionneuse avant que l'étudiant ait lu comment enregistrer —
            // et un téléchargement forcé n'y existe de toute façon pas.
            setTimeout(function () {
                window.location.href = document.getElementById('dl').href;
            }, 600);
        </script>
    @endunless
</body>
</html>
