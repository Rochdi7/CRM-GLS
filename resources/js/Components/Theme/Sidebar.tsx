import { backofficeNavigation } from '@/Config/backofficeNavigation';
import NavLink from '@/Components/Navigation/NavLink';
import { isPathActive, useCurrentPath } from '@/Hooks/useActivePath';

interface SidebarProps {
    permissions: string[];
    isSuperAdmin: boolean;
    mobileOpen: boolean;
    onNavigate: () => void;
}

function hasAnyPermission(userPermissions: string[], isSuperAdmin: boolean, required: string[] | undefined): boolean {
    if (!required || required.length === 0) {
        return true;
    }

    // Super-admin bypasses all permission checks server-side (Gate::before)
    // and holds no permissions directly, so it needs an explicit flag here
    // to render every gated item — matching Blade's @can, which resolves
    // true for this role via Gate::before too.
    if (isSuperAdmin) {
        return true;
    }

    return required.some((permission) => userPermissions.includes(permission));
}

export default function Sidebar({ permissions, isSuperAdmin, mobileOpen, onNavigate }: SidebarProps) {
    const currentPath = useCurrentPath();

    return (
        <div
            className={`sidebar${mobileOpen ? ' sidebar-mobile-open' : ''}`}
            id="sidebar"
            // PreSkool mini-sidebar hover-expand (theme script.js behavior):
            // while body.mini-sidebar is active, hovering the rail expands it
            // via body.expand-menu. No-ops when the sidebar isn't collapsed.
            onMouseEnter={() => {
                if (document.body.classList.contains('mini-sidebar')) {
                    document.body.classList.add('expand-menu');
                }
            }}
            onMouseLeave={() => document.body.classList.remove('expand-menu')}
        >
            <div className="sidebar-inner slimscroll">
                <div id="sidebar-menu" className="sidebar-menu">
                    <ul>
                        {backofficeNavigation.map((group) => {
                            const visibleItems = group.items.filter((item) =>
                                hasAnyPermission(permissions, isSuperAdmin, item.permissions),
                            );

                            if (visibleItems.length === 0) {
                                return null;
                            }

                            return (
                                <li key={group.label}>
                                    <h6 className="submenu-hdr">
                                        <span>{group.label}</span>
                                    </h6>
                                    <ul>
                                        {visibleItems.map((item) => (
                                            <li
                                                key={item.href}
                                                className={isPathActive(currentPath, item.matchPaths) ? 'active' : ''}
                                            >
                                                <NavLink href={item.href} inertia={item.inertia} onClick={onNavigate}>
                                                    <i className={item.icon} />
                                                    <span>{item.label}</span>
                                                </NavLink>
                                            </li>
                                        ))}
                                    </ul>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </div>
        </div>
    );
}
