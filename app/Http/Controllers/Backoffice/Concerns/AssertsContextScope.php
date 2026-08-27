<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Concerns;

use App\Models\Group;
use App\Models\Inscription;
use App\Models\Student;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Guard for every write whose scope (centre + année) is inherited from a
 * CLIENT-CHOSEN parent record (group, inscription, student, article…) or
 * that mutates a record which itself carries a centre/année. The
 * inheritance mechanism is correct — but only if the parent sits inside
 * the user's reach AND the active working context; otherwise a forged or
 * stale id (a dropdown loaded before the top-bar switch) writes records
 * into a foreign centre/year where no list shows them (the importer bug of
 * 24/08/2026, generalised on 27/08/2026 to inscriptions, payments, refunds,
 * cheques, stock and every mutation of a year-bearing record).
 *
 * ResourcePolicy only answers "may this user reach that centre?" — it
 * knows nothing about the top-bar context, and create() takes no model at
 * all. So every such store()/mutation must call one of these.
 *
 * Rules (CLAUDE.md §11 « Context scoping is MANDATORY »):
 *  - a NULL context centre (« Tous les centres », super-admin only) or a
 *    NULL record centre (global record) never conflicts;
 *  - a NULL record année (record with no year FK) never conflicts;
 *  - otherwise both must match the active context exactly.
 */
trait AssertsContextScope
{
    private function assertGroupInContext(Request $request, Group $group, string $field = 'group_id'): void
    {
        $this->assertRecordInContext(
            $request,
            $field,
            $group->etablissement_id,
            $group->annee_scolaire_id,
            __('This group belongs to another centre than the active one.'),
            __('This group belongs to another academic year than the active one.'),
        );
    }

    private function assertInscriptionInContext(Request $request, Inscription $inscription, string $field = 'inscription_id'): void
    {
        $this->assertRecordInContext(
            $request,
            $field,
            $inscription->etablissement_id,
            $inscription->annee_scolaire_id,
            __('This registration belongs to another centre than the active one.'),
            __('This registration belongs to another academic year than the active one.'),
        );
    }

    /** Students carry no year — only the centre is checked (NULL = global student, allowed everywhere). */
    private function assertStudentInContext(Request $request, Student $student, string $field = 'student_id'): void
    {
        $this->assertRecordInContext(
            $request,
            $field,
            $student->etablissement_id,
            null,
            __('This student belongs to another centre than the active one.'),
            '',
        );
    }

    /**
     * Generic form: centre reach (403) + active centre + active année.
     * `$anneeId` null ⇒ no year check (students, stock, caisses…).
     */
    private function assertRecordInContext(
        Request $request,
        string $field,
        ?int $etablissementId,
        ?int $anneeId,
        string $centreMessage,
        string $anneeMessage,
    ): void {
        abort_unless(
            app(CenterAccessService::class)->canAccessCenter($request->user(), $etablissementId),
            403,
        );

        $context = app(CurrentContext::class);

        $contextCentre = $context->etablissementId();
        if ($contextCentre !== null && $etablissementId !== null && (int) $etablissementId !== $contextCentre) {
            throw ValidationException::withMessages([$field => $centreMessage]);
        }

        $contextAnnee = $context->anneeScolaireId();
        if ($contextAnnee !== null && $anneeId !== null && (int) $anneeId !== $contextAnnee) {
            throw ValidationException::withMessages([$field => $anneeMessage]);
        }
    }
}
