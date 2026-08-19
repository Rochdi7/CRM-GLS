import type { ReactNode } from 'react';

export interface AuthUser {
    id: number;
    name: string;
    email: string | null;
    photoUrl: string;
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

export interface FlashMessages {
    success: string | null;
    error: string | null;
    warning: string | null;
    info: string | null;
    /** Laravel's password-broker flash convention (`->with('status', ...)`) — rendered like `success`. */
    status: string | null;
    /**
     * One-time login credentials for a just-created employee
     * (EmployeeController::store() → EmployeeObserver). Shown once by the
     * Employees add/edit modal, driven off this shared prop (not a
     * sentinel form-state id) — never persisted, never re-shown on reload.
     */
    newEmployeeCredentials?: { username: string; password: string } | null;
    /**
     * One-time notice after a group's teacher changeover: the group's emploi
     * du temps was stopped (créneaux closed, future "Prévue" séances removed)
     * and a new one must be built for the incoming teacher.
     */
    emploiDuTempsArrete?: { creneaux: number; seances: number; url: string } | null;
    /** One-time regenerated password for an existing user (Users module) — not consumed by the Employees page. */
    regeneratedPassword?: string | null;
}

export interface SharedProps {
    auth: {
        user: AuthUser | null;
        permissions: string[];
        isSuperAdmin: boolean;
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
    enseignantsTotal: number;
    parentsTotal: number;
    groupsTotal: number;
    groupsEnFormation: number;
    inscriptionsTotal: number;
    inscriptionsActives: number;
    inscriptionsAnnulees: number;
    inscriptionsChangement: number;
    paymentsMonth: string;
    depensesMonth: string;
    depensesMonthCount: number;
    anneeLabel: string | null;
    centreLabel: string | null;
}

/** One séance inside the "Résumé des séances" dashboard calendar (GetSeancesCalendar). */
export interface SeanceCalendarEntry {
    id: number;
    groupNom: string | null;
    enseignant: string | null;
    statut: string;
    heureDebut: string | null;
    heureFin: string | null;
    showUrl: string;
}

/** Mirrors GetSeancesCalendar's return shape — one month of séances keyed by 'YYYY-MM-DD'. */
export interface SeancesCalendarData {
    month: string;
    days: Record<string, SeanceCalendarEntry[]>;
}

/** Mirrors App\Domain\Reports\Actions\GetAnnualFraisSummary's return shape — 12 monthly points, 5 series, each a pre-formatted decimal string (money never floated over the wire). */
export interface AnnualFraisSummary {
    months: string[];
    chiffreAffaire: string[];
    collecte: string[];
    resteAPayer: string[];
    depenses: string[];
    encaissements: string[];
}

export interface DashboardPageProps {
    stats: DashboardStats;
    annualFrais: AnnualFraisSummary;
    annualFraisYear: number;
    annualFraisYears: number[];
    seancesCalendar: SeancesCalendarData;
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
    dateEcheance: string | null;
}

export interface GroupInscriptionRow {
    reference: string;
    student: string | null;
    studentShowUrl: string | null;
    date: string | null;
    dateDebut: string | null;
    dateFin: string | null;
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
    /** Header banner title, driven by statut (e.g. "Formation en cours" for En formation). */
    statutLabel: string;
    canArchive: boolean;
    isFinished: boolean;
    archiveUrl: string;
    etudiantsDistinctsCount: number;
    inscriptionsActivesCount: number;
    inscriptionsChangementCount: number;
    inscriptionsAnnuleesCount: number;
    fees: GroupFeeRow[];
    inscriptions: GroupInscriptionRow[];
    /** Full teaching-assignment history — the Actif period first, then the archived ones. */
    enseignantsHistorique: GroupEnseignantRow[];
    canChangeEnseignant: boolean;
    changerEnseignantUrl: string;
    emploiDuTempsUrl: string;
}

/**
 * One teaching-assignment period of a group. Exactly one row is `isActif`;
 * the others are archived past periods kept for per-teacher payroll
 * (dateDebut → dateFin).
 */
export interface GroupEnseignantRow {
    id: number;
    enseignant: string | null;
    dateDebut: string | null;
    dateFin: string | null;
    statut: string;
    isActif: boolean;
    motif: string | null;
}

export interface InscriptionFeeRow {
    nom: string;
    montantInitial: MoneyDisplay;
    montant: MoneyDisplay;
    paye: MoneyDisplay;
    dateEcheance: string | null;
    statut: string;
}

/** One row of the "Paiements" table on the Inscription Show page — GetInscriptionPayments's own shape, dates re-formatted to d/m/Y by GetInscriptionDetails. */
export interface InscriptionPaymentRow {
    id: number;
    reference: string;
    feeNom: string | null;
    montant: MoneyDisplay;
    methode: string;
    datePaiement: string | null;
    rembourse: boolean;
}

export interface InscriptionDetails {
    id: number;
    reference: string;
    student: string | null;
    studentShowUrl: string | null;
    groupe: string | null;
    groupShowUrl: string | null;
    enseignant: string | null;
    anneeScolaire: string | null;
    date: string | null;
    dateDebut: string | null;
    dateFin: string | null;
    statut: string;
    totalDu: MoneyDisplay;
    totalPaye: MoneyDisplay;
    reste: MoneyDisplay;
    fees: InscriptionFeeRow[];
    payments: InscriptionPaymentRow[];
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

// --- Attendance (Présences) -------------------------------------------------

export interface SeanceRow {
    id: number;
    dateSeance: string;
    heureDebut: string | null;
    heureFin: string | null;
    groupId: number;
    groupNom: string | null;
    groupNiveau: string | null;
    enseignant: string | null;
    enseignantId: number | null;
    statut: string;
    note: string | null;
    presencesCount: number;
    presentsCount: number;
    absentsCount: number;
    showUrl: string;
}

export interface SeanceForm {
    group_id: string;
    date_seance: string;
    heure_debut: string;
    heure_fin: string;
    enseignant_id: string;
    statut: string;
    note: string;
    [key: string]: string;
}

// --- Emploi du temps (weekly recurring schedule — créneaux) -----------------

/** One créneau row — mirrors GetCreneauxGrille's ->map() output exactly. */
export interface CreneauRow {
    id: number;
    groupId: number;
    groupNom: string;
    groupNiveau: string | null;
    jourSemaine: number;
    heureDebut: string;
    heureFin: string;
    enseignant: string | null;
    enseignantId: number | null;
    salle: string | null;
    salleId: number | null;
}

/** Edit form — one créneau, one day. */
export interface CreneauForm {
    group_id: string;
    jour_semaine: string;
    heure_debut: string;
    heure_fin: string;
    enseignant_id: string;
    salle_id: string;
    [key: string]: string;
}

/** Create form — one submit can create a créneau per selected day (jours_semaine). */
export interface CreneauCreateForm {
    group_id: string;
    jours_semaine: string[];
    heure_debut: string;
    heure_fin: string;
    enseignant_id: string;
    salle_id: string;
    [key: string]: string | string[];
}

export interface SeanceStudentLine {
    id: number;
    reference: string;
    nom: string;
    prenom: string;
    photoUrl: string | null;
    statut: string | null;
    note: string;
}

export interface SeanceDetails {
    id: number;
    dateSeance: string;
    heureDebut: string | null;
    heureFin: string | null;
    groupNom: string | null;
    groupNiveau: string | null;
    enseignant: string | null;
    enseignantId: number | null;
    statut: string;
    note: string | null;
    motifAnnulation: string | null;
    students: SeanceStudentLine[];
}

// --- Stock ------------------------------------------------------------------

export interface StockArticleRow {
    id: number;
    reference: string;
    nom: string;
    stockTypeId: number;
    stockType: string | null;
    quantite: number;
    seuilAlerte: number | null;
    enAlerte: boolean;
    etablissement: string | null;
    statut: string;
    note: string | null;
    mouvementsCount: number;
}

export interface StockArticleForm {
    nom: string;
    stock_type_id: number | '';
    seuil_alerte: string;
    statut: string;
    note: string;
    [key: string]: string | number;
}

export interface StockMouvementRow {
    id: number;
    date: string | null;
    articleNom: string | null;
    articleReference: string | null;
    type: string;
    quantite: number;
    quantiteAvant: number;
    quantiteApres: number;
    note: string | null;
    par: string | null;
}

export interface StockMouvementForm {
    stock_article_id: string;
    type: string;
    quantite: string;
    note: string;
    [key: string]: string;
}

// --- Shared form/CRUD primitives -------------------------------------------

export interface SelectOption {
    value: string | number;
    label: string;
    /** Optional leading visual (e.g. a country flag) rendered before the label, both in the closed control and the option list. */
    icon?: ReactNode;
    /** Optional compact label shown in the closed control only (e.g. a dial code); the option list always shows the full `label`. */
    shortLabel?: string;
}

/** Machine-readable subset of Laravel validation errors (Inertia's `errors` shared prop shape). */
export type LaravelValidationErrors = Record<string, string>;

/** Lightweight action-availability flags — UI convenience only; every endpoint re-authorizes server-side. */
export interface CrudPermissions {
    create: boolean;
    update: boolean;
    delete: boolean;
}

// --- Phase 6: simple CRUD modules ------------------------------------------

export type SettingsTab = 'etablissements' | 'annees-scolaires' | 'salles' | 'frais' | 'banques' | 'motifs-annulation';

export interface EtablissementRow {
    id: number;
    nomCentre: string;
    ville: string;
    adresse: string | null;
    /** ICE (Identifiant Commun de l'Entreprise) — printed on payment receipts. */
    ice: string | null;
    telephone: string | null;
    email: string | null;
    siegeSocial: boolean;
    sallesCount: number;
}

export interface EtablissementForm {
    nom_centre: string;
    ville: string;
    telephone: string;
    email: string;
    siege_social: boolean;
}

export interface AnneeScolaireRow {
    id: number;
    nom: string;
    dateDebut: string;
    dateFin: string;
    parDefaut: boolean;
    inscriptionOuverte: boolean;
}

export interface AnneeScolaireForm {
    nom: string;
    date_debut: string;
    date_fin: string;
    par_defaut: boolean;
    inscription_ouverte: boolean;
}

export interface SalleRow {
    id: number;
    nom: string;
    centre: string | null;
    capacite: number | null;
    statut: string;
}

export interface SalleForm {
    nom: string;
    etablissement_id: number | '';
    capacite: number | '';
    statut: string;
}

export interface FraisRow {
    id: number;
    nom: string;
    /** Catalog default every group starts from; a group may override it. */
    montantDefaut: string;
    statut: string;
    groupsCount: number;
}

export interface FraisForm {
    nom: string;
    montant_defaut: string;
    statut: string;
}

export interface BanqueRow {
    id: number;
    nom: string;
    statut: string;
}

export interface BanqueForm {
    nom: string;
    statut: string;
}

export interface MotifAnnulationRow {
    id: number;
    nom: string;
    isSystem: boolean;
    statut: string;
}

export interface MotifAnnulationForm {
    nom: string;
    statut: string;
}

export interface TypeDepenseRow {
    id: number;
    nom: string;
    statut: string;
    isSystem: boolean;
    depensesCount: number;
}

export interface TypeDepenseForm {
    nom: string;
    statut: string;
}

export interface StockTypeRow {
    id: number;
    nom: string;
    statut: string;
    isSystem: boolean;
    articlesCount: number;
}

export interface StockTypeForm {
    nom: string;
    statut: string;
}

export interface SettingsPageProps {
    activeTab: SettingsTab;
    availableTabs: SettingsTab[];
    permissions: Record<SettingsTab, CrudPermissions>;
    etablissements?: PaginatedData<EtablissementRow>;
    anneesScolaires?: PaginatedData<AnneeScolaireRow>;
    salles?: PaginatedData<SalleRow>;
    centerOptions?: SelectOption[];
    /** Salles tab: false only on "Tous les centres" — gates the redundant Centre column. */
    centerLocked?: boolean;
    frais?: PaginatedData<FraisRow>;
    banques?: PaginatedData<BanqueRow>;
    motifsAnnulation?: PaginatedData<MotifAnnulationRow>;
    [key: string]: unknown;
}

export interface TypesDepensesPageProps {
    types: PaginatedData<TypeDepenseRow>;
    filters: { search: string };
    permissions: CrudPermissions;
    [key: string]: unknown;
}

// --- Phase 7: Roles & Permissions (full-page create/edit, no modal) -------

/** Mirrors App\Domain\Settings\Queries\GetRolesList::present() exactly. */
export interface RoleRow {
    id: number;
    name: string;
    label: string;
    isProtected: boolean;
    permissionsCount: number;
    usersCount: number;
}

export interface RolesIndexPageProps {
    roles: PaginatedData<RoleRow>;
    search: string;
    perPage: number;
    [key: string]: unknown;
}

/** `{ [groupLabel]: { [permissionName]: frenchLabel } }` — PermissionRegistry::grouped(). */
export type PermissionGroups = Record<string, Record<string, string>>;

export interface RoleCreatePageProps {
    permissionGroups: PermissionGroups;
    [key: string]: unknown;
}

/** RoleController@edit's `role` prop shape — id/name/label only, no permissions embedded (those come separately as selectedPermissions). */
export interface RoleEditSummary {
    id: number;
    name: string;
    label: string;
}

export interface RoleEditPageProps {
    role: RoleEditSummary;
    selectedPermissions: string[];
    permissionGroups: PermissionGroups;
    [key: string]: unknown;
}

/** Payload submitted to backoffice.roles.store / backoffice.roles.update. */
export interface RoleFormPayload {
    label: string;
    name: string;
    permissions: string[];
}

// --- Phase 7: Users list + authorization -----------------------------------

/** One row of App\Domain\Employees\Queries\GetUsersList — see UserController::index(). */
export interface UserRow {
    id: number;
    name: string;
    email: string;
    username: string | null;
    isActive: boolean;
    mustChangePassword: boolean;
    roles: string[];
    employee: {
        reference: string;
        nomComplet: string;
        etablissement: string | null;
    } | null;
}

export interface UsersIndexPageProps {
    users: PaginatedData<UserRow>;
    filters: { search: string; perPage: number };
    perPageOptions: number[];
    /** False only on "Tous les centres" — gates the redundant Centre column. */
    centerLocked: boolean;
    [key: string]: unknown;
}

/** Edit-modal form fields — matches UpdateUserRequest::rules() exactly. */
export interface UserEditForm {
    name: string;
    email: string;
    username: string;
    is_active: boolean;
}

/** One selectable role — matches UserAuthorizationController::edit()'s `roles` prop shape. */
export interface AuthorizationRoleOption {
    name: string;
    label: string;
    permissionsCount: number;
    /** Permission machine names this role grants — lets the UI compute "via role" provenance live, before saving. */
    permissionNames: string[];
}

/** UserAuthorizationController::edit()'s `targetUser` prop shape. */
export interface AuthorizationTargetUser {
    id: number;
    name: string;
    email: string;
}

/**
 * Full props for Backoffice/Users/Authorization.tsx — mirrors
 * UserAuthorizationController::edit() + App\Livewire\Backoffice\Users\ManageAuthorization
 * exactly (roles/groups/labels all come from PermissionRegistry, see
 * app/Support/Authorization/PermissionRegistry.php).
 */
export interface UsersAuthorizationPageProps {
    targetUser: AuthorizationTargetUser;
    selectedRoles: string[];
    directPermissions: string[];
    roles: AuthorizationRoleOption[];
    roleLabels: Record<string, string>;
    /** [French group label => [permission name => French label]] — PermissionRegistry::grouped(). */
    groups: Record<string, Record<string, string>>;
    totalPermissions: number;
    isSuperAdmin: boolean;
    canAssignDirect: boolean;
    [key: string]: unknown;
}

/** useForm() payload for the authorization Save action — matches SyncUserAuthorizationRequest::rules(). */
export interface SyncUserAuthorizationForm {
    roles: string[];
    directPermissions: string[];
}

// --- Phase 7: Employees (Inertia/React list + modal CRUD) -------------------

/** One row of the Employees list — mirrors GetEmployeesList's ->through() mapping exactly. */
export interface EmployeeRow {
    id: number;
    reference: string;
    nom: string;
    prenom: string;
    nomComplet: string;
    sexe: string | null;
    categorie: string;
    statut: string;
    telephone: string | null;
    whatsapp: string | null;
    email: string | null;
    adresse: string | null;
    note: string | null;
    dateNaissance: string | null;
    dateEmbauche: string | null;
    salaire: MoneyDisplay | null;
    /** Primary center (first assigned) — kept for the detail/edit defaults. */
    etablissementId: number | null;
    etablissement: string | null;
    /** Every center this employee is assigned to (always at least one). */
    etablissementIds: number[];
    etablissementNoms: string[];
    photoUrl: string | null;
    photoThumbUrl: string | null;
    userId: number | null;
    username: string | null;
}

export interface EmployeesFilters {
    search: string;
    categorieFilter: string;
    statutFilter: string;
    etablissementFilter: string;
    perPage: number;
}

export interface EmployeesPageProps {
    employees: PaginatedData<EmployeeRow>;
    filters: EmployeesFilters;
    perPageOptions: number[];
    categories: string[];
    statuts: string[];
    sexes: string[];
    defaultCountry: string;
    etablissements: Array<{ id: number; nom_centre: string }>;
    centerLocked: boolean;
    contextCenterId: number | null;
    contextCenterName: string | null;
    /** UI convenience only — hides the "Voir le compte" row action; real enforcement is users.assign-roles on the server (UserController::regeneratePassword). */
    canManageUsers: boolean;
    [key: string]: unknown;
}

// --- Phase 8: Students (Inertia/React list + modal CRUD) --------------------

/** One row of the Students list — mirrors GetStudentsList's ->through() mapping exactly. */
export interface StudentRow {
    id: number;
    reference: string;
    nomComplet: string;
    prenom: string;
    nom: string;
    sexe: string | null;
    dateNaissance: string | null;
    age: number | null;
    cin: string | null;
    telephone: string | null;
    whatsapp: string | null;
    email: string | null;
    adresse: string | null;
    niveau: string | null;
    orientation: string | null;
    domaine: string | null;
    examenType: string | null;
    etablissementId: number | null;
    etablissement: string | null;
    parentNom: string | null;
    parentRelation: string | null;
    parentSexe: string | null;
    parentCin: string | null;
    parentTelephone: string | null;
    parentWhatsapp: string | null;
    note: string | null;
    photoUrl: string | null;
    photoThumbUrl: string | null;
}

export interface StudentsFilters {
    search: string;
    niveauFilter: string;
    sexeFilter: string;
    etablissementFilter: string;
    ageSort: string;
    referenceFilter: string;
    nomFilter: string;
    prenomFilter: string;
    telephoneFilter: string;
    /** État d'inscription — '' (tous) | 'active' | 'cancelled' | 'none'. */
    inscriptionFilter: string;
    perPage: number;
}

export interface StudentsPageProps {
    students: PaginatedData<StudentRow>;
    filters: StudentsFilters;
    perPageOptions: number[];
    niveauxInteret: string[];
    domaines: string[];
    examenTypes: string[];
    sexes: string[];
    parentRelations: string[];
    niveauxAvecDomaine: string[];
    niveauStudium: string;
    defaultCountry: string;
    etablissements: Array<{ id: number; nom_centre: string }>;
    centerLocked: boolean;
    contextCenterId: number | null;
    [key: string]: unknown;
}

// --- Phase 8: Groups (Inertia/React list + modal CRUD with fee lines) ------

/** One row of the Groups list — mirrors GetGroupsList's ->through() mapping exactly. */
export interface GroupRow {
    id: number;
    nom: string;
    niveau: string;
    enseignant: string | null;
    enseignantId: number | null;
    dateDebutFormation: string | null;
    dateFinFormation: string | null;
    statut: string;
    inscriptionsCount: number;
    inscriptionsActivesCount: number;
    inscriptionsAnnuleesCount: number;
    inscriptionsChangementCount: number;
    etudiantsDistinctsCount: number;
    fraisCount: number;
    showUrl: string;
    /** Keyed by frais_id — prefills the edit modal's fee-lines table without a second request. */
    fraisLignes: Record<number, { montant: string; dateEcheance: string; classification: string }>;
}

/** One row of the "Statistique" drill-down modal (GetGroupStudentsBySegment). */
export interface GroupStudentSegmentRow {
    reference: string;
    prenom: string;
    nom: string;
    cin: string | null;
    telephone: string | null;
    dateNaissance: string | null;
    niveauScolaire: string | null;
    dateInscription: string | null;
}

export interface GroupFraisLigne {
    montant: string;
    date_echeance: string;
    classification: string;
}

export interface GroupsFilters {
    search: string;
    statutFilter: string;
    enseignantFilter: string;
    dateFrom: string;
    dateTo: string;
    perPage: number;
}

export interface GroupFormOption {
    id: number;
    nom: string;
}

export interface GroupsPageProps {
    groups: PaginatedData<GroupRow>;
    statutCounts: Record<string, number>;
    filters: GroupsFilters;
    perPageOptions: number[];
    niveaux: string[];
    statuts: string[];
    enseignants: GroupFormOption[];
    fraisCatalog: GroupFormOption[];
    [key: string]: unknown;
}

// --- Phase 9: Inscriptions (Inertia/React list + modal CRUD with fee lines) -

/** One row of the Inscriptions list — mirrors GetInscriptionsList's ->through() mapping exactly. */
export interface InscriptionRow {
    id: number;
    reference: string;
    studentId: number | null;
    groupId: number | null;
    student: string | null;
    studentShowUrl: string | null;
    groupe: string | null;
    date: string | null;
    dateDebut: string | null;
    dateFin: string | null;
    montantTotal: MoneyDisplay | null;
    feesCount: number;
    statut: string;
    showUrl: string;
}

export interface InscriptionFormOption {
    id: number;
    label: string;
}

/** One "Frais disponible" line loaded from the selected group (GetGroupInscriptionFees). */
export interface InscriptionGroupFee {
    fraisId: number;
    nom: string;
    montantInitial: MoneyDisplay;
    dateEcheance: string;
}

export interface InscriptionGroupFeesResponse {
    fees: InscriptionGroupFee[];
    dateDebut: string | null;
    dateFin: string | null;
}

/**
 * One editable fee line — used both by the create form's "Frais
 * disponibles" table (mirrors Livewire's $feeLines shape) and the edit
 * modal's "Frais de cette inscription" table. `id` is set only for an
 * existing InscriptionFee row being edited; omitted/undefined for a
 * brand-new line (create form, or a line added while editing).
 */
export interface InscriptionFeeLine {
    id?: number;
    fraisId: number | null;
    nom: string;
    montantInitial: string;
    remisePct: string;
    remiseMontant: string;
    note: string;
    dateEcheance: string;
    /** Server-computed, informational only on the edit table (Non payé/Payé partiellement/Payé). Absent on create-form lines. */
    statut?: string;
    /** Server-computed, informational only on the edit table (amount already paid — drives "Reste à payer"). Absent on create-form lines. */
    paye?: string;
}

/** One hidden ("masqué") fee line on an existing inscription — read-only, restorable via inscriptions.fees.restore. */
export interface HiddenInscriptionFee {
    id: number;
    nom: string;
    montant: string;
    dateEcheance: string;
}

export interface InscriptionsFilters {
    search: string;
    statutFilter: string;
    groupFilter: string;
    referenceFilter: string;
    studentFilter: string;
    perPage: number;
}

export interface InscriptionsPageProps {
    inscriptions: PaginatedData<InscriptionRow>;
    filters: InscriptionsFilters;
    perPageOptions: number[];
    statuts: string[];
    niveaux: string[];
    niveauxInteret: string[];
    domaines: string[];
    examenTypes: string[];
    sexes: string[];
    parentRelations: string[];
    niveauxAvecDomaine: string[];
    niveauStudium: string;
    defaultCountry: string;
    students: InscriptionFormOption[];
    groups: InscriptionFormOption[];
    /** Active fee catalog — feeds the edit modal's "Ajouter un frais" picker. */
    frais: InscriptionFormOption[];
    /** UI convenience only — hides the edit-modal fee controls; real enforcement is registrations.manage-fees on the server (InscriptionController::updateFees). */
    canManageFees: boolean;
    /** UI convenience only — hides the "Changement de groupe" row action; real enforcement is registrations.change-group on the server (InscriptionController::changeGroup). */
    canChangeGroup: boolean;
    [key: string]: unknown;
}

// --- Phase 10: Finance (Caisses, Encaissements, Depenses, Remboursements, CaisseTransfers) ---

export interface FinanceOption {
    id: number;
    nom: string;
}

/** One row of the Caisses list — mirrors GetCaissesList's ->through() mapping exactly. */
export interface CaisseRow {
    id: number;
    nom: string;
    centre: string | null;
    responsable: string | null;
    solde: MoneyDisplay;
    statut: string;
    showUrl: string;
}

/** One journal row — mirrors GetCaisseJournal's rows() mapping exactly. */
export interface CaisseJournalRow {
    type: 'paiement' | 'depense' | 'remboursement' | 'transfert';
    reference: string;
    libelle: string | null;
    tiers: string | null;
    montant: MoneyDisplay;
    sens: 1 | -1;
    date: string | null;
    note: string | null;
    agent: string | null;
    url: string | null;
}

/** GetCaisseJournal's full return shape for one scope ('mine'|'all'). */
export interface CaisseJournalData {
    caissesInScope: Array<{ id: number; nom: string }>;
    totalEncaissements: MoneyDisplay;
    totalDepenses: MoneyDisplay;
    /** Refunds paid out of the till — a separate outflow from `totalDepenses`, never folded into it. */
    totalRemboursements: MoneyDisplay;
    solde: MoneyDisplay;
    totauxParType: Record<string, MoneyDisplay>;
    total: number;
    lastPage: number;
    page: number;
    rows: CaisseJournalRow[];
}

/** One row of the Caisse Transfers list — mirrors GetCaisseTransfersList's ->through() mapping exactly. */
export interface CaisseTransferRow {
    id: number;
    reference: string;
    /** Owning employee's name (Caisse::responsable()), resolved rather than read off caisses.nom. */
    expediteur: string | null;
    destinataire: string | null;
    caisseSourceId: number | null;
    caisseDestinationId: number | null;
    /** Relative to the viewer: 'Réception' when one of their own tills is the destination, 'Transfert' otherwise. */
    typeTransaction: 'Réception' | 'Transfert';
    montant: MoneyDisplay;
    dateTransfert: string | null;
    statut: string;
    requestedBy: string | null;
    requestedById: number | null;
    validatedBy: string | null;
    note: string | null;
    isPending: boolean;
    showUrl: string;
}

export interface CaisseTransferFormOption extends FinanceOption {
    solde: MoneyDisplay;
}

export interface CaissesPageProps {
    canViewCaisses: boolean;
    canViewTransfers: boolean;
    journalMine: CaisseJournalData | null;
    transfers: PaginatedData<CaisseTransferRow> | null;
    transferStatutCounts: Record<string, number>;
    transferCaisses: CaisseTransferFormOption[];
    transferStatuts: string[];
    currentEmployeeId: number | null;
    /** The acting employee's OWN till — the transfer modal's fixed, read-only source (null when the account has no employee/till). */
    myCaisse: CaisseTransferFormOption | null;
    transferFilters: { search: string; statutFilter: string; typeFilter: string };
    [key: string]: unknown;
}

/** One row of the Encaissements list — mirrors GetEncaissementsList's ->through() mapping exactly. */
export interface EncaissementRow {
    id: number;
    reference: string;
    student: string | null;
    /** Student matricule (ETU-… reference) — shown as "REF | NOM" in the edit modal. */
    studentRef: string | null;
    studentId: number | null;
    inscriptionId: number | null;
    feeNom: string | null;
    /** Full amount of the paid fee — read-only context in the edit modal. */
    feeMontantTotal: MoneyDisplay | null;
    /** Fee amount minus everything already paid on it. */
    feeReste: MoneyDisplay | null;
    caisse: string | null;
    caisseId: number | null;
    montant: MoneyDisplay;
    methode: string;
    datePaiement: string | null;
    numeroCheque: string | null;
    banque: string | null;
    dateEcheanceCheque: string | null;
    note: string | null;
    agent: string | null;
    /** Only populated on the Avances tab — how much of this advance has already been applied to a fee. */
    montantUtilise: MoneyDisplay | null;
    /** Only populated on the Avances tab — montant minus montantUtilise. */
    montantRestant: MoneyDisplay | null;
    studentEmail: string | null;
    showUrl: string;
    /** Printable receipt page — append ?format=a6|a5|a5x2. */
    recuUrl: string;
    /** POST target to email the A5 receipt (SendRecuEmailRequest: { email }). */
    recuEmailUrl: string;
}

export interface EncaissementsFilters {
    search: string;
    caisseFilter: string;
    methodeFilter: string;
    dateFrom: string;
    dateTo: string;
    perPage: number;
    /** Page view tab: '' (all) | 'avance' (partially-settled fees) | 'cheque'. */
    view: string;
    referenceFilter: string;
    studentFilter: string;
    numeroChequeFilter: string;
    banqueFilter: string;
}

export interface EncaissementsPageProps {
    encaissements: PaginatedData<EncaissementRow>;
    caisses: FinanceOption[];
    students: FinanceOption[];
    methodes: string[];
    /** Active bank names from the catalog (Paramètres → Banques) — the Chèque form's dropdown source. */
    banques: string[];
    filters: EncaissementsFilters;
    [key: string]: unknown;
}

/** One row of the Chèques list — mirrors GetChequesList's ->through() mapping exactly. */
export interface ChequeLinkedEncaissement {
    id: number;
    reference: string;
    montant: MoneyDisplay;
    studentId: number | null;
    studentNom: string | null;
}

export interface ChequeRow {
    id: number;
    reference: string;
    source: string;
    studentId: number | null;
    proprietaire: string | null;
    proprietaireNom: string | null;
    telephone: string | null;
    whatsapp: string | null;
    numeroCheque: string;
    montant: MoneyDisplay;
    reste: MoneyDisplay;
    banque: string | null;
    dateReception: string | null;
    type: string;
    dateEcheance: string | null;
    statut: string;
    note: string;
    agentNom: string | null;
    retourneLe: string | null;
    retourneParNom: string | null;
    encaissements: ChequeLinkedEncaissement[];
}

export interface ChequesFilters {
    numeroFilter: string;
    proprietaireFilter: string;
    banqueFilter: string;
    typeFilter: string;
    statutFilter: string;
    dateEcheanceFrom: string;
    dateEcheanceTo: string;
    perPage: number;
}

/** A student with a parent/guardian on file — feeds the "Source: Parents" owner picker. */
export interface ChequeParentOption {
    id: number;
    studentNom: string;
    parentNom: string;
    parentRelation: string | null;
}

export interface ChequesPageProps {
    cheques: PaginatedData<ChequeRow>;
    filters: ChequesFilters;
    perPageOptions: number[];
    sources: string[];
    types: string[];
    statuts: string[];
    banques: string[];
    students: FinanceOption[];
    parents: ChequeParentOption[];
    canCreate: boolean;
    canUpdate: boolean;
    [key: string]: unknown;
}

/** One chèque option in the payment form's "Payer avec un chèque" dropdown (ChequeController::studentCheques). */
export interface StudentChequeOption {
    id: number;
    numeroCheque: string;
    banque: string | null;
    montant: MoneyDisplay;
    reste: MoneyDisplay;
    statut: string;
}

/** One "Frais disponible" line loaded from the selected inscription (GetInscriptionUnpaidFees). */
export interface UnpaidFee {
    id: number;
    nom: string;
    montantInitial: MoneyDisplay;
    paye: MoneyDisplay;
    reste: MoneyDisplay;
    statut: string;
    dateEcheance: string | null;
}

/** One payment row of the "Convertir en avance" checklist (GetInscriptionPayments). */
export interface InscriptionPaymentRow {
    id: number;
    reference: string;
    feeNom: string | null;
    montant: MoneyDisplay;
    methode: string;
    datePaiement: string | null;
    /** Already refunded — cannot be converted into an advance. */
    rembourse: boolean;
}

/** One editable payment line in the create-form's cascade table — mirrors Livewire's $paymentLines shape. */
export interface PaymentLine {
    feeId: number;
    nom: string;
    montantInitial: string;
    reste: string;
    dateEcheance: string | null;
    montant: string;
    methode: string;
    datePaiement: string;
    /** Tracked chèque (Chèques module) this row pays with, when methode = Chèque — required, no manual entry fallback. */
    chequeId: number | '';
}

/** One row of the Depenses list — mirrors GetDepensesList's ->through() mapping exactly. */
export interface DepenseRow {
    id: number;
    reference: string;
    typeDepense: string | null;
    typeDepenseId: number | null;
    caisse: string | null;
    caisseId: number | null;
    groupId: number | null;
    montant: MoneyDisplay;
    methodePaiement: string | null;
    dateDepense: string | null;
    referenceFacture: string | null;
    description: string | null;
    motsCles: string | null;
    note: string | null;
    agent: string | null;
    receiptsCount: number;
    showUrl: string;
}

export interface DepensesFilters {
    search: string;
    typeFilter: string;
    caisseFilter: string;
    dateFrom: string;
    dateTo: string;
    perPage: number;
}

/** One row of the Remboursements list — mirrors GetRemboursementsList's ->through() mapping exactly. */
export interface RemboursementRow {
    id: number;
    reference: string;
    beneficiaire: string | null;
    beneficiaireId: number | null;
    caisse: string | null;
    caisseId: number | null;
    montant: MoneyDisplay;
    dateRemboursement: string | null;
    motif: string | null;
    note: string | null;
    agent: string | null;
}

/** One of a student's fee-targeted payments — the Remboursement form's "which payment?" picker (GetStudentPaymentsForRefund). */
export interface EncaissementFormOption {
    id: number;
    reference: string;
    montant: MoneyDisplay;
    methode: string;
    date: string | null;
    feeNom: string | null;
    dejaRembourse: MoneyDisplay;
}

export interface DepensesPageProps {
    canViewDepenses: boolean;
    canViewRemboursements: boolean;
    /** The acting employee's own till balance — null if they have no employee record. */
    soldeActuel: MoneyDisplay | null;
    depenses: PaginatedData<DepenseRow> | null;
    montantTotal: MoneyDisplay | null;
    /** Only the "Paiement prof" system type — its own tab, excluded from `depenses`. */
    paiementsProf: PaginatedData<DepenseRow> | null;
    paiementsProfTotal: MoneyDisplay | null;
    typesDepenses: FinanceOption[];
    /** id of the "Paiement prof" type — filtered out of the Dépenses tab's Type filter. */
    paiementProfTypeId: number | null;
    groups: FinanceOption[];
    methodes: string[];
    justificatifMimes: string[];
    justificatifMaxKb: number;
    remboursements: PaginatedData<RemboursementRow> | null;
    students: FinanceOption[];
    filters: DepensesFilters;
    [key: string]: unknown;
}

// --- Gestion des recouvrements (overdue fees report, GetRetardsList) ------

/** One row of the Recouvrement list — mirrors GetRetardsList's ->mapRow() mapping exactly. */
export interface RetardRow {
    id: number;
    reference: string | null;
    studentId: number | null;
    studentNom: string | null;
    studentShowUrl: string | null;
    telephone: string | null;
    whatsapp: string | null;
    groupe: string | null;
    frais: string | null;
    statut: string;
    dateEcheance: string | null;
    retardJours: number;
    resteAPayer: string;
    inscriptionShowUrl: string | null;
}

export interface RecouvrementFilters {
    groupFilter: string;
    fraisFilter: string;
    statutFilter: string;
    dateFrom: string;
    dateTo: string;
    dureeBucket: string;
    perPage: number;
}

export interface RecouvrementPageProps {
    retards: PaginatedData<RetardRow>;
    bucketCounts: Record<string, number>;
    filters: RecouvrementFilters;
    perPageOptions: number[];
    groupOptions: SelectOption[];
    fraisOptions: SelectOption[];
    statuts: string[];
    [key: string]: unknown;
}

// --- Journal d'audit -------------------------------------------------------

/** One changed column on an audited record: what it was, what it became. */
export interface AuditChange {
    field: string;
    old: string | null;
    new: string | null;
}

export interface AuditLogRow {
    id: number;
    logName: string | null;
    logLabel: string;
    description: string;
    event: string | null;
    eventLabel: string | null;
    /** Actor identity frozen at write time (see App\Models\Activity). */
    causerLabel: string | null;
    causerId: number | null;
    subjectType: string | null;
    subjectLabel: string | null;
    subjectId: number | null;
    subjectRef: string | null;
    ipAddress: string | null;
    userAgent: string | null;
    method: string | null;
    url: string | null;
    routeName: string | null;
    /** 'Y-m-d H:i:s' — second precision, deliberately (fraud ordering). */
    createdAt: string | null;
    createdAtHuman: string | null;
    changes: AuditChange[];
    properties: Record<string, unknown>;
}

export interface AuditLogFilters {
    search: string;
    logName: string;
    event: string;
    causerId: string;
    subjectType: string;
    dateFrom: string;
    dateTo: string;
    ip: string;
    financeOnly: boolean;
    perPage: number;
}

export interface AuditLogPageProps {
    entries: PaginatedData<AuditLogRow>;
    logNames: { value: string; label: string }[];
    events: { value: string; label: string }[];
    causers: { id: number; nom: string }[];
    subjectTypes: { value: string; label: string }[];
    filters: AuditLogFilters;
    [key: string]: unknown;
}
