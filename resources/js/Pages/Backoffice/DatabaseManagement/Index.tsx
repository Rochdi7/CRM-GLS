import { Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import SearchInput from '@/Components/Tables/SearchInput';
import StatusBadge from '@/Components/Details/StatusBadge';
import { t } from '@/Lib/i18n';
import type { DatabaseManagementIndexProps, DatabaseTableSummary } from '@/Types';

function formatCount(value: number): string {
    return new Intl.NumberFormat('fr-FR').format(value);
}

function tableUrl(name: string): string {
    return `/backoffice/database-management/${encodeURIComponent(name)}`;
}

/**
 * « Gestion de la base de données » — every table of the connected
 * database, with its row count and whether the tool may write to it.
 *
 * Écran de MAINTENANCE réservé au compte de maintenance (une identité, pas
 * une permission — §16), atteint par son lien direct
 * /backoffice/database-management et volontairement absent de la barre
 * latérale. Comme pour « Réconciliation des paiements importés », l'absence
 * de menu n'est PAS ce qui le protège : le gate serveur décide.
 *
 * The name filter is client-side on purpose: this is a static list of ~50
 * table names, not a CRM dataset (the server-side pagination rule of §5
 * applies to the ROWS of a table, on the next page).
 */
export default function DatabaseManagementIndex({ database, tables }: DatabaseManagementIndexProps) {
    const [search, setSearch] = useState('');

    const visible = useMemo(() => {
        const needle = search.trim().toLowerCase();

        return needle === '' ? tables : tables.filter((table) => table.name.includes(needle));
    }, [tables, search]);

    const totalRows = tables.reduce((sum, table) => sum + table.rows, 0);

    function stateBadge(table: DatabaseTableSummary) {
        if (table.readOnly) {
            return <StatusBadge label={t('Read-only')} variant="secondary" dot />;
        }

        if (!table.editable) {
            return <StatusBadge label={t('No primary key')} variant="warning" dot />;
        }

        return <StatusBadge label={t('Editable')} variant="success" dot />;
    }

    return (
        <BackofficeLayout
            title={t('Database management')}
            breadcrumbs={[
                { label: t('Dashboard'), href: '/backoffice/dashboard' },
                { label: t('Database management') },
            ]}
        >
            <div className="alert alert-warning border fs-13 mb-4">
                <strong className="d-block mb-1">
                    <i className="ti ti-alert-triangle me-1" />
                    {t('Maintenance tool')}
                </strong>
                <span>
                    {t(
                        'Every change made here is written straight into the table, bypassing the application rules (till balances, cascades, invariants). Each write is recorded in the audit journal. Make a database backup before any repair.',
                    )}
                </span>
            </div>

            <div className="row">
                <div className="col-md-4">
                    <div className="card">
                        <div className="card-body d-flex align-items-center">
                            <span className="avatar avatar-lg bg-primary-transparent rounded me-3 flex-shrink-0">
                                <i className="ti ti-database fs-24" />
                            </span>
                            <div>
                                <p className="mb-1 text-muted fs-13">{t('Database')}</p>
                                <h5 className="mb-0 text-normal-case">{database}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="card">
                        <div className="card-body d-flex align-items-center">
                            <span className="avatar avatar-lg bg-info-transparent rounded me-3 flex-shrink-0">
                                <i className="ti ti-table fs-24" />
                            </span>
                            <div>
                                <p className="mb-1 text-muted fs-13">{t('Tables')}</p>
                                <h5 className="mb-0">{formatCount(tables.length)}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="card">
                        <div className="card-body d-flex align-items-center">
                            <span className="avatar avatar-lg bg-success-transparent rounded me-3 flex-shrink-0">
                                <i className="ti ti-list-numbers fs-24" />
                            </span>
                            <div>
                                <p className="mb-1 text-muted fs-13">{t('Total rows')}</p>
                                <h5 className="mb-0">{formatCount(totalRows)}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Card title={t('Tables')} bodyClassName="p-0 py-3">
                <TableLengthRow
                    search={<SearchInput value={search} onSearch={setSearch} placeholder={t('Filter tables')} debounceMs={150} />}
                />

                {visible.length === 0 ? (
                    <EmptyState title={t('No table matches this filter.')} icon="ti ti-table-off" />
                ) : (
                    <DataTable
                        head={
                            <tr>
                                <th>{t('Table')}</th>
                                <th className="text-end">{t('Rows')}</th>
                                <th className="text-end">{t('Columns')}</th>
                                <th>{t('State')}</th>
                                <th className="text-end">{t('Action')}</th>
                            </tr>
                        }
                    >
                        {visible.map((table) => (
                            <tr key={table.name}>
                                <td className="text-normal-case">
                                    <Link href={tableUrl(table.name)} className="fw-medium">
                                        <i className="ti ti-table me-2 text-muted" />
                                        {table.name}
                                    </Link>
                                </td>
                                <td className="text-end">{formatCount(table.rows)}</td>
                                <td className="text-end">{table.columns}</td>
                                <td>{stateBadge(table)}</td>
                                <td className="text-end">
                                    <div className="d-inline-flex gap-1">
                                        <Link href={tableUrl(table.name)} className="btn btn-sm btn-outline-primary">
                                            <i className="ti ti-eye me-1" />
                                            {t('Open')}
                                        </Link>
                                        <a href={`${tableUrl(table.name)}/export`} className="btn btn-sm btn-outline-secondary">
                                            <i className="ti ti-download me-1" />
                                            CSV
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </DataTable>
                )}
            </Card>
        </BackofficeLayout>
    );
}
