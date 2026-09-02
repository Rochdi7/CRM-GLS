import { usePage } from '@inertiajs/react';
import type { SharedProps } from '@/Types';

/**
 * True while the ACTIVE academic year is closed (« Année clôturée »).
 *
 * Use it to disable « Ajouter »/« Modifier » affordances so staff are not
 * invited to fill a form the server will refuse. This is UI convenience
 * ONLY — the real lock is AssertsContextScope, which refuses the write for
 * everyone including a super-admin (CLAUDE.md §11). Never treat a false
 * here as permission to write.
 */
export function useAnneeCloturee(): boolean {
    const { context } = usePage<SharedProps>().props;

    return context?.anneeCloturee ?? false;
}
