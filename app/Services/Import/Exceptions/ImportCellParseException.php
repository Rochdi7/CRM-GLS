<?php

declare(strict_types=1);

namespace App\Services\Import\Exceptions;

/** A single cell couldn't be normalized (bad date/money format). Caller turns this into an ERREUR row. */
final class ImportCellParseException extends \RuntimeException {}
