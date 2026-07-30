<?php

declare(strict_types=1);

namespace App\Domain\Groups\Queries;

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
                    $q->where(fn ($sub) => $sub->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
                }
            })
            ->orderBy('nom')
            ->get()
            ->map(fn (Employee $e): array => ['id' => $e->id, 'nom' => $e->nomComplet()]);
    }

    /**
     * Active catalog fees — every one becomes a fraisLignes row on the form.
     *
     * @return Collection<int, array{id: int, nom: string}>
     */
    public function fraisCatalog(): Collection
    {
        return Frais::query()
            ->where('statut', Frais::STATUT_ACTIF)
            ->orderBy('nom')
            ->get()
            ->map(fn (Frais $f): array => ['id' => $f->id, 'nom' => $f->nom]);
    }

    /** @return list<string> */
    public function niveaux(): array
    {
        return Group::NIVEAUX;
    }
}
