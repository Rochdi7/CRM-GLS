import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import FormActions from '@/Components/Forms/FormActions';
import type { GroupFraisLigne, GroupRow, GroupsPageProps, SelectOption } from '@/Types';

const STATUT_TABS: Array<{ key: string; icon: string; label: string }> = [
    { key: 'En formation', icon: 'ti-school', label: 'En formation' },
    { key: 'Pré-inscription', icon: 'ti-folder', label: 'Pré-inscription' },
    { key: 'Fin de formation', icon: 'ti-history', label: 'Historique' },
];

interface GroupFormState {
    nom: string;
    niveau: string;
    enseignant_id: number | '';
    statut: string;
    date_debut_formation: string;
    date_fin_formation: string;
    fraisLignes: Record<number, GroupFraisLigne>;
}

function emptyFraisLignes(fraisCatalog: SelectOption[]): Record<number, GroupFraisLigne> {
    const lignes: Record<number, GroupFraisLigne> = {};
    fraisCatalog.forEach((f) => {
        lignes[f.value as number] = { montant: '0', date_echeance: '', classification: '' };
    });

    return lignes;
}

function emptyForm(fraisCatalog: SelectOption[]): GroupFormState {
    return {
        nom: '',
        niveau: '',
        enseignant_id: '',
        statut: 'Pré-inscription',
        date_debut_formation: '',
        date_fin_formation: '',
        fraisLignes: emptyFraisLignes(fraisCatalog),
    };
}

/**
 * Replaces App\Livewire\Backoffice\Groups\GroupsIndex — same fields (Name,
 * Level, Teacher, Status, dates, fee lines), same status tabs, same
 * "every active catalog fee always assigned" behavior, now driven by real
 * HTTP requests (GroupController) instead of a Livewire component.
 * Deliberately does NOT include room/capacity/schedule fields — confirmed
 * absent from the current Livewire form (docs/phase-8-students-groups-
 * inventory.md).
 */
