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
        /** Selected rows still pending after this call — chunked commit() calls loop until this reaches 0. */
        public int $remaining = 0,
    ) {}
}
