import { Link } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import { t } from '@/Lib/i18n';

/**
 * « Statistiques » tab of Gestion des séances — placeholder only
 * (21/10/2026). Carries the same tab bar as Index / AbsenceParGroupe / Show
 * and an « En cours de développement » notice; no data is served yet.
 */
export default function Statistiques() {
    return (
        <BackofficeLayout
            title="Gestion des séances"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Gestion des séances', href: '/backoffice/seances' },
                { label: 'Statistiques' },
            ]}
        >
            <ul className="nav nav-tabs mb-3" role="tablist">
                <li className="nav-item" role="presentation">
                    <Link href="/backoffice/seances" className="nav-link fw-medium" role="tab" aria-selected="false">
                        <i className="ti ti-calendar-check me-2" />
                        Séances
                    </Link>
                </li>
                <li className="nav-item" role="presentation">
                    <Link
                        href="/backoffice/seances/saisir-absence"
                        className="nav-link fw-medium"
                        role="tab"
                        aria-selected="false"
                    >
                        <i className="ti ti-checklist me-2" />
                        Saisir l'absence
                    </Link>
                </li>
                <li className="nav-item" role="presentation">
                    <Link
                        href="/backoffice/seances/absence-par-groupe"
                        className="nav-link fw-medium"
                        role="tab"
                        aria-selected="false"
                    >
                        <i className="ti ti-table me-2" />
                        Absence par groupe
                    </Link>
                </li>
                <li className="nav-item" role="presentation">
                    <button type="button" className="nav-link active fw-medium" role="tab" aria-selected="true">
                        <i className="ti ti-chart-bar me-2" />
                        Statistiques
                    </button>
                </li>
            </ul>

            <Card>
                <EmptyState
                    icon="ti ti-tools"
                    title={t('Under development')}
                    message={t('This section is being built and will be available soon.')}
                >
                    <span className="badge badge-soft-warning fs-13">
                        <i className="ti ti-clock me-1" />
                        {t('Coming soon')}
                    </span>
                </EmptyState>
            </Card>
        </BackofficeLayout>
    );
}
