export interface AuthUser {
    id: number;
    name: string;
    email: string | null;
}

export interface Context {
    anneeScolaireId: number | null;
    etablissementId: number | null;
    isAllCenters: boolean;
    canSwitchCenter: boolean;
}

export interface FlashMessages {
    success: string | null;
    error: string | null;
    warning: string | null;
    info: string | null;
}

export interface SharedProps {
    auth: {
        user: AuthUser | null;
        permissions: string[];
    };
    context: Context | null;
    flash: FlashMessages;
    locale: string;
    [key: string]: unknown;
}

export interface Breadcrumb {
    label: string;
    /** Omit for the current (non-navigable) page. */
    href?: string;
    /** True when `href` is a real Inertia page; false for legacy Blade/Livewire routes. */
    inertia?: boolean;
}

export interface NavItem {
    label: string;
    href: string;
    icon: string;
    /** One or more permission strings — item shows if the user has ANY of them. Omit for always-visible items (e.g. Dashboard). */
    permissions?: string[];
    /** Route-name prefixes (Laravel-style, e.g. "backoffice.students.") used for active-state matching against the current URL. */
    matchPaths: string[];
    /** True once this item has a real Inertia page; false renders a plain anchor to the legacy Blade/Livewire route. */
    inertia?: boolean;
}

export interface NavGroup {
    label: string;
    items: NavItem[];
}
