<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Maintenance\RunLegacyReconciliationRequest;
use App\Models\Etablissement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

/**
 * « Réconciliation des paiements importés » — l'interface de
 * `paiements:reconcilier` (voir docs/legacy-import-cli.md).
 *
 * Compare l'export de l'ancien CRM aux paiements en base et rattache chaque
 * paiement au frais que nomme SON FICHIER SOURCE. Trois réparations
 * manuelles des 07–08/09/2026 (ISMAIL AMARIR, EL ABLAOUI, HAMZA LACHKAR)
 * ont motivé son existence.
 *
 * ⚠ RÉSERVÉ AU COMPTE DE MAINTENANCE — c'est une IDENTITÉ, pas une
 * permission, et elle n'est accordable à personne
 * (`AppServiceProvider::MAINTAINER_ONLY_ABILITIES`, décidée AU-DESSUS du
 * bypass super-admin, même raisonnement que `GroupPolicy@updateClosed`).
 * L'outil réécrit l'affectation d'argent déjà encaissé en lisant des
 * fichiers du serveur : ce n'est pas un écran d'exploitation, c'est un
 * outil de réparation de données.
 *
 * Volontairement ABSENT de la barre latérale : atteint par son lien direct
 * `/backoffice/reconciliation-paiements`. L'absence d'entrée de menu n'est
 * JAMAIS une protection — c'est le gate ci-dessous qui décide, comme pour
 * « Échéances en masse » (§16).
 *
 * Ce que l'écran ne peut pas faire, par construction (garanties de la
 * commande, testées dans ReconcilierPaiementsLegacyTest) : toucher un
 * `montant`, une `date_paiement`, une `methode`, un `caisse_id` ou
 * `caisses.solde` ; supprimer un enregistrement monétaire ; écrire dans une
 * année clôturée.
 */
final class LegacyReconciliationController extends Controller
{
    /** Ability d'identité — voir AppServiceProvider::MAINTAINER_ONLY_ABILITIES. */
    private const ABILITY = 'legacy-payments.reconcile';

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can(self::ABILITY), 403);

        return Inertia::render('Backoffice/Maintenance/LegacyReconciliation', [
            'etablissements' => Etablissement::query()
                ->orderBy('nom_centre')
                ->get(['id', 'nom_centre'])
                ->map(fn (Etablissement $e): array => [
                    'value' => (string) $e->id,
                    'label' => $e->nom_centre,
                ]),
            'dossierDefaut' => config('gls.legacy_import_path', base_path('data')),
            'resultat' => session('reconciliation_resultat'),
        ]);
    }

    /**
     * Lance la commande et rend sa sortie telle quelle.
     *
     * `--apply` n'est transmis QUE si la requête le demande explicitement :
     * le défaut de l'écran, comme celui de la commande, est la simulation.
     */
    public function run(RunLegacyReconciliationRequest $request): RedirectResponse
    {
        abort_unless($request->user()->can(self::ABILITY), 403);

        $validated = $request->validated();
        $apply = (bool) ($validated['apply'] ?? false);

        $options = ['--dossier' => $validated['dossier']];

        if (($validated['centre'] ?? '') !== '') {
            $options['--centre'] = $validated['centre'];
        }

        if (($validated['etudiant'] ?? '') !== '') {
            $options['--etudiant'] = $validated['etudiant'];
        }

        if ($apply) {
            $options['--apply'] = true;
        }

        $code = Artisan::call('paiements:reconcilier', $options);

        return back()->with('reconciliation_resultat', [
            'apply' => $apply,
            'sortie' => Artisan::output(),
            'succes' => $code === 0,
            'lancee_le' => now()->format('d/m/Y H:i'),
        ]);
    }
}
