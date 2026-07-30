import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { Context } from '@/Types';

interface ContextSwitcherProps {
    context: Context;
}

/**
 * Markup mirrors resources/views/livewire/backoffice/context/context-switcher.blade.php
 * exactly (two `.dropdown` buttons, same classes/icons/badges) — dropdown
 * open state is React-owned instead of Bootstrap's data-bs-toggle DOM
 * scanning (docs/bootstrap-react-integration-decision.md). Native buttons,
 * no Select2, no jQuery.
 *
 * Posts to backoffice.context.update; CurrentContext (server-side) is the
 * actual authorization boundary — an inaccessible/invalid id posted here
 * is silently ignored there, never trusted client-side.
 */
export default function ContextSwitcher({ context }: ContextSwitcherProps) {
    const [yearMenuOpen, setYearMenuOpen] = useState(false);
    const [centerMenuOpen, setCenterMenuOpen] = useState(false);
    const [processing, setProcessing] = useState(false);
    const yearRef = useRef<HTMLDivElement>(null);
    const centerRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (yearRef.current && !yearRef.current.contains(event.target as Node)) {
                setYearMenuOpen(false);
            }
            if (centerRef.current && !centerRef.current.contains(event.target as Node)) {
                setCenterMenuOpen(false);
            }
        }

        function handleEscape(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setYearMenuOpen(false);
                setCenterMenuOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, []);

    function submitContext(payload: { annee_scolaire_id?: number; etablissement_id?: number | null }) {
        if (processing) {
            return;
        }

        setProcessing(true);
        setYearMenuOpen(false);
        setCenterMenuOpen(false);

        router.post('/backoffice/context', payload, {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
        });
    }

    return (
        <div className="gls-context-switcher d-flex align-items-center flex-wrap gap-2">
            <div className="dropdown" ref={yearRef}>
                <button
                    type="button"
                    className="btn btn-outline-light bg-white d-flex align-items-center dropdown-toggle"
                    onClick={() => setYearMenuOpen((open) => !open)}
                    aria-expanded={yearMenuOpen}
                    disabled={processing}
                >
                    <i className="ti ti-calendar me-2" />
                    <span className="fw-semibold">{context.currentAcademicYear?.name ?? 'Année scolaire'}</span>
                </button>
                <div className={`dropdown-menu dropdown-menu-end${yearMenuOpen ? ' show' : ''}`}>
                    {context.availableAcademicYears.map((annee) => (
                        <button
                            key={annee.id}
                            type="button"
                            className={`dropdown-item d-flex align-items-center justify-content-between w-100 text-start border-0 bg-transparent${context.currentAcademicYear?.id === annee.id ? ' active' : ''}`}
                            onClick={() => submitContext({ annee_scolaire_id: annee.id })}
                        >
                            <span>{annee.name}</span>
                        </button>
                    ))}
                </div>
            </div>

            <div className="dropdown" ref={centerRef}>
                <button
                    type="button"
                    className={`btn btn-outline-light bg-white d-flex align-items-center${context.canSwitchCenter ? ' dropdown-toggle' : ''}`}
                    onClick={() => context.canSwitchCenter && setCenterMenuOpen((open) => !open)}
                    aria-expanded={centerMenuOpen}
                    disabled={processing || !context.canSwitchCenter}
                >
                    <i className="ti ti-building me-2" />
                    <span className="fw-semibold">
                        {context.isAllCenters ? 'Tous les centres' : (context.currentCenter?.name ?? 'Centre')}
                    </span>
                </button>
                {context.canSwitchCenter && (
                    <div className={`dropdown-menu dropdown-menu-end${centerMenuOpen ? ' show' : ''}`}>
                        <button
                            type="button"
                            className={`dropdown-item border-0 bg-transparent w-100 text-start${context.isAllCenters ? ' active' : ''}`}
                            onClick={() => submitContext({ etablissement_id: null })}
                        >
                            <i className="ti ti-world me-2" />
                            Tous les centres
                        </button>
                        <div className="dropdown-divider" />
                        {context.availableCenters.map((centre) => (
                            <button
                                key={centre.id}
                                type="button"
                                className={`dropdown-item border-0 bg-transparent w-100 text-start${!context.isAllCenters && context.currentCenter?.id === centre.id ? ' active' : ''}`}
                                onClick={() => submitContext({ etablissement_id: centre.id })}
                            >
                                {centre.name}
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
