import { useEffect, useRef, useState } from 'react';
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

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleEscape);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleEscape);
        };
    }, [userMenuOpen]);

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

            <div className="header-user">
                <div className="nav user-menu">
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
                                type="button"
                                className="dropdown-toggle d-flex align-items-center border-0 bg-transparent"
                                onClick={() => setUserMenuOpen((open) => !open)}
                                aria-expanded={userMenuOpen}
                            >
                                <span className="avatar avatar-md rounded">
                                    <img src={user?.photoUrl ?? '/assets/images/avatar/defaultman.webp'} alt="" className="img-fluid" />
                                </span>
                            </button>
                            <div className={`dropdown-menu dropdown-menu-end${userMenuOpen ? ' show' : ''}`}>
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
