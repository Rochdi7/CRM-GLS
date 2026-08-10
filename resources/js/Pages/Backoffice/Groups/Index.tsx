import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import FilterTextInput from '@/Components/Tables/FilterTextInput';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import DateField from '@/Components/Forms/DateField';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import FormActions from '@/Components/Forms/FormActions';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import type { GroupFraisLigne, GroupRow, GroupsPageProps, SelectOption } from '@/Types';

const STATUT_TABS: Array<{ key: string; icon: string; label: string }> = [
    { key: 'En formation', icon: 'ti-school', label: 'En formation' },
    { key: 'En inscription', icon: 'ti-folder', label: 'En inscription' },
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
        statut: 'En inscription',
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
    const isLoading = useInertiaLoading();
    const [showModal, setShowModal] = useState(false);
    const [editingGroup, setEditingGroup] = useState<GroupRow | null>(null);

    const fraisCatalogOptions: SelectOption[] = fraisCatalog.map((f) => ({ value: f.id, label: f.nom }));
    const niveauOptions: SelectOption[] = niveaux.map((n) => ({ value: n, label: n }));
    const enseignantOptions: SelectOption[] = enseignants.map((e) => ({ value: e.id, label: e.nom }));
    const statutFilterOptions: SelectOption[] = STATUT_TABS.map((tab) => ({ value: tab.key, label: tab.label }));
    // Create-mode status options omit "Fin de formation" — a group can only
    // reach it through the archive action (Group::archiverCommeTermine).
    const createStatutOptions: SelectOption[] = [
        { value: 'En inscription', label: 'En inscription' },
        { value: 'En formation', label: 'En formation' },
    ];
    const editStatutOptions: SelectOption[] = [
        { value: 'En inscription', label: 'En inscription' },
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
            <Card title="Groupes" bodyClassName="p-0 py-3">
                {/* Filter row (reference CRM's Groupes filters, without a
                    Formation column) — Groupe/Statut/Enseignant/dates. */}
                <div className="px-3 pt-2">
                    <TableToolbar>
                        <div style={{ width: 220 }}>
                            <label className="form-label" htmlFor="grp-f-nom">
                                Groupe
                            </label>
                            <FilterTextInput
                                id="grp-f-nom"
                                value={filters.search}
                                onChange={(value) => reload({ search: value })}
                                placeholder="ex : Herr Driss 13h"
                            />
                        </div>
                        <div style={{ width: 190 }}>
                            <label className="form-label" htmlFor="grp-f-statut">
                                Statut
                            </label>
                            <SelectField
                                id="grp-f-statut"
                                options={statutFilterOptions}
                                placeholder="Choisir un statut"
                                value={filters.statutFilter}
                                onChange={(event) => setStatutTab(event.target.value)}
                            />
                        </div>
                        <div style={{ width: 220 }}>
                            <label className="form-label" htmlFor="grp-f-enseignant">
                                Enseignant
                            </label>
                            <SelectField
                                id="grp-f-enseignant"
                                options={enseignantOptions}
                                placeholder="Choisir un enseignant"
                                value={filters.enseignantFilter}
                                onChange={(event) => reload({ enseignantFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="grp-f-du">
                                Date de début
                            </label>
                            <DateField
                                id="grp-f-du"
                                value={filters.dateFrom}
                                onChange={(event) => reload({ dateFrom: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="grp-f-au">
                                Date de fin
                            </label>
                            <DateField
                                id="grp-f-au"
                                value={filters.dateTo}
                                onChange={(event) => reload({ dateTo: event.target.value })}
                            />
                        </div>
                    </TableToolbar>
                </div>

                <TableLengthRow
                    perPage={filters.perPage}
                    perPageOptions={perPageOptions}
                    onPerPageChange={(perPage) => reload({ perPage })}
                />

                <ul className="nav nav-tabs nav-tabs-solid nav-tabs-rounded-fill mb-3 px-3" role="tablist">
                    {STATUT_TABS.map((tab) => (
                        <li className="me-2 mb-2" role="presentation" key={tab.key}>
                            <button
                                type="button"
                                className={`nav-link rounded${filters.statutFilter === tab.key ? ' active' : ''}`}
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
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Nom</th>
                                    <th>Classification</th>
                                    <th>Enseignant</th>
                                    <th>Statistique</th>
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
                                        <div className="d-flex flex-wrap gap-1">
                                            <span className="badge badge-soft-secondary" title="Total inscriptions">
                                                {group.inscriptionsCount}
                                            </span>
                                            <span className="badge badge-soft-success" title="Inscriptions actives">
                                                {group.inscriptionsActivesCount}
                                            </span>
                                            <span className="badge badge-soft-danger" title="Inscriptions annulées">
                                                {group.inscriptionsAnnuleesCount}
                                            </span>
                                            <span className="badge badge-soft-info" title="Étudiants">
                                                {group.etudiantsDistinctsCount}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <StatusBadge
                                            label={group.statut}
                                            variant={
                                                group.statut === 'En formation'
                                                    ? 'success'
                                                    : group.statut === 'Fin de formation'
                                                      ? 'secondary'
                                                      : 'warning'
                                            }
                                            dot
                                        />
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
                        <Pagination paginator={groups} showJumpToPage />
                    </>
                )}
            </Card>

            <Modal show={showModal} title={editingGroup ? 'Modifier le groupe' : 'Ajouter un groupe'} onClose={closeModal} processing={form.processing} size="xl">
                <form onSubmit={submit}>
                    <div className="row">
                        <div className="col-md-3">
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
                        <div className="col-md-3">
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
                        <div className="col-md-3">
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
                        <div className="col-md-3">
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
                        <div className="col-md-3">
                            <DateField
                                id="grp-debut"
                                label="Date de début"
                                value={form.data.date_debut_formation}
                                onChange={(event) => form.setData('date_debut_formation', event.target.value)}
                                error={form.errors.date_debut_formation}
                            />
                        </div>
                        <div className="col-md-3">
                            <DateField
                                id="grp-fin"
                                label="Date de fin"
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
                                                        <SelectField
                                                            id={`grp-fee-c-${fee.value}`}
                                                            options={niveauOptions}
                                                            placeholder="—"
                                                            value={ligne.classification}
                                                            onChange={(event) => setLigne(fee.value as number, 'classification', event.target.value)}
                                                            error={classificationError}
                                                        />
                                                    </td>
                                                    <td>
                                                        <DateField
                                                            id={`grp-fee-d-${fee.value}`}
                                                            value={ligne.date_echeance}
                                                            onChange={(event) => setLigne(fee.value as number, 'date_echeance', event.target.value)}
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
