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
    /**
     * Année clôturée — a hard server-side write lock (CLAUDE.md §11): the
     * year accepts NO creation or modification, super-admin included. Used
     * here only to draw the padlock/badge and disable « Ajouter » buttons;
     * the refusal itself always comes from AssertsContextScope.
     */
    cloturee?: boolean;
}

export interface Context {
    anneeScolaireId: number | null;
    etablissementId: number | null;
    isAllCenters: boolean;
    canSwitchCenter: boolean;
    /** « Tous les centres » is offered to global users only (super-admin / hand-granted centers.access-all). */
    canPickAllCenters: boolean;
    currentCenter: ContextOption | null;
    currentAcademicYear: ContextOption | null;
    availableCenters: ContextOption[];
    availableAcademicYears: ContextOption[];
    /**
     * True when the ACTIVE year is closed — every create/edit button on
     * every page is disabled and a banner explains why. UI convenience
     * only: the server refuses the write regardless.
     */
    anneeCloturee: boolean;
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
    /**
     * The registration just created (InscriptionController::store()) — drives
     * the Inscriptions page's « Voulez-vous ajouter un paiement ? » prompt and
     * pre-scopes the payment modal to it. One-time (`pull()` server-side), so
     * it never reappears on a later reload of the list.
     */
    nouvelleInscription?: NouvelleInscription | null;
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

/** Mirrors App\Domain\Reports\Actions\GetAnnualFraisSummary's return shape — one monthly point per month of the active année scolaire, 5 series, each a pre-formatted decimal string (money never floated over the wire). */
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
    /** The année scolaire the chart window covers (top-bar switcher), e.g. "2025/2026". */
    annualFraisPeriode: string;
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
    anneeScolaire: string | null;
}

/** Inscriptions grouped per année scolaire, newest year first. */
export interface StudentInscriptionsParAnnee {
    annee: string | null;
    inscriptions: StudentInscriptionRow[];
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
    inscriptionsParAnnee: StudentInscriptionsParAnnee[];
    /** Which inscription statut the Paiements list is scoped to (Active > Annulée > Changement), null = no inscription. */
    paiementsScope: string | null;
    paiementsTotal: MoneyDisplay;
    paiements: StudentPaymentRow[];
}

/**
 * Pourquoi un groupe ne génère plus de séances automatiques — `null` quand
 * tout va bien. Produit côté serveur par DiagnostiquerEmploiDuTemps, qui
 * REFLÈTE les refus de `seances:generate` sans les redériver : quatre causes
 * (aucune date de début, formation terminée, aucun créneau, tous les créneaux
 * clôturés), chacune avec sa marche à suivre.
 */
export interface EmploiDuTempsProbleme {
    code:
        | 'aucun_creneau'
        | 'creneaux_fermes'
        | 'creneaux_partiels'
        | 'date_debut_manquante'
        | 'formation_terminee';
    titre: string;
    message: string;
    action: string;
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
    /** Super-admin only, and only on a terminal group: reopen it. */
    canReopen: boolean;
    reopenUrl: string;
    anneeScolaireId: number | null;
    /** Super-admin only (groups.move-year): move the group + its inscriptions/séances/payments to another année. */
    canMoveYear: boolean;
    moveYearUrl: string;
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
    /**
     * Pourquoi ce groupe ne génère plus de séances — `null` quand tout va
     * bien. Dérivé côté serveur de l'état réel du groupe et de ses créneaux :
     * c'est une bannière permanente, pas un flash.
     */
    emploiDuTempsProbleme: EmploiDuTempsProbleme | null;
}

/**
 * One teaching-assignment period of a group. Exactly one row is `isActif`;
 * the others are archived past periods kept for per-teacher payroll
 * (dateDebut → dateFin).
 */
