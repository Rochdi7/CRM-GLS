import { useEffect, useRef, useState } from 'react';
import type { CSSProperties } from 'react';
import { Link, router } from '@inertiajs/react';
import ContextSwitcher from '@/Components/Context/ContextSwitcher';
import type { AuthUser, Context } from '@/Types';
import { t } from '@/Lib/i18n';

interface HeaderProps {
    user: AuthUser | null;
    context: Context | null;
    canManageSettings: boolean;
    onMobileMenuToggle: () => void;
}

/**
 * Adapted from components/backoffice/layout/header.blade.php (the GLS-specific
 * trimmed header, not the raw theme demo — no search/notifications/mega-menu,
 * per that file's own history). Dropdown is React-owned (open/close state),
 * not Bootstrap's data-bs-toggle DOM scanning — see
 * docs/bootstrap-react-integration-decision.md.
 */
export default function Header({ user, context, canManageSettings, onMobileMenuToggle }: HeaderProps) {
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const [darkMode, setDarkMode] = useState(() => localStorage.getItem('gls-theme') === 'dark');
    const [miniSidebar, setMiniSidebar] = useState(() => localStorage.getItem('gls-mini-sidebar') === '1');
    const menuRef = useRef<HTMLDivElement>(null);
    const avatarButtonRef = useRef<HTMLButtonElement>(null);

    /*
     * The avatar menu is measured and rendered `position: fixed`, the same
     * way ContextSwitcher.tsx handles its own two dropdowns. On mobile this
     * menu lives inside the context bar, which is `overflow: hidden`
     * (app.css) so the bar can never paint onto the page underneath — an
     * absolutely-positioned menu is clipped by that. Fixed positioning
     * anchored to the trigger's own rect escapes the clip entirely.
     */
    const [avatarMenuPos, setAvatarMenuPos] = useState<{ top: number; left: number } | null>(null);

    // Mobile only: the context switcher row is collapsed behind a "⋯" button
    // (see app.css) rather than always occupying a second header row — that
    // permanent row cost 48px of vertical space on every page. Desktop
    // ignores this state entirely; the row is always visible there.
    const [mobileBarOpen, setMobileBarOpen] = useState(false);

    // PreSkool dark mode: <html data-theme="dark"> (mainlayout.blade.php
    // variant) — the theme CSS handles everything else. Persisted like the
    // old Blade theme-settings component did (Phase 13 header parity).
    useEffect(() => {
        document.documentElement.setAttribute('data-theme', darkMode ? 'dark' : 'light');
        localStorage.setItem('gls-theme', darkMode ? 'dark' : 'light');
    }, [darkMode]);

    // PreSkool collapsed sidebar: body.mini-sidebar (#toggle_btn in the
    // theme header); Sidebar.tsx adds body.expand-menu on hover while
    // collapsed, matching the theme JS behavior.
    useEffect(() => {
        document.body.classList.toggle('mini-sidebar', miniSidebar);
        localStorage.setItem('gls-mini-sidebar', miniSidebar ? '1' : '0');
    }, [miniSidebar]);

    useEffect(() => {
        if (!userMenuOpen) {
            return;
        }

        function handleClickOutside(event: MouseEvent) {
            if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
                setUserMenuOpen(false);
            }
        }

        function handleEscape(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setUserMenuOpen(false);
            }
        }

        // A fixed menu doesn't follow its trigger, so re-measure on scroll
        // (capture phase, to catch scrolling containers too) and on resize.
        function reposition() {
            if (avatarButtonRef.current) {
                const rect = avatarButtonRef.current.getBoundingClientRect();
                setAvatarMenuPos({ top: rect.bottom + 4, left: rect.right });
            }
        }

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);
        window.addEventListener('scroll', reposition, true);
        window.addEventListener('resize', reposition);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
            window.removeEventListener('scroll', reposition, true);
            window.removeEventListener('resize', reposition);
        };
    }, [userMenuOpen]);

    // Escape closes the mobile context bar, matching every other React-owned
    // overlay in the app. No outside-click handler: the bar hosts its own
    // ContextSwitcher dropdowns (which render as `position: fixed` menus
    // outside the bar's own DOM subtree), so a containment test would close
    // the bar the moment a user picked a year or centre from them.
    useEffect(() => {
        if (!mobileBarOpen) {
            return;
        }

        function handleEscape(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                setMobileBarOpen(false);
            }
        }

        document.addEventListener('keydown', handleEscape);

        // Close on navigation too — switching centre/year triggers an Inertia
        // visit, and the bar shouldn't stay open over the next page.
        const stopStart = router.on('start', () => setMobileBarOpen(false));

        return () => {
            document.removeEventListener('keydown', handleEscape);
            stopStart();
        };
    }, [mobileBarOpen]);

    function toggleUserMenu() {
        if (!userMenuOpen && avatarButtonRef.current) {
            const rect = avatarButtonRef.current.getBoundingClientRect();
            // Anchored by its right edge (translateX(-100%) in the style
            // below), matching .dropdown-menu-end's own alignment.
            setAvatarMenuPos({ top: rect.bottom + 4, left: rect.right });
        }
        setUserMenuOpen((open) => !open);
    }

    function handleLogout() {
        router.post('/backoffice/logout');
    }

    return (
        <div className="header">
            <div className="header-left active">
                <a href="/backoffice/dashboard" className="logo logo-normal">
                    <img src="/assets/images/logo/gls-noir.png" alt="GLS CRM" />
                </a>
                <a href="/backoffice/dashboard" className="logo-small">
                    <img src="/assets/images/logo/gls-noir.png" alt="GLS CRM" />
                </a>
                <a href="/backoffice/dashboard" className="dark-logo">
                    <img src="/assets/images/logo/gls-blanc.webp" alt="GLS CRM" />
                </a>
                <button
                    type="button"
                    id="toggle_btn"
                    className="border-0 bg-transparent p-0"
                    // The theme CSS shrinks #toggle_btn to opacity:0/0x0 while
                    // collapsed (.mini-sidebar .header #toggle_btn) unless
                    // body.expand-menu is also set — which Sidebar.tsx only
                    // adds while the cursor is hovering the sidebar rail
                    // itself. Moving the cursor from the rail to this button
                    // (a different element) drops that hover state first,
                    // making the only way to re-expand the sidebar disappear
                    // mid-click. Force it visible/clickable at all times
                    // instead of depending on that hover coupling.
                    style={miniSidebar ? { opacity: 1, height: 'auto', width: 'auto' } : undefined}
                    onClick={() => setMiniSidebar((v) => !v)}
                    aria-label={t('Collapse sidebar')}
                    aria-pressed={miniSidebar}
                >
                    <i className="ti ti-menu-deep" aria-hidden="true" />
                </button>
            </div>

            <button
                type="button"
                id="mobile_btn"
                className="mobile_btn border-0 bg-transparent"
                onClick={onMobileMenuToggle}
                aria-label={t('Toggle menu')}
            >
                <span className="bar-icon">
                    <span />
                    <span />
                    <span />
                </span>
            </button>

            {/*
             * Mobile-only "⋯" toggle for the context/user row below. Hidden on
             * desktop via .gls-mobile-bar-toggle (app.css), where that row is
             * always shown.
             */}
            <button
                type="button"
                className="gls-mobile-bar-toggle border-0 bg-transparent"
                onClick={() => setMobileBarOpen((open) => !open)}
                aria-label={t('Toggle context bar')}
                aria-expanded={mobileBarOpen}
            >
                <i className="ti ti-dots" aria-hidden="true" />
            </button>

            <div className="header-user">
                <div className={`nav user-menu${mobileBarOpen ? ' gls-bar-open' : ''}`}>
                    <div className="nav-item me-auto">{context && <ContextSwitcher context={context} />}</div>

                    <div className="d-flex align-items-center">
                        <div className="pe-1">
                            <button
                                type="button"
                                className="btn btn-outline-light bg-white btn-icon me-1"
                                onClick={() => setDarkMode((v) => !v)}
                                aria-label={darkMode ? t('Switch to light mode') : t('Switch to dark mode')}
                                aria-pressed={darkMode}
                            >
                                <i className={darkMode ? 'ti ti-brightness-up' : 'ti ti-moon'} aria-hidden="true" />
                            </button>
                        </div>
                        <div className="dropdown ms-1" ref={menuRef}>
                            <button
                                ref={avatarButtonRef}
                                type="button"
                                className="dropdown-toggle d-flex align-items-center border-0 bg-transparent"
                                onClick={toggleUserMenu}
                                aria-expanded={userMenuOpen}
                            >
                                <span className="avatar avatar-md rounded">
                                    <img src={user?.photoUrl ?? '/assets/images/avatar/defaultman.webp'} alt="" className="img-fluid" />
                                </span>
                            </button>
                            <div
                                className={`gls-avatar-menu dropdown-menu dropdown-menu-end${userMenuOpen ? ' show' : ''}`}
                                style={
                                    userMenuOpen && avatarMenuPos
                                        ? ({
                                              position: 'fixed',
                                              top: avatarMenuPos.top,
                                              right: 'auto',
                                              transform: 'translateX(-100%)',
                                              // Read back by app.css with !important — the theme's
                                              // own `.header .dropdown-menu { left: unset !important }`
                                              // would otherwise override a plain inline `left`.
                                              ['--gls-menu-left' as string]: `${avatarMenuPos.left}px`,
                                          } as CSSProperties)
                                        : undefined
                                }
                            >
                                <div className="d-block">
                                    <div className="d-flex align-items-center p-2">
                                        <span className="avatar avatar-md me-2 online avatar-rounded">
                                            <img src={user?.photoUrl ?? '/assets/images/avatar/defaultman.webp'} alt="" />
                                        </span>
                                        <div>
                                            <h6>{user?.name ?? 'GLS'}</h6>
                                            <p className="text-primary mb-0">Administrateur</p>
                                        </div>
                                    </div>
                                    <hr className="m-0" />
                                    <Link
                                        className="dropdown-item d-inline-flex align-items-center p-2"
                                        href="/backoffice/profile"
                                    >
                                        <i className="ti ti-user-circle me-2" />
                                        Profil
                                    </Link>
                                    {canManageSettings && (
                                        <a
                                            className="dropdown-item d-inline-flex align-items-center p-2"
                                            href="/backoffice/settings"
                                        >
                                            <i className="ti ti-settings me-2" />
                                            Paramètres
                                        </a>
                                    )}
                                    <hr className="m-0" />
                                    <button
                                        type="button"
                                        className="dropdown-item d-inline-flex align-items-center p-2 w-100 text-start border-0 bg-transparent"
                                        onClick={handleLogout}
                                    >
                                        <i className="ti ti-login me-2" />
                                        Déconnexion
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
