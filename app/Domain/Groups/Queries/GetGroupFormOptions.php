<?php

declare(strict_types=1);

namespace App\Domain\Groups\Queries;

use App\Domain\Settings\Support\FraisEcheanceResolver;
use App\Models\Employee;
use App\Models\Frais;
use App\Models\Group;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Collection;

/**
 * Options for the Groups create/edit form — extracted verbatim from
 * GroupsIndex::enseignants()/fraisCatalog() (same category filter, same
 * active-center scoping for teachers, same active-only filter for fees).
 */
final class GetGroupFormOptions
{
    public function __construct(private readonly CurrentContext $context) {}

    /**
     * Teacher select follows the active center too (global NULL-center staff
     * stay listed).
     *
     * @return Collection<int, array{id: int, nom: string}>
     */
    public function enseignants(): Collection
    {
        return Employee::query()
            ->where('categorie', Employee::CATEGORIE_ENSEIGNANT)
            ->tap(function ($q): void {
                $id = $this->context->etablissementId();
                if ($id !== null) {
                    // Center access follows the employee_etablissement pivot,
                    // not only the primary column (CLAUDE.md §16) — a teacher
                    // whose PRIMARY center is elsewhere but who is assigned to
                    // this one must still be selectable, otherwise the group
                    // form silently drops them and no assignment period (i.e.
                    // no teacher history) can ever be opened for them here.
                    $q->where(fn ($sub) => $sub->whereNull('etablissement_id')
                        ->orWhere('etablissement_id', $id)
                        ->orWhereHas('etablissements', fn ($e) => $e->where('etablissements.id', $id)));
                }
            })
            ->orderBy('nom')
            ->get()
            ->map(fn (Employee $e): array => ['id' => $e->id, 'nom' => $e->nomComplet()]);
    }

    /**
     * Active catalog fees — every one becomes a fraisLignes row on the form.
     *
     * Each row also carries the amount THIS CENTER charges and the month
     * its name implies, so the create form can pre-fill both instead of
     * showing 0 / blank and making the user retype the standard values.
     * The pre-fill is a starting point only: the fields stay editable, and
     * whatever ends up in them is what gets saved.
     *
     * The amount comes from the fee's price line for the active center
     * (frais_etablissement) — the same "Frais de Septembre" is 1400 in
     * Rabat and 1200 in Agadir — falling back to the catalog default when
     * the center has no line, or when the context is on "Tous les centres"
     * and no single branch price applies.
     *
     * @return Collection<int, array{id: int, nom: string, montantDefaut: string, moisEcheance: ?int}>
     */
    public function fraisCatalog(): Collection
    {
        $etablissementId = $this->context->etablissementId();

        return Frais::query()
            ->where('statut', Frais::STATUT_ACTIF)
            ->with('etablissements:id')
            ->orderBy('nom')
            ->get()
            // Teaching-calendar order (janvier → décembre) rather than
            // alphabetical, which interleaves Avril/Août/Octobre. Non-monthly
            // fees (inscription, examen) sort first — see
            // FraisEcheanceResolver::ordreFromNom(). Sorted in PHP because the
            // key comes from the fee's NAME, not a column; the catalog is a
            // dozen-odd rows, so there is nothing to gain from SQL here.
            ->sortBy([
                fn (Frais $a, Frais $b): int => FraisEcheanceResolver::ordreFromNom($a->nom)
                    <=> FraisEcheanceResolver::ordreFromNom($b->nom),
                fn (Frais $a, Frais $b): int => strcmp($a->nom, $b->nom),
            ])
            ->values()
            ->map(fn (Frais $f): array => [
                'id' => $f->id,
                'nom' => $f->nom,
                'montantDefaut' => number_format($f->montantPourCentre($etablissementId), 2, '.', ''),
                'moisEcheance' => FraisEcheanceResolver::moisFromNom($f->nom),
            ]);
    }

    /** @return list<string> */
    public function niveaux(): array
    {
        return Group::NIVEAUX;
    }
}
