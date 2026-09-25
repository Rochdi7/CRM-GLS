import { router } from '@inertiajs/react';
import { useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import PageTabs from '@/Components/Navigation/PageTabs';
import { STUDENTS_TABS } from '@/Config/pageTabs';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import SelectField from '@/Components/Forms/SelectField';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { useFilterReset } from '@/Hooks/useFilterReset';
import type { SelectOption, StudentTransferRow, StudentTransfersPageProps } from '@/Types';

type Decision = { kind: 'validate' | 'refuse' | 'cancel'; row: StudentTransferRow };

function statutVariant(statut: string): 'success' | 'danger' | 'warning' | 'secondary' {
    if (statut === 'Validé') return 'success';
    if (statut === 'Refusé') return 'danger';
    if (statut === 'En attente') return 'warning';

    return 'secondary';
}

/**
 * Transferts d'étudiants entre centres (25/09/2026) — la liste des
 * demandes. Une demande naît sur la page Étudiants (action de ligne
 * « Demander un transfert ») ; ici on la VOIT, on l'ANNULE (demandeur) ou
 * on la DÉCIDE (super-admin : valider / refuser avec motif). Tout ce qui
 * est offert vient du serveur (`canDecide` / `canCancel`,
 * GetStudentTransfersList) — la page ne redérive aucune règle (§5).
 */
export default function StudentTransfersIndex({ transfers, filters, perPageOptions, statuts, permissions, tabCounts }: StudentTransfersPageProps) {
    const isLoading = useInertiaLoading();
    const [decision, setDecision] = useState<Decision | null>(null);
    const [motif, setMotif] = useState('');
    const [error, setError] = useState<string | undefined>(undefined);
    const [processing, setProcessing] = useState(false);

    const statutOptions: SelectOption[] = statuts.map((s) => ({ value: s, label: s }));
    const perPageSelectOptions: SelectOption[] = perPageOptions.map((n) => ({ value: n, label: `${n}` }));

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/student-transfers',
            { ...filters, ...nextFilters, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const filterReset = useFilterReset(filters, reload, { perPage: filters.perPage });

    function open(kind: Decision['kind'], row: StudentTransferRow) {
        setDecision({ kind, row });
        setMotif('');
        setError(undefined);
    }

    function close() {
        setDecision(null);
        setMotif('');
        setError(undefined);
    }

    function submit() {
        if (!decision) {
            return;
        }

        const url = `/backoffice/student-transfers/${decision.row.id}/${decision.kind}`;
        const payload = decision.kind === 'validate' ? {} : { motif_decision: motif };

        setProcessing(true);
        router.put(url, payload, {
            preserveScroll: true,
            onSuccess: () => close(),
            onError: (errors) => {
                setError(errors.validate ?? errors.statut ?? errors.motif_decision ?? Object.values(errors)[0] ?? 'Action impossible.');
            },
            onFinish: () => setProcessing(false),
        });
    }

    const dialog = decision
        ? {
              validate: {
                  title: 'Valider le transfert',
                  message: `Copier la fiche à ${decision.row.centreCible ?? '—'}, l'inscrire au groupe « ${decision.row.groupeCible?.nom ?? '—'} » et y emporter tous ses paiements ? Les dossiers du centre de départ passeront « Transférée », ses présences y resteront.`,
                  confirmLabel: 'Oui, valider le transfert',
                  processingLabel: 'Validation...',
                  icon: 'ti-arrows-exchange',
                  variant: 'primary' as const,
              },
              refuse: {
                  title: 'Refuser le transfert',
                  message: "L'étudiant restera dans son centre actuel. Indiquez le motif du refus.",
                  confirmLabel: 'Refuser',
                  processingLabel: 'Refus...',
                  icon: 'ti-x',
                  variant: 'danger' as const,
              },
              cancel: {
                  title: 'Annuler la demande',
                  message: 'La demande sera retirée sans rien changer à la fiche de l’étudiant.',
                  confirmLabel: 'Oui, annuler la demande',
                  processingLabel: 'Annulation...',
                  icon: 'ti-trash',
                  variant: 'danger' as const,
              },
          }[decision.kind]
        : null;

    return (
        <BackofficeLayout
            title="Transferts d'étudiants"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Étudiants', href: '/backoffice/students' },
                { label: "Transferts d'étudiants" },
            ]}
        >
            <PageTabs tabs={STUDENTS_TABS} counts={tabCounts} />

            <Card title="Demandes de transfert entre centres" bodyClassName="p-0 py-3">
                <div className="px-3 pt-2">
                    <TableToolbar
                        onReset={filterReset.reset}
                        resetActive={filterReset.active}
                        search={
                            <SearchInput
                                value={filters.search}
                                onSearch={(value) => reload({ search: value })}
                                placeholder="Référence, nom ou prénom"
                            />
                        }
                    >
                        <div style={{ width: 200 }}>
                            <label className="form-label" htmlFor="tre-f-statut">
                                Statut
                            </label>
                            <SelectField
                                id="tre-f-statut"
                                options={statutOptions}
                                placeholder="Tous les statuts"
                                value={filters.statutFilter}
                                onChange={(event) => reload({ statutFilter: event.target.value })}
                                searchable={false}
                            />
                        </div>
                        <div style={{ width: 120 }}>
                            <label className="form-label" htmlFor="tre-f-perpage">
                                Par page
                            </label>
                            <SelectField
                                id="tre-f-perpage"
                                options={perPageSelectOptions}
                                value={filters.perPage}
                                onChange={(event) => reload({ perPage: Number(event.target.value) })}
                                searchable={false}
                            />
                        </div>
                    </TableToolbar>
                </div>

                {!permissions.validate && (
                    <div className="alert alert-info mx-3 mb-3 fs-13" role="alert">
                        <i className="ti ti-info-circle me-1" />
                        Suivez ici vos demandes. Le backoffice les valide ou les refuse.
                    </div>
                )}

                {transfers.data.length === 0 ? (
                    <EmptyState title="Aucune demande de transfert" icon="ti ti-arrows-exchange" />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Étudiant</th>
                                    <th>De</th>
                                    <th>Vers</th>
                                    <th>Groupe d'affectation</th>
                                    <th>Motif</th>
                                    <th>Demandé</th>
                                    <th>Statut</th>
                                    <th>Décision</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {transfers.data.map((row) => (
                                <tr key={row.id}>
                                    <td>
                                        <code>{row.reference}</code>
                                    </td>
                                    <td>
                                        <a href={`/backoffice/students/${row.student.id}`} className="fw-medium text-dark">
                                            {row.student.nomComplet}
                                        </a>
                                        {row.student.reference && (
                                            <div className="fs-12 text-muted">
                                                <code>{row.student.reference}</code>
                                            </div>
                                        )}
                                        {row.nouveauStudent && (
                                            <div className="fs-12">
                                                <i className="ti ti-arrow-right me-1 text-muted" />
                                                <a href={`/backoffice/students/${row.nouveauStudent.id}`}>
                                                    Nouvelle fiche <code>{row.nouveauStudent.reference}</code>
                                                </a>
                                            </div>
                                        )}
                                    </td>
                                    <td>{row.centreSource ?? '-'}</td>
                                    <td>{row.centreCible ?? '-'}</td>
                                    <td>
                                        {row.groupeCible ? (
                                            <>
                                                <span className="fw-medium">{row.groupeCible.nom}</span>
                                                {row.groupeCible.niveau && (
                                                    <span className="badge badge-soft-info ms-1">{row.groupeCible.niveau}</span>
                                                )}
                                                {row.nouvelleInscription && (
                                                    <div className="fs-12">
                                                        <a href={`/backoffice/inscriptions/${row.nouvelleInscription.id}`}>
                                                            <code>{row.nouvelleInscription.reference}</code>
                                                        </a>
                                                    </div>
                                                )}
                                            </>
                                        ) : (
                                            <span className="text-danger">Groupe supprimé</span>
                                        )}
                                    </td>
                                    <td className="text-normal-case" style={{ maxWidth: 240, whiteSpace: 'normal' }}>
                                        {row.motif}
                                    </td>
                                    <td>
                                        <div>{row.demandePar ?? '-'}</div>
                                        <div className="fs-12 text-muted">{row.demandeLe}</div>
                                    </td>
                                    <td>
                                        <StatusBadge label={row.statut} variant={statutVariant(row.statut)} dot />
                                        {row.montantTransfere !== null && (
                                            <div className="fs-12 text-muted mt-1">{row.montantTransfere} DH emportés</div>
                                        )}
                                    </td>
                                    <td className="text-normal-case" style={{ maxWidth: 220, whiteSpace: 'normal' }}>
                                        {row.decidePar ? (
                                            <>
                                                <div>{row.decidePar}</div>
                                                <div className="fs-12 text-muted">{row.decideLe}</div>
                                                {row.motifDecision && <div className="fs-12">{row.motifDecision}</div>}
                                            </>
                                        ) : (
                                            '-'
                                        )}
                                    </td>
                                    <td className="text-end">
                                        {(row.canDecide || row.canCancel) && (
                                            <RowActions>
                                                {row.canDecide && (
                                                    <RowActionItem icon="ti-check" onClick={() => open('validate', row)}>
                                                        Valider le transfert
                                                    </RowActionItem>
                                                )}
                                                {row.canDecide && (
                                                    <RowActionItem icon="ti-x" danger onClick={() => open('refuse', row)}>
                                                        Refuser
                                                    </RowActionItem>
                                                )}
                                                {row.canCancel && (
                                                    <RowActionItem icon="ti-trash" danger onClick={() => open('cancel', row)}>
                                                        Annuler la demande
                                                    </RowActionItem>
                                                )}
                                            </RowActions>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                        <Pagination paginator={transfers} />
                    </>
                )}
            </Card>

            {decision && dialog && (
                <ConfirmDialog
                    show
                    title={dialog.title}
                    recordLabel={`${decision.row.student.nomComplet} (${decision.row.reference})`}
                    message={dialog.message}
                    error={error}
                    processing={processing}
                    icon={dialog.icon}
                    variant={dialog.variant}
                    confirmLabel={dialog.confirmLabel}
                    processingLabel={dialog.processingLabel}
                    onConfirm={submit}
                    onCancel={close}
                >
                    {decision.kind !== 'validate' && (
                        <div className="text-start mb-3">
                            <label className="form-label" htmlFor="tre-motif">
                                Motif{decision.kind === 'refuse' && <span className="text-danger ms-1">*</span>}
                            </label>
                            <textarea
                                id="tre-motif"
                                className="form-control"
                                rows={3}
                                value={motif}
                                onChange={(event) => setMotif(event.target.value)}
                                maxLength={1000}
                            />
                        </div>
                    )}
                </ConfirmDialog>
            )}
        </BackofficeLayout>
    );
}
