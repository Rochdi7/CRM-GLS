import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DataTable from '@/Components/Tables/DataTable';
import EmptyState from '@/Components/Shared/EmptyState';
import { t } from '@/Lib/i18n';

interface PermissionsIndexProps {
    groups: Record<string, Record<string, string>>;
    seededCount: number;
}

export default function PermissionsIndex({ groups, seededCount }: PermissionsIndexProps) {
    const groupEntries = Object.entries(groups);

    return (
        <BackofficeLayout
            title={t('Permissions')}
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Rôles & Permissions', href: '/backoffice/roles' },
                { label: 'Permissions' },
            ]}
        >
            <div className="alert alert-info d-flex align-items-center justify-content-between" role="alert">
                <div className="d-flex align-items-center">
                    Les permissions sont définies par l'application et ne peuvent pas être créées depuis l'interface. (
                    {seededCount} initialisées)
                </div>
            </div>

            {groupEntries.length === 0 ? (
                <EmptyState
                    title="Aucune permission trouvée"
                    message="Exécutez le seeder des rôles et permissions pour initialiser le catalogue."
                    icon="fa fa-key"
                />
            ) : (
                <div className="row">
                    {groupEntries.map(([group, permissions]) => (
                        <div className="col-lg-6" key={group}>
                            <Card title={group}>
                                <DataTable
                                    hover={false}
                                    head={
                                        <tr>
                                            <th>{t('Permission')}</th>
                                            <th>{t('Nom technique')}</th>
                                        </tr>
                                    }
                                >
                                    {Object.entries(permissions).map(([name, label]) => (
                                        <tr key={name}>
                                            <td>{label}</td>
                                            <td>
                                                <code>{name}</code>
                                            </td>
                                        </tr>
                                    ))}
                                </DataTable>
                            </Card>
                        </div>
                    ))}
                </div>
            )}
        </BackofficeLayout>
    );
}
