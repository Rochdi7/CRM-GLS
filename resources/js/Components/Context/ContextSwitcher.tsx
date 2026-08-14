import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { CSSProperties } from 'react';
import type { Context } from '@/Types';

interface ContextSwitcherProps {
    context: Context;
}

/** Fixed-position coordinates for a dropdown menu, measured from its trigger. */
type MenuPos = { top: number; left: number } | null;

/**
 * Measures a trigger button's position for a `position: fixed` dropdown menu
 * — plain `.dropdown-menu` (position: absolute, relative to `.dropdown`)
 * gets pushed out of place by this header's own layout shifts (e.g. the
 * mini-sidebar's narrower `.header-left`), landing on top of the sidebar and
 * eating its clicks. Fixed positioning anchored to the trigger's own
 * bounding rect sidesteps that entirely — same fix already applied to
 * RowActions.tsx for the same class of bug.
 *
 * Anchored via `left` (not `right`) and reported through the
 * `--gls-menu-left` custom property (see app.css): the theme's own
 * `.header .dropdown-menu { left: unset !important; right: 0 !important; }`
 * (style.css, from _header.scss — written for Bootstrap's own JS-driven
 * data-bs-popper dropdowns, which this app never uses) unconditionally wins
 * over a plain inline `left`/`right`, snapping the menu to the viewport's
 * true right edge regardless of the trigger's position. Only `!important`
 * beats `!important`, so app.css re-asserts `left` with its own
 * `!important`, reading the actual offset from this custom property (custom
 * properties aren't subject to the same shorthand override). The menu's
 * right edge is then aligned to the trigger's right edge with
 * `translateX(-100%)`.
 */
function measureMenuPos(trigger: HTMLElement): MenuPos {
    const rect = trigger.getBoundingClientRect();

    return { top: rect.bottom + 4, left: rect.right };
}

/** Builds the fixed-position style, including the `--gls-menu-left` custom property app.css reads. */
function menuStyle(pos: MenuPos): CSSProperties | undefined {
    if (!pos) {
        return undefined;
    }

    return {
        position: 'fixed',
        top: pos.top,
        right: 'auto',
        transform: 'translateX(-100%)',
        ['--gls-menu-left' as string]: `${pos.left}px`,
    } as CSSProperties;
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
    const [yearMenuPos, setYearMenuPos] = useState<MenuPos>(null);
    const [centerMenuPos, setCenterMenuPos] = useState<MenuPos>(null);
    const [processing, setProcessing] = useState(false);
    const yearRef = useRef<HTMLDivElement>(null);
    const centerRef = useRef<HTMLDivElement>(null);
    const yearButtonRef = useRef<HTMLButtonElement>(null);
    const centerButtonRef = useRef<HTMLButtonElement>(null);

    function toggleYearMenu() {
        if (!yearMenuOpen && yearButtonRef.current) {
            setYearMenuPos(measureMenuPos(yearButtonRef.current));
        }
        setYearMenuOpen((open) => !open);
    }

    function toggleCenterMenu() {
        if (!context.canSwitchCenter) {
            return;
        }
        if (!centerMenuOpen && centerButtonRef.current) {
            setCenterMenuPos(measureMenuPos(centerButtonRef.current));
        }
        setCenterMenuOpen((open) => !open);
    }

    useEffect(() => {
        if (!yearMenuOpen && !centerMenuOpen) {
            return;
        }

        function handleReposition() {
            setYearMenuOpen(false);
            setCenterMenuOpen(false);
        }

        // A fixed-position menu drifts from its trigger on scroll/resize —
        // close instead of tracking (same behavior as RowActions.tsx).
        window.addEventListener('scroll', handleReposition, true);
        window.addEventListener('resize', handleReposition);

        return () => {
            window.removeEventListener('scroll', handleReposition, true);
            window.removeEventListener('resize', handleReposition);
        };
    }, [yearMenuOpen, centerMenuOpen]);

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
                    ref={yearButtonRef}
                    type="button"
                    className="btn btn-outline-light bg-white d-flex align-items-center dropdown-toggle"
                    onClick={toggleYearMenu}
                    aria-expanded={yearMenuOpen}
                    disabled={processing}
                >
                    <i className="fa fa-calendar me-2" />
                    <span className="fw-semibold">{context.currentAcademicYear?.name ?? 'Année scolaire'}</span>
                </button>
                <div
                    className={`dropdown-menu dropdown-menu-end${yearMenuOpen ? ' show' : ''}`}
                    style={yearMenuOpen ? menuStyle(yearMenuPos) : undefined}
                >
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
                    ref={centerButtonRef}
                    type="button"
                    className={`btn btn-outline-light bg-white d-flex align-items-center${context.canSwitchCenter ? ' dropdown-toggle' : ''}`}
                    onClick={toggleCenterMenu}
                    aria-expanded={centerMenuOpen}
                    disabled={processing || !context.canSwitchCenter}
                >
                    <i className="fa fa-building me-2" />
                    <span className="fw-semibold">
                        {context.isAllCenters ? 'Tous les centres' : (context.currentCenter?.name ?? 'Centre')}
                    </span>
                </button>
                {context.canSwitchCenter && (
                    <div
                        className={`dropdown-menu dropdown-menu-end${centerMenuOpen ? ' show' : ''}`}
                        style={centerMenuOpen ? menuStyle(centerMenuPos) : undefined}
                    >
                        <button
                            type="button"
                            className={`dropdown-item border-0 bg-transparent w-100 text-start${context.isAllCenters ? ' active' : ''}`}
                            onClick={() => submitContext({ etablissement_id: null })}
                        >
                            <i className="fa fa-globe me-2" />
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
