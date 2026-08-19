import { router } from '@inertiajs/react';
import { useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DataTable from '@/Components/Tables/DataTable';
import EmptyState from '@/Components/Shared/EmptyState';
import Pagination from '@/Components/Tables/Pagination';
import SearchInput from '@/Components/Tables/SearchInput';
import TableToolbar from '@/Components/Tables/TableToolbar';
import SelectField from '@/Components/Forms/SelectField';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { t } from '@/Lib/i18n';
import type { AuditLogFilters, AuditLogPageProps, AuditLogRow } from '@/Types';

/**
 * « Journal d'audit » — the read-only forensic trail (CLAUDE.md §11).
 *
 * Every row answers who / what / when / from where, and expands to the exact
 * before→after diff of each changed column. There is no edit or delete
 * affordance anywhere on this page by design: the journal is evidence, and the
 * backend refuses to mutate it regardless of what the UI offers.
 *
 * The row is expandable rather than a detail page because an investigation
 * reads a SEQUENCE — comparing several entries around a suspicious payment —
 * and navigating away to a detail page for each one loses that context.
 */

/** Colour cue per event so a page of entries can be scanned, not read. */
function eventVariant(event: string | null) {
    switch (event) {
        case 'created':
            return 'success';
        case 'updated':
            return 'warning';
        case 'deleted':
            return 'danger';
        case 'login':
            return 'info';
        case 'logout':
            return 'secondary';
        case 'login_failed':
        case 'lockout':
            return 'danger';
        default:
            return 'secondary';
    }
}

export default function AuditLogsIndex({
    entries,
    logNames,
    events,
    causers,
    subjectTypes,
    filters,
}: AuditLogPageProps) {
    const loading = useInertiaLoading();
    const [expanded, setExpanded] = useState<number | null>(null);

    // Any filter change resets to page 1 — staying on page 7 of a narrower
    // result set silently shows nothing and reads as "no activity".
    function reload(next: Partial<AuditLogFilters>) {
        router.get(
            '/backoffice/audit-logs',
            { ...filters, ...next, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const hasFilters =
        filters.search !== '' ||
        filters.logName !== '' ||
        filters.event !== '' ||
        filters.causerId !== '' ||
        filters.subjectType !== '' ||
        filters.dateFrom !== '' ||
        filters.dateTo !== '' ||
        filters.ip !== '' ||
        filters.financeOnly;

    return (
        <BackofficeLayout
            title={t('Audit journal')}
            breadcrumbs={[
                { label: t('Dashboard'), href: '/backoffice/dashboard' },
                { label: t('Audit journal') },
            ]}
        >
            <Card title={t('Audit journal')} bodyClassName="p-0 py-3">
                <div className="px-3">
                    <TableToolbar
                        search={
                            <SearchInput
                                value={filters.search}
                                onSearch={(value) => reload({ search: value })}
                                placeholder={t('Reference, actor, IP, value…')}
                            />
                        }
                        actions={
                            hasFilters ? (
                                <button
                                    type="button"
                                    className="btn btn-outline-light"
                                    onClick={() =>
                                        reload({
                                            search: '',
                                            logName: '',
                                            event: '',
                                            causerId: '',
                                            subjectType: '',
                                            dateFrom: '',
                                            dateTo: '',
                                            ip: '',
                                            financeOnly: false,
                                        })
                                    }
                                >
                                    <i className="ti ti-filter-off me-1" aria-hidden="true" />
                                    {t('Clear filters')}
                                </button>
                            ) : undefined
                        }
                    >
                        <div style={{ width: 200 }}>
                            <SelectField
                                id="audit-log-name"
                                label={t('Module')}
                                value={filters.logName}
                                placeholder={t('All modules')}
                                options={logNames.map((o) => ({ value: o.value, label: o.label }))}
                                onChange={(event) => reload({ logName: event.target.value })}
                            />
                        </div>

                        <div style={{ width: 180 }}>
                            <SelectField
                                id="audit-event"
                                label={t('Action')}
                                value={filters.event}
                                placeholder={t('All actions')}
                                options={events.map((o) => ({ value: o.value, label: o.label }))}
                                onChange={(event) => reload({ event: event.target.value })}
                            />
                        </div>

                        <div style={{ width: 220 }}>
                            <SelectField
                                id="audit-causer"
                                label={t('Performed by')}
                                value={filters.causerId}
                                placeholder={t('Anyone')}
                                options={causers.map((c) => ({ value: String(c.id), label: c.nom }))}
                                onChange={(event) => reload({ causerId: event.target.value })}
                            />
                        </div>

                        <div style={{ width: 200 }}>
                            <SelectField
                                id="audit-subject-type"
                                label={t('Record type')}
                                value={filters.subjectType}
                                placeholder={t('All records')}
                                options={subjectTypes.map((o) => ({ value: o.value, label: o.label }))}
                                onChange={(event) => reload({ subjectType: event.target.value })}
                            />
                        </div>

                        <div>
                            <label className="form-label" htmlFor="audit-date-from">
                                {t('From')}
                            </label>
                            <input
                                id="audit-date-from"
                                type="date"
                                className="form-control"
                                value={filters.dateFrom}
                                onChange={(event) => reload({ dateFrom: event.target.value })}
                            />
                        </div>

                        <div>
                            <label className="form-label" htmlFor="audit-date-to">
                                {t('To')}
                            </label>
                            <input
                                id="audit-date-to"
                                type="date"
                                className="form-control"
                                value={filters.dateTo}
                                onChange={(event) => reload({ dateTo: event.target.value })}
                            />
                        </div>

                        <div style={{ width: 150 }}>
                            <label className="form-label" htmlFor="audit-ip">
                                {t('IP address')}
                            </label>
                            <input
                                id="audit-ip"
                                type="text"
                                className="form-control"
                                value={filters.ip}
                                placeholder="192.168.1.1"
                                onChange={(event) => reload({ ip: event.target.value })}
                            />
                        </div>

                        <div>
                            <label className="form-label d-block">{t('Scope')}</label>
                            <button
                                type="button"
                                className={`btn ${filters.financeOnly ? 'btn-primary' : 'btn-outline-primary'}`}
                                onClick={() => reload({ financeOnly: !filters.financeOnly })}
                                aria-pressed={filters.financeOnly}
                            >
                                <i className="ti ti-coins me-1" aria-hidden="true" />
                                {t('Money only')}
                            </button>
                        </div>
                    </TableToolbar>
                </div>

                {entries.data.length === 0 ? (
                    <EmptyState title={t('No audit entry')} icon="ti ti-history-off" />
                ) : (
                    <DataTable
                        loading={loading}
                        head={
                            <tr>
                                <th style={{ width: 34 }} aria-label={t('Details')} />
                                <th>{t('Date & time')}</th>
                                <th>{t('Performed by')}</th>
                                <th>{t('Action')}</th>
                                <th>{t('Record')}</th>
                                <th>{t('Changes')}</th>
                                <th>{t('IP address')}</th>
                            </tr>
                        }
                    >
                        {entries.data.map((row) => (
                            <AuditRow
                                key={row.id}
                                row={row}
                                expanded={expanded === row.id}
                                onToggle={() => setExpanded(expanded === row.id ? null : row.id)}
                            />
                        ))}
                    </DataTable>
                )}

                <div className="px-3">
                    <Pagination paginator={entries} showJumpToPage />
                </div>
            </Card>
        </BackofficeLayout>
    );
}

interface AuditRowProps {
    row: AuditLogRow;
    expanded: boolean;
    onToggle: () => void;
}

function AuditRow({ row, expanded, onToggle }: AuditRowProps) {
    const changeCount = row.changes.length;
    const hasDetail = changeCount > 0 || Object.keys(row.properties).length > 0 || row.url !== null;

    return (
        <>
            <tr>
                <td>
                    {hasDetail && (
                        <button
                            type="button"
                            className="btn btn-sm btn-icon btn-outline-light"
                            onClick={onToggle}
                            aria-expanded={expanded}
                            aria-label={expanded ? t('Hide details') : t('Show details')}
                        >
                            <i
                                className={`ti ${expanded ? 'ti-chevron-down' : 'ti-chevron-right'}`}
                                aria-hidden="true"
                            />
                        </button>
                    )}
                </td>
                <td className="text-nowrap">
                    <span className="fw-medium">{row.createdAt ?? '—'}</span>
                    {row.createdAtHuman && (
                        <span className="d-block fs-12 text-muted">{row.createdAtHuman}</span>
                    )}
                </td>
                <td>{row.causerLabel ?? <span className="text-muted">{t('System')}</span>}</td>
                <td>
                    <StatusBadge label={row.eventLabel ?? row.description} variant={eventVariant(row.event)} />
                </td>
                <td>
                    <span className="fw-medium">{row.subjectLabel ?? row.logLabel}</span>
                    {row.subjectRef && <span className="d-block fs-12 text-muted">{row.subjectRef}</span>}
                </td>
                <td>
                    {changeCount > 0 ? (
                        <StatusBadge
                            label={`${changeCount} ${changeCount > 1 ? t('fields') : t('field')}`}
                            variant="secondary"
                        />
                    ) : (
                        <span className="text-muted">—</span>
                    )}
                </td>
                <td className="text-nowrap">{row.ipAddress ?? '—'}</td>
            </tr>

            {expanded && (
                <tr>
                    <td colSpan={7} className="bg-light">
                        <div className="p-3">
                            {changeCount > 0 && (
                                <>
                                    <h6 className="mb-2">{t('Changed fields')}</h6>
                                    <div className="table-responsive mb-3">
                                        <table className="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>{t('Field')}</th>
                                                    <th>{t('Before')}</th>
                                                    <th>{t('After')}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {row.changes.map((change) => (
                                                    <tr key={change.field}>
                                                        <td className="fw-medium">{change.field}</td>
                                                        <td className="text-danger">
                                                            {change.old ?? <span className="text-muted">{t('empty')}</span>}
                                                        </td>
                                                        <td className="text-success">
                                                            {change.new ?? <span className="text-muted">{t('empty')}</span>}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </>
                            )}

                            {Object.keys(row.properties).length > 0 && (
                                <>
                                    <h6 className="mb-2">{t('Context')}</h6>
                                    <pre className="mb-3 fs-12 bg-white border rounded p-2 overflow-auto">
                                        {JSON.stringify(row.properties, null, 2)}
                                    </pre>
                                </>
                            )}

                            <h6 className="mb-2">{t('Origin')}</h6>
                            <dl className="row mb-0 fs-13">
                                <dt className="col-sm-3">{t('IP address')}</dt>
                                <dd className="col-sm-9">{row.ipAddress ?? '—'}</dd>

                                <dt className="col-sm-3">{t('Request')}</dt>
                                <dd className="col-sm-9">
                                    {row.method ? `${row.method} ` : ''}
                                    {row.url ?? '—'}
                                </dd>

                                <dt className="col-sm-3">{t('Route')}</dt>
                                <dd className="col-sm-9">{row.routeName ?? '—'}</dd>

                                <dt className="col-sm-3">{t('Browser')}</dt>
                                <dd className="col-sm-9 text-break">{row.userAgent ?? '—'}</dd>
                            </dl>
                        </div>
                    </td>
                </tr>
            )}
        </>
    );
}
