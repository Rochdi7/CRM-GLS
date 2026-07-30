import type { PropsWithChildren, ReactNode } from 'react';
import DataTable from '@/Components/Tables/DataTable';
import EmptyState from '@/Components/Shared/EmptyState';

interface RelatedRecordsTableProps extends PropsWithChildren {
    isEmpty: boolean;
    emptyTitle: string;
    emptyIcon?: string;
    head: ReactNode;
}

/**
 * Thin wrapper around the existing DataTable/EmptyState (Phase 2) for the
 * "related records" pattern repeated across every detail page (inscriptions
 * on a student, students in a group, fee lines on an inscription, movements
 * on a caisse). Columns/rows stay explicit per page — this only removes the
 * isEmpty ? EmptyState : DataTable boilerplate, it does not render arbitrary
 * data generically.
 */
export default function RelatedRecordsTable({ isEmpty, emptyTitle, emptyIcon, head, children }: RelatedRecordsTableProps) {
    if (isEmpty) {
        return <EmptyState title={emptyTitle} icon={emptyIcon} />;
    }

    return <DataTable head={head}>{children}</DataTable>;
}
