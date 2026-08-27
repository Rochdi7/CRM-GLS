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
    fraisOptions: SelectOption[];
}

const URL = '/backoffice/encaissements/reaffecter';

export default function ReallocatePayments({ paiements, montantTotal, filters, groupes, annees, fraisOptions }: Props) {
    const loading = useInertiaLoading();
    const [selected, setSelected] = useState<number[]>([]);
    const [showModal, setShowModal] = useState(false);

    const form = useForm<{ encaissement_ids: number[]; group_id: number | '' }>({
        encaissement_ids: [],
        group_id: '',
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
        form.setData({ encaissement_ids: selected, group_id: '' });
        setShowModal(true);
    };

    // Each student's money can only ever land on HIS OWN registration in the
    // target group, so the modal shows who is involved rather than asking the
    // operator to pick one registration for everybody.
    const selectedByStudent = useMemo(() => {
        const map = new Map<string, { count: number; montant: number }>();

        rows.filter((r) => selected.includes(r.id)).forEach((r) => {
            const current = map.get(r.etudiant) ?? { count: 0, montant: 0 };
            map.set(r.etudiant, { count: current.count + 1, montant: current.montant + Number(r.montant) });
        });

        return [...map.entries()].sort((a, b) => a[0].localeCompare(b[0]));
    }, [rows, selected]);

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
                        options={fraisOptions}
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
                        id="group_id"
                        value={form.data.group_id}
                        options={groupes}
                        placeholder={t('Choose a group')}
                        onChange={(e) => form.setData('group_id', e.target.value ? Number(e.target.value) : '')}
                        error={form.errors.group_id}
                        required
                    />

                    <div className="mt-3">
                        <div className="fw-semibold fs-13 mb-2">
                            {t('Students concerned')} ({selectedByStudent.length})
                        </div>
                        <div className="border rounded" style={{ maxHeight: 220, overflowY: 'auto' }}>
                            <table className="table table-sm mb-0">
                                <tbody>
                                    {selectedByStudent.map(([etudiant, info]) => (
                                        <tr key={etudiant}>
                                            <td>{etudiant}</td>
                                            <td className="text-end text-muted">{info.count}</td>
                                            <td className="text-end">{info.montant.toFixed(2)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <p className="text-muted fs-13 mt-2 mb-0">
                            {t('Each student’s money goes to HIS OWN registration in that group — money never moves between students. A student not enrolled there keeps his payment as an unallocated advance.')}
                        </p>
                    </div>
                </form>
            </Modal>
        </BackofficeLayout>
    );
}
