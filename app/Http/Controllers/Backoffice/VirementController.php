<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Payments\Actions\DemanderVirement;
use App\Domain\Payments\Actions\RefuserVirement;
use App\Domain\Payments\Actions\ValiderVirement;
use App\Domain\Payments\Queries\GetVirementsList;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Controllers\Backoffice\Concerns\RedirectsPreservingFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Virements\RefuserVirementRequest;
use App\Http\Requests\Backoffice\Virements\StoreVirementRequest;
use App\Models\Employee;
use App\Models\Inscription;
use App\Models\Virement;
use App\Services\Context\CurrentContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Demandes de virement (07/10/2026) — un virement bancaire est DÉCLARÉ par
 * le guichet (depuis le modal « Enregistrer un paiement », méthode
 * « Virement ») puis VÉRIFIÉ par le comptable, qui le valide (l'encaissement
 * naît à ce moment, daté du jour de la demande) ou le refuse avec un motif.
 *
 * Une demande ne touche jamais `caisses.solde` par elle-même : toute la
 * règle vit dans DemanderVirement / ValiderVirement / RefuserVirement.
 */
final class VirementController extends Controller
{
    use AssertsContextScope;
    use RedirectsPreservingFilters;

    public function index(Request $request, GetVirementsList $getVirementsList): Response|RedirectResponse
    {
        $this->authorize('viewAny', Virement::class);
        // La page est la boîte de travail du comptable (08/10/2026) : la
        // route le filtre déjà, le contrôleur le revérifie.
        abort_unless($request->user()->can('virements.validate'), 403);

        // Première visite nue : la page ouvre sur ce qu'il reste à VÉRIFIER,
        // via UNE redirection vers l'URL canonique qui porte le filtre
        // explicitement (même mécanisme que la fenêtre du jour sur
        // Encaissements) — jamais un repli à la lecture, sinon un filtre
        // effacé se réarme tout seul.
        if ($request->query() === [] && ! $request->hasHeader('X-Inertia-Partial-Data')) {
            return redirect()->route('backoffice.virements.index', [
                'statutFilter' => Virement::STATUT_EN_ATTENTE,
            ]);
        }

        $search = (string) $request->string('search');
        $statutFilter = (string) $request->string('statutFilter');
        if (! in_array($statutFilter, Virement::STATUTS, true)) {
            $statutFilter = '';
        }
        $dateFrom = (string) $request->string('dateFrom');
        $dateTo = (string) $request->string('dateTo');
        $perPage = (int) $request->integer('perPage', GetVirementsList::DEFAULT_PER_PAGE);

        // « Jamais touché » (aucune clé) vs « effacé » (clé vide) — seul le
        // request le sait ; la fenêtre de l'année ne se réarme pas dans le
        // second cas (§5 : effacer un filtre ne peut qu'ÉLARGIR).
        $dateFilterEngaged = $request->has('dateFrom') || $request->has('dateTo');

        $list = $getVirementsList($request->user(), $search, $statutFilter, $dateFrom, $dateTo, $perPage, $dateFilterEngaged);

        return Inertia::render('Backoffice/Virements/Index', [
            'virements' => $list['data'],
            'montantTotal' => $list['montantTotal'],
            'enAttente' => $list['enAttente'],
            'filters' => [
                'search' => $search,
                'statutFilter' => $statutFilter,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'perPage' => in_array($perPage, GetVirementsList::PER_PAGE_OPTIONS, true)
                    ? $perPage
                    : GetVirementsList::DEFAULT_PER_PAGE,
            ],
            'perPageOptions' => GetVirementsList::PER_PAGE_OPTIONS,
            'statuts' => Virement::STATUTS,
            // Confort d'interface : valider()/refuser() ré-autorisent.
            'canValidate' => $request->user()->can('virements.validate'),
        ]);
    }

    /**
     * DÉCLARATION d'un virement par le guichet — même permission que
     * l'encaissement qu'il deviendra (`payments.create`). Rien n'est
     * encaissé : la demande attend le comptable.
     */
    public function store(StoreVirementRequest $request, DemanderVirement $action): RedirectResponse
    {
        $this->authorize('create', Virement::class);

        $agent = $this->agentOrFail($request, 'montant');
        $data = $request->validated();

        // La date de l'opération devient la date du paiement : la choisir
        // est réservé au super-admin (`payments.update-date`, même droit que
        // re-dater un encaissement). Pour tout autre, c'est le jour même —
        // le champ est grisé dans le modal, une valeur postée est ignorée.
        if (! $request->user()->can('payments.update-date')) {
            $data['date_operation'] = now()->toDateString();
        }

        /** @var Inscription $inscription */
        $inscription = Inscription::query()->findOrFail((int) $data['inscription_id']);

        // Portée centre + contexte ACTIF (centre + année) : l'inscription
        // réglée décide où le futur encaissement sera listé.
        $this->assertInscriptionInContext($request, $inscription);

        // Le centre de la demande = le centre ACTIF (repli : celui de
        // l'inscription) — c'est le compte « Virement » de CE centre qui
        // sera crédité à la validation, jamais le contexte du comptable.
        $etablissementId = app(CurrentContext::class)->etablissementId() ?? (int) $inscription->etablissement_id;

        $action->handle($data, $request->file('justificatif'), $agent, $etablissementId);

        // Le modal de demande est partagé par les pages Paiements et
        // Inscriptions (07/10/2026) : on revient sur celle d'où il a été
        // ouvert. `retour` est une CLÉ d'une liste blanche, jamais une URL
        // fournie par le client (§5).
        $liste = $request->string('retour')->toString() === 'inscriptions'
            ? 'backoffice.inscriptions.index'
            : 'backoffice.encaissements.index';

        return $this->backToListPreservingFilters($request, $liste)
            ->with('success', __('Bank transfer declared: it now awaits the accountant verification.'));
    }

    /** Décision du comptable : VALIDER — l'encaissement est créé ici. */
    public function valider(Request $request, Virement $virement, ValiderVirement $action): RedirectResponse
    {
        $this->authorize('validate', $virement);
        $this->assertRecordInContext(
            $request,
            'statut',
            $virement->etablissement_id,
            null,
            __('This bank transfer belongs to another centre than the active one.'),
            '',
        );

        $action->handle($virement, $this->agentOrFail($request, 'statut'));

        return $this->backToListPreservingFilters($request, 'backoffice.virements.index')
            ->with('success', __('Bank transfer validated: the payment is now recorded.'));
    }

    /** Décision du comptable : REFUSER — motif obligatoire, la ligne reste. */
    public function refuser(RefuserVirementRequest $request, Virement $virement, RefuserVirement $action): RedirectResponse
    {
        $this->authorize('validate', $virement);
        $this->assertRecordInContext(
            $request,
            'statut',
            $virement->etablissement_id,
            null,
            __('This bank transfer belongs to another centre than the active one.'),
            '',
        );

        $action->handle($virement, $this->agentOrFail($request, 'statut'), (string) $request->validated('motif'));

        return $this->backToListPreservingFilters($request, 'backoffice.virements.index')
            ->with('success', __('Bank transfer refused.'));
    }

    private function agentOrFail(Request $request, string $field): Employee
    {
        $agent = $request->user()->employee;

        if ($agent === null) {
            throw ValidationException::withMessages([
                $field => __('Your account is not linked to any employee record.'),
            ]);
        }

        return $agent;
    }
}
