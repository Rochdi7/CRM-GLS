import { Link } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DataTable from '@/Components/Tables/DataTable';
import StatusBadge from '@/Components/Details/StatusBadge';
import { t } from '@/Lib/i18n';
import type { AuditChange, AuditLogShowPageProps } from '@/Types';

/**
 * « Détail de l'entrée » — one audit entry, in full.
 *
 * The list page's inline expand answers "what changed" at a glance while
 * scanning a sequence; this page answers "explain this one to me". It exists
 * because the people who actually read the journal (a director, not a
 * developer) need the values spelled out, a URL they can send to someone, and
 * the back button — none of which a drawer inside a table row provides.
 *
 * Read-only throughout, like the rest of the module: no edit, no delete, no
 * form. The backend refuses to mutate an entry regardless (App\Models\Activity),
 * so offering any such control here would be a lie.
 */

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

/**
 * One side of a change.
 *
 * The resolved name leads and the raw stored value follows in small text: a
 * reader sees « Karim Benali » first, while an investigator can still confirm
 * the record actually holds `11`. Showing only the name would hide what was
 * written; showing only the id is what made the journal unreadable.
 */
function ValueCell({ raw, label, tone }: { raw: string | null; label: string | null; tone: 'old' | 'new' }) {
    if (raw === null || raw === '') {
        return <span className="text-muted fst-italic">{t('empty')}</span>;
    }

    const color = tone === 'old' ? 'text-danger' : 'text-success';

    if (label === null) {
        return <span className={color}>{raw}</span>;
    }

    return (
        <span className={color}>
            {label}
            <span className="d-block fs-12 text-muted text-normal-case">#{raw}</span>
        </span>
    );
}

