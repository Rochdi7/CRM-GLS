import { router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import FilterDropdown from '@/Components/Tables/FilterDropdown';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
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
import FormErrorsSummary from '@/Components/Forms/FormErrorsSummary';
import StatusBadge from '@/Components/Details/StatusBadge';
import { splitPhone } from '@/Data/countries';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import type { EmployeeRow, EmployeesPageProps, SelectOption } from '@/Types';

/** Gender icon, matching components/backoffice/ui/sexe-icon.blade.php. */
function SexeIcon({ sexe }: { sexe: string | null }) {
    if (sexe === 'Homme') {
        return <i className="ti ti-man fs-16 text-primary" title="Homme" />;
    }
    if (sexe === 'Femme') {
        return <i className="ti ti-woman fs-16 text-pink" title="Femme" />;
    }
    return <>—</>;
}

interface EmployeeFormState {
    nom: string;
    prenom: string;
    sexe: string;
    categorie: string;
    statut: string;
    phone_pays: string;
    telephone: string;
    whatsapp: string;
    email: string;
    adresse: string;
    note: string;
    photo: File | null;
    date_naissance: string;
    date_embauche: string;
    salaire: string;
    etablissement_id: number | '';
}

function emptyForm(defaultCountry: string, contextCenterId: number | null): EmployeeFormState {
    return {
        nom: '',
        prenom: '',
        sexe: 'Homme',
        categorie: 'Enseignant',
        statut: 'Actif',
        phone_pays: defaultCountry,
        telephone: '',
        whatsapp: '',
        email: '',
        adresse: '',
        note: '',
        photo: null,
        date_naissance: '',
        date_embauche: '',
        salaire: '',
        etablissement_id: contextCenterId ?? '',
    };
}

/**
 * Replaces App\Livewire\Backoffice\Employees\EmployeesIndex — same fields,
 * same search/filter behavior, same modal add/edit + one-time credentials
 * flow, now driven by real HTTP requests (EmployeeController) instead of a
 * Livewire component. Server-side search/filter/pagination preserved
 * exactly (CLAUDE.md's DataTables rule) via router.get with the query
 * string, matching Pagination.tsx's own navigation convention.
 */
export default function EmployeesIndex({
    employees,
    filters,
    perPageOptions,
    categories,
    statuts,
    sexes,
    defaultCountry,
    etablissements,
    centerLocked,
    contextCenterId,
}: EmployeesPageProps) {
    const { props } = usePage<{ flash: { newEmployeeCredentials?: { username: string; password: string } | null } }>();
    const isLoading = useInertiaLoading();

    const [showModal, setShowModal] = useState(false);
    const [editingEmployee, setEditingEmployee] = useState<EmployeeRow | null>(null);
    const [existingPhotoUrl, setExistingPhotoUrl] = useState<string | null>(null);
    const [photoPreview, setPhotoPreview] = useState<string | null>(null);
    const [credentials, setCredentials] = useState<{ username: string; password: string } | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<EmployeeRow | null>(null);
    const [deleteError, setDeleteError] = useState<string | undefined>(undefined);
    const [deleteProcessing, setDeleteProcessing] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const form = useForm<EmployeeFormState>(emptyForm(defaultCountry, contextCenterId));

    // Surface the one-time credentials exactly once, driven off the shared
    // flash prop (not a sentinel form-state id like the Livewire original's
    // `editingId = -1`) — cleaner in React per the task's guidance.
    useEffect(() => {
        const newCredentials = props.flash?.newEmployeeCredentials;

        if (newCredentials) {
            setCredentials(newCredentials);
            setShowModal(true);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [props.flash?.newEmployeeCredentials]);

    const categoryOptions: SelectOption[] = categories.map((categorie) => ({ value: categorie, label: categorie }));
    const statutOptions: SelectOption[] = statuts.map((statut) => ({ value: statut, label: statut }));
    const centerOptions: SelectOption[] = etablissements.map((etab) => ({ value: etab.id, label: etab.nom_centre }));
    const categorieFilterOptions: SelectOption[] = categories.map((categorie) => ({ value: categorie, label: categorie }));

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/employees',
            { ...filters, ...nextFilters, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function openCreate() {
        setEditingEmployee(null);
        setCredentials(null);
        setExistingPhotoUrl(null);
        setPhotoPreview(null);
        form.reset();
        form.clearErrors();
        form.setData(emptyForm(defaultCountry, contextCenterId));
        setShowModal(true);
    }

    function openEdit(employee: EmployeeRow) {
        setEditingEmployee(employee);
        setCredentials(null);
        setExistingPhotoUrl(employee.photoUrl);
        setPhotoPreview(null);
        form.clearErrors();
        const [phonePays, telephone] = splitPhone(employee.telephone);
        const [, whatsapp] = splitPhone(employee.whatsapp);
        form.setData({
            nom: employee.nom,
            prenom: employee.prenom,
            sexe: employee.sexe ?? 'Homme',
            categorie: employee.categorie,
            statut: employee.statut,
            phone_pays: phonePays,
            telephone,
            whatsapp,
            email: employee.email ?? '',
            adresse: employee.adresse ?? '',
            note: employee.note ?? '',
            photo: null,
            date_naissance: employee.dateNaissance ?? '',
            date_embauche: employee.dateEmbauche ?? '',
            salaire: employee.salaire ?? '',
            etablissement_id: employee.etablissementId ?? '',
        });
        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditingEmployee(null);
        setCredentials(null);
        setExistingPhotoUrl(null);
        setPhotoPreview(null);
        form.reset();
        form.clearErrors();
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }

    function handlePhotoChange(event: React.ChangeEvent<HTMLInputElement>) {
        const file = event.target.files?.[0] ?? null;
        form.setData('photo', file);

        if (file) {
            const reader = new FileReader();
            reader.onload = () => setPhotoPreview(reader.result as string);
            reader.readAsDataURL(file);
        } else {
            setPhotoPreview(null);
        }
    }

    function submit(event: FormEvent) {
        event.preventDefault();

        if (editingEmployee) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(`/backoffice/employees/${editingEmployee.id}`, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => closeModal(),
                // Reset the transform so a later create on this shared form
                // doesn't POST with a stale _method=put (Phase 12 UX fix).
                onFinish: () => form.transform((data) => data),
            });

            return;
        }

        form.post('/backoffice/employees', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                // Keep the modal open — the shared `flash.newEmployeeCredentials`
                // prop drives the credentials view via the effect above.
                form.reset();
            },
        });
    }

    function confirmDelete(employee: EmployeeRow) {
        setDeleteTarget(employee);
        setDeleteError(undefined);
    }

    function handleDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleteProcessing(true);
        router.delete(`/backoffice/employees/${deleteTarget.id}`, {
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

    const modalTitle = credentials ? 'Employé créé' : editingEmployee ? "Modifier l'employé" : 'Ajouter un employé';

    return (
        <BackofficeLayout
            title="Employés"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Employés' }]}
            actions={
                <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                    <i className="ti ti-square-rounded-plus me-2" />
                    Ajouter un employé
                </button>
            }
        >
            <Card
                title="Employés"
                bodyClassName="p-0 py-3"
                tools={
                    <FilterDropdown
                        fields={[
                            {
                                name: 'categorieFilter',
                                label: 'Catégorie',
                                value: filters.categorieFilter,
                                options: categorieFilterOptions,
                                placeholder: 'Toutes les catégories',
                            },
                        ]}
                        onApply={(values) => reload(values)}
                        onReset={() => reload({ categorieFilter: '' })}
                    />
                }
            >
                <TableLengthRow
                    perPage={filters.perPage}
                    perPageOptions={perPageOptions}
                    onPerPageChange={(perPage) => reload({ perPage })}
                    search={<SearchInput value={filters.search} onSearch={(value) => reload({ search: value })} placeholder="Rechercher" />}
                />

                {employees.data.length === 0 ? (
                    <EmptyState title="Aucun employé" icon="ti ti-users" />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Nom</th>
                                    <th>Genre</th>
                                    <th>Catégorie</th>
                                    <th>Centre</th>
                                    <th>Téléphone</th>
                                    <th>Statut</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {employees.data.map((employee) => (
                                <tr key={employee.id}>
                                    <td>
                                        <code>{employee.reference}</code>
                                    </td>
                                    <td>
                                        <span className="d-inline-flex align-items-center">
                                            <span className="avatar avatar-sm rounded-circle bg-primary-transparent me-2 d-inline-flex align-items-center justify-content-center overflow-hidden">
                                                {employee.photoThumbUrl ? (
                                                    <img
                                                        src={employee.photoThumbUrl}
                                                        alt=""
                                                        className="w-100 h-100"
                                                        style={{ objectFit: 'cover' }}
                                                        loading="lazy"
                                                    />
                                                ) : (
                                                    <span className="fw-bold text-primary">
                                                        {employee.prenom.charAt(0).toUpperCase()}
                                                    </span>
                                                )}
                                            </span>
                                            <span className="fw-medium">{employee.nomComplet}</span>
                                        </span>
                                    </td>
                                    <td>
                                        <SexeIcon sexe={employee.sexe} />
                                    </td>
                                    <td>
                                        <span className="badge badge-soft-info">{employee.categorie}</span>
                                    </td>
                                    <td>{employee.etablissement ?? '—'}</td>
                                    <td>{employee.telephone ?? '—'}</td>
                                    <td>
                                        <StatusBadge
                                            label={employee.statut}
                                            variant={employee.statut === 'Actif' ? 'success' : 'secondary'}
                                            dot
                                        />
                                    </td>
                                    <td className="text-end">
                                        <RowActions>
                                            <RowActionItem icon="ti-edit" onClick={() => openEdit(employee)}>
                                                Modifier
                                            </RowActionItem>
                                            <RowActionItem icon="ti-trash" danger onClick={() => confirmDelete(employee)}>
                                                Supprimer
                                            </RowActionItem>
                                        </RowActions>
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                        <Pagination paginator={employees} showJumpToPage />
                    </>
                )}
            </Card>

            <Modal show={showModal} title={modalTitle} onClose={closeModal} processing={form.processing} size="lg">
                {credentials ? (
                    <div>
                        <div className="text-center mb-3">
                            <span className="avatar avatar-xl bg-success-transparent rounded-circle d-inline-flex align-items-center justify-content-center mb-2">
                                <i className="ti ti-circle-check fs-24 text-success" />
                            </span>
                            <h5>Employé créé</h5>
                            <p className="text-muted">
                                Partagez ces identifiants de connexion avec l'employé. Ils ne seront plus affichés ensuite.
                            </p>
                        </div>
                        <div className="alert alert-info">
                            <div className="d-flex justify-content-between mb-2">
                                <span className="fw-medium">Nom d'utilisateur</span>
                                <code className="fs-14">{credentials.username}</code>
                            </div>
                            <div className="d-flex justify-content-between">
                                <span className="fw-medium">Mot de passe</span>
                                <code className="fs-14">{credentials.password}</code>
                            </div>
                        </div>
                        <div className="text-end mt-3">
                            <button type="button" className="btn btn-primary" onClick={closeModal}>
                                Terminé
                            </button>
                        </div>
                    </div>
                ) : (
                    <form onSubmit={submit}>
                        <FormErrorsSummary message={form.errors.etablissement_id && centerLocked ? form.errors.etablissement_id : undefined} />

                        <div className="d-flex align-items-center mb-4">
                            <span className="avatar avatar-xl rounded-circle bg-light me-3 overflow-hidden d-inline-flex align-items-center justify-content-center">
                                {photoPreview ? (
                                    <img src={photoPreview} alt="" className="w-100 h-100" style={{ objectFit: 'cover' }} />
                                ) : existingPhotoUrl ? (
                                    <img src={existingPhotoUrl} alt="" className="w-100 h-100" style={{ objectFit: 'cover' }} />
                                ) : (
                                    <i className="ti ti-camera fs-24 text-muted" />
                                )}
                            </span>
                            <div>
                                <label className="form-label mb-1" htmlFor="emp-photo">
                                    Photo
                                </label>
                                <input
                                    ref={fileInputRef}
                                    id="emp-photo"
                                    type="file"
                                    accept="image/*"
                                    className={`form-control form-control-sm${form.errors.photo ? ' is-invalid' : ''}`}
                                    onChange={handlePhotoChange}
                                />
                                {form.errors.photo && <div className="invalid-feedback d-block">{form.errors.photo}</div>}
                            </div>
                        </div>

                        <div className="row">
                            <div className="col-md-6">
                                <FormField
                                    id="emp-nom"
                                    label="Nom"
                                    required
                                    value={form.data.nom}
                                    onChange={(event) => form.setData('nom', event.target.value)}
                                    error={form.errors.nom}
                                    placeholder="ex : Rafik"
                                />
                            </div>
                            <div className="col-md-6">
                                <FormField
                                    id="emp-prenom"
                                    label="Prénom"
                                    required
                                    value={form.data.prenom}
                                    onChange={(event) => form.setData('prenom', event.target.value)}
                                    error={form.errors.prenom}
                                    placeholder="ex : Mohammed"
                                />
                            </div>

                            <div className="col-md-6">
                                <div className="mb-3">
                                    <label className="form-label d-block">
                                        Genre<span className="text-danger ms-1">*</span>
                                    </label>
                                    <div className="d-flex gap-2" role="group" aria-label="Genre">
                                        {sexes.map((sexe) => (
                                            <div key={sexe} className="flex-fill d-grid">
                                                <input
                                                    type="radio"
                                                    className="btn-check"
                                                    name="sexe"
                                                    id={`emp-sexe-${sexe}`}
                                                    value={sexe}
                                                    checked={form.data.sexe === sexe}
                                                    onChange={() => form.setData('sexe', sexe)}
                                                    autoComplete="off"
                                                />
                                                <label
                                                    className="btn btn-outline-primary d-inline-flex align-items-center justify-content-center px-4"
                                                    htmlFor={`emp-sexe-${sexe}`}
                                                >
                                                    <i className={`ti ${sexe === 'Homme' ? 'ti-man' : 'ti-woman'} me-1`} />
                                                    {sexe === 'Homme' ? 'Masculin' : 'Féminin'}
                                                </label>
                                            </div>
                                        ))}
                                    </div>
                                    {form.errors.sexe && <div className="text-danger fs-12 mt-1">{form.errors.sexe}</div>}
                                </div>
                            </div>

                            <div className="col-md-6">
                                <FormField
                                    id="emp-naissance"
                                    label="Date de naissance"
                                    type="date"
                                    value={form.data.date_naissance}
                                    onChange={(event) => form.setData('date_naissance', event.target.value)}
                                    error={form.errors.date_naissance}
                                />
                            </div>

                            <div className="col-md-6">
                                <SelectField
                                    id="emp-cat"
                                    label="Catégorie de l'employé"
                                    required
                                    options={categoryOptions}
                                    value={form.data.categorie}
                                    onChange={(event) => form.setData('categorie', event.target.value)}
                                    error={form.errors.categorie}
                                />
                            </div>
                            <div className="col-md-6">
                                <SelectField
                                    id="emp-statut"
                                    label="Statut"
                                    required
                                    options={statutOptions}
                                    value={form.data.statut}
                                    onChange={(event) => form.setData('statut', event.target.value)}
                                    error={form.errors.statut}
                                />
                            </div>

                            <div className="col-md-6">
                                <PhoneField
                                    id="emp-tel"
                                    label="Téléphone"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.telephone}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('telephone', value)}
                                    error={form.errors.telephone}
                                />
                            </div>
                            <div className="col-md-6">
                                <PhoneField
                                    id="emp-wa"
                                    label="WhatsApp"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.whatsapp}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('whatsapp', value)}
                                    error={form.errors.whatsapp}
                                />
                            </div>

                            <div className="col-md-6">
                                <FormField
                                    id="emp-email"
                                    label="Email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(event) => form.setData('email', event.target.value)}
                                    error={form.errors.email}
                                    placeholder="ex : nom@domaine.com"
                                />
                            </div>
                            <div className="col-md-6">
                                <FormField
                                    id="emp-adresse"
                                    label="Adresse"
                                    value={form.data.adresse}
                                    onChange={(event) => form.setData('adresse', event.target.value)}
                                    error={form.errors.adresse}
                                    placeholder="ex : 12 rue Al Massira, Marrakech"
                                />
                            </div>

                            {/* A specific center active in the top bar assigns the record
                                automatically — the field only shows on « Tous les centres ». */}
                            {!centerLocked && (
                                <div className="col-md-6">
                                    <SelectField
                                        id="emp-etab"
                                        label="Centre"
                                        options={centerOptions}
                                        placeholder="Choisir…"
                                        value={form.data.etablissement_id}
                                        onChange={(event) =>
                                            form.setData('etablissement_id', event.target.value ? Number(event.target.value) : '')
                                        }
                                        error={form.errors.etablissement_id}
                                    />
                                </div>
                            )}

                            <div className="col-md-6">
                                <FormField
                                    id="emp-embauche"
                                    label="Date d'embauche"
                                    type="date"
                                    value={form.data.date_embauche}
                                    onChange={(event) => form.setData('date_embauche', event.target.value)}
                                    error={form.errors.date_embauche}
                                />
                            </div>
                            <div className="col-md-6">
                                <div className="mb-3">
                                    <label className="form-label" htmlFor="emp-salaire">
                                        Salaire
                                    </label>
                                    <div className="input-group">
                                        <input
                                            id="emp-salaire"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            className={`form-control${form.errors.salaire ? ' is-invalid' : ''}`}
                                            placeholder="ex : 5000"
                                            value={form.data.salaire}
                                            onChange={(event) => form.setData('salaire', event.target.value)}
                                        />
                                        <span className="input-group-text">MAD</span>
                                        {form.errors.salaire && <div className="invalid-feedback">{form.errors.salaire}</div>}
                                    </div>
                                </div>
                            </div>

                            <div className="col-12">
                                <TextareaField
                                    id="emp-note"
                                    label="Note"
                                    rows={2}
                                    value={form.data.note}
                                    onChange={(event) => form.setData('note', event.target.value)}
                                    error={form.errors.note}
                                />
                            </div>
                        </div>

                        {!editingEmployee && (
                            <p className="text-muted fs-13 mb-0">
                                <i className="ti ti-info-circle me-1" />
                                Un compte de connexion sera créé automatiquement pour cet employé.
                            </p>
                        )}

                        <div className="d-flex justify-content-end gap-2 mt-4">
                            <FormActions onCancel={closeModal} processing={form.processing} />
                        </div>
                    </form>
                )}
            </Modal>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer l'employé"
                recordLabel={deleteTarget?.nomComplet ?? ''}
                message="Voulez-vous vraiment supprimer cet employé ?"
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
