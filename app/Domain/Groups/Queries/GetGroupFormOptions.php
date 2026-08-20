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
     * Each row also carries the catalog's own default amount and the month
     * its name implies, so the create form can pre-fill both instead of
     * showing 0 / blank and making the user retype the standard values.
     * The pre-fill is a starting point only: the fields stay editable, and
     * whatever ends up in them is what gets saved.
     *
     * @return Collection<int, array{id: int, nom: string, montantDefaut: string, moisEcheance: ?int}>
     */
    public function fraisCatalog(): Collection
    {
        return Frais::query()
            ->where('statut', Frais::STATUT_ACTIF)
            ->orderBy('nom')
            ->get()
            ->map(fn (Frais $f): array => [
                'id' => $f->id,
                'nom' => $f->nom,
                'montantDefaut' => (string) $f->montant_defaut,
                'moisEcheance' => FraisEcheanceResolver::moisFromNom($f->nom),
            ]);
    }

    /** @return list<string> */
    public function niveaux(): array
    {
        return Group::NIVEAUX;
    }
}
