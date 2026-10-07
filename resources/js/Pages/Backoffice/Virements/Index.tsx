import { router } from '@inertiajs/react';
import { useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import TextareaField from '@/Components/Forms/TextareaField';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { useFilterReset } from '@/Hooks/useFilterReset';
import type { SelectOption, VirementRow, VirementsPageProps } from '@/Types';

const STATUT_EN_ATTENTE = 'En attente de vérification';

function statutVariant(statut: string): 'warning' | 'success' | 'danger' | 'secondary' {
    if (statut === 'Validé') return 'success';
    if (statut === 'Refusé') return 'danger';
    if (statut === STATUT_EN_ATTENTE) return 'warning';
    return 'secondary';
}

/**
 * Virements (07/10/2026) — un virement bancaire n'est plus encaissé
 * directement. Le guichet le DÉCLARE depuis « Enregistrer un paiement »
 * (méthode « Virement ») avec le payeur, la référence bancaire et le
 * justificatif ; le COMPTABLE le vérifie sur le relevé puis le valide — le
 * paiement est alors enregistré, daté du jour où l'étudiant s'est présenté —
 * ou le refuse avec un motif. Une demande ne bouge jamais une caisse par
 * elle-même ; la page des paiements compte les demandes en attente À PART
 * de « Montant total ».
 */
export default function VirementsIndex({ virements, montantTotal, enAttente, filters, statuts, canValidate }: VirementsPageProps) {
    const isLoading = useInertiaLoading();
    const [validerTarget, setValiderTarget] = useState<VirementRow | null>(null);
    const [validerError, setValiderError] = useState<string | undefined>(undefined);
    const [validerProcessing, setValiderProcessing] = useState(false);
    const [refuserTarget, setRefuserTarget] = useState<VirementRow | null>(null);
    const [refuserMotif, setRefuserMotif] = useState('');
    const [refuserError, setRefuserError] = useState<string | undefined>(undefined);
    const [refuserProcessing, setRefuserProcessing] = useState(false);
    const [detailsTarget, setDetailsTarget] = useState<VirementRow | null>(null);

    const statutOptions: SelectOption[] = statuts.map((s) => ({ value: s, label: s }));
    const ongletAVerifier = filters.statutFilter === STATUT_EN_ATTENTE;

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/virements',
            { ...filters, ...nextFilters, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    // « Réinitialiser » ramène sur ce qu'il reste à vérifier — l'état
    // d'arrivée de la page (URL canonique), pour que le bouton et
    // l'atterrissage disent la même chose.
    const filterReset = useFilterReset(filters, reload, { perPage: filters.perPage, statutFilter: STATUT_EN_ATTENTE });

    // --- Validation --------------------------------------------------------

    function openValider(row: VirementRow) {
        setValiderTarget(row);
        setValiderError(undefined);
    }

    function closeValider() {
        setValiderTarget(null);
        setValiderError(undefined);
        setValiderProcessing(false);
    }

    function runValider() {
        if (!validerTarget) {
            return;
        }

        setValiderProcessing(true);
        setValiderError(undefined);
        router.patch(
            `/backoffice/virements/${validerTarget.id}/valider`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => closeValider(),
                onError: (errors) => {
                    setValiderError(errors.statut ?? Object.values(errors)[0]);
                    setValiderProcessing(false);
                },
                onFinish: () => setValiderProcessing(false),
            },
        );
    }

    // --- Refus -------------------------------------------------------------

    function openRefuser(row: VirementRow) {
        setRefuserTarget(row);
        setRefuserMotif('');
        setRefuserError(undefined);
    }

    function closeRefuser() {
        setRefuserTarget(null);
        setRefuserMotif('');
        setRefuserError(undefined);
        setRefuserProcessing(false);
    }

    function runRefuser() {
        if (!refuserTarget) {
            return;
        }

        setRefuserProcessing(true);
        setRefuserError(undefined);
        router.patch(
            `/backoffice/virements/${refuserTarget.id}/refuser`,
            { motif: refuserMotif },
            {
                preserveScroll: true,
                onSuccess: () => closeRefuser(),
                onError: (errors) => {
                    setRefuserError(errors.motif ?? errors.statut ?? Object.values(errors)[0]);
                    setRefuserProcessing(false);
                },
                onFinish: () => setRefuserProcessing(false),
            },
        );
    }

    function renderResume(row: VirementRow) {
        return (
            <div className="row">
                <div className="col-md-6 mb-3">
                    <div className="text-muted small mb-1">Étudiant</div>
                    <div className="fw-medium text-uppercase">{row.studentNom ?? '-'}</div>
                </div>
                <div className="col-md-6 mb-3">
                    <div className="text-muted small mb-1">Montant</div>
                    <div className="fw-medium">{Number(row.montant).toFixed(2)} MAD</div>
                </div>
                <div className="col-md-6 mb-3">
                    <div className="text-muted small mb-1">Frais</div>
                    <div className="fw-medium text-uppercase">
                        {row.feeNom ?? '-'}
                        {row.inscriptionReference && (
                            <span className="text-muted fw-normal text-normal-case ms-2">({row.inscriptionReference})</span>
                        )}
                    </div>
                </div>
                <div className="col-md-6 mb-3">
                    <div className="text-muted small mb-1">Date de l'opération</div>
                    <div className="fw-medium">{row.dateOperation ?? '-'}</div>
                </div>
                <div className="col-md-6 mb-3">
                    <div className="text-muted small mb-1">Payeur</div>
                    <div className="fw-medium text-uppercase">{row.nomPayeur}</div>
                </div>
                <div className="col-md-6 mb-3">
                    <div className="text-muted small mb-1">Référence du virement</div>
                    <div className="fw-medium">
                        <code>{row.referenceVirement}</code>
                    </div>
                </div>
                <div className="col-md-6 mb-3">
                    <div className="text-muted small mb-1">Déclaré par</div>
                    <div className="fw-medium">
                        {row.demandeParNom ?? '-'}
                        {row.demandeLe && <span className="text-muted fw-normal ms-2">le {row.demandeLe}</span>}
                    </div>
                </div>
                <div className="col-md-6 mb-3">
                    <div className="text-muted small mb-1">Justificatif</div>
                    <div className="fw-medium">
                        {row.justificatifUrl ? (
                            <a href={row.justificatifUrl} target="_blank" rel="noreferrer" className="text-normal-case">
                                <i className="ti ti-photo me-1" aria-hidden="true" />
                                Ouvrir le justificatif
                            </a>
                        ) : (
                            '-'
                        )}
                    </div>
                </div>
                {row.note && (
                    <div className="col-12 mb-3">
                        <div className="text-muted small mb-1">Note</div>
                        <div className="text-normal-case">{row.note}</div>
                    </div>
                )}
            </div>
        );
    }

    return (
        <BackofficeLayout
            title="Virements"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Virements' }]}
        >
            {/* Cross-links back into Encaissements, mirroring that page's own tab bar (Encaissements /
                Avances / Chèques / Virements) — same convention as the Chèques page. */}
            <ul className="nav nav-tabs p-0 border-bottom rounded-0 mb-4" role="tablist">
                <li className="nav-item" role="presentation">
                    <a href="/backoffice/encaissements" className="nav-link d-inline-flex align-items-center">
                        <i className="ti ti-cash-banknote me-2" aria-hidden="true" />
                        Encaissements
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a href="/backoffice/encaissements?view=avance" className="nav-link d-inline-flex align-items-center">
                        <i className="ti ti-clock-dollar me-2" aria-hidden="true" />
                        Avances
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <a href="/backoffice/cheques" className="nav-link d-inline-flex align-items-center">
                        <i className="ti ti-building-bank me-2" aria-hidden="true" />
                        Chèques
                    </a>
                </li>
                <li className="nav-item" role="presentation">
                    <button
                        type="button"
                        className={`nav-link d-inline-flex align-items-center${ongletAVerifier ? ' active' : ''}`}
                        aria-current={ongletAVerifier ? 'page' : undefined}
                        onClick={() => !ongletAVerifier && reload({ statutFilter: STATUT_EN_ATTENTE })}
                    >
                        <i className="ti ti-checks me-2" aria-hidden="true" />
                        Virements à vérifier
                        {enAttente.count > 0 && <span className="badge bg-warning text-dark ms-2">{enAttente.count}</span>}
                    </button>
                </li>
                <li className="nav-item" role="presentation">
                    <button
                        type="button"
                        className={`nav-link d-inline-flex align-items-center${ongletAVerifier ? '' : ' active'}`}
                        aria-current={ongletAVerifier ? undefined : 'page'}
                        onClick={() => ongletAVerifier && reload({ statutFilter: '' })}
                    >
                        <i className="ti ti-transfer-in me-2" aria-hidden="true" />
                        Tous les virements
                    </button>
                </li>
            </ul>

            <Card title={ongletAVerifier ? 'Virements à vérifier' : 'Virements'} bodyClassName="p-0 py-3">
                <div className="px-3 pt-2">
                    <TableToolbar
                        onReset={filterReset.reset}
                        resetActive={filterReset.active}
                        search={<SearchInput value={filters.search} onSearch={(value) => reload({ search: value })} placeholder="Étudiant, référence, payeur…" />}
                    >
                        <div style={{ width: 230 }}>
                            <label className="form-label" htmlFor="vir-f-statut">
                                Statut
                            </label>
                            <SelectField
                                id="vir-f-statut"
                                options={statutOptions}
                                placeholder="Tous les statuts"
                                value={filters.statutFilter}
                                onChange={(event) => reload({ statutFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="vir-f-du">
                                Opération du
                            </label>
                            <DateField id="vir-f-du" value={filters.dateFrom} onChange={(event) => reload({ dateFrom: event.target.value })} />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="vir-f-au">
                                Opération au
                            </label>
                            <DateField id="vir-f-au" value={filters.dateTo} onChange={(event) => reload({ dateTo: event.target.value })} />
                        </div>
                    </TableToolbar>
                </div>

                <div className="d-flex flex-wrap align-items-center gap-3 px-3 mb-3">
                    <p className="fw-medium mb-0">
                        Montant total : {Number(montantTotal).toFixed(2)} MAD
                        <span className="text-muted fw-normal fs-13 ms-2 text-normal-case">(virements affichés)</span>
                    </p>
                    {/* L'argent en attente n'est PAS encaissé — c'est ce qui reste à vérifier. */}
                    <p className="fw-medium mb-0 text-warning text-normal-case">
                        En attente de vérification : {Number(enAttente.montant).toFixed(2)} MAD
                        <span className="badge bg-warning text-dark ms-2">{enAttente.count}</span>
                    </p>
                </div>

                {virements.data.length === 0 ? (
                    <EmptyState
                        title="Aucun virement"
                        message={
                            ongletAVerifier
                                ? 'Aucun virement à vérifier : les demandes déclarées depuis « Enregistrer un paiement » apparaissent ici.'
                                : 'Aucun virement ne correspond aux filtres.'
                        }
                        icon="ti ti-transfer-in"
                    />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Référence</th>
                                    <th>Date opération</th>
                                    <th>Étudiant</th>
                                    <th>Frais</th>
                                    <th>Montant</th>
                                    <th>Payeur</th>
                                    <th>Réf. virement</th>
                                    <th>Justificatif</th>
                                    <th>Déclaré par</th>
                                    <th>Statut</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {virements.data.map((row) => (
                                <tr key={row.id} className={row.statut === 'Refusé' ? 'text-muted' : undefined}>
                                    <td>
                                        <code className="text-normal-case">{row.reference}</code>
                                    </td>
                                    <td>{row.dateOperation ?? '-'}</td>
                                    <td className="fw-medium">
                                        {row.studentId ? (
                                            <a href={`/backoffice/students/${row.studentId}`}>{row.studentNom ?? '-'}</a>
                                        ) : (
                                            row.studentNom ?? '-'
                                        )}
                                    </td>
                                    <td>
                                        {row.feeNom ?? '-'}
                                        {row.groupeNom && <div className="fs-12 text-muted">{row.groupeNom}</div>}
                                    </td>
                                    <td className={`fw-medium${row.statut === 'Refusé' ? ' text-decoration-line-through' : ''}`}>
                                        {Number(row.montant).toFixed(2)} DH
                                    </td>
                                    <td>{row.nomPayeur}</td>
                                    <td>
                                        <code className="text-normal-case">{row.referenceVirement}</code>
                                    </td>
                                    <td>
                                        {row.justificatifUrl ? (
                                            <a href={row.justificatifUrl} target="_blank" rel="noreferrer" title="Voir le justificatif">
                                                <i className="ti ti-photo" />
                                            </a>
                                        ) : (
                                            '-'
                                        )}
                                    </td>
                                    <td>
                                        {row.demandeParNom ?? '-'}
                                        {row.demandeLe && <div className="fs-12 text-muted text-normal-case">{row.demandeLe}</div>}
                                    </td>
                                    <td>
                                        <div className="d-flex align-items-center gap-2">
                                            <StatusBadge label={row.statut} variant={statutVariant(row.statut)} dot />
                                            <button
                                                type="button"
                                                className="btn btn-link p-0"
                                                title="Détail de la demande"
                                                onClick={() => setDetailsTarget(row)}
                                            >
                                                <i className="ti ti-info-circle text-muted" />
                                            </button>
                                        </div>
                                        {row.statut === 'Validé' && row.encaissementReference && (
                                            <div className="fs-12 text-muted text-normal-case">
                                                → {row.encaissementReference}
                                                {row.decideParNom && ` · ${row.decideParNom}`}
                                            </div>
                                        )}
                                        {row.statut === 'Refusé' && row.motifRefus && (
                                            <div className="fs-12 text-danger text-normal-case">{row.motifRefus}</div>
                                        )}
                                    </td>
                                    <td className="text-end">
                                        {canValidate && row.statut === STATUT_EN_ATTENTE && (
                                            <RowActions>
                                                <RowActionItem icon="ti-checks" onClick={() => openValider(row)}>
                                                    Valider
                                                </RowActionItem>
                                                <RowActionItem icon="ti-x" danger onClick={() => openRefuser(row)}>
                                                    Refuser
                                                </RowActionItem>
                                            </RowActions>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                        <Pagination paginator={virements} />
                    </>
                )}
            </Card>

            {/* Détail d'une demande (lecture seule). */}
            <Modal
                show={detailsTarget !== null}
                title={detailsTarget ? `Virement ${detailsTarget.reference}` : 'Virement'}
                onClose={() => setDetailsTarget(null)}
                size="lg"
                footer={
                    <button type="button" className="btn btn-light" onClick={() => setDetailsTarget(null)}>
                        Fermer
                    </button>
                }
            >
                {detailsTarget && (
                    <>
                        {renderResume(detailsTarget)}
                        <div className="d-flex align-items-center gap-2">
                            <StatusBadge label={detailsTarget.statut} variant={statutVariant(detailsTarget.statut)} dot />
                            {detailsTarget.decideParNom && (
                                <span className="text-muted text-normal-case">
                                    par {detailsTarget.decideParNom}
                                    {detailsTarget.decideLe && ` le ${detailsTarget.decideLe}`}
                                </span>
                            )}
                        </div>
                        {detailsTarget.motifRefus && <div className="alert alert-danger text-normal-case mt-3 mb-0">{detailsTarget.motifRefus}</div>}
                    </>
                )}
            </Modal>

            {/* Décision du COMPTABLE : valider = le paiement est enregistré
                (compte « Virement » du centre crédité, daté du jour de
                l'opération). Les refus possibles viennent du SERVEUR. */}
            <Modal
                show={validerTarget !== null}
                title="Valider le virement"
                onClose={closeValider}
                processing={validerProcessing}
                size="lg"
                footer={
                    <>
                        <button type="button" className="btn btn-light" onClick={closeValider} disabled={validerProcessing}>
                            Annuler
                        </button>
                        <button
                            type="button"
                            className="btn btn-primary"
                            onClick={runValider}
                            disabled={validerProcessing || !!validerTarget?.validationBlocker || !!validerTarget?.autoValidation}
                        >
                            {validerProcessing ? 'Enregistrement…' : 'Valider : enregistrer le paiement'}
                        </button>
                    </>
                }
            >
                {validerTarget && (
                    <>
                        {renderResume(validerTarget)}
                        {validerTarget.autoValidation ? (
                            <div className="alert alert-warning text-normal-case mb-0">
                                Vous avez déclaré ce virement : un autre employé doit le valider.
                            </div>
                        ) : validerTarget.validationBlocker ? (
                            <div className="alert alert-warning text-normal-case mb-0">{validerTarget.validationBlocker}</div>
                        ) : (
                            <div className="alert alert-info text-normal-case mb-0">
                                <i className="ti ti-info-circle me-1" aria-hidden="true" />
                                Vérifiez que le virement figure bien sur le relevé bancaire. La validation enregistre le
                                paiement de {Number(validerTarget.montant).toFixed(2)} MAD à la date du {validerTarget.dateOperation ?? '-'}.
                            </div>
                        )}
                        {validerError && <div className="alert alert-danger text-normal-case mt-3 mb-0">{validerError}</div>}
                    </>
                )}
            </Modal>

            {/* Refus — motif obligatoire, la ligne reste avec son motif. */}
            <Modal
                show={refuserTarget !== null}
                title="Refuser le virement"
                onClose={closeRefuser}
                processing={refuserProcessing}
                size="lg"
                footer={
                    <>
                        <button type="button" className="btn btn-light" onClick={closeRefuser} disabled={refuserProcessing}>
                            Annuler
                        </button>
                        <button type="button" className="btn btn-danger" onClick={runRefuser} disabled={refuserProcessing || refuserMotif.trim().length < 3}>
                            {refuserProcessing ? 'Enregistrement…' : 'Refuser le virement'}
                        </button>
                    </>
                }
            >
                {refuserTarget && (
                    <>
                        {renderResume(refuserTarget)}
                        <TextareaField
                            id="vir-refus-motif"
                            label="Motif du refus"
                            required
                            rows={3}
                            placeholder="Ex. : aucun virement de ce montant sur le relevé du mois"
                            value={refuserMotif}
                            onChange={(event) => setRefuserMotif(event.target.value)}
                            error={refuserError}
                        />
                        <div className="alert alert-info text-normal-case mb-0">
                            Aucun argent ne bouge : rien n'avait été encaissé. La demande reste visible avec son motif.
                        </div>
                    </>
                )}
            </Modal>
        </BackofficeLayout>
    );
}
