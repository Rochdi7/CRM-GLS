import { router } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import PageTabs from '@/Components/Navigation/PageTabs';
import { INSCRIPTIONS_TABS } from '@/Config/pageTabs';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import StatusBadge from '@/Components/Details/StatusBadge';
import { statutVariant } from '@/Lib/inscriptionStatut';
import { formatMontant } from '@/Lib/money';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { useFilterReset } from '@/Hooks/useFilterReset';
import type { InscriptionsCorbeillePageProps, SelectOption } from '@/Types';

/**
 * Corbeille des inscriptions (19/10/2026) — les dossiers SUPPRIMÉS, par
 * qui et quand. Lecture seule : rien ne se restaure ni ne s'efface ici,
 * une ligne de corbeille est une trace (InscriptionCorbeille, append-only).
 * Le total et la répartition par auteur viennent du SERVEUR, sur tout
 * l'ensemble filtré — jamais réadditionnés sur la page.
 */
export default function InscriptionsCorbeille({
    rows,
    total,
    parAuteur,
    auteurOptions,
    tabCounts,
    filters,
    perPageOptions,
}: InscriptionsCorbeillePageProps) {
    const isLoading = useInertiaLoading();

    const auteurSelectOptions: SelectOption[] = auteurOptions.map((a) => ({ value: a.id, label: a.label }));
    const perPageSelectOptions: SelectOption[] = perPageOptions.map((n) => ({ value: n, label: `${n}` }));

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/inscriptions/corbeille',
            { ...filters, ...nextFilters, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const filterReset = useFilterReset(filters, reload, { perPage: filters.perPage });

    return (
        <BackofficeLayout
            title="Corbeille des inscriptions"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Inscriptions', href: '/backoffice/inscriptions' },
                { label: 'Corbeille' },
            ]}
        >
            <PageTabs tabs={INSCRIPTIONS_TABS} counts={tabCounts} />

            <Card title="Inscriptions supprimées" bodyClassName="p-0 py-3">
                <div className="px-3 pt-2">
                    <TableToolbar
                        onReset={filterReset.reset}
                        resetActive={filterReset.active}
                        search={
                            <SearchInput
                                value={filters.search}
                                onSearch={(value) => reload({ search: value })}
                                placeholder="Référence, étudiant ou groupe"
                            />
                        }
                    >
                        <div style={{ width: 240 }}>
                            <label className="form-label" htmlFor="corb-f-auteur">
                                Supprimé par
                            </label>
                            <SelectField
                                id="corb-f-auteur"
                                options={auteurSelectOptions}
                                placeholder="Tous"
                                value={filters.auteurFilter}
                                onChange={(event) => reload({ auteurFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 160 }}>
                            <label className="form-label" htmlFor="corb-f-from">
                                Du
                            </label>
                            <DateField
                                id="corb-f-from"
                                value={filters.dateFrom}
                                onChange={(event) => reload({ dateFrom: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 160 }}>
                            <label className="form-label" htmlFor="corb-f-to">
                                Au
                            </label>
                            <DateField
                                id="corb-f-to"
                                value={filters.dateTo}
                                onChange={(event) => reload({ dateTo: event.target.value })}
                                panelAlign="right"
                            />
                        </div>
                        <div style={{ width: 120 }}>
                            <label className="form-label" htmlFor="corb-f-perpage">
                                Par page
                            </label>
                            <SelectField
                                id="corb-f-perpage"
                                options={perPageSelectOptions}
                                value={filters.perPage}
                                onChange={(event) => reload({ perPage: Number(event.target.value) })}
                                searchable={false}
                            />
                        </div>
                    </TableToolbar>
                </div>

                {/* Compteurs — SERVEUR, sur tout l'ensemble filtré. */}
                <div className="px-3 pb-3 d-flex flex-wrap align-items-center gap-2">
                    <span className="badge badge-soft-danger fs-13">
                        <i className="ti ti-trash me-1" />
                        {total.toLocaleString('fr-FR')} inscription{total > 1 ? 's' : ''} supprimée{total > 1 ? 's' : ''}
                    </span>
                    {parAuteur.map((a) => (
                        <button
                            key={a.userId ?? 'inconnu'}
                            type="button"
                            className={`badge border-0 fs-13 ${
                                a.userId !== null && String(a.userId) === filters.auteurFilter ? 'bg-primary' : 'badge-soft-primary'
                            }`}
                            title={a.userId !== null ? 'Filtrer sur cette personne' : undefined}
                            onClick={() => a.userId !== null && reload({ auteurFilter: String(a.userId) === filters.auteurFilter ? '' : String(a.userId) })}
                        >
                            <i className="ti ti-user me-1" />
                            {a.label} : {a.count.toLocaleString('fr-FR')}
                        </button>
                    ))}
                </div>

                {rows.data.length === 0 ? (
                    <EmptyState
                        title="Corbeille vide"
                        message="Aucune inscription supprimée dans ce centre et cette année scolaire."
                        icon="ti ti-trash"
                    />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Étudiant</th>
                                    <th>Groupe</th>
                                    <th>Année</th>
                                    <th>Statut</th>
                                    <th>Inscrit le</th>
                                    <th className="text-end">Montant</th>
                                    <th>Paiements détachés</th>
                                    <th>Supprimé par</th>
                                    <th>Supprimé le</th>
                                </tr>
                            }
                        >
                            {rows.data.map((row) => (
                                <tr key={row.id}>
                                    <td>
                                        <code>{row.reference}</code>
                                    </td>
                                    <td>
                                        {row.student.id ? (
                                            <a href={`/backoffice/students/${row.student.id}`} className="fw-medium text-dark">
                                                {row.student.nomComplet}
                                            </a>
                                        ) : (
                                            <span className="fw-medium">{row.student.nomComplet}</span>
                                        )}
                                        {row.student.reference && (
                                            <div className="fs-12 text-muted">
                                                <code>{row.student.reference}</code>
                                            </div>
                                        )}
                                    </td>
                                    <td>{row.groupe ?? '-'}</td>
                                    <td>{row.anneeScolaire ?? '-'}</td>
                                    <td>{row.statut ? <StatusBadge label={row.statut} variant={statutVariant(row.statut)} /> : '-'}</td>
                                    <td>{row.dateInscription ?? '-'}</td>
                                    <td className="text-end">{row.montantTotal !== null ? `${formatMontant(row.montantTotal)} MAD` : '-'}</td>
                                    <td>
                                        {row.paiementsDetaches.length === 0 ? (
                                            <span className="text-muted">-</span>
                                        ) : (
                                            <ul className="list-unstyled mb-0 fs-12">
                                                {row.paiementsDetaches.map((p, index) => (
                                                    <li key={index} className="text-normal-case">
                                                        <code>{p.reference}</code> {formatMontant(p.montant)} MAD
                                                        {p.frais ? ` — ${p.frais}` : ''}
                                                        {p.rembourse ? ` (remboursé ${formatMontant(p.rembourse)})` : ''}
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </td>
                                    <td className="text-normal-case">{row.supprimePar ?? <span className="text-muted">Compte inconnu</span>}</td>
                                    <td>{row.supprimeLe ?? '-'}</td>
                                </tr>
                            ))}
                        </DataTable>
                        <Pagination paginator={rows} />
                    </>
                )}
            </Card>
        </BackofficeLayout>
    );
}
