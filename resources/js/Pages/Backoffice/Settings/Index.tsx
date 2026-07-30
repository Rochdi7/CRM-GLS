import { router } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EtablissementsPanel from '@/Pages/Backoffice/Settings/EtablissementsPanel';
import AnneesScolairesPanel from '@/Pages/Backoffice/Settings/AnneesScolairesPanel';
import SallesPanel from '@/Pages/Backoffice/Settings/SallesPanel';
import FraisPanel from '@/Pages/Backoffice/Settings/FraisPanel';
import type { SettingsPageProps, SettingsTab } from '@/Types';

const TAB_LABELS: Record<SettingsTab, { label: string; icon: string }> = {
    etablissements: { label: 'Centres', icon: 'ti ti-building' },
    'annees-scolaires': { label: 'Années scolaires', icon: 'ti ti-calendar' },
    salles: { label: 'Salles', icon: 'ti ti-door' },
    frais: { label: 'Frais', icon: 'ti ti-receipt' },
};

/**
 * Paramètres — Inertia/React (Phase 6), replacing the Livewire tabbed
 * Settings page. React owns visual tab state but the URL (?tab=...) stays
 * authoritative: clicking a tab navigates via router.get so refresh and
 * browser back/forward both land on the correct tab. Only the active tab's
 * data is fetched server-side (SettingController) — switching tabs is a
 * real navigation, not a client-side toggle over pre-loaded data.
 */
export default function SettingsIndex({
    activeTab,
    availableTabs,
    permissions,
    etablissements,
    anneesScolaires,
    salles,
    centerOptions,
    frais,
}: SettingsPageProps) {
    function switchTab(tab: SettingsTab) {
        if (tab === activeTab) {
            return;
        }

        router.get('/backoffice/settings', { tab }, { preserveState: false, preserveScroll: true });
    }

    return (
        <BackofficeLayout
            title="Paramètres"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Paramètres' }]}
        >
            <Card className="p-0">
                <ul className="nav nav-tabs nav-tabs-solid mb-4" role="tablist">
                    {availableTabs.map((tab) => (
                        <li className="nav-item" role="presentation" key={tab}>
                            <button
                                type="button"
                                className={`nav-link border-0 bg-transparent${tab === activeTab ? ' active' : ''}`}
                                onClick={() => switchTab(tab)}
                                role="tab"
                                aria-selected={tab === activeTab}
                            >
                                <i className={`${TAB_LABELS[tab].icon} me-1`} />
                                {TAB_LABELS[tab].label}
                            </button>
                        </li>
                    ))}
                </ul>

                <div className="tab-content">
                    {activeTab === 'etablissements' && etablissements && (
                        <EtablissementsPanel etablissements={etablissements} permissions={permissions.etablissements} />
                    )}
                    {activeTab === 'annees-scolaires' && anneesScolaires && (
                        <AnneesScolairesPanel anneesScolaires={anneesScolaires} permissions={permissions['annees-scolaires']} />
                    )}
                    {activeTab === 'salles' && salles && (
                        <SallesPanel salles={salles} centerOptions={centerOptions ?? []} permissions={permissions.salles} />
                    )}
                    {activeTab === 'frais' && frais && <FraisPanel frais={frais} permissions={permissions.frais} />}
                </div>
            </Card>
        </BackofficeLayout>
    );
}
