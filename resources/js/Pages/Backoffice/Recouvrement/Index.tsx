import { router } from '@inertiajs/react';
import { useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import Pagination from '@/Components/Tables/Pagination';
import DateField from '@/Components/Forms/DateField';
import SelectField from '@/Components/Forms/SelectField';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { t } from '@/Lib/i18n';
import type { RecouvrementPageProps, SelectOption } from '@/Types';

type Tab = 'duree' | 'criteres';

const DUREE_TABS: Array<{ key: string; label: string }> = [
    { key: '1j', label: 'Last 1 day' },
    { key: '7j', label: 'Last 7 days' },
    { key: '15j', label: 'Last 15 days' },
    { key: '30j', label: 'Last 30 days' },
    { key: 'plus30j', label: 'More than 30 days' },
];

/**
 * Gestion des recouvrements — read-only overdue-fees report. Two tabs
 * ("Retards selon la durée" / "Retards selon les critères") share the same
 * filters/table/query (GetRetardsList/RecouvrementController); the durée tab
 * just adds its bucket to the request. Client-side tab switch only — no
 * route change, matching the Groupes-style statut tabs pattern.
 */
export default function RecouvrementIndex({
    retards,
    bucketCounts,
    filters,
    perPageOptions,
    groupOptions,
    fraisOptions,
    statuts,
}: RecouvrementPageProps) {
    const isLoading = useInertiaLoading();
    const [tab, setTab] = useState<Tab>(filters.dureeBucket !== '' ? 'duree' : 'criteres');

    const groupSelectOptions: SelectOption[] = groupOptions;
    const fraisSelectOptions: SelectOption[] = fraisOptions;
    const statutSelectOptions: SelectOption[] = statuts.map((s) => ({ value: s, label: s }));

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/recouvrement',
            { ...filters, ...nextFilters, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function switchTab(next: Tab) {
        setTab(next);
        if (next === 'criteres') {
            reload({ dureeBucket: '' });
        } else if (filters.dureeBucket === '') {
            reload({ dureeBucket: '1j' });
        }
    }

    function setDureeBucket(bucket: string) {
        reload({ dureeBucket: bucket });
    }

    const totalReste = retards.data.reduce((sum, row) => sum + Number(row.resteAPayer), 0);

    return (
        <BackofficeLayout
            title={t('Collections management')}
            breadcrumbs={[{ label: t('Dashboard'), href: '/backoffice/dashboard' }, { label: t('Collections management') }]}
        >
            <Card title={t('Collections management')} bodyClassName="p-0 py-3">
                <ul className="nav nav-tabs nav-tabs-solid nav-tabs-rounded-fill mb-3 px-3" role="tablist">
                    <li className="me-2 mb-2" role="presentation">
                        <button
                            type="button"
                            className={`nav-link rounded${tab === 'duree' ? ' active' : ''}`}
                            onClick={() => switchTab('duree')}
                        >
                            <i className="fa fa-credit-card me-1" />
                            {t('Overdue by duration')}
                        </button>
                    </li>
                    <li className="me-2 mb-2" role="presentation">
                        <button
                            type="button"
                            className={`nav-link rounded${tab === 'criteres' ? ' active' : ''}`}
                            onClick={() => switchTab('criteres')}
                        >
                            <i className="fa fa-credit-card me-1" />
                            {t('Overdue by criteria')}
                        </button>
                    </li>
                </ul>

                {tab === 'duree' && (
                    <div className="d-flex flex-wrap gap-3 px-3 mb-3">
                        {DUREE_TABS.map((bucket) => (
                            <button
                                key={bucket.key}
                                type="button"
                                className={`btn btn-sm ${filters.dureeBucket === bucket.key ? 'btn-primary' : 'btn-link text-decoration-none'}`}
                                onClick={() => setDureeBucket(bucket.key)}
                            >
                                <i className="fa fa-magnifying-glass me-1" />
                                {t(bucket.label)}
                                <span className={`badge ms-1 ${filters.dureeBucket === bucket.key ? 'bg-white text-dark' : 'badge-soft-secondary'}`}>
                                    {bucketCounts[bucket.key] ?? 0}
                                </span>
                            </button>
                        ))}
                    </div>
                )}

                <div className="px-3 pt-2">
                    <TableToolbar>
                        <div style={{ width: 220 }}>
                            <label className="form-label" htmlFor="rec-f-groupe">
                                {t('Group')}
                            </label>
                            <SelectField
                                id="rec-f-groupe"
                                options={groupSelectOptions}
                                placeholder={t('Choose a group')}
                                value={filters.groupFilter}
                                onChange={(event) => reload({ groupFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 220 }}>
                            <label className="form-label" htmlFor="rec-f-frais">
                                {t('Fee')}
                            </label>
                            <SelectField
                                id="rec-f-frais"
                                options={fraisSelectOptions}
                                placeholder={t('Choose an item')}
                                value={filters.fraisFilter}
                                onChange={(event) => reload({ fraisFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 200 }}>
                            <label className="form-label" htmlFor="rec-f-statut">
                                {t('Status')}
                            </label>
                            <SelectField
                                id="rec-f-statut"
                                options={statutSelectOptions}
                                placeholder={t('Choose a status')}
                                value={filters.statutFilter}
                                onChange={(event) => reload({ statutFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="rec-f-du">
                                {t('Start date')}
                            </label>
                            <DateField id="rec-f-du" value={filters.dateFrom} onChange={(event) => reload({ dateFrom: event.target.value })} />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="rec-f-au">
                                {t('End date')}
                            </label>
                            <DateField id="rec-f-au" value={filters.dateTo} onChange={(event) => reload({ dateTo: event.target.value })} />
                        </div>
                    </TableToolbar>
                </div>

                {retards.data.length > 0 && (
                    <div className="px-3 mb-2">
                        <span className="fw-semibold">{t('Total amount')} : {totalReste.toFixed(2)} DH</span>
                    </div>
                )}

                <TableLengthRow perPage={filters.perPage} perPageOptions={perPageOptions} onPerPageChange={(perPage) => reload({ perPage })} />

                {retards.data.length === 0 ? (
                    <EmptyState title={t('No collections found')} icon="fa fa-circle-exclamation" />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>{t('Reference')}</th>
                                    <th>{t('Student')}</th>
                                    <th>{t('Status')}</th>
                                    <th>{t('Phone')}</th>
                                    <th>{t('Fee')}</th>
                                    <th>{t('Due date')}</th>
                                    <th>{t('Overdue')}</th>
                                    <th>{t('Remaining')}</th>
                                </tr>
                            }
                        >
                            {retards.data.map((row) => (
                                <tr key={row.id}>
                                    <td>
                                        {row.inscriptionShowUrl ? (
                                            <a href={row.inscriptionShowUrl}>
                                                <code>{row.reference}</code>
                                            </a>
                                        ) : (
                                            <code>{row.reference}</code>
                                        )}
                                    </td>
                                    <td>
                                        {row.studentShowUrl ? (
                                            <a href={row.studentShowUrl} className="fw-medium">
                                                {row.studentNom}
                                            </a>
                                        ) : (
                                            row.studentNom ?? '—'
                                        )}
                                    </td>
                                    <td>
                                        <StatusBadge label={row.statut} variant={row.statut === 'Non payé' ? 'danger' : 'warning'} />
                                    </td>
                                    <td>
                                        <div className="d-flex flex-column gap-1">
                                            {row.telephone && (
                                                <a href={`tel:${row.telephone}`} className="d-inline-flex align-items-center">
                                                    <i className="fa fa-phone me-1" />
                                                    {row.telephone}
                                                </a>
                                            )}
                                            {row.whatsapp && (
                                                <a
                                                    href={`https://wa.me/${row.whatsapp.replace(/\D/g, '')}`}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="d-inline-flex align-items-center text-success"
                                                >
                                                    <i className="fa fa-whatsapp me-1" />
                                                    {row.whatsapp}
                                                </a>
                                            )}
                                        </div>
                                    </td>
                                    <td>{row.frais ?? '—'}</td>
                                    <td>{row.dateEcheance ?? '—'}</td>
                                    <td>
                                        <span className="badge badge-soft-danger">
                                            {row.retardJours} {t('days')}
                                        </span>
                                    </td>
                                    <td className="fw-medium">{row.resteAPayer} DH</td>
                                </tr>
                            ))}
                        </DataTable>
                        <Pagination paginator={retards} showJumpToPage />
                    </>
                )}
            </Card>
        </BackofficeLayout>
    );
}
