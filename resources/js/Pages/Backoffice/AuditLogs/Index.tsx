import { Link, router } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DataTable from '@/Components/Tables/DataTable';
import EmptyState from '@/Components/Shared/EmptyState';
import Pagination from '@/Components/Tables/Pagination';
import SearchInput from '@/Components/Tables/SearchInput';
import TableToolbar from '@/Components/Tables/TableToolbar';
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { t } from '@/Lib/i18n';
import type { AuditLogFilters, AuditLogPageProps, AuditLogRow } from '@/Types';

/**
 * « Journal d'audit » — the read-only forensic trail (CLAUDE.md §11).
 *
 * Every row answers who / what / when / from where at a glance, and links to
 * its own detail page for the full before→after breakdown. There is no edit or
 * delete affordance anywhere on this page by design: the journal is evidence,
 * and the backend refuses to mutate it regardless of what the UI offers.
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
    caisses,
    hasDeveloperAccount,
    filters,
}: AuditLogPageProps) {
    const loading = useInertiaLoading();

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
        filters.caisseId !== '' ||
        filters.includeDeveloper ||
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
                                            caisseId: '',
                                            includeDeveloper: false,
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

                        <div style={{ width: 200 }}>
                            <SelectField
                                id="audit-caisse"
                                label={t('Cash register')}
                                value={filters.caisseId}
                                placeholder={t('All cash registers')}
                                options={caisses.map((o) => ({ value: o.value, label: o.label }))}
                                onChange={(event) => reload({ caisseId: event.target.value })}
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
                            <DateField
                                id="audit-date-from"
                                label={t('From')}
                                value={filters.dateFrom}
                                onChange={(event) => reload({ dateFrom: event.target.value })}
                            />
                        </div>

                        <div>
                            <DateField
                                id="audit-date-to"
                                label={t('To')}
                                panelAlign="right"
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

                        {/* The maintainer's entries are recorded like anyone
                            else's — this only brings them back into view. */}
                        {hasDeveloperAccount && (
                            <div>
                                <label className="form-label d-block">{t('Technical account')}</label>
                                <button
                                    type="button"
                                    className={`btn ${filters.includeDeveloper ? 'btn-secondary' : 'btn-outline-secondary'}`}
                                    onClick={() => reload({ includeDeveloper: !filters.includeDeveloper })}
                                    aria-pressed={filters.includeDeveloper}
                                    title={t('The technical account is always recorded; this only shows it.')}
                                >
                                    <i
                                        className={`ti ${filters.includeDeveloper ? 'ti-eye' : 'ti-eye-off'} me-1`}
                                        aria-hidden="true"
                                    />
                                    {t('Include technical account')}
                                </button>
                            </div>
                        )}
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
                                <th className="text-end">{t('Amount')}</th>
                                <th>{t('Balance after')}</th>
                                <th>{t('Changes')}</th>
                                <th>{t('IP address')}</th>
                            </tr>
                        }
                    >
                        {entries.data.map((row) => (
                            <AuditRow key={row.id} row={row} />
                        ))}
                    </DataTable>
                )}

                <div className="px-3">
                    <Pagination paginator={entries} />
                </div>
            </Card>
        </BackofficeLayout>
    );
}

interface AuditRowProps {
    row: AuditLogRow;
}

/**
 * One journal line. The whole entry opens on its own page (Show.tsx) rather
 * than unfolding here: the readers who use this are not developers, and a
 * cramped drawer of raw column names is what made the trail unreadable. The
 * row keeps only what is scannable — when, who, what, and how many fields
 * moved — and defers the explanation to the detail page.
 */
function AuditRow({ row }: AuditRowProps) {
    const changeCount = row.changes.length;

    return (
        <tr>
            <td>
                <Link
                    href={`/backoffice/audit-logs/${row.id}`}
                    className="btn btn-sm btn-icon btn-outline-light"
                    aria-label={t('Show details')}
                    title={t('Show details')}
                >
                    <i className="ti ti-eye" aria-hidden="true" />
                </Link>
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
            <td className="text-end text-nowrap text-normal-case">
                {row.money ? (
                    <span className={`fw-bold ${row.money.isCredit ? 'text-success' : 'text-danger'}`}>
                        {row.money.isCredit ? '+' : '−'} {row.money.montant}
                    </span>
                ) : (
                    <span className="text-muted">—</span>
                )}
            </td>
            <td className="text-nowrap text-normal-case">
                {row.money ? (
                    <>
                        <span className="fw-medium">{row.money.soldeApres}</span>
                        <span className="d-block fs-12 text-muted">
                            {t('was')} {row.money.soldeAvant}
                        </span>
                        {!row.money.coherent && (
                            <span className="d-block fs-12 text-danger">
                                <i className="ti ti-alert-triangle me-1" aria-hidden="true" />
                                {t('Inconsistent')}
                            </span>
                        )}
                    </>
                ) : (
                    <span className="text-muted">—</span>
                )}
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
            <td className="text-nowrap text-normal-case">{row.ipAddress ?? '—'}</td>
        </tr>
    );
}
