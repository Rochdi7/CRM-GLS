<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Registrations\Actions\AttribuerLivreEnMasse;
use App\Domain\Registrations\Queries\GetInscriptionFormOptions;
use App\Domain\Registrations\Queries\GetLivresGroupe;
use App\Domain\Students\Queries\GetStudentTransfersList;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Students\AttribuerLivreEnMasseRequest;
use App\Models\Group;
use App\Models\Inscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Onglet « Livres » des Étudiants (12/10/2026) — remise d'un livre en masse
 * à un groupe : choisir le groupe et le livre, cocher tout le monde ou un par
 * un. Même droit que l'éditeur de livres d'une inscription
 * (`registrations.manage-fees`), porté par la route ET revérifié ici.
 */
final class StudentLivresController extends Controller
{
    use AssertsContextScope;

    public function index(
        Request $request,
        GetInscriptionFormOptions $options,
        GetLivresGroupe $livres,
        GetStudentTransfersList $transfers,
    ): Response {
        abort_unless($request->user()->can('registrations.manage-fees'), 403);

        $groupId = $request->integer('group_id') ?: null;
        $livreId = $request->integer('livre_id') ?: null;
        $statut = (string) $request->string('statut');
        if ($statut !== '' && ! in_array($statut, Inscription::STATUTS, true)) {
            $statut = '';
        }

        $group = $groupId !== null ? Group::query()->find($groupId) : null;
        if ($group !== null) {
            $this->assertGroupInContext($request, $group);
        }

        $livresDuCentre = $group !== null ? $livres->livresDuCentre($group->etablissement_id) : collect();
        if ($livreId !== null && ! $livresDuCentre->contains('id', $livreId)) {
            $livreId = null;
        }

        return Inertia::render('Backoffice/Students/Livres', [
            'tabCounts' => fn () => $transfers->tabCounts($request->user()),
            'filters' => ['group_id' => $group?->id, 'livre_id' => $livreId, 'statut' => $statut],
            'groupOptions' => fn () => $options->groups($request->user())->values(),
            'statuts' => Inscription::STATUTS,
            'livres' => $livresDuCentre,
            'lignes' => $group !== null ? $livres->lignes($group, $livreId, $statut) : [],
        ]);
    }

    public function store(AttribuerLivreEnMasseRequest $request, AttribuerLivreEnMasse $action): RedirectResponse
    {
        abort_unless($request->user()->can('registrations.manage-fees'), 403);

        $data = $request->validated();
        $group = Group::query()->findOrFail($data['group_id']);
        $this->assertGroupInContext($request, $group);

        $resultat = $action->handle(
            $group,
            (int) $data['stock_article_id'],
            $data['inscription_ids'],
            $request->user()?->employee,
        );

        $message = __(':count book(s) « :book » assigned.', ['count' => $resultat['attribues'], 'book' => $resultat['livre']]);
        if ($resultat['dejaServis'] > 0) {
            $message .= ' '.__(':count student(s) already had this book and were skipped.', ['count' => $resultat['dejaServis']]);
        }

        return back()->with('success', $message);
    }
}
