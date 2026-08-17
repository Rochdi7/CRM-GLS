<?php

declare(strict_types=1);

namespace App\Services\Import\DTO;

final readonly class ImportResult
{
    public function __construct(
        public int $insertedCount,
        public int $skippedCount,
        public int $errorCount,
        public ?string $errorReportPath = null,
    ) {}
}
