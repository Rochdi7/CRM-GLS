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

/** One entry of Laravel's paginator ->links() array (as serialized by Inertia). */
export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

/**
 * Mirrors Laravel's LengthAwarePaginator JSON shape exactly (data/links/
 * current_page/last_page/total/per_page/from/to) — never re-derive these
 * client-side. `T` is the row shape for the page in question.
 */
export interface PaginatedData<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number | null;
    to: number | null;
}

/**
 * A monetary value already formatted server-side (fixed 2-decimal string,
 * e.g. "1234.56") — never a raw float over the wire (CLAUDE.md §17 Money
 * rules). Parse only for display (`Number(value).toFixed(2)`), never for
 * arithmetic; all totals are computed by Laravel.
 */
export type MoneyDisplay = string;

export interface SafeMediaFile {
    name: string;
    url: string;
    mimeType: string;
    size: number;
}

export interface RelatedRecordLink {
    label: string;
    href: string;
    inertia: boolean;
}

export interface GroupsHistoriqueRow {
    id: number;
    nom: string;
    niveau: string;
    enseignant: string | null;
    centre: string | null;
    anneeScolaire: string | null;
    nombreEtudiants: number;
    dateDebutFormation: string | null;
    dateFinFormation: string | null;
    archivedAt: string | null;
    archivedBy: string | null;
    groupShowUrl: string | null;
}

export interface StudentInscriptionRow {
    reference: string;
    groupe: string | null;
    date: string | null;
    total: MoneyDisplay | null;
    statut: string;
}

export interface StudentPaymentRow {
    reference: string;
    montant: MoneyDisplay;
    methode: string;
    date: string | null;
    caisse: string | null;
}

export interface StudentDetails {
    id: number;
    reference: string;
    nomComplet: string;
    prenom: string;
    niveau: string | null;
    orientation: string | null;
    sexe: string | null;
    dateNaissance: string | null;
    cin: string | null;
    telephone: string | null;
    whatsapp: string | null;
    email: string | null;
    adresse: string | null;
    centre: string | null;
    photoUrl: string | null;
    parent: {
        relation: string | null;
        nom: string | null;
        sexe: string | null;
        cin: string | null;
        telephone: string | null;
    } | null;
    inscriptions: StudentInscriptionRow[];
    paiementsTotal: MoneyDisplay;
    paiements: StudentPaymentRow[];
}

export interface GroupFeeRow {
    nom: string;
    classification: string | null;
    montant: MoneyDisplay;
}

export interface GroupInscriptionRow {
    reference: string;
    student: string | null;
    studentShowUrl: string | null;
    date: string | null;
    statut: string;
}

export interface GroupDetails {
    id: number;
    nom: string;
    niveau: string | null;
    enseignant: string | null;
    centre: string | null;
    anneeScolaire: string | null;
    dateDebutFormation: string | null;
    dateFinFormation: string | null;
    statut: string;
    canArchive: boolean;
    isFinished: boolean;
    archiveUrl: string;
    fees: GroupFeeRow[];
    inscriptions: GroupInscriptionRow[];
}

export interface InscriptionFeeRow {
    nom: string;
    montant: MoneyDisplay;
    paye: MoneyDisplay;
    dateEcheance: string | null;
    statut: string;
}

export interface InscriptionDetails {
    id: number;
    reference: string;
    student: string | null;
    studentShowUrl: string | null;
    groupe: string | null;
    anneeScolaire: string | null;
    date: string | null;
    statut: string;
    totalDu: MoneyDisplay;
    totalPaye: MoneyDisplay;
    reste: MoneyDisplay;
    fees: InscriptionFeeRow[];
}

export interface CaisseMovementRow {
    reference: string;
    label: string;
    date: string | null;
    montant: MoneyDisplay;
    extra?: string | null;
}

export interface CaisseTransferRow extends CaisseMovementRow {
    direction: 'in' | 'out';
    statut: string;
}

export interface CaisseDetails {
    id: number;
    nom: string;
    centre: string | null;
    responsable: string | null;
    solde: MoneyDisplay;
    statut: string;
    encaissements: CaisseMovementRow[];
    depenses: CaisseMovementRow[];
    remboursements: CaisseMovementRow[];
    transfers: CaisseTransferRow[];
}

export interface EncaissementDetails {
    id: number;
    reference: string;
    montant: MoneyDisplay;
    methode: string;
    date: string | null;
    student: string | null;
    studentShowUrl: string | null;
    inscriptionReference: string | null;
    inscriptionShowUrl: string | null;
    groupe: string | null;
    caisse: string | null;
    agent: string | null;
    note: string | null;
    cheque: {
        numero: string | null;
        banque: string | null;
        dateEcheance: string | null;
    } | null;
    fee: {
        nom: string;
        dateEcheance: string | null;
        totalDu: MoneyDisplay;
        totalPaye: MoneyDisplay;
        reste: MoneyDisplay;
        statut: string;
    } | null;
}

export interface DepenseDetails {
    id: number;
    reference: string;
    montant: MoneyDisplay;
    typeDepense: string | null;
    dateDepense: string | null;
    caisse: string | null;
    centre: string | null;
    agent: string | null;
    recordedAt: string | null;
    description: string | null;
    motsCles: string[];
    note: string | null;
    canViewList: boolean;
    receipts: SafeMediaFile[];
}

export interface CaisseTransferDetails {
    id: number;
    reference: string;
    caisseSource: string | null;
    caisseDestination: string | null;
    montant: MoneyDisplay;
    date: string | null;
    statut: string;
    requestedBy: string | null;
    validatedBy: string | null;
    note: string | null;
    isPending: boolean;
    soldeSourceAvant: MoneyDisplay | null;
    soldeSourceApres: MoneyDisplay | null;
    soldeDestAvant: MoneyDisplay | null;
    soldeDestApres: MoneyDisplay | null;
}