export default function GroupsIndex({
    groups,
    statutCounts,
    filters,
    perPageOptions,
    niveaux,
    enseignants,
    fraisCatalog,
}: GroupsPageProps) {
    const [showModal, setShowModal] = useState(false);
    const [editingGroup, setEditingGroup] = useState<GroupRow | null>(null);

    const fraisCatalogOptions: SelectOption[] = fraisCatalog.map((f) => ({ value: f.id, label: f.nom }));
    const niveauOptions: SelectOption[] = niveaux.map((n) => ({ value: n, label: n }));
    const enseignantOptions: SelectOption[] = enseignants.map((e) => ({ value: e.id, label: e.nom }));
    // Create-mode status options omit "Fin de formation" — matches the
    // Livewire form's select which only lists Pré-inscription/En formation
    // when creating.
    const createStatutOptions: SelectOption[] = [
        { value: 'Pré-inscription', label: 'Pré-inscription' },
        { value: 'En formation', label: 'En formation' },
    ];
    const editStatutOptions: SelectOption[] = [
        { value: 'Pré-inscription', label: 'Pré-inscription' },
        { value: 'En formation', label: 'En formation' },
        { value: 'Fin de formation', label: 'Fin de formation' },
    ];

    const form = useForm<GroupFormState>(emptyForm(fraisCatalogOptions));

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/groups',
            { ...filters, ...nextFilters, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function setStatutTab(statut: string) {
        reload({ statutFilter: statut });
    }

    function openCreate() {
        setEditingGroup(null);
        form.reset();
        form.clearErrors();
        form.setData(emptyForm(fraisCatalogOptions));
        setShowModal(true);
    }

    function openEdit(group: GroupRow) {
        setEditingGroup(group);
        form.clearErrors();

        // The list row already carries this group's own fee-line amounts
        // (GetGroupsList keys them by frais_id) — no second request needed.
        const lignes = emptyFraisLignes(fraisCatalogOptions);
        Object.entries(group.fraisLignes).forEach(([fraisId, ligne]) => {
            lignes[Number(fraisId)] = {
                montant: ligne.montant,
                date_echeance: ligne.dateEcheance,
                classification: ligne.classification,
            };
        });

        form.setData({
            nom: group.nom,
            niveau: group.niveau,
            enseignant_id: group.enseignantId ?? '',
            statut: group.statut,
            date_debut_formation: group.dateDebutFormation ?? '',
            date_fin_formation: group.dateFinFormation ?? '',
            fraisLignes: lignes,
        });

        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditingGroup(null);
        form.reset();
        form.clearErrors();
    }

    function setLigne(fraisId: number, field: keyof GroupFraisLigne, value: string) {
        form.setData('fraisLignes', {
            ...form.data.fraisLignes,
            [fraisId]: { ...form.data.fraisLignes[fraisId], [field]: value },
        });
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => closeModal() };

        if (editingGroup) {
            form.put(`/backoffice/groups/${editingGroup.id}`, options);
        } else {
            form.post('/backoffice/groups', options);
        }
    }

    return (
        <BackofficeLayout
            title="Groupes"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Groupes' }]}
            actions={
                <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                    <i className="ti ti-square-rounded-plus me-2" />
                    Ajouter un groupe
                </button>
            }
        >
            <Card title="Groupes">
                <TableToolbar search={<SearchInput value={filters.search} onSearch={(value) => reload({ search: value })} placeholder="Rechercher" />} />

                <ul className="nav nav-tabs nav-tabs-solid mb-3" role="tablist">
                    {STATUT_TABS.map((tab) => (
                        <li className="nav-item" role="presentation" key={tab.key}>
                            <button
                                type="button"
                                className={`nav-link border-0${filters.statutFilter === tab.key ? ' active' : ''}`}
                                onClick={() => setStatutTab(tab.key)}
                            >
                                <i className={`ti ${tab.icon} me-1`} />
                                {tab.label}
                                <span className={`badge ${filters.statutFilter === tab.key ? 'bg-white text-dark' : 'badge-soft-secondary'} ms-1`}>
                                    {statutCounts[tab.key] ?? 0}
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>

                {groups.data.length === 0 ? (
                    <EmptyState title="Aucun groupe avec ce statut" icon="ti ti-users-group" />
                ) : (
                    <>
                        <DataTable
                            head={
                                <tr>
                                    <th>Nom</th>
                                    <th>Niveau</th>
                                    <th>Enseignant</th>
                                    <th>Étudiants</th>
                                    <th>Frais</th>
                                    <th>Statut</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {groups.data.map((group) => (
                                <tr key={group.id}>
                                    <td className="fw-medium">{group.nom}</td>
                                    <td>
                                        <span className="badge badge-soft-info">{group.niveau}</span>
                                    </td>
                                    <td>{group.enseignant ?? '—'}</td>
                                    <td>
                                        <span className="badge badge-soft-secondary">{group.inscriptionsCount}</span>
                                    </td>
                                    <td>
                                        <span className="badge badge-soft-secondary">{group.fraisCount}</span>
                                    </td>
                                    <td>
                                        <span
                                            className={`badge ${group.statut === 'En formation' ? 'badge-soft-success' : group.statut === 'Fin de formation' ? 'badge-soft-secondary' : 'badge-soft-warning'}`}
                                        >
                                            {group.statut}
                                        </span>
                                    </td>
                                    <td className="text-end">
                                        <RowActions view={group.showUrl}>
                                            {group.statut !== 'Fin de formation' && (
                                                <RowActionItem icon="ti-edit" onClick={() => openEdit(group)}>
                                                    Modifier
                                                </RowActionItem>
                                            )}
                                        </RowActions>
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                        <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 px-3">
                            <div className="d-flex align-items-center gap-2">
                                <label className="text-muted mb-0" htmlFor="grp-per-page">
                                    Par page
                                </label>
                                <select
                                    id="grp-per-page"
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
                        <Pagination paginator={groups} />
                    </>
                )}
            </Card>

            <Modal show={showModal} title={editingGroup ? 'Modifier le groupe' : 'Ajouter un groupe'} onClose={closeModal} processing={form.processing} size="lg">
                <form onSubmit={submit}>
                    <div className="row">
                        <div className="col-md-8">
                            <FormField
                                id="grp-nom"
                                label="Nom"
                                required
                                value={form.data.nom}
                                onChange={(event) => form.setData('nom', event.target.value)}
                                error={form.errors.nom}
                                placeholder="ex : Herr Driss 13h - Intensifs"
                            />
                        </div>
                        <div className="col-md-4">
                            <SelectField
                                id="grp-niveau"
                                label="Niveau"
                                required
                                options={niveauOptions}
                                placeholder="Choisir…"
                                value={form.data.niveau}
                                onChange={(event) => form.setData('niveau', event.target.value)}
                                error={form.errors.niveau}
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="grp-ens"
                                label="Enseignant"
                                options={enseignantOptions}
                                placeholder="Choisir…"
                                value={form.data.enseignant_id}
                                onChange={(event) => form.setData('enseignant_id', event.target.value ? Number(event.target.value) : '')}
                                error={form.errors.enseignant_id}
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="grp-statut"
                                label="Statut"
                                required
                                options={editingGroup ? editStatutOptions : createStatutOptions}
                                value={form.data.statut}
                                onChange={(event) => form.setData('statut', event.target.value)}
                                error={form.errors.statut}
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="grp-debut"
                                label="Date de début"
                                type="date"
                                value={form.data.date_debut_formation}
                                onChange={(event) => form.setData('date_debut_formation', event.target.value)}
                                error={form.errors.date_debut_formation}
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="grp-fin"
                                label="Date de fin"
                                type="date"
                                value={form.data.date_fin_formation}
                                onChange={(event) => form.setData('date_fin_formation', event.target.value)}
                                error={form.errors.date_fin_formation}
                            />
                        </div>
                    </div>

                    <div className="border-top pt-3">
                        <h6 className="mb-1">Frais du groupe</h6>
                        <p className="text-muted fs-13 mb-3">
                            Définissez le montant (et la date d'échéance) de chaque frais pour ce groupe — les montants sont à 0 par
                            défaut. Tous les frais sont reportés sur l'inscription lorsqu'un étudiant est assigné à ce groupe.
                        </p>

                        {fraisCatalogOptions.length === 0 ? (
                            <div className="alert alert-warning mb-0">
                                Aucun frais dans le catalogue. Ajoutez des frais dans Paramètres → Frais d'abord.
                            </div>
                        ) : (
                            <div className="table-responsive">
                                <table className="table table-bordered table-sm align-middle text-center mb-0">
                                    <thead className="table-light">
                                        <tr>
                                            <th className="text-center" style={{ width: '34%' }}>
                                                Frais
                                            </th>
                                            <th className="text-center" style={{ width: '18%' }}>
                                                Classification
                                            </th>
                                            <th className="text-center" style={{ width: '24%' }}>
                                                Échéance
                                            </th>
                                            <th className="text-center" style={{ width: '24%' }}>
                                                Montant
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {fraisCatalogOptions.map((fee) => {
                                            const ligne = form.data.fraisLignes[fee.value as number] ?? {
                                                montant: '0',
                                                date_echeance: '',
                                                classification: '',
                                            };
                                            const montantError = (form.errors as Record<string, string>)[`fraisLignes.${fee.value}.montant`];
                                            const classificationError = (form.errors as Record<string, string>)[
                                                `fraisLignes.${fee.value}.classification`
                                            ];

                                            return (
                                                <tr key={fee.value}>
                                                    <td>
                                                        <label className="form-label mb-0" htmlFor={`grp-fee-m-${fee.value}`}>
                                                            {fee.label}
                                                        </label>
                                                    </td>
                                                    <td>
                                                        <select
                                                            className={`form-select form-select-sm text-center${classificationError ? ' is-invalid' : ''}`}
                                                            value={ligne.classification}
                                                            onChange={(event) => setLigne(fee.value as number, 'classification', event.target.value)}
                                                            title="Classification"
                                                        >
                                                            <option value="">—</option>
                                                            {niveaux.map((n) => (
                                                                <option key={n} value={n}>
                                                                    {n}
                                                                </option>
                                                            ))}
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input
                                                            type="date"
                                                            className="form-control form-control-sm text-center"
                                                            value={ligne.date_echeance}
                                                            onChange={(event) => setLigne(fee.value as number, 'date_echeance', event.target.value)}
                                                            title="Échéance"
                                                        />
                                                    </td>
                                                    <td>
                                                        <div className="input-group input-group-sm">
                                                            <input
                                                                id={`grp-fee-m-${fee.value}`}
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                className={`form-control text-center${montantError ? ' is-invalid' : ''}`}
                                                                placeholder="0"
                                                                value={ligne.montant}
                                                                onChange={(event) => setLigne(fee.value as number, 'montant', event.target.value)}
                                                            />
                                                            <span className="input-group-text">DH</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>

                    <div className="d-flex justify-content-end gap-2 mt-4">
                        <FormActions onCancel={closeModal} processing={form.processing} />
                    </div>
                </form>
            </Modal>
        </BackofficeLayout>
    );
}
