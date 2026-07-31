import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import TableToolbar from '@/Components/Tables/TableToolbar';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import TextareaField from '@/Components/Forms/TextareaField';
import PhoneField from '@/Components/Forms/PhoneField';
import FormActions from '@/Components/Forms/FormActions';
import type {
    InscriptionFeeLine,
    InscriptionGroupFeesResponse,
    InscriptionRow,
    InscriptionsPageProps,
    SelectOption,
} from '@/Types';

interface InscriptionFormState {
    inscription_mode: 'new' | 'existing';
    student_id: number | '';
    group_id: number | '';
    statut: string;
    date_inscription: string;
    date_debut: string;
    date_fin: string;
    note: string;
    fee_lines: InscriptionFeeLine[];
    // New-student inline fields.
    new_nom: string;
    new_prenom: string;
    new_sexe: string;
    new_date_naissance: string;
    new_cin: string;
    new_niveau: string;
    new_domaine: string;
    new_examen_type: string;
    new_email: string;
    new_telephone: string;
    new_whatsapp: string;
    new_adresse: string;
    new_parent_relation: string;
    new_parent_nom: string;
    new_parent_sexe: string;
    new_parent_cin: string;
    new_parent_telephone: string;
    new_parent_whatsapp: string;
    phone_pays: string;
}

function emptyForm(defaultCountry: string): InscriptionFormState {
    return {
        inscription_mode: 'new',
        student_id: '',
        group_id: '',
        statut: 'Active',
        date_inscription: new Date().toISOString().slice(0, 10),
        date_debut: '',
        date_fin: '',
        note: '',
        fee_lines: [],
        new_nom: '',
        new_prenom: '',
        new_sexe: '',
        new_date_naissance: '',
        new_cin: '',
        new_niveau: '',
        new_domaine: '',
        new_examen_type: '',
        new_email: '',
        new_telephone: '',
        new_whatsapp: '',
        new_adresse: '',
        new_parent_relation: '',
        new_parent_nom: '',
        new_parent_sexe: '',
        new_parent_cin: '',
        new_parent_telephone: '',
        new_parent_whatsapp: '',
        phone_pays: defaultCountry,
    };
}

/**
 * Mirrors InscriptionFee::computeMontant() exactly — display-only preview;
 * the server independently recomputes this from the same inputs and that
 * server value is what actually persists (docs/phase-9-inscriptions-
 * mapping.md's Money handling section).
 */
function computeLineMontant(initial: number, remisePct: number | null, remiseMontant: number | null): number {
    if (remisePct !== null && remisePct > 0) {
        return Math.round(initial * (1 - Math.min(remisePct, 100) / 100) * 100) / 100;
    }

    return Math.round(Math.max(0, initial - (remiseMontant ?? 0)) * 100) / 100;
}

/**
 * Replaces App\Livewire\Backoffice\Inscriptions\InscriptionsIndex — the most
 * business-critical module migrated so far. Preserves every calculation and
 * asymmetry exactly: fee lines only apply on create (never on edit); a new
 * registration always starts "Active" and its center/year/dates are always
 * derived from the selected group server-side; the discount two-way sync
 * and final-amount preview are display-only, never trusted as the source
 * of truth (the server recomputes independently on save).
 */
