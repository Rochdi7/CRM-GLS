<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Concerns;

use App\Models\AnneeScolaire;
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

        $this->assertAnneeNotCloturee($field, $anneeId);
    }

    /**
     * ⚠ « Année clôturée » — the absolute write lock (02/09/2026).
     *
     * A year ticked « clôturée » in Paramètres → Années scolaires accepts NO
     * write at all: no creation, no modification, in any module, by anyone —
     * a super-admin included. It is a business invariant like the money rules
     * of CLAUDE.md §11, NOT a permission, so there is deliberately no bypass:
     * to correct something in a closed year a super-admin must first un-tick
     * the box, which is an explicit and audited gesture (AnneeScolaire is
     * Auditable) rather than a silent override nobody can see afterwards.
     *
     * TWO years are checked, and both matter:
     *
     *  1. the record's OWN year ($anneeId) — a stale dropdown or a forged id
     *     pointing at a closed year;
     *  2. the ACTIVE context year — because the reported incident had no
     *     year FK at all. Dépenses, remboursements, chèques and caisse
     *     journal rows carry NO annee_scolaire_id (they are date-windowed
     *     instead, §11), so $anneeId is null for them and check 1 can never
     *     fire. An employee left the top-bar switcher on 2025/2026 and keyed
     *     dépenses there; only the ACTIVE year identifies that mistake.
     *
     * Placed here rather than in each controller because every guarded write
     * already funnels through assertRecordInContext() — so a future module
     * inherits the lock by calling the guard it must call anyway, and cannot
     * forget it.
     */
    /**
     * Public entry point for a write that carries NO centre/année parent to
     * check — an ordinary dépense, a remboursement edit, a caisse transfer.
     * Those never reach assertRecordInContext(), yet they are precisely the
     * date-windowed money records that landed in the wrong year (§11), so
     * they must assert the ACTIVE year on their own.
     *
     * $field is the form field the refusal is attached to, so the message
     * shows up under a real input in the modal.
     */
    private function assertContextAnneeOuverte(string $field): void
    {
        $this->assertAnneeNotCloturee($field, null);
    }

    private function assertAnneeNotCloturee(string $field, ?int $anneeId): void
    {
        $context = app(CurrentContext::class);

        // The active year first: it is what the user is actually working in,
        // so its name is the one that makes the refusal understandable.
        $active = $context->anneeScolaire();

        if ($active !== null && $active->estCloturee()) {
            throw ValidationException::withMessages([$field => __(
                'The academic year :name is closed: no record can be created or modified in it. A super-admin must reopen it in Paramètres → Années scolaires.',
                ['name' => $active->nom],
            )]);
        }

        // Then the record's own year, when it carries one and differs (a
        // « Changement de groupe » legitimately crosses years, §11 — but
        // never INTO a closed one).
        if ($anneeId === null || $anneeId === $active?->id) {
            return;
        }

        $annee = AnneeScolaire::find($anneeId);

        if ($annee !== null && $annee->estCloturee()) {
            throw ValidationException::withMessages([$field => __(
                'The academic year :name is closed: no record can be created or modified in it. A super-admin must reopen it in Paramètres → Années scolaires.',
                ['name' => $annee->nom],
            )]);
        }
    }
}
