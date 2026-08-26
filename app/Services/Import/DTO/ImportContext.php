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
     * @param  bool  $includeInactiveInscriptions  Encaissements only: opt-in.
     *         By default a payment only attaches to an ACTIVE inscription.
     *         When true, a student's cancelled ("Annulée") or changed
     *         ("Changement" — the old CRM's "Archivée") inscription is
     *         accepted as a fallback, so historical payments from a
     *         cancelled enrolment still land instead of being refused.
     *         Active always wins where both exist.
     * @param  ?int  $caisseForceeId  Encaissements only, IMPORT ONLY: put every
     *         imported payment in THIS caisse whatever its méthode, instead of
     *         the normal routing (Espèces → the agent's till, TPE/Chèque/
     *         Virement → the centre's account for that method, CLAUDE.md §11).
     *         Exists because the legacy export's « Opérateur » column names
     *         people who no longer hold a till, and the historical money is
     *         reconciled in one place rather than re-split across nine
     *         cashiers. NEVER set outside the legacy import: normal payment
     *         entry must keep the per-centre routing, which is what makes a
     *         centre's TPE/Chèque totals reconcilable at all.
     * @param  list<string>  $statutsRetenus  Inscriptions only: which statuts
     *         this batch imports into the selected year (after the legacy
     *         translation, so "Archivée" is filtered as "Changement"). Empty
     *         = all. Lets one legacy file be split across years: the
     *         terminated history (Annulée + Changement) into the old year,
     *         the Active rows into the current one. A filtered-out row is
     *         recorded as ignorée, never silently dropped.
     */
    public function __construct(
        public int $etablissementId,
        public int $anneeScolaireId,
        public array $groupeMapping = [],
        public array $operateurMapping = [],
        public bool $includeInactiveInscriptions = false,
        public array $statutsRetenus = [],
        public ?int $caisseForceeId = null,
    ) {}
}