export default function AuditLogShow({ entry }: AuditLogShowPageProps) {
    const hasChanges = entry.changes.length > 0;
    const hasProperties = entry.properties.length > 0;

    return (
        <BackofficeLayout
            title={t('Audit entry')}
            breadcrumbs={[
                { label: t('Dashboard'), href: '/backoffice/dashboard' },
                { label: t('Audit journal'), href: '/backoffice/audit-logs' },
                { label: `#${entry.id}` },
            ]}
        >
            <div className="d-flex justify-content-end mb-3">
                <Link href="/backoffice/audit-logs" className="btn btn-outline-light">
                    <i className="ti ti-arrow-left me-1" aria-hidden="true" />
                    {t('Back to the journal')}
                </Link>
            </div>

            {/* ── Summary: the whole event in one sentence's worth of facts ── */}
            <Card title={t('Summary')} className="mb-3">
                <dl className="row mb-0">
                    <dt className="col-sm-3">{t('Action')}</dt>
                    <dd className="col-sm-9">
                        <StatusBadge
                            label={entry.eventLabel ?? entry.description}
                            variant={eventVariant(entry.event)}
                        />
                    </dd>

                    <dt className="col-sm-3">{t('Record')}</dt>
                    <dd className="col-sm-9">
                        {entry.subjectLabel ?? entry.logLabel}
                        {entry.subjectRef && (
                            <span className="text-muted"> — {entry.subjectRef}</span>
                        )}
                    </dd>

                    <dt className="col-sm-3">{t('Performed by')}</dt>
                    <dd className="col-sm-9">
                        {entry.causerLabel ?? <span className="text-muted">{t('System')}</span>}
                    </dd>

                    <dt className="col-sm-3">{t('Date & time')}</dt>
                    <dd className="col-sm-9">
                        {entry.createdAt ?? '—'}
                        {entry.createdAtHuman && (
                            <span className="text-muted"> ({entry.createdAtHuman})</span>
                        )}
                    </dd>

                    <dt className="col-sm-3">{t('IP address')}</dt>
                    <dd className="col-sm-9 text-normal-case">{entry.ipAddress ?? '—'}</dd>
                </dl>
            </Card>

            {/* ── Money first: on a finance entry this IS the answer ── */}
            {entry.money && (
                <Card title={t('Cash movement')} className="mb-3">
                    <div className="row g-3 align-items-stretch">
                        <div className="col-md-3">
                            <div className="border rounded p-3 h-100">
                                <div className="fs-12 text-muted">{t('Cash register')}</div>
                                <div className="fw-medium fs-16">{entry.money.caisse ?? '—'}</div>
                            </div>
                        </div>
                        <div className="col-md-3">
                            <div className="border rounded p-3 h-100">
                                <div className="fs-12 text-muted">{t('Balance before')}</div>
                                <div className="fw-medium fs-18 text-normal-case">
                                    {entry.money.soldeAvant} {t('MAD')}
                                </div>
                            </div>
                        </div>
                        <div className="col-md-3">
                            <div
                                className={`border rounded p-3 h-100 ${
                                    entry.money.isCredit ? 'border-success' : 'border-danger'
                                }`}
                            >
                                <div className="fs-12 text-muted">{entry.money.sens ?? t('Movement')}</div>
                                <div
                                    className={`fw-bold fs-18 text-normal-case ${
                                        entry.money.isCredit ? 'text-success' : 'text-danger'
                                    }`}
                                >
                                    {entry.money.isCredit ? '+' : '−'} {entry.money.montant} {t('MAD')}
                                </div>
                            </div>
                        </div>
                        <div className="col-md-3">
                            <div className="border rounded p-3 h-100">
                                <div className="fs-12 text-muted">{t('Balance after')}</div>
                                <div className="fw-medium fs-18 text-normal-case">
                                    {entry.money.soldeApres} {t('MAD')}
                                </div>
                            </div>
                        </div>
                    </div>

                    {entry.money.motif && (
                        <div className="mt-3">
                            <span className="text-muted">{t('Reason')} : </span>
                            {entry.money.motif}
                            {entry.money.origineReference && (
                                <span className="text-muted text-normal-case">
                                    {' '}
                                    ({entry.money.origineReference})
                                </span>
                            )}
                        </div>
                    )}

                    {/* An incoherent line is the whole reason to read this page:
                        the stored before/after do not match the recorded amount. */}
                    {!entry.money.coherent && (
                        <div className="alert alert-danger mt-3 mb-0" role="alert">
                            <i className="ti ti-alert-triangle me-1" aria-hidden="true" />
                            {t('Inconsistency: the balance difference does not match the recorded amount.')}
                            <span className="text-normal-case"> ({entry.money.delta})</span>
                        </div>
                    )}
                </Card>
            )}

            {/* ── The diff: what actually changed, in readable values ── */}
            {hasChanges && (
                <Card title={t('Changed fields')} className="mb-3" bodyClassName="p-0 py-3">
                    <DataTable
                        head={
                            <tr>
                                <th>{t('Field')}</th>
                                <th>{t('Before')}</th>
                                <th>{t('After')}</th>
                            </tr>
                        }
                    >
                        {entry.changes.map((change: AuditChange) => (
                            <tr key={change.field}>
                                <td className="fw-medium">{change.label}</td>
                                <td>
                                    <ValueCell raw={change.old} label={change.oldLabel} tone="old" />
                                </td>
                                <td>
                                    <ValueCell raw={change.new} label={change.newLabel} tone="new" />
                                </td>
                            </tr>
                        ))}
                    </DataTable>
                </Card>
            )}

            {hasProperties && (
                <Card title={t('Context')} className="mb-3">
                    <dl className="row mb-0">
                        {entry.properties.map((property) => (
                            <div className="row mb-0" key={property.key}>
                                <dt className="col-sm-3">{property.label}</dt>
                                <dd className="col-sm-9">{property.value}</dd>
                            </div>
                        ))}
                    </dl>
                </Card>
            )}

            {/* ── Provenance: the "prove it" section ── */}
            <Card title={t('Origin')}>
                <dl className="row mb-0">
                    <dt className="col-sm-3">{t('IP address')}</dt>
                    <dd className="col-sm-9 text-normal-case">{entry.ipAddress ?? '—'}</dd>

                    <dt className="col-sm-3">{t('Request')}</dt>
                    <dd className="col-sm-9 text-break text-normal-case">
                        {entry.method ? `${entry.method} ` : ''}
                        {entry.url ?? '—'}
                    </dd>

                    <dt className="col-sm-3">{t('Route')}</dt>
                    <dd className="col-sm-9 text-normal-case">{entry.routeName ?? '—'}</dd>

                    <dt className="col-sm-3">{t('Browser')}</dt>
                    <dd className="col-sm-9 text-break text-normal-case">{entry.userAgent ?? '—'}</dd>
                </dl>
            </Card>
        </BackofficeLayout>
    );
}