export interface GroupEnseignantRow {
    id: number;
    enseignant: string | null;
    /** Display strings (d/m/Y). */
    dateDebut: string | null;
    dateFin: string | null;
    /** Raw Y-m-d values, for the "Modifier" modal's date inputs. */
    dateDebutIso: string | null;
    dateFinIso: string | null;
    statut: string;
    isActif: boolean;
    motif: string | null;
    /** Endpoint correcting this period's dates/motif — never its teacher. */
    updateUrl: string;
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
    /** True when the row carries no fee — money received and not yet allocated. */
    isAvance: boolean;
    montantUtilise: MoneyDisplay;
    montantRestant: MoneyDisplay;
    /** Fee lines this avance paid for (empty for an ordinary payment). */
    applications: Array<{
        id: number;
        /** False for a refunded row, or one already detached from its fee. */
        detachable: boolean;
        reference: string;
        frais: string | null;
        groupe: string | null;
        montant: MoneyDisplay;
        date: string | null;
        showUrl: string;
    }>;
    /** Set when this row is itself the application of an earlier avance. */
    appliedFrom: {
        reference: string;
        montant: MoneyDisplay;
        date: string | null;
        showUrl: string;
    } | null;
}

export interface DepenseDetails {
    id: number;
    reference: string;
    montant: MoneyDisplay;
    typeDepense: string | null;
    dateDepense: string | null;
    /** « Paiement prof » only — the teaching period the payment covers. */
    periodeDebut: string | null;
    periodeFin: string | null;
    caisse: string | null;
    centre: string | null;
    agent: string | null;
    recordedAt: string | null;
    description: string | null;
    motsCles: string[];
    note: string | null;
    canViewList: boolean;
    receipts: SafeMediaFile[];
    statut: string;
    methodePaiement: string | null;
    referenceFacture: string | null;
    groupe: string | null;
    approvedBy: string | null;
    approvedAt: string | null;
    motifRefus: string | null;
    /** Operation trail — super-admin only; see DepenseRow for the rule. */
    createdAt?: string | null;
    updatedAt?: string | null;
    wasEdited?: boolean;
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
    /**
     * Créneau CLÔTURÉ : il ne génère plus aucune séance. La grille l'affichait
     * comme un créneau vivant, donnant un emploi du temps d'apparence complète
     * sur un groupe qui ne produisait plus rien — il est donc grisé et marqué,
     * jamais masqué (sinon il n'y aurait plus rien à corriger à l'écran).
     */
    clos: boolean;
    /** Date de clôture (d/m/Y), pour l'afficher sur la case grisée. */
    dateFin: string | null;
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
    etablissementId: number | null;
    etablissement: string | null;
    statut: string;
    note: string | null;
    mouvementsCount: number;
}

export interface StockArticleForm {
    nom: string;
    stock_type_id: number | '';
    etablissement_id: number | '';
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

export type SettingsTab = 'etablissements' | 'annees-scolaires' | 'salles' | 'frais' | 'banques' | 'motifs-annulation' | 'systeme';

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
    cloturee: boolean;
}

