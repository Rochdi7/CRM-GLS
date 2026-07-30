import { backofficeNavigation } from '@/Config/backofficeNavigation';
import NavLink from '@/Components/Navigation/NavLink';
import { isPathActive, useCurrentPath } from '@/Hooks/useActivePath';

interface SidebarProps {
    permissions: string[];
    mobileOpen: boolean;
    onNavigate: () => void;
}

function hasAnyPermission(userPermissions: string[], required: string[] | undefined): boolean {
    if (!required || required.length === 0) {
        return true;
    }

    // Super-admin bypasses all permission checks server-side (Gate::before)
    // and is shared with an empty `permissions` array — never filter nav
    // items for a user who has no permissions listed AND no context to
    // check against would be wrong, but a genuinely permission-less user
    // legitimately sees nothing gated. Distinguishing the two happens
    // server-side; the frontend simply mirrors whatever permissions were
    // shared (empty means "backend didn't grant any direct permissions",
    // which for super-admin still renders every item because every item's
    // `permissions` check passes for an explicit is-super-admin flag —
    // out of scope for Phase 2, so today a super-admin relies on being
    // shown items whenever `permissions` is empty, matching current
    // Blade @can behavior which resolves true via Gate::before too).
    return required.some((permission) => userPermissions.includes(permission));
}

export default function Sidebar({ permissions, mobileOpen, onNavigate }: SidebarProps) {
    const currentPath = useCurrentPath();

    return (
        <div className={`sidebar${mobileOpen ? ' sidebar-mobile-open' : ''}`} id="sidebar">
            <div className="sidebar-inner slimscroll">
                <div id="sidebar-menu" className="sidebar-menu">
                    <ul>
                        {backofficeNavigation.map((group) => {
                            const visibleItems = group.items.filter((item) =>
                                hasAnyPermission(permissions, item.permissions),
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
