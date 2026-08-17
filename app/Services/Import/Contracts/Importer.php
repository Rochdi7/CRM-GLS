<?php

declare(strict_types=1);

namespace App\Services\Import\Contracts;

use App\Models\Employee;
use App\Models\ImportBatch;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportResult;
use Illuminate\Http\UploadedFile;

interface Importer
{
    /** import_batches.module value this importer handles ('students'|'inscriptions'|'encaissements'). */
    public function module(): string;

    /**
     * Phase 1 — parses the file, runs dedupe + validation + resolution per
     * row, persists one ImportRow per data row with its computed status.
     * Read-only against the target tables (students/inscriptions/
     * encaissements) — never writes to them.
     */
    public function analyze(UploadedFile $file, ImportContext $context, Employee $importingAdmin): ImportBatch;

    /**
     * Phase 2 — writes only rows in $selectedRowIds whose status is
     * NOUVEAU or a resolved CONFLIT (DOUBLON/ERREUR rows are always
     * skipped, re-checked server-side regardless of what was posted).
     */
    public function commit(ImportBatch $batch, array $selectedRowIds, Employee $importingAdmin): ImportResult;
}