export interface AnneeScolaireForm {
    nom: string;
    date_debut: string;
    date_fin: string;
    par_defaut: boolean;
    inscription_ouverte: boolean;
    cloturee: boolean;
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

/** One center's own price for a fee (frais_etablissement pivot). */
export interface FraisCentreLigne {
    etablissementId: number;
    nomCentre: string;
    montant: string;
}

export interface FraisRow {
    id: number;
    nom: string;
    /** Fallback used by any center with no price line of its own. */
    montantDefaut: string;
    statut: string;
    groupsCount: number;
    /** Centers charging this fee, each with that center's amount. */
    centres: FraisCentreLigne[];
}

/** A center price line as submitted by the form. */
export interface FraisCentreForm {
    etablissement_id: number;
    montant: string;
}

export interface FraisForm {
    nom: string;
    montant_defaut: string;
    statut: string;
    centres: FraisCentreForm[];
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
    /** Which cancellation form offers it: 'tous' | 'inscription' | 'seance'. */
    portee: string;
    statut: string;
}

export interface MotifAnnulationForm {
    nom: string;
    portee: string;
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
    /** Shared by the Salles and Frais tabs — centers the user may act on. */
    centerOptions?: SelectOption[];
    /** Salles tab: false only on "Tous les centres" — gates the redundant Centre column. */
    centerLocked?: boolean;
    frais?: PaginatedData<FraisRow>;
    banques?: PaginatedData<BanqueRow>;
    motifsAnnulation?: PaginatedData<MotifAnnulationRow>;
    /** Système tab: application-wide switches (AppSettings). */
    systeme?: { expenseApproval: boolean };
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
/**
 * Impact réel d'une suppression définitive de groupe, calculé côté serveur
 * (GroupController@deletionImpact) et affiché dans l'avertissement : jamais
 * recompté côté client, pour que l'utilisateur voie les vrais chiffres.
 */
export interface GroupDeletionImpact {
    nom: string;
    inscriptions: number;
    etudiants: number;
    frais: number;
    encaissements: number;
    /** Montant déjà encaissé ; > 0 ⇒ suppression refusée par le serveur. */
    montantEncaisse: number;
    /** > 0 ⇒ suppression refusée par le serveur. */
    seances: number;
}

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
    /**
     * Pourquoi ce groupe ne génère plus de séances — `null` quand tout va
     * bien. Signalé dans la liste, détaillé sur la fiche.
     */
    emploiDuTempsProbleme: EmploiDuTempsProbleme | null;
    showUrl: string;
    /** Keyed by frais_id — prefills the edit modal's fee-lines table without a second request. */
    fraisLignes: Record<number, { montant: string; dateEcheance: string; classification: string }>;
    /**
     * Active catalog fees this group NO LONGER carries — the edit modal's
     * « Frais retirés » list, the only place a removed fee can be restored
     * from (mirrors the Inscriptions modal's « Frais masqués »).
     */
    fraisRetires: GroupFormOption[];
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

/**
 * "Détails paiement" — the group's payment matrix
 * (App\Domain\Groups\Queries\GetGroupPaymentMatrix).
 */
export type GroupPaymentCellState = 'paye' | 'partiel' | 'impaye';

export interface GroupPaymentCell {
    state: GroupPaymentCellState;
    /** What the student actually paid on this fee line. */
    montant: string;
    /** What the line is worth after remise. */
    du: string;
    reste: string;
}

export interface GroupPaymentColumn {
    /** frais_id as a string — the key into GroupPaymentRow.cells. */
    key: string;
    nom: string;
    dateEcheance: string | null;
    dateEcheanceIso: string | null;
    classification: string | null;
    montant: string;
    /** Column footer: everything collected on this fee across the group. */
    total: string;
}

export interface GroupPaymentRow {
    key: string;
    numero: string;
    student: string | null;
    studentShowUrl: string | null;
    reference: string;
    /** Active | Changement | Annulée — drives the row colour. */
    statut: string;
    /** Cancellation/departure reason — null while the inscription is Active. */
    motifAnnulation: string | null;
    /** End date recorded with that reason, when there is one. */
    dateFin: string | null;
    /** Enrollment note (a cancellation appends its comment to it). */
    note: string | null;
    dateInscription: string | null;
    dateInscriptionIso: string | null;
    total: string;
    reste: string;
    /**
     * Keyed by column key. A MISSING key means the fee is not on this
     * student's inscription — an empty grey cell, not a debt.
     */
    cells: Record<string, GroupPaymentCell>;
}

export type GroupPaymentSort = 'nom' | 'date' | 'nom_desc';

export interface GroupPaymentMatrix {
    columns: GroupPaymentColumn[];
    rows: GroupPaymentRow[];
    totals: { parColonne: Record<string, string>; general: string };
    sort: GroupPaymentSort;
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

/**
 * One active catalog fee as offered to the Groups form. Beyond the label it
 * carries the catalog default the create form pre-fills (`montantDefaut`)
 * and the month the fee's own name implies (`moisEcheance`, 1-12, null when
 * the name names no month) used to pre-fill the due date from the group's
 * start date.
 */
export interface GroupFraisCatalogOption extends GroupFormOption {
    montantDefaut: string;
    moisEcheance: number | null;
}

export interface GroupsPageProps {
    groups: PaginatedData<GroupRow>;
    statutCounts: Record<string, number>;
    filters: GroupsFilters;
    perPageOptions: number[];
    niveaux: string[];
    statuts: string[];
    enseignants: GroupFormOption[];
    fraisCatalog: GroupFraisCatalogOption[];
    [key: string]: unknown;
}

// --- Phase 9: Inscriptions (Inertia/React list + modal CRUD with fee lines) -

/** One row of the Inscriptions list — mirrors GetInscriptionsList's ->through() mapping exactly. */
/** One-time hand-off from a successful inscription creation to the payment prompt. */
export interface NouvelleInscription {
    id: number;
    reference: string;
    studentId: number | null;
    studentLabel: string;
}

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
    /** ISO (yyyy-mm-dd) copies of the three dates above, for the edit modal's date inputs. */
    dateIso: string | null;
    dateDebutIso: string | null;
    dateFinIso: string | null;
    montantTotal: MoneyDisplay | null;
    feesCount: number;
    statut: string;
    showUrl: string;
}

export interface InscriptionFormOption {
    id: number;
    label: string;
}

/** Group option with its statut — « Modification du groupe » only offers groups still « En inscription » (server re-checks in ModifierGroupeInscription). */
export interface InscriptionGroupOption extends InscriptionFormOption {
    statut: string;
}

/**
 * Target-group option for the « Changement de groupe » modal — the only
 * group dropdown NOT limited to the active academic year, so a student can
 * be moved into next year's group. Carries the year so the modal can filter
 * the list behind its « Année scolaire » selector.
 */
export interface InscriptionChangeGroupOption extends InscriptionGroupOption {
    anneeScolaireId: number | null;
    anneeLabel: string | null;
}

/** One "Frais disponible" line loaded from the selected group (GetGroupInscriptionFees). */
/** Catalog fee for the edit modal's "Ajouter un frais" select; montantDefaut is the fallback amount when the group assigns no own amount. */
export interface InscriptionFraisOption extends InscriptionFormOption {
    montantDefaut: MoneyDisplay;
}

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
    groups: InscriptionGroupOption[];
    /** Target groups for « Changement de groupe » — all reachable academic years, not just the active one. */
    changeGroupGroups: InscriptionChangeGroupOption[];
    /** Academic years present in changeGroupGroups — the modal's « Année scolaire » selector. */
    changeGroupAnnees: InscriptionFormOption[];
    /** Active fee catalog — feeds the edit modal's "Ajouter un frais" picker. */
    frais: InscriptionFraisOption[];
    /** UI convenience only — hides the edit-modal fee controls; real enforcement is registrations.manage-fees on the server (InscriptionController::updateFees). */
    canManageFees: boolean;
    /** UI convenience only — hides the "Changement de groupe" row action; real enforcement is registrations.change-group on the server (InscriptionController::changeGroup). */
    canChangeGroup: boolean;
    /** Active cancellation reasons (« Changement de groupe » excluded) for the "Annuler l'inscription" form. */
    motifsAnnulation: string[];
    /** UI convenience only — hides « Ajouter un paiement »; real enforcement is payments.create on EncaissementController::store. */
    canCreatePayment: boolean;
    /** Encaissement::METHODES — the payment modal's per-row méthode dropdown. */
    methodesPaiement: string[];
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

/** « Caisse globale » tab — mirrors GetCaisseGlobale exactly. */
export interface CaisseGlobaleData {
    /** One card per kind of account, in display order (Caissière, TPE, Virement, Chèque — Externe hidden for now). */
    cards: Array<{ type: string; label: string; total: MoneyDisplay; count: number }>;
    /** The accounts of each kind, keyed by Caisse type. */
    comptes: Record<string, Array<{ id: number; nom: string; centre: string | null; responsable: string | null; solde: MoneyDisplay; showUrl: string }>>;
    total: MoneyDisplay;
    /**
     * The day every figure above is a balance AS OF (yyyy-mm-dd), or null when
     * no « Date de fin » filter is set and the figures are today's stored
     * soldes. The date window rewinds the balances, it never sums a period —
     * see GetCaisseGlobale's docblock.
     */
    asOf: string | null;
    /**
     * First day the CaisseLedger journal can answer for (yyyy-mm-dd), or null
     * when the journal is empty. A rewind older than this is impossible.
     */
    journalDepuis: string | null;
    /**
     * True when the requested « Date de fin » predates journalDepuis: the
     * rewind was NOT performed (it would have printed 0.00 DH for every
     * account) and the figures above are today's stored soldes.
     */
    avantJournal: boolean;
}

/**
 * GetCaisseJournal's full return shape for one scope ('mine'|'all').
 * Every header figure is a CASH figure: an employee's till holds Espèces
 * only; non-cash money lives on the « Caisse globale » tab.
 */
export interface CaisseJournalData {
    caissesInScope: Array<{ id: number; nom: string }>;
    /** Everything the till owner(s) collected, ALL methods — only the Espèces part is inside `solde`. */
    totalEncaissements: MoneyDisplay;
    /** Breakdown of `totalEncaissements` keyed by method (Espèces / TPE / Chèque / Virement). */
    encaissementsParMethode: Record<string, MoneyDisplay>;
    totalDepenses: MoneyDisplay;
    /** Refunds paid out of the till — a separate outflow from `totalDepenses`, never folded into it. */
    totalRemboursements: MoneyDisplay;
    /** Solde espèces — the physical till(s) in scope, never cash + TPE + chèque + virement. */
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
    /**
     * Recipient-only validation: true only when the viewer owns the
     * DESTINATION till and did not request the transfer. UI convenience -
     * CaisseTransferPolicy@validate is the real gate.
     */
    canValidate: boolean;
    showUrl: string;
}

export interface CaisseTransferFormOption extends FinanceOption {
    solde: MoneyDisplay;
}

/**
 * One row of the « Comptes de caisse » tab — mirrors GetComptesCaisse exactly.
 * Every row is a real `caisses` account with a stored, ledger-maintained solde.
 */
export interface CompteCaisseRow {
    id: number;
    nom: string;
    /** "Caissière" / "Externe" (cash) or "TPE" / "Chèque" / "Virement" (one per centre). */
    type: string;
    centre: string | null;
    responsable: string | null;
    encaissements: string;
    depenses: string;
    /** Stored `caisses.solde`. */
    solde: string;
    statut: string;
    /** created_at as dd/mm/yyyy. */
    dateAjout: string | null;
    /** True for a centre's TPE/Chèque/Virement account — provisioned, never edited or deleted. */
    compteMethode: boolean;
    showUrl: string;
}

/**
 * Add/edit form of a cash account — deliberately just Type + Nom (+ Centre).
 * No opening balance: a solde is never typed by hand, it only moves through
 * CaisseLedger. `type` is create-only and frozen afterwards. Mirrors
 * StoreCaisseRequest/UpdateCaisseRequest.
 */
export interface CompteCaisseForm {
    nom: string;
    type: string;
    etablissement_id: number | '';
}

export interface CaissesPageProps {
    canViewCaisses: boolean;
    canViewTransfers: boolean;
    /** « Comptes de caisse » tab — super-admin only (`cash-accounts.view` is in no role). */
    canViewComptes: boolean;
    journalMine: CaisseJournalData | null;
    /** « Caisse globale » tab (cash-registers.view), computed only when that tab is active. */
    globale: CaisseGlobaleData | null;
    transfers: PaginatedData<CaisseTransferRow> | null;
    /** Montant summed over the WHOLE filtered set of transfers, not the visible page. */
    transfersMontantTotal: MoneyDisplay;
    transferStatutCounts: Record<string, number>;
    transferCaisses: CaisseTransferFormOption[];
    transferStatuts: string[];
    currentEmployeeId: number | null;
    /** The acting employee's OWN till — the transfer modal's fixed, read-only source (null when the account has no employee/till). */
    myCaisse: CaisseTransferFormOption | null;
    transferFilters: { search: string; statutFilter: string; typeFilter: string };
    comptes: PaginatedData<CompteCaisseRow> | null;
    /** Creatable kinds — "Externe" only; see GetComptesCaisse::CREATABLE_TYPES. */
    compteTypes: string[];
    /** Every kind the tab can show, for the filter dropdown. */
    compteTypeFilters: string[];
    compteEtablissements: { id: number; nom: string }[];
    comptePermissions: CrudPermissions;
    compteFilters: { compteSearch: string; compteTypeFilter: string };
    /** « Caisse globale » date window; globaleDateTo rewinds the balances to that day. */
    globaleFilters: { globaleDateFrom: string; globaleDateTo: string };
    [key: string]: unknown;
}

/** One row of the Encaissements list — mirrors GetEncaissementsList's ->through() mapping exactly. */
export interface EncaissementRow {
    id: number;
    reference: string;
    /** Part of this payment already given back (Remboursements linked to it). A fully refunded payment is not listed at all. */
    montantRembourse: MoneyDisplay;
    student: string | null;
    /** Student matricule (ETU-… reference) — shown as "REF | NOM" in the edit modal. */
    studentRef: string | null;
    studentId: number | null;
    inscriptionId: number | null;
    feeNom: string | null;
    /** True when the money is not allocated to any fee (feeNom is then null) — the Frais column reads « Avance ». */
    isAvance: boolean;
    /** Avances tab only — the fee this money sat on before it was detached (group change, cancellation, conversion); null for a fresh avance. */
    ancienFrais: string | null;
    /** Group of that former fee's inscription. */
    ancienFraisGroupe: string | null;
    /** Fee lines this avance was applied to (empty when none) — feeds the list cell’s hover detail. */
    fraisAppliques: Array<{ frais: string; groupe: string | null; montant: MoneyDisplay; date: string | null }>;
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
    /**
     * Whether AppliquerAvance would accept this row — an avance funded by a
     * REJECTED cheque has a remaining balance but can never be applied. Gate
     * the "Appliquer à un frais" action on this, never on montantRestant
     * alone.
     */
    applicable?: boolean;
    /** The funding cheque bounced: the money was reversed off the Chèque account. */
    chequeRejete?: boolean;
    /**
     * Whether RequalifierMethodeEncaissement would accept this row — false
     * for an advance allocation (it credited no caisse), a payment linked to
     * a tracked cheque, and an already-refunded payment. Gate the « Méthode »
     * select on this AND on `can.updateMethode`; the action refuses again
     * server-side.
     */
    methodeRequalifiable?: boolean;
    /**
     * Whether CorrigerMontantEncaissement would accept this row — false for
     * an advance allocation, a payment linked to a tracked cheque, an
     * already-refunded payment, and an advance already applied to a fee.
     * Gate the « Montant » input on this AND on `can.updateAmount`
     * (super-admin only); the action refuses again server-side.
     */
    montantCorrigible?: boolean;
    studentEmail: string | null;
    showUrl: string;
    /** Printable receipt page — append ?format=a6|a5|a5x2. */
    recuUrl: string;
    /** POST target to email the A5 receipt (SendRecuEmailRequest: { email }). */
    recuEmailUrl: string;
    /**
     * GET endpoint returning { url, phone } — the WhatsApp click-to-chat link
     * built server-side (RecuWhatsAppLink): the PDF travels as a SIGNED URL
     * inside the message text, since click-to-chat cannot attach a file.
     * Answers 422 when the student has no reachable number or APP_URL is local.
     */
    recuWhatsAppUrl: string;
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
    /** Avances tab only: 'restant' (default) | 'epuise' | 'tous'. */
    soldeFilter: string;
    /** Group id: fee rows by their inscription's group, avances by the student's inscriptions. */
    groupFilter: string;
}

export interface EncaissementsPageProps {
    encaissements: PaginatedData<EncaissementRow>;
    /** Sum of `montant` over every row matching the current filters/tab (not just the page shown). */
    montantTotal: MoneyDisplay;
    caisses: FinanceOption[];
    students: FinanceOption[];
    /** Groups of the active centre + année — the « Groupe » filter's options. */
    groups: FinanceOption[];
    methodes: string[];
    /** Active bank names from the catalog (Paramètres → Banques) — the Chèque form's dropdown source. */
    banques: string[];
    filters: EncaissementsFilters;
    /**
     * UI convenience only — the endpoints re-authorize server-side.
     * `updateDate` is `payments.update-date` (super-admin only, 30/08/2026):
     * without it the edit modal's Date field is disabled and the controller
     * drops any posted value.
     *
     * `updateMethode` is `payments.update-method` (rôles de direction +
     * super-admin, 01/09/2026): without it the edit modal's « Méthode de
     * paiement » select is disabled. Changing it is not a label edit — the
     * server moves the money between the two caisses and journals both legs
     * (RequalifierMethodeEncaissement), and refuses a different value posted
     * without the permission.
     */
    can?: { delete: boolean; updateDate?: boolean; updateMethode?: boolean; updateAmount?: boolean };
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
    /** Sum of `montant` over every chèque matching the current filters (not just the page shown). */
    montantTotal: MoneyDisplay;
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
    groupNom: string | null;
    montant: MoneyDisplay;
    methodePaiement: string | null;
    dateDepense: string | null;
    /** « Paiement prof » only — the teaching period the payment covers. */
    periodeDebut: string | null;
    periodeFin: string | null;
    referenceFacture: string | null;
    description: string | null;
    motsCles: string | null;
    note: string | null;
    agent: string | null;
    receiptsCount: number;
    /** Approval workflow — "En attente" | "Approuvée" | "Refusée". */
    statut: string;
    /** Pending: no money has left the till yet. */
    isEnAttente: boolean;
    isRefusee: boolean;
    approvedBy: string | null;
    approvedAt: string | null;
    motifRefus: string | null;
    /**
     * Operation trail — when the row was actually keyed in / last edited, as
     * opposed to `dateDepense` (the business date the user types, freely
     * backdatable). Sent ONLY to users holding `expenses.approve`
     * (super-admin); undefined for everyone else because DepenseController
     * strips the fields server-side rather than hiding them in the UI.
     */
    createdAt?: string | null;
    updatedAt?: string | null;
    /** True when the row was really edited after creation (>1s apart). */
    wasEdited?: boolean;
    showUrl: string;
}

export interface DepensesFilters {
    search: string;
    typeFilter: string;
    caisseFilter: string;
    dateFrom: string;
    dateTo: string;
    statutFilter: string;
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
    /** Annulé par écriture compensatoire : la caisse a été recréditée, donc plus aucune sortie d'argent. */
    annule: boolean;
}

/** A cash till the refund form may draw from, with its responsable's name and current balance. */
export interface RemboursementCaisseOption {
    id: number;
    nom: string;
    solde: MoneyDisplay;
}

/** One of a student's fee-targeted payments — the Remboursement form's "which payment?" picker (GetStudentPaymentsForRefund). */
export interface EncaissementFormOption {
    id: number;
    reference: string;
    montant: MoneyDisplay;
    methode: string;
    date: string | null;
    /** null on an avance — the money is not attached to a fee (yet). */
    feeNom: string | null;
    /** True when the payment is an unallocated avance (inscription_fee_id null). */
    isAvance: boolean;
    dejaRembourse: MoneyDisplay;
    /**
     * What this row can still give back, computed SERVER-side (an avance is
     * capped at its unallocated remainder, a fee payment at what it brought
     * in less prior refunds). Never re-derive it from `montant` on the
     * client — that overstates a partly-applied avance.
     */
    montantRemboursable: MoneyDisplay;
}

export interface DepensesPageProps {
    canViewDepenses: boolean;
    canViewRemboursements: boolean;
    /** The acting employee's own till balance — null if they have no employee record. */
    soldeActuel: MoneyDisplay | null;
    depenses: PaginatedData<DepenseRow> | null;
    /** Approved only — money that actually left the tills. */
    montantTotal: MoneyDisplay | null;
    /** Pending only — money on hold, awaiting a decision. */
    montantEnAttente: MoneyDisplay | null;
    enAttenteCount: number;
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
    /** Cash tills the refund may be paid out of — active centre, reachable centres only. */
    remboursementCaisses: RemboursementCaisseOption[];
    /** Paramètres → Système « Validation des dépenses » — drives the whole approval UI. */
    approvalEnabled: boolean;
    /** UI convenience only (`expenses.approve`); the policy is the real gate. */
    canApprove: boolean;
    /**
     * Same permission, different job: gates the « Date d'operation » column
     * and the « Validation des depenses » tab. When false the controller has
     * already stripped createdAt/updatedAt from every row, so there is
     * nothing to hide client-side.
     */
    canAudit: boolean;
    depenseStatuts: string[];
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
    /** Reste-à-payer summed over the WHOLE filtered set, not the visible page. */
    montantTotal: MoneyDisplay;
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
    /** Raw column name, e.g. `enseignant_id` — kept for the technical reader. */
    field: string;
    /** French label for the column, e.g. « Enseignant ». */
    label: string;
    old: string | null;
    /** Name behind `old` when the column is a foreign key, else null. */
    oldLabel: string | null;
    /** Who wrote the OLD value (detail page only); null when it predates the journal. */
    oldAuthor: string | null;
    /** When that earlier change happened, 'd/m/Y H:i:s'. */
    oldAuthorAt: string | null;
    /** Journal entry that wrote the OLD value, so it can be opened directly. */
    oldEntryId: number | null;
    new: string | null;
    /** Name behind `new` when the column is a foreign key, else null. */
    newLabel: string | null;
    /** Who wrote the NEW value — this entry's own actor. */
    newAuthor: string | null;
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
    /** Context key/values, French-labelled; money/origin keys are excluded. */
    properties: { key: string; label: string; value: string }[];
    /** Non-null only for entries that moved a till balance. */
    money: AuditMoney | null;
}

/**
 * The cash arithmetic behind an entry — present only when money actually
 * moved. `coherent` is false when the recorded before/after do not agree with
 * the amount, which is itself a finding.
 */
export interface AuditMoney {
    caisse: string | null;
    sens: string | null;
    isCredit: boolean;
    montant: string;
    soldeAvant: string;
    soldeApres: string;
    delta: string;
    coherent: boolean;
    motif: string | null;
    origineReference: string | null;
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
    /** Money-only scope — no longer on the filter bar, still honoured by URL. */
    financeOnly: boolean;
    caisseId: string;
    /** Centre of the RECORD touched, not of the actor who touched it. */
    etablissementId: string;
    /** Show the maintainer account's entries (hidden by default, never unrecorded). */
    includeDeveloper: boolean;
    perPage: number;
}

export interface AuditLogPageProps {
    entries: PaginatedData<AuditLogRow>;
    logNames: { value: string; label: string }[];
    events: { value: string; label: string }[];
    causers: { id: number; nom: string }[];
    subjectTypes: { value: string; label: string }[];
    caisses: { value: string; label: string }[];
    /** Centres this reader may narrow the journal to (« Centres affectés »). */
    etablissements: { value: string; label: string }[];
    filters: AuditLogFilters;
    [key: string]: unknown;
}

export interface AuditLogShowPageProps {
    entry: AuditLogRow;
    [key: string]: unknown;
}

// --- Gestion des rapports --------------------------------------------------

/** Un rapport proposé par le sélecteur « Rapport » d'un onglet. */
export interface RapportOption {
    value: string;
    label: string;
}

/**
 * Un domaine du catalogue serveur (RapportCatalogue). `rapports` vide = domaine
 * prévu mais sans rapport implémenté ; la page aplatit tous les domaines en un
 * seul sélecteur, donc un domaine vide n'apparaît simplement pas.
 */
export interface RapportOnglet {
    key: string;
    label: string;
    rapports: RapportOption[];
}

export interface RapportFilters {
    rapport: string;
    groupFilter: string;
    statutFilter: string;
    dateFrom: string;
    dateTo: string;
}

export interface RapportsPageProps {
    onglets: RapportOnglet[];
    filters: RapportFilters;
    groupOptions: SelectOption[];
    statutOptions: SelectOption[];
    /** Nombre de lignes que le document contiendra avec les filtres courants. */
    nombreLignes: number;
    [key: string]: unknown;
}
