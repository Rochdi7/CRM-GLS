<?php

declare(strict_types=1);

namespace App\Services\Import\DTO;

/**
 * The batch's mandatory, immutable scope + per-batch mapping choices.
 * etablissementId/anneeScolaireId are never optional — see the import
 * plan's "Mandatory Centre + Année scolaire scope" section.
 */
final readonly class ImportContext
{
    /**
     * @param  array<string, array{action: 'map', group_id: int}|array{action: 'create', nom: string, niveau: string}>  $groupeMapping  Inscriptions only: raw "Groupe" label => resolution choice
     * @param  array<string, int>  $operateurMapping  Encaissements only: raw "Opérateur" label => employees.id
     */
    public function __construct(
        public int $etablissementId,
        public int $anneeScolaireId,
        public array $groupeMapping = [],
        public array $operateurMapping = [],
    ) {}
}
