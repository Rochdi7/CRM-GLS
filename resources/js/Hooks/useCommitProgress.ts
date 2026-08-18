import { useCallback, useRef, useState } from 'react';
import type { ImportBatch } from '@/Types/import';

interface CommitProgressState {
    running: boolean;
    inserted: number;
    errors: number;
    total: number;
    done: boolean;
    failed: boolean;
}

interface CommitChunkResponse {
    inserted: number;
    errors: number;
    remaining: number;
    batch: ImportBatch;
}

const INITIAL_STATE: CommitProgressState = {
    running: false,
    inserted: 0,
    errors: 0,
    total: 0,
    done: false,
    failed: false,
};

/**
 * Drives a chunked commit: calls `commitUrl` repeatedly (small batches per
 * request, server-side chunkSize) so the caller can render an incrementing
 * progress bar instead of one long request with no feedback. Calls
 * `onDone(batch)` once every selected row has been processed.
 */
export function useCommitProgress(commitUrl: string, onDone: (batch: ImportBatch) => void) {
    const [state, setState] = useState<CommitProgressState>(INITIAL_STATE);
    const cancelledRef = useRef(false);

    const start = useCallback(
        async (selectedRowIds: number[]) => {
            cancelledRef.current = false;
            setState({ ...INITIAL_STATE, running: true, total: selectedRowIds.length });

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            let lastBatch: ImportBatch | null = null;
            let insertedSoFar = 0;
            let errorsSoFar = 0;
            let remaining = selectedRowIds.length;

            try {
                while (remaining > 0 && !cancelledRef.current) {
                    const response = await fetch(commitUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ selected_row_ids: selectedRowIds }),
                    });

                    if (!response.ok) {
                        setState((prev) => ({ ...prev, running: false, failed: true }));
                        return;
                    }

                    const json = (await response.json()) as CommitChunkResponse;
                    insertedSoFar += json.inserted;
                    errorsSoFar += json.errors;
                    remaining = json.remaining;
                    lastBatch = json.batch;

                    setState({
                        running: true,
                        inserted: insertedSoFar,
                        errors: errorsSoFar,
                        total: selectedRowIds.length,
                        done: false,
                        failed: false,
                    });
                }

                if (cancelledRef.current) {
                    return;
                }

                setState((prev) => ({ ...prev, running: false, done: true }));

                if (lastBatch) {
                    onDone(lastBatch);
                }
            } catch {
                setState((prev) => ({ ...prev, running: false, failed: true }));
            }
        },
        [commitUrl, onDone]
    );

    const cancel = useCallback(() => {
        cancelledRef.current = true;
        setState((prev) => ({ ...prev, running: false }));
    }, []);

    return { ...state, start, cancel };
}
