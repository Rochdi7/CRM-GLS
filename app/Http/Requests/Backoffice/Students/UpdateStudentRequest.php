<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Students;

/**
 * Identical rule set to StoreStudentRequest — the two files had drifted
 * into byte-for-byte duplication, so Update now extends Store (Phase 12
 * dedup). If update ever needs to diverge (e.g. locking a field after
 * creation, like the Dépenses pair deliberately does), override rules()
 * here rather than re-forking the whole list.
 */
final class UpdateStudentRequest extends StoreStudentRequest
{
}