export default function InscriptionsIndex({
    inscriptions,
    filters,
    perPageOptions,
    statuts,
    niveaux,
    domaines,
    examenTypes,
    sexes,
    parentRelations,
    niveauxAvecDomaine,
    niveauStudium,
    defaultCountry,
    students,
    groups,
}: InscriptionsPageProps) {
    const isLoading = useInertiaLoading();
    const [showModal, setShowModal] = useState(false);
    const [editingInscription, setEditingInscription] = useState<InscriptionRow | null>(null);
    const [activeTab, setActiveTab] = useState<'affectation' | 'contact' | 'parent' | 'autre'>('affectation');
    const [loadingGroupFees, setLoadingGroupFees] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<InscriptionRow | null>(null);
    const [deleteError, setDeleteError] = useState<string | undefined>(undefined);
    const [deleteProcessing, setDeleteProcessing] = useState(false);

    const form = useForm<InscriptionFormState>(emptyForm(defaultCountry));

    const studentOptions: SelectOption[] = students.map((s) => ({ value: s.id, label: s.label }));
    const groupOptions: SelectOption[] = groups.map((g) => ({ value: g.id, label: g.label }));
    const niveauOptions: SelectOption[] = niveaux.map((n) => ({ value: n, label: n }));
    const domaineOptions: SelectOption[] = domaines.map((d) => ({ value: d, label: d }));
    const examenOptions: SelectOption[] = examenTypes.map((e) => ({ value: e, label: e }));
    const parentRelationOptions: SelectOption[] = parentRelations.map((r) => ({ value: r, label: r }));
    const statutOptions: SelectOption[] = statuts.map((s) => ({ value: s, label: s }));
    const statutFilterOptions: SelectOption[] = statuts.map((s) => ({ value: s, label: s }));

    const showsDomaine = niveauxAvecDomaine.includes(form.data.new_niveau);
    const showsExamen = form.data.new_niveau === niveauStudium;

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/inscriptions',
            { ...filters, ...nextFilters, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function openCreate() {
        setEditingInscription(null);
        setActiveTab('affectation');
        form.reset();
        form.clearErrors();
        form.setData(emptyForm(defaultCountry));
        setShowModal(true);
    }

    function openEdit(inscription: InscriptionRow) {
        // The edit form only ever changes 6 columns — no fee-line loading,
        // matching the Livewire form's own `@unless ($editingId)` fee-table
        // scoping exactly. The row exposes the raw student/group ids so the
        // selects preselect by id (label-prefix matching used to pick the
        // wrong record when two students shared a name — Phase 12 fix).
        setEditingInscription(inscription);
        setActiveTab('affectation');
        form.clearErrors();
        form.setData({
            ...emptyForm(defaultCountry),
            inscription_mode: 'existing',
            student_id: inscription.studentId ?? '',
            group_id: inscription.groupId ?? '',
            statut: inscription.statut,
        });
        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditingInscription(null);
        form.reset();
        form.clearErrors();
    }

    function handleNiveauChange(value: string) {
        form.setData('new_niveau', value);
        if (!niveauxAvecDomaine.includes(value)) {
            form.setData('new_domaine', '');
        }
        if (value !== niveauStudium) {
            form.setData('new_examen_type', '');
        }
    }

    function handleModeChange(mode: 'new' | 'existing') {
        form.setData({
            ...form.data,
            inscription_mode: mode,
            student_id: '',
            new_nom: '',
            new_prenom: '',
            new_sexe: '',
            new_date_naissance: '',
            new_cin: '',
            new_niveau: '',
            new_domaine: '',
            new_examen_type: '',
            new_email: '',
            new_telephone: '',
            new_whatsapp: '',
            new_adresse: '',
            new_parent_relation: '',
            new_parent_nom: '',
            new_parent_sexe: '',
            new_parent_cin: '',
            new_parent_telephone: '',
            new_parent_whatsapp: '',
        });
        form.clearErrors();
    }

    function handleGroupChange(groupId: number | '') {
        form.setData((data) => ({ ...data, group_id: groupId, fee_lines: [], date_debut: '', date_fin: '' }));

        if (groupId === '' || editingInscription) {
            return;
        }

        setLoadingGroupFees(true);
        fetch(`/backoffice/groups/${groupId}/inscription-fees`, {
            headers: { Accept: 'application/json' },
        })
            .then((response) => response.json())
            .then((data: InscriptionGroupFeesResponse) => {
                form.setData((prev) => ({
                    ...prev,
                    date_debut: data.dateDebut ?? '',
                    date_fin: data.dateFin ?? '',
                    fee_lines: data.fees.map((fee) => ({
                        fraisId: fee.fraisId,
                        nom: fee.nom,
                        montantInitial: fee.montantInitial,
                        remisePct: '',
                        remiseMontant: '',
                        note: '',
                        dateEcheance: fee.dateEcheance,
                    })),
                }));
            })
            .finally(() => setLoadingGroupFees(false));
    }

    function setLine(index: number, field: keyof InscriptionFeeLine, value: string) {
        const lines = [...form.data.fee_lines];
        const line = { ...lines[index], [field]: value };

        const initial = parseFloat(line.montantInitial || '0');

        if (field === 'remisePct' && initial > 0) {
            const pct = Math.min(100, Math.max(0, parseFloat(value || '0')));
            line.remiseMontant = value === '' ? '' : String(Math.round((initial * pct) / 100 * 100) / 100);
        } else if (field === 'remiseMontant' && initial > 0) {
            const dh = Math.min(initial, Math.max(0, parseFloat(value || '0')));
            line.remisePct = value === '' ? '' : String(Math.round((dh / initial) * 100 * 100) / 100);
        } else if (field === 'montantInitial' && line.remisePct !== '') {
            const pct = Math.min(100, Math.max(0, parseFloat(line.remisePct || '0')));
            line.remiseMontant = String(Math.round((parseFloat(value || '0') * pct) / 100 * 100) / 100);
        }

        lines[index] = line;
        form.setData('fee_lines', lines);
    }

    function lineTotal(): number {
        return form.data.fee_lines.reduce((sum, line) => {
            const initial = parseFloat(line.montantInitial || '0');
            const pct = line.remisePct !== '' ? parseFloat(line.remisePct) : null;
            const dh = line.remiseMontant !== '' ? parseFloat(line.remiseMontant) : null;

            return sum + computeLineMontant(initial, pct, dh);
        }, 0);
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => closeModal() };

        if (editingInscription) {
            form.put(`/backoffice/inscriptions/${editingInscription.id}`, options);
        } else {
            form.post('/backoffice/inscriptions', options);
        }
    }

    function confirmDelete(inscription: InscriptionRow) {
        setDeleteTarget(inscription);
        setDeleteError(undefined);
    }

    function handleDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleteProcessing(true);
        router.delete(`/backoffice/inscriptions/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteTarget(null);
                setDeleteError(undefined);
            },
            onError: (errors) => {
                setDeleteError(errors.delete ?? 'Suppression impossible.');
            },
            onFinish: () => setDeleteProcessing(false),
        });
    }

    return (
        <BackofficeLayout
            title="Inscriptions"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Inscriptions' }]}
            actions={
                <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                    <i className="ti ti-square-rounded-plus me-2" />
                    Ajouter une inscription
                </button>
            }
        >
            <Card title="Inscriptions">
                <TableToolbar search={<SearchInput value={filters.search} onSearch={(value) => reload({ search: value })} placeholder="Rechercher" />}>
                    <div style={{ width: 200 }}>
                        <SelectField
                            id="ins-statut-filter"
                            label="Statut"
                            options={statutFilterOptions}
                            placeholder="Tous les statuts"
                            value={filters.statutFilter}
                            onChange={(event) => reload({ statutFilter: event.target.value })}
                        />
                    </div>
                </TableToolbar>

                {inscriptions.data.length === 0 ? (
                    <EmptyState title="Aucune inscription pour le moment" icon="ti ti-clipboard-list" />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Étudiant</th>
                                    <th>Groupe</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Frais</th>
                                    <th>Statut</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {inscriptions.data.map((inscription) => (
                                <tr key={inscription.id}>
                                    <td>
                                        <code>{inscription.reference}</code>
                                    </td>
                                    <td className="fw-medium">{inscription.student ?? '—'}</td>
                                    <td>{inscription.groupe ?? '—'}</td>
                                    <td>{inscription.date ?? '—'}</td>
                                    <td>{inscription.montantTotal !== null ? `${Number(inscription.montantTotal).toFixed(2)} MAD` : '—'}</td>
                                    <td>
                                        <span className="badge badge-soft-secondary">{inscription.feesCount}</span>
                                    </td>
                                    <td>
                                        <span className="badge badge-soft-info">{inscription.statut}</span>
                                    </td>
                                    <td className="text-end">
                                        <RowActions view={inscription.showUrl}>
                                            <RowActionItem icon="ti-edit" onClick={() => openEdit(inscription)}>
                                                Modifier
                                            </RowActionItem>
                                            <RowActionItem icon="ti-trash" danger onClick={() => confirmDelete(inscription)}>
                                                Supprimer
                                            </RowActionItem>
                                        </RowActions>
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                        <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 px-3">
                            <div className="d-flex align-items-center gap-2">
                                <label className="text-muted mb-0" htmlFor="ins-per-page">
                                    Par page
                                </label>
                                <select
                                    id="ins-per-page"
                                    className="form-select form-select-sm"
                                    style={{ width: 90 }}
                                    value={filters.perPage}
                                    onChange={(event) => reload({ perPage: Number(event.target.value) })}
                                >
                                    {perPageOptions.map((option) => (
                                        <option key={option} value={option}>
                                            {option}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <Pagination paginator={inscriptions} />
                    </>
                )}
            </Card>

            <Modal
                show={showModal}
                title={editingInscription ? "Modifier l'inscription" : 'Ajouter une inscription'}
                onClose={closeModal}
                processing={form.processing}
                size="lg"
            >
                <form onSubmit={submit}>
                    <div className="row">
                        {!editingInscription && (
                            <div className="col-md-4">
                                <SelectField
                                    id="ins-mode"
                                    label="Inscrire pour"
                                    required
                                    options={[
                                        { value: 'new', label: 'Nouvel étudiant' },
                                        { value: 'existing', label: 'Étudiant existant' },
                                    ]}
                                    value={form.data.inscription_mode}
                                    onChange={(event) => handleModeChange(event.target.value as 'new' | 'existing')}
                                />
                            </div>
                        )}

                        {(editingInscription || form.data.inscription_mode === 'existing') && (
                            <div className="col-md-8">
                                <SelectField
                                    id="ins-student"
                                    label="Étudiant"
                                    required
                                    options={studentOptions}
                                    placeholder="Choisir…"
                                    value={form.data.student_id}
                                    onChange={(event) => form.setData('student_id', event.target.value ? Number(event.target.value) : '')}
                                    error={form.errors.student_id}
                                />
                            </div>
                        )}

                        {!editingInscription && form.data.inscription_mode === 'new' && (
                            <>
                                <div className="col-md-4">
                                    <FormField
                                        id="ins-new-nom"
                                        label="Nom"
                                        required
                                        value={form.data.new_nom}
                                        onChange={(event) => form.setData('new_nom', event.target.value)}
                                        error={form.errors.new_nom}
                                        placeholder="ex : Rafik"
                                    />
                                </div>
                                <div className="col-md-4">
                                    <FormField
                                        id="ins-new-prenom"
                                        label="Prénom"
                                        required
                                        value={form.data.new_prenom}
                                        onChange={(event) => form.setData('new_prenom', event.target.value)}
                                        error={form.errors.new_prenom}
                                        placeholder="ex : Mohammed"
                                    />
                                </div>
                                <div className="col-md-4">
                                    <div className="mb-3">
                                        <label className="form-label d-block">Genre</label>
                                        <div className="btn-group" role="group" aria-label="Genre">
                                            {sexes.map((sexe) => (
                                                <div key={sexe}>
                                                    <input
                                                        type="radio"
                                                        className="btn-check"
                                                        name="ins-new-sexe"
                                                        id={`ins-new-sexe-${sexe}`}
                                                        value={sexe}
                                                        checked={form.data.new_sexe === sexe}
                                                        onChange={() => form.setData('new_sexe', sexe)}
                                                        autoComplete="off"
                                                    />
                                                    <label
                                                        className="btn btn-outline-primary d-inline-flex align-items-center justify-content-center px-4"
                                                        htmlFor={`ins-new-sexe-${sexe}`}
                                                    >
                                                        <i className={`ti ${sexe === 'Homme' ? 'ti-man' : 'ti-woman'} me-1`} />
                                                        {sexe === 'Homme' ? 'Masculin' : 'Féminin'}
                                                    </label>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                                <div className="col-md-4">
                                    <FormField
                                        id="ins-new-naiss"
                                        label="Date de naissance"
                                        type="date"
                                        value={form.data.new_date_naissance}
                                        onChange={(event) => form.setData('new_date_naissance', event.target.value)}
                                        error={form.errors.new_date_naissance}
                                    />
                                </div>
                                <div className="col-md-4">
                                    <FormField
                                        id="ins-new-cin"
                                        label="Carte d'identité"
                                        value={form.data.new_cin}
                                        onChange={(event) => form.setData('new_cin', event.target.value)}
                                        error={form.errors.new_cin}
                                        placeholder="ex : AB123456"
                                    />
                                </div>
                                <div className="col-md-4">
                                    <SelectField
                                        id="ins-new-niveau"
                                        label="Intéressé par"
                                        options={niveauOptions}
                                        placeholder="Choisir…"
                                        value={form.data.new_niveau}
                                        onChange={(event) => handleNiveauChange(event.target.value)}
                                        error={form.errors.new_niveau}
                                    />
                                </div>
                                {showsDomaine && (
                                    <div className="col-md-4">
                                        <SelectField
                                            id="ins-new-domaine"
                                            label="Domaine"
                                            required
                                            options={domaineOptions}
                                            placeholder="Choisir…"
                                            value={form.data.new_domaine}
                                            onChange={(event) => form.setData('new_domaine', event.target.value)}
                                            error={form.errors.new_domaine}
                                        />
                                    </div>
                                )}
                                {showsExamen && (
                                    <div className="col-md-4">
                                        <SelectField
                                            id="ins-new-examen"
                                            label="Examen d'entrée"
                                            required
                                            options={examenOptions}
                                            placeholder="Choisir…"
                                            value={form.data.new_examen_type}
                                            onChange={(event) => form.setData('new_examen_type', event.target.value)}
                                            error={form.errors.new_examen_type}
                                        />
                                    </div>
                                )}
                                <div className="col-md-4">
                                    <FormField
                                        id="ins-new-date"
                                        label="Date d'inscription"
                                        type="date"
                                        required
                                        value={form.data.date_inscription}
                                        onChange={(event) => form.setData('date_inscription', event.target.value)}
                                        error={form.errors.date_inscription}
                                    />
                                </div>
                            </>
                        )}

                        {editingInscription && (
                            <div className="col-md-4">
                                <FormField
                                    id="ins-edit-date"
                                    label="Date d'inscription"
                                    type="date"
                                    required
                                    value={form.data.date_inscription}
                                    onChange={(event) => form.setData('date_inscription', event.target.value)}
                                    error={form.errors.date_inscription}
                                />
                            </div>
                        )}
                    </div>

                    <ul className="nav nav-tabs nav-tabs-solid mt-2 mb-3" role="tablist">
                        <li className="nav-item">
                            <button
                                type="button"
                                className={`nav-link border-0 bg-transparent${activeTab === 'affectation' ? ' active' : ''}`}
                                onClick={() => setActiveTab('affectation')}
                            >
                                <i className="ti ti-calendar-event me-1" />
                                Affectation
                            </button>
                        </li>
                        {!editingInscription && form.data.inscription_mode === 'new' && (
                            <>
                                <li className="nav-item">
                                    <button
                                        type="button"
                                        className={`nav-link border-0 bg-transparent${activeTab === 'contact' ? ' active' : ''}`}
                                        onClick={() => setActiveTab('contact')}
                                    >
                                        <i className="ti ti-mail me-1" />
                                        Contact
                                    </button>
                                </li>
                                <li className="nav-item">
                                    <button
                                        type="button"
                                        className={`nav-link border-0 bg-transparent${activeTab === 'parent' ? ' active' : ''}`}
                                        onClick={() => setActiveTab('parent')}
                                    >
                                        <i className="ti ti-user me-1" />
                                        Parent
                                    </button>
                                </li>
                            </>
                        )}
                        <li className="nav-item">
                            <button
                                type="button"
                                className={`nav-link border-0 bg-transparent${activeTab === 'autre' ? ' active' : ''}`}
                                onClick={() => setActiveTab('autre')}
                            >
                                <i className="ti ti-info-circle me-1" />
                                Autres informations
                            </button>
                        </li>
                    </ul>

                    {activeTab === 'affectation' && (
                        <div>
                            <div className="row">
                                <div className="col-md-6">
                                    <SelectField
                                        id="ins-group"
                                        label="Groupe"
                                        required
                                        options={groupOptions}
                                        placeholder="Choisir une formation"
                                        value={form.data.group_id}
                                        onChange={(event) => handleGroupChange(event.target.value ? Number(event.target.value) : '')}
                                        error={form.errors.group_id}
                                    />
                                </div>
                                {editingInscription && (
                                    <div className="col-md-6">
                                        <SelectField
                                            id="ins-statut"
                                            label="Statut"
                                            required
                                            options={statutOptions}
                                            value={form.data.statut}
                                            onChange={(event) => form.setData('statut', event.target.value)}
                                            error={form.errors.statut}
                                        />
                                    </div>
                                )}
                                {form.data.group_id !== '' && (
                                    <>
                                        <div className="col-md-6">
                                            <FormField
                                                id="ins-debut"
                                                label="Date de début"
                                                type="date"
                                                value={form.data.date_debut}
                                                readOnly
                                            />
                                            <div className="form-text">Provient du groupe</div>
                                        </div>
                                        <div className="col-md-6">
                                            <FormField id="ins-fin" label="Date de fin" type="date" value={form.data.date_fin} readOnly />
                                            <div className="form-text">Provient du groupe</div>
                                        </div>
                                    </>
                                )}
                            </div>

                            {!editingInscription && (
                                <div className="border-top pt-3">
                                    <h6 className="mb-1">Frais disponibles</h6>
                                    {form.data.group_id === '' ? (
                                        <p className="text-muted fs-13">Sélectionnez un groupe pour voir ses frais disponibles.</p>
                                    ) : loadingGroupFees ? (
                                        <p className="text-muted fs-13">Chargement…</p>
                                    ) : form.data.fee_lines.length === 0 ? (
                                        <div className="alert alert-warning mb-0">
                                            Ce groupe n'a aucun frais assigné. Assignez des frais sur le groupe d'abord.
                                        </div>
                                    ) : (
                                        <div className="table-responsive">
                                            <table className="table table-sm align-middle mb-1">
                                                <thead className="thead-light">
                                                    <tr>
                                                        <th>Frais</th>
                                                        <th>Initial (DH)</th>
                                                        <th>Remise</th>
                                                        <th>Note</th>
                                                        <th className="text-end">Montant</th>
                                                        <th>Échéance</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {form.data.fee_lines.map((line, index) => {
                                                        const initial = parseFloat(line.montantInitial || '0');
                                                        const pct = line.remisePct !== '' ? parseFloat(line.remisePct) : null;
                                                        const dh = line.remiseMontant !== '' ? parseFloat(line.remiseMontant) : null;
                                                        const final = computeLineMontant(initial, pct, dh);
                                                        const montantError = (form.errors as Record<string, string>)[`fee_lines.${index}.montant_initial`];

                                                        return (
                                                            <tr key={line.fraisId ?? index}>
                                                                <td className="fw-medium">{line.nom}</td>
                                                                <td style={{ width: 110 }}>
                                                                    <input
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0"
                                                                        className={`form-control form-control-sm${montantError ? ' is-invalid' : ''}`}
                                                                        value={line.montantInitial}
                                                                        onChange={(event) => setLine(index, 'montantInitial', event.target.value)}
                                                                    />
                                                                </td>
                                                                <td style={{ width: 190 }}>
                                                                    <div className="input-group input-group-sm">
                                                                        <input
                                                                            type="number"
                                                                            step="0.01"
                                                                            min="0"
                                                                            max="100"
                                                                            className="form-control"
                                                                            placeholder="%"
                                                                            value={line.remisePct}
                                                                            onChange={(event) => setLine(index, 'remisePct', event.target.value)}
                                                                        />
                                                                        <span className="input-group-text">%</span>
                                                                        <input
                                                                            type="number"
                                                                            step="0.01"
                                                                            min="0"
                                                                            className="form-control"
                                                                            placeholder="DH"
                                                                            value={line.remiseMontant}
                                                                            onChange={(event) => setLine(index, 'remiseMontant', event.target.value)}
                                                                        />
                                                                        <span className="input-group-text">DH</span>
                                                                    </div>
                                                                </td>
                                                                <td style={{ width: 150 }}>
                                                                    <input
                                                                        type="text"
                                                                        className="form-control form-control-sm"
                                                                        value={line.note}
                                                                        onChange={(event) => setLine(index, 'note', event.target.value)}
                                                                    />
                                                                </td>
                                                                <td className="text-end fw-semibold" style={{ width: 110 }}>
                                                                    {final.toFixed(2)} DH
                                                                </td>
                                                                <td style={{ width: 150 }}>
                                                                    <input
                                                                        type="date"
                                                                        className="form-control form-control-sm"
                                                                        value={line.dateEcheance}
                                                                        onChange={(event) => setLine(index, 'dateEcheance', event.target.value)}
                                                                    />
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colSpan={4} className="text-end fw-semibold">
                                                            Total à payer
                                                        </td>
                                                        <td className="text-end fw-bold">{lineTotal().toFixed(2)} DH</td>
                                                        <td />
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    )}

                    {activeTab === 'contact' && !editingInscription && form.data.inscription_mode === 'new' && (
                        <div className="row">
                            <div className="col-md-6">
                                <FormField
                                    id="ins-email"
                                    label="Email"
                                    type="email"
                                    value={form.data.new_email}
                                    onChange={(event) => form.setData('new_email', event.target.value)}
                                    error={form.errors.new_email}
                                    placeholder="ex : nom@domaine.com"
                                />
                            </div>
                            <div className="col-md-6">
                                <PhoneField
                                    id="ins-tel"
                                    label="Téléphone"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.new_telephone}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('new_telephone', value)}
                                    error={form.errors.new_telephone}
                                />
                            </div>
                            <div className="col-md-6">
                                <PhoneField
                                    id="ins-wa"
                                    label="WhatsApp"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.new_whatsapp}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('new_whatsapp', value)}
                                    error={form.errors.new_whatsapp}
                                />
                            </div>
                            <div className="col-md-6">
                                <FormField
                                    id="ins-adresse"
                                    label="Adresse"
                                    value={form.data.new_adresse}
                                    onChange={(event) => form.setData('new_adresse', event.target.value)}
                                    error={form.errors.new_adresse}
                                    placeholder="ex : 7 rue des fleurs"
                                />
                            </div>
                        </div>
                    )}

                    {activeTab === 'parent' && !editingInscription && form.data.inscription_mode === 'new' && (
                        <div className="row">
                            <div className="col-md-4">
                                <SelectField
                                    id="ins-prelation"
                                    label="Catégorie"
                                    options={parentRelationOptions}
                                    placeholder="Choisir la relation"
                                    value={form.data.new_parent_relation}
                                    onChange={(event) => form.setData('new_parent_relation', event.target.value)}
                                    error={form.errors.new_parent_relation}
                                />
                            </div>
                            <div className="col-md-4">
                                <FormField
                                    id="ins-pnom"
                                    label="Nom du parent"
                                    value={form.data.new_parent_nom}
                                    onChange={(event) => form.setData('new_parent_nom', event.target.value)}
                                    error={form.errors.new_parent_nom}
                                    placeholder="ex : Alaoui"
                                />
                            </div>
                            <div className="col-md-4">
                                <div className="mb-3">
                                    <label className="form-label d-block">Genre</label>
                                    <div className="btn-group" role="group" aria-label="Genre du parent">
                                        {sexes.map((sexe) => (
                                            <div key={sexe}>
                                                <input
                                                    type="radio"
                                                    className="btn-check"
                                                    name="ins-psexe"
                                                    id={`ins-psexe-${sexe}`}
                                                    value={sexe}
                                                    checked={form.data.new_parent_sexe === sexe}
                                                    onChange={() => form.setData('new_parent_sexe', sexe)}
                                                    autoComplete="off"
                                                />
                                                <label
                                                    className="btn btn-outline-primary d-inline-flex align-items-center justify-content-center px-4"
                                                    htmlFor={`ins-psexe-${sexe}`}
                                                >
                                                    <i className={`ti ${sexe === 'Homme' ? 'ti-man' : 'ti-woman'} me-1`} />
                                                    {sexe === 'Homme' ? 'Masculin' : 'Féminin'}
                                                </label>
                                            </div>
                                        ))}
                                    </div>
                                    {form.errors.new_parent_sexe && <div className="text-danger fs-12 mt-1">{form.errors.new_parent_sexe}</div>}
                                </div>
                            </div>
                            <div className="col-md-4">
                                <FormField
                                    id="ins-pcin"
                                    label="CIN"
                                    value={form.data.new_parent_cin}
                                    onChange={(event) => form.setData('new_parent_cin', event.target.value)}
                                    error={form.errors.new_parent_cin}
                                    placeholder="ex : AB123456"
                                />
                            </div>
                            <div className="col-md-4">
                                <PhoneField
                                    id="ins-ptel"
                                    label="Téléphone du parent"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.new_parent_telephone}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('new_parent_telephone', value)}
                                    error={form.errors.new_parent_telephone}
                                />
                            </div>
                            <div className="col-md-4">
                                <PhoneField
                                    id="ins-pwa"
                                    label="WhatsApp du parent"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.new_parent_whatsapp}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('new_parent_whatsapp', value)}
                                    error={form.errors.new_parent_whatsapp}
                                />
                            </div>
                        </div>
                    )}

                    {activeTab === 'autre' && (
                        <TextareaField
                            id="ins-note"
                            label="Note"
                            rows={3}
                            value={form.data.note}
                            onChange={(event) => form.setData('note', event.target.value)}
                            error={form.errors.note}
                            placeholder="Notes complémentaires sur cette inscription"
                        />
                    )}

                    <div className="d-flex justify-content-end gap-2 mt-4">
                        <FormActions onCancel={closeModal} processing={form.processing} />
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer cette inscription"
                recordLabel={deleteTarget?.reference ?? ''}
                message="Voulez-vous vraiment supprimer cette inscription ?"
                error={deleteError}
                processing={deleteProcessing}
                onConfirm={handleDelete}
                onCancel={() => {
                    setDeleteTarget(null);
                    setDeleteError(undefined);
                }}
            />
        </BackofficeLayout>
    );
}
