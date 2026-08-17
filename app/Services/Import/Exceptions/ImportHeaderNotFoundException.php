<?php

declare(strict_types=1);

namespace App\Services\Import\Exceptions;

/** No qualifying header row found in the scanned range, or an expected column is missing. */
final class ImportHeaderNotFoundException extends \RuntimeException {}
