export interface AuthUser {
    id: number;
    name: string;
    email: string | null;
}

export interface ContextOption {
    id: number;
    name: string;
}

export interface Context {
    anneeScolaireId: number | null;
    etablissementId: number | null;
    isAllCenters: boolean;
    canSwitchCenter: boolean;
    currentCenter: ContextOption | null;
    currentAcademicYear: ContextOption | null;
    availableCenters: ContextOption[];
    availableAcademicYears: ContextOption[];
}

/** POST /backoffice/context payload — null etablissement_id means "all centers". */
export interface ContextUpdateForm {
    annee_scolaire_id: number | null;
    etablissement_id: number | null;
}

export interface FlashMessages {
    success: string | null;
    error: string | null;
    warning: string | null;
    info: string | null;
    /** Laravel's password-broker flash convention (`->with('status', ...)`) — rendered like `success`. */
    status: string | null;
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

/**
 * Mirrors App\Domain\Reports\DTOs\DashboardStatsData::toArray() exactly —
 * see docs/dashboard-livewire-to-inertia-map.md for the full per-stat
 * source mapping. paymentsMonth is a pre-formatted decimal string (never a
 * raw float over the wire — CLAUDE.md §17 Money rules), parsed only for
 * display, never for arithmetic.
 */
export interface DashboardStats {
    studentsTotal: number;
    employeesTotal: number;
    employeesActive: number;
    groupsTotal: number;
    groupsEnFormation: number;
    inscriptionsTotal: number;
    inscriptionsActives: number;
    paymentsMonth: string;
    anneeLabel: string | null;
    centreLabel: string | null;
}

export interface DashboardPageProps {
    stats: DashboardStats;
    [key: string]: unknown;
}
