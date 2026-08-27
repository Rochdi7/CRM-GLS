import { router, useForm } from '@inertiajs/react';
import { useMemo, useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import SelectField from '@/Components/Forms/SelectField';
import Modal from '@/Components/Modals/Modal';
import FormActions from '@/Components/Forms/FormActions';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { t } from '@/Lib/i18n';
import type { PaginatedData, SelectOption } from '@/Types';

interface ReallocatableRow {
    id: number;
    reference: string;
    legacyRef: string | null;
    etudiant: string;
    montant: string;
    methode: string;
    datePaiement: string | null;
    frais: string | null;
    groupe: string | null;
    annee: string | null;
    inscriptionId: number | null;
}

interface GroupOption extends SelectOption {
    anneeId: number | null;
}

interface Props {
    paiements: PaginatedData<ReallocatableRow>;
    montantTotal: string;
    filters: { search: string; group_id: number | null; frais: string; annee_id: number | null };
    groupes: GroupOption[];
    annees: SelectOption[];
}

const URL = '/backoffice/encaissements/reaffecter';

export default function ReallocatePayments({ paiements, montantTotal, filters, groupes, annees }: Props) {
    const loading = useInertiaLoading();
    const [selected, setSelected] = useState<number[]>([]);
    const [showModal, setShowModal] = useState(false);
    const [targetGroup, setTargetGroup] = useState<string>('');
    const [targetInscriptions, setTargetInscriptions] = useState<SelectOption[]>([]);

    const form = useForm<{ encaissement_ids: number[]; inscription_id: number | '' }>({
        encaissement_ids: [],
        inscription_id: '',
    });

    const rows = paiements.data;
    const allOnPageSelected = rows.length > 0 && rows.every((r) => selected.includes(r.id));

    // Only the rows actually ticked count towards the banner — a page total
    // would claim money the operator is not about to move.
    const selectedTotal = useMemo(
        () => rows.filter((r) => selected.includes(r.id)).reduce((sum, r) => sum + Number(r.montant), 0),
        [rows, selected],
    );

    const reload = (next: Partial<Props['filters']>) => {
        router.get(URL, { ...filters, ...next }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const toggle = (id: number) => {
        setSelected((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
    };

    const toggleAllOnPage = () => {
        setSelected((prev) =>
            allOnPageSelected
                ? prev.filter((id) => !rows.some((r) => r.id === id))
                : [...prev, ...rows.filter((r) => !prev.includes(r.id)).map((r) => r.id)],
        );
    };

    const openModal = () => {
        setTargetGroup('');
        setTargetInscriptions([]);
        form.setData({ encaissement_ids: selected, inscription_id: '' });
        setShowModal(true);
    };

    const onGroupChange = async (groupId: string) => {
        setTargetGroup(groupId);
        form.setData('inscription_id', '');
        setTargetInscriptions([]);

        if (groupId === '') {
            return;
        }

        const res = await fetch(`${URL}/groupes/${groupId}/inscriptions`, {
            headers: { Accept: 'application/json' },
        });
        setTargetInscriptions(await res.json());
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(URL, {
            preserveScroll: true,
            onSuccess: () => {
                setShowModal(false);
                setSelected([]);
            },
        });
    };

    return (
        <BackofficeLayout
            title={t('Move payments')}
            breadcrumbs={[
                { label: t('Dashboard'), href: '/backoffice/dashboard' },
                { label: t('Payments'), href: '/backoffice/encaissements' },
                { label: t('Move payments') },
            ]}
        >
            <Card title={t('Move payments to another group / year')}>
                <p className="text-muted fs-13 mb-3">
                    {t('Every year is listed here on purpose — the rows to fix are the ones the active year hides. The payment date is never changed, and no money leaves the till: only which fee it is booked against.')}
                </p>

                <TableToolbar
                    search={
                        <SearchInput
                            value={filters.search}
                            onSearch={(value: string) => reload({ search: value })}
                            placeholder={t('Reference, student…')}
                        />
                    }
                >
                    <SelectField
                        label={t('Group')}
                        id="group_id"
                        value={filters.group_id ?? ''}
                        options={groupes}
                        placeholder={t('All groups')}
                        onChange={(e) => reload({ group_id: e.target.value ? Number(e.target.value) : null })}
                    />
                    <SelectField
                        label={t('Academic year')}
                        id="annee_id"
                        value={filters.annee_id ?? ''}
                        options={annees}
                        placeholder={t('All years')}
                        onChange={(e) => reload({ annee_id: e.target.value ? Number(e.target.value) : null })}
                    />
                    <SelectField
                        label={t('Fee')}
                        id="frais"
                        value={filters.frais}
                        options={[...new Set(rows.map((r) => r.frais).filter(Boolean))].map((nom) => ({
                            value: nom as string,
                            label: nom as string,
                        }))}
                        placeholder={t('All fees')}
                        onChange={(e) => reload({ frais: e.target.value })}
                    />
                </TableToolbar>

                {selected.length > 0 && (
                    <div className="alert alert-info d-flex align-items-center justify-content-between py-2 mb-3">
                        <span>
                            <strong>{selected.length}</strong> {t('payment(s) selected')} —{' '}
                            <strong>{selectedTotal.toFixed(2)} MAD</strong>
                        </span>
                        <span className="d-flex gap-2">
                            <button type="button" className="btn btn-sm btn-light" onClick={() => setSelected([])}>
                                {t('Clear')}
                            </button>
                            <button type="button" className="btn btn-sm btn-primary" onClick={openModal}>
                                {t('Move the selection')}
                            </button>
                        </span>
                    </div>
                )}

                <DataTable loading={loading}>
                    <thead>
                        <tr>
                            <th style={{ width: 40 }}>
                                <input
                                    type="checkbox"
                                    className="form-check-input"
                                    checked={allOnPageSelected}
                                    onChange={toggleAllOnPage}
                                    aria-label={t('Select every row on this page')}
                                />
                            </th>
                            <th>{t('Reference')}</th>
                            <th>{t('Student')}</th>
                            <th>{t('Fee')}</th>
                            <th>{t('Group')}</th>
                            <th>{t('Year')}</th>
                            <th className="text-end">{t('Amount')}</th>
                            <th>{t('Date')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.id} className={selected.includes(row.id) ? 'table-active' : undefined}>
                                <td>
                                    <input
                                        type="checkbox"
                                        className="form-check-input"
                                        checked={selected.includes(row.id)}
                                        onChange={() => toggle(row.id)}
                                        aria-label={`${t('Select')} ${row.reference}`}
                                    />
                                </td>
                                <td className="text-normal-case">{row.reference}</td>
                                <td>{row.etudiant}</td>
                                <td>{row.frais ?? '—'}</td>
                                <td>{row.groupe ?? '—'}</td>
                                <td>{row.annee ?? '—'}</td>
                                <td className="text-end">{Number(row.montant).toFixed(2)}</td>
                                <td className="text-normal-case">{row.datePaiement ?? '—'}</td>
                            </tr>
                        ))}
                        {rows.length === 0 && (
                            <tr>
                                <td colSpan={8}>
                                    <EmptyState title={t('No payment matches these filters.')} />
                                </td>
                            </tr>
                        )}
                    </tbody>
                </DataTable>

                <div className="d-flex align-items-center justify-content-between mt-3">
                    <span className="text-muted fs-13">
                        {t('Total displayed')} : <strong>{montantTotal} MAD</strong>
                    </span>
                    <Pagination paginator={paiements} />
                </div>
            </Card>

            <Modal
                show={showModal}
                title={t('Move the selected payments')}
                onClose={() => setShowModal(false)}
                processing={form.processing}
                footer={
                    <FormActions
                        form="reallocate-form"
                        onCancel={() => setShowModal(false)}
                        processing={form.processing}
                        submitLabel={t('Move')}
                    />
                }
            >
                <form id="reallocate-form" onSubmit={submit}>
                    <p className="text-muted fs-13">
                        {selected.length} {t('payment(s)')} — {selectedTotal.toFixed(2)} MAD.{' '}
                        {t('Each payment lands on the target registration’s fee of the SAME name, capped at what that fee still owes. One with no matching fee stays an unallocated advance.')}
                    </p>

                    <SelectField
                        label={t('Target group')}
                        id="target_group"
                        value={targetGroup}
                        options={groupes}
                        placeholder={t('Choose a group')}
                        onChange={(e) => onGroupChange(e.target.value)}
                        required
                    />

                    <SelectField
                        label={t('Target registration')}
                        id="inscription_id"
                        value={form.data.inscription_id}
                        options={targetInscriptions}
                        placeholder={targetGroup === '' ? t('Choose a group first') : t('Choose a registration')}
                        onChange={(e) => form.setData('inscription_id', e.target.value ? Number(e.target.value) : '')}
                        error={form.errors.inscription_id}
                        required
                    />
                </form>
            </Modal>
        </BackofficeLayout>
    );
}
