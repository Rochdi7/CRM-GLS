import { router, useForm } from '@inertiajs/react';
import { useRef, useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
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
import { splitPhone } from '@/Data/countries';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import type { SelectOption, StudentRow, StudentsPageProps } from '@/Types';

interface StudentFormState {
    nom: string;
    prenom: string;
    sexe: string;
    date_naissance: string;
    phone_pays: string;
    telephone: string;
    whatsapp: string;
    email: string;
    adresse: string;
    cin: string;
    niveau: string;
    domaine: string;
    examen_type: string;
    etablissement_id: number | '';
    parent_nom: string;
    parent_relation: string;
    parent_sexe: string;
    parent_cin: string;
    parent_telephone: string;
    parent_whatsapp: string;
    note: string;
    photo: File | null;
}

function emptyForm(defaultCountry: string, contextCenterId: number | null): StudentFormState {
    return {
        nom: '',
        prenom: '',
        sexe: '',
        date_naissance: '',
        phone_pays: defaultCountry,
        telephone: '',
        whatsapp: '',
        email: '',
        adresse: '',
        cin: '',
        niveau: '',
        domaine: '',
        examen_type: '',
        etablissement_id: contextCenterId ?? '',
        parent_nom: '',
        parent_relation: '',
        parent_sexe: '',
        parent_cin: '',
        parent_telephone: '',
        parent_whatsapp: '',
        note: '',
        photo: null,
    };
}

/**
 * Replaces App\Livewire\Backoffice\Students\StudentsIndex — same fields,
 * same search/filter/sort behavior, same modal add/edit + photo upload,
 * now driven by real HTTP requests (StudentController) instead of a Livewire
 * component. Server-side search/filter/pagination preserved exactly.
 */
export default function StudentsIndex({
    students,
    filters,
    perPageOptions,
    niveaux,
    domaines,
    examenTypes,
    sexes,
    parentRelations,
    niveauxAvecDomaine,
    niveauStudium,
    defaultCountry,
    etablissements,
    centerLocked,
    contextCenterId,
}: StudentsPageProps) {
    const isLoading = useInertiaLoading();
    const [showModal, setShowModal] = useState(false);
    const [editingStudent, setEditingStudent] = useState<StudentRow | null>(null);
    const [existingPhotoUrl, setExistingPhotoUrl] = useState<string | null>(null);
    const [photoPreview, setPhotoPreview] = useState<string | null>(null);
    const [activeTab, setActiveTab] = useState<'contact' | 'parent' | 'autre'>('contact');
    const [deleteTarget, setDeleteTarget] = useState<StudentRow | null>(null);
    const [deleteError, setDeleteError] = useState<string | undefined>(undefined);
    const [deleteProcessing, setDeleteProcessing] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const form = useForm<StudentFormState>(emptyForm(defaultCountry, contextCenterId));

    const niveauOptions: SelectOption[] = niveaux.map((n) => ({ value: n, label: n }));
    const domaineOptions: SelectOption[] = domaines.map((d) => ({ value: d, label: d }));
    const examenOptions: SelectOption[] = examenTypes.map((e) => ({ value: e, label: e }));
    const parentRelationOptions: SelectOption[] = parentRelations.map((r) => ({ value: r, label: r }));
    const centerOptions: SelectOption[] = etablissements.map((etab) => ({ value: etab.id, label: etab.nom_centre }));
    const niveauFilterOptions: SelectOption[] = niveaux.map((n) => ({ value: n, label: n }));

    const showsDomaine = niveauxAvecDomaine.includes(form.data.niveau);
    const showsExamen = form.data.niveau === niveauStudium;

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/students',
            { ...filters, ...nextFilters, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function toggleAgeSort() {
        reload({ ageSort: filters.ageSort === 'asc' ? 'desc' : 'asc' });
    }

    function openCreate() {
        setEditingStudent(null);
        setExistingPhotoUrl(null);
        setPhotoPreview(null);
        setActiveTab('contact');
        form.reset();
        form.clearErrors();
        form.setData(emptyForm(defaultCountry, contextCenterId));
        setShowModal(true);
    }

    function openEdit(student: StudentRow) {
        setEditingStudent(student);
        setExistingPhotoUrl(student.photoUrl);
        setPhotoPreview(null);
        setActiveTab('contact');
        form.clearErrors();
        const [phonePays, telephone] = splitPhone(student.telephone);
        const [, whatsapp] = splitPhone(student.whatsapp);
        const [, parentTelephone] = splitPhone(student.parentTelephone);
        const [, parentWhatsapp] = splitPhone(student.parentWhatsapp);
        form.setData({
            nom: student.nom,
            prenom: student.prenom,
            sexe: student.sexe ?? '',
            date_naissance: student.dateNaissance ?? '',
            phone_pays: phonePays,
            telephone,
            whatsapp,
            email: student.email ?? '',
            adresse: student.adresse ?? '',
            cin: student.cin ?? '',
            niveau: student.niveau ?? '',
            domaine: student.domaine ?? '',
            examen_type: student.examenType ?? '',
            etablissement_id: student.etablissementId ?? '',
            parent_nom: student.parentNom ?? '',
            parent_relation: student.parentRelation ?? '',
            parent_sexe: student.parentSexe ?? '',
            parent_cin: student.parentCin ?? '',
            parent_telephone: parentTelephone,
            parent_whatsapp: parentWhatsapp,
            note: student.note ?? '',
            photo: null,
        });
        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditingStudent(null);
        setExistingPhotoUrl(null);
        setPhotoPreview(null);
        form.reset();
        form.clearErrors();
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }

    function handleNiveauChange(value: string) {
        form.setData('niveau', value);
        if (!niveauxAvecDomaine.includes(value)) {
            form.setData('domaine', '');
        }
        if (value !== niveauStudium) {
            form.setData('examen_type', '');
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

        if (editingStudent) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(`/backoffice/students/${editingStudent.id}`, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => closeModal(),
                // Reset the transform so a later create on this shared form
                // doesn't POST with a stale _method=put (Phase 12 UX fix).
                onFinish: () => form.transform((data) => data),
            });

            return;
        }

        form.post('/backoffice/students', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }

    function confirmDelete(student: StudentRow) {
        setDeleteTarget(student);
        setDeleteError(undefined);
    }

    function handleDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleteProcessing(true);
        router.delete(`/backoffice/students/${deleteTarget.id}`, {
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
            title="Étudiants"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Étudiants' }]}
            actions={
                <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                    <i className="ti ti-square-rounded-plus me-2" />
                    Ajouter un étudiant
                </button>
            }
        >
            <Card title="Étudiants">
                <TableToolbar search={<SearchInput value={filters.search} onSearch={(value) => reload({ search: value })} placeholder="Rechercher" />}>
                    <div style={{ width: 200 }}>
                        <SelectField
                            id="stu-niveau-filter"
                            label="Niveau"
                            options={niveauFilterOptions}
                            placeholder="Tous les niveaux"
                            value={filters.niveauFilter}
                            onChange={(event) => reload({ niveauFilter: event.target.value })}
                        />
                    </div>
                </TableToolbar>

                {students.data.length === 0 ? (
                    <EmptyState title="Aucun étudiant" message="Ajoutez votre premier étudiant pour commencer." icon="ti ti-school" />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Nom</th>
                                    <th>Intéressé par</th>
                                    <th>
                                        <a
                                            href="#"
                                            onClick={(event) => {
                                                event.preventDefault();
                                                toggleAgeSort();
                                            }}
                                            className="text-dark d-inline-flex align-items-center"
                                        >
                                            Âge
                                            <i
                                                className={`ti ${filters.ageSort === 'asc' ? 'ti-sort-ascending' : filters.ageSort === 'desc' ? 'ti-sort-descending' : 'ti-arrows-sort'} ms-1 text-muted`}
                                            />
                                        </a>
                                    </th>
                                    <th>Centre</th>
                                    <th>Téléphone</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {students.data.map((student) => (
                                <tr key={student.id}>
                                    <td>
                                        <code>{student.reference}</code>
                                    </td>
                                    <td>
                                        <a href={`/backoffice/students/${student.id}`} className="d-flex align-items-center text-dark">
                                            <span className="avatar avatar-sm rounded-circle bg-primary-transparent me-2 d-inline-flex align-items-center justify-content-center overflow-hidden">
                                                {student.photoThumbUrl ? (
                                                    <img
                                                        src={student.photoThumbUrl}
                                                        alt=""
                                                        className="w-100 h-100"
                                                        style={{ objectFit: 'cover' }}
                                                        loading="lazy"
                                                    />
                                                ) : (
                                                    <span className="fw-bold text-primary">{student.prenom.charAt(0).toUpperCase()}</span>
                                                )}
                                            </span>
                                            <span className="fw-medium">{student.nomComplet}</span>
                                        </a>
                                    </td>
                                    <td>
                                        {student.niveau ? (
                                            <>
                                                <span className="badge badge-soft-info">{student.niveau}</span>
                                                {student.orientation && (
                                                    <span className="badge badge-soft-secondary ms-1">{student.orientation}</span>
                                                )}
                                            </>
                                        ) : (
                                            '—'
                                        )}
                                    </td>
                                    <td>{student.age ?? '—'}</td>
                                    <td>{student.etablissement ?? '—'}</td>
                                    <td>{student.telephone ?? '—'}</td>
                                    <td className="text-end">
                                        <RowActions>
                                            <RowActionItem icon="ti-edit" onClick={() => openEdit(student)}>
                                                Modifier
                                            </RowActionItem>
                                            <RowActionItem icon="ti-trash" danger onClick={() => confirmDelete(student)}>
                                                Supprimer
                                            </RowActionItem>
                                        </RowActions>
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                        <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 px-3">
                            <div className="d-flex align-items-center gap-2">
                                <label className="text-muted mb-0" htmlFor="stu-per-page">
                                    Par page
                                </label>
                                <select
                                    id="stu-per-page"
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
                        <Pagination paginator={students} showJumpToPage />
                    </>
                )}
            </Card>

            <Modal
                show={showModal}
                title={editingStudent ? "Modifier l'étudiant" : 'Ajouter un étudiant'}
                onClose={closeModal}
                processing={form.processing}
                size="lg"
            >
                <form onSubmit={submit}>
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
                            <label className="form-label mb-1" htmlFor="stu-photo">
                                Photo
                            </label>
                            <input
                                ref={fileInputRef}
                                id="stu-photo"
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
                                id="stu-nom"
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
                                id="stu-prenom"
                                label="Prénom"
                                required
                                value={form.data.prenom}
                                onChange={(event) => form.setData('prenom', event.target.value)}
                                error={form.errors.prenom}
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
                                                name="stu-sexe"
                                                id={`stu-sexe-${sexe}`}
                                                value={sexe}
                                                checked={form.data.sexe === sexe}
                                                onChange={() => form.setData('sexe', sexe)}
                                                autoComplete="off"
                                            />
                                            <label
                                                className="btn btn-outline-primary d-inline-flex align-items-center justify-content-center px-4"
                                                htmlFor={`stu-sexe-${sexe}`}
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
                        <div className="col-md-4">
                            <FormField
                                id="stu-naissance"
                                label="Date de naissance"
                                type="date"
                                value={form.data.date_naissance}
                                onChange={(event) => form.setData('date_naissance', event.target.value)}
                                error={form.errors.date_naissance}
                            />
                        </div>
                        <div className="col-md-4">
                            <FormField
                                id="stu-cin"
                                label="Carte d'identité"
                                value={form.data.cin}
                                onChange={(event) => form.setData('cin', event.target.value)}
                                error={form.errors.cin}
                                placeholder="ex : AB123456"
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="stu-niveau"
                                label="Intéressé par"
                                options={niveauOptions}
                                placeholder="Choisir…"
                                value={form.data.niveau}
                                onChange={(event) => handleNiveauChange(event.target.value)}
                                error={form.errors.niveau}
                            />
                        </div>
                        {showsDomaine && (
                            <div className="col-md-6">
                                <SelectField
                                    id="stu-domaine"
                                    label="Domaine"
                                    required
                                    options={domaineOptions}
                                    placeholder="Choisir…"
                                    value={form.data.domaine}
                                    onChange={(event) => form.setData('domaine', event.target.value)}
                                    error={form.errors.domaine}
                                />
                            </div>
                        )}
                        {showsExamen && (
                            <div className="col-md-6">
                                <SelectField
                                    id="stu-examen"
                                    label="Examen d'entrée"
                                    required
                                    options={examenOptions}
                                    placeholder="Choisir…"
                                    value={form.data.examen_type}
                                    onChange={(event) => form.setData('examen_type', event.target.value)}
                                    error={form.errors.examen_type}
                                />
                            </div>
                        )}
                    </div>

                    <ul className="nav nav-tabs nav-tabs-solid mt-2 mb-3" role="tablist">
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

                    {activeTab === 'contact' && (
                        <div className="row">
                            <div className="col-md-6">
                                <PhoneField
                                    id="stu-tel"
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
                                    id="stu-wa"
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
                                    id="stu-email"
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
                                    id="stu-adresse"
                                    label="Adresse"
                                    value={form.data.adresse}
                                    onChange={(event) => form.setData('adresse', event.target.value)}
                                    error={form.errors.adresse}
                                    placeholder="ex : 7 rue des fleurs"
                                />
                            </div>
                            {!centerLocked && (
                                <div className="col-md-6">
                                    <SelectField
                                        id="stu-etab"
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
                        </div>
                    )}

                    {activeTab === 'parent' && (
                        <div className="row">
                            <div className="col-md-4">
                                <SelectField
                                    id="stu-prelation"
                                    label="Catégorie"
                                    options={parentRelationOptions}
                                    placeholder="Choisir la relation"
                                    value={form.data.parent_relation}
                                    onChange={(event) => form.setData('parent_relation', event.target.value)}
                                    error={form.errors.parent_relation}
                                />
                            </div>
                            <div className="col-md-4">
                                <FormField
                                    id="stu-pnom"
                                    label="Nom du parent"
                                    value={form.data.parent_nom}
                                    onChange={(event) => form.setData('parent_nom', event.target.value)}
                                    error={form.errors.parent_nom}
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
                                                    name="stu-psexe"
                                                    id={`stu-psexe-${sexe}`}
                                                    value={sexe}
                                                    checked={form.data.parent_sexe === sexe}
                                                    onChange={() => form.setData('parent_sexe', sexe)}
                                                    autoComplete="off"
                                                />
                                                <label
                                                    className="btn btn-outline-primary d-inline-flex align-items-center justify-content-center px-4"
                                                    htmlFor={`stu-psexe-${sexe}`}
                                                >
                                                    <i className={`ti ${sexe === 'Homme' ? 'ti-man' : 'ti-woman'} me-1`} />
                                                    {sexe === 'Homme' ? 'Masculin' : 'Féminin'}
                                                </label>
                                            </div>
                                        ))}
                                    </div>
                                    {form.errors.parent_sexe && <div className="text-danger fs-12 mt-1">{form.errors.parent_sexe}</div>}
                                </div>
                            </div>
                            <div className="col-md-4">
                                <FormField
                                    id="stu-pcin"
                                    label="CIN"
                                    value={form.data.parent_cin}
                                    onChange={(event) => form.setData('parent_cin', event.target.value)}
                                    error={form.errors.parent_cin}
                                    placeholder="ex : AB123456"
                                />
                            </div>
                            <div className="col-md-4">
                                <PhoneField
                                    id="stu-ptel"
                                    label="Téléphone du parent"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.parent_telephone}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('parent_telephone', value)}
                                    error={form.errors.parent_telephone}
                                />
                            </div>
                            <div className="col-md-4">
                                <PhoneField
                                    id="stu-pwa"
                                    label="WhatsApp du parent"
                                    countryIso={form.data.phone_pays}
                                    national={form.data.parent_whatsapp}
                                    onCountryChange={(iso) => form.setData('phone_pays', iso)}
                                    onNationalChange={(value) => form.setData('parent_whatsapp', value)}
                                    error={form.errors.parent_whatsapp}
                                />
                            </div>
                        </div>
                    )}

                    {activeTab === 'autre' && (
                        <TextareaField
                            id="stu-note"
                            label="Note"
                            rows={3}
                            value={form.data.note}
                            onChange={(event) => form.setData('note', event.target.value)}
                            error={form.errors.note}
                            placeholder="Notes complémentaires sur cet étudiant"
                        />
                    )}

                    <div className="d-flex justify-content-end gap-2 mt-4">
                        <FormActions onCancel={closeModal} processing={form.processing} />
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer l'étudiant"
                recordLabel={deleteTarget?.nomComplet ?? ''}
                message="Voulez-vous vraiment supprimer cet étudiant ?"
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
