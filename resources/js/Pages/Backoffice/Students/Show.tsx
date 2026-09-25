import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DetailRow from '@/Components/Details/DetailRow';
import StatusBadge from '@/Components/Details/StatusBadge';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import { statutVariant } from '@/Lib/inscriptionStatut';
import { useState } from 'react';
import AbsencesPanel, { presenceVariant } from '@/Components/Attendance/AbsencesPanel';
import type { AbsencesEtudiant, StudentDetails } from '@/Types';

interface StudentShowProps {
    student: StudentDetails;
    absences: AbsencesEtudiant;
}

function SexeLabel({ sexe }: { sexe: string | null }) {
    if (sexe === 'Homme') {
        return (
            <span className="d-inline-flex align-items-center">
                <i className="ti ti-man fs-16 text-primary" />
            </span>
        );
    }
    if (sexe === 'Femme') {
        return (
            <span className="d-inline-flex align-items-center">
                <i className="ti ti-woman fs-16 text-pink" />
            </span>
        );
    }
    return <>-</>;
}

/**
 * Replaces resources/views/backoffice/students/show.blade.php exactly —
 * identity card, parent/guardian section (only when present), registrations
 * table, payments table with total. No edit controls (none existed on this
 * page). Read-only.
 */
export default function StudentShow({ student, absences }: StudentShowProps) {
    const [tab, setTab] = useState<'infos' | 'absences' | 'historique'>('infos');
    // Onglet « Historique » : seulement pour une fiche issue d'un transfert.
    const aHistorique = student.historiqueTransfert.length > 0;

    return (
        <BackofficeLayout
            title={student.nomComplet}
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Étudiants', href: '/backoffice/students' },
                { label: student.nomComplet },
            ]}
        >
            {student.statut === 'Transféré' && (
                <div className="alert alert-warning d-flex align-items-start" role="alert">
                    <i className="ti ti-arrows-exchange me-2 mt-1 fs-18" aria-hidden="true" />
                    <div>
                        <p className="fw-semibold mb-1">Étudiant transféré vers un autre centre</p>
                        <p className="mb-0 fs-13">
                            Cette fiche est close ici : elle garde ses présences et son historique, mais son dossier vivant et ses
                            paiements sont sur sa nouvelle fiche
                            {student.transfereVers && (
                                <>
                                    {' '}
                                    à {student.transfereVers.centre ?? '-'} :{' '}
                                    <a href={`/backoffice/students/${student.transfereVers.id}`}>
                                        <code>{student.transfereVers.reference}</code>
                                    </a>
                                </>
                            )}
                            .
                        </p>
                    </div>
                </div>
            )}

            <ul className="nav nav-tabs nav-tabs-solid nav-tabs-rounded-fill mb-3" role="tablist">
                <li className="me-2 mb-2" role="presentation">
                    <button
                        type="button"
                        className={`nav-link rounded${tab === 'infos' ? ' active' : ''}`}
                        onClick={() => setTab('infos')}
                    >
                        <i className="ti ti-user me-1" />
                        Informations
                    </button>
                </li>
                <li className="me-2 mb-2" role="presentation">
                    <button
                        type="button"
                        className={`nav-link rounded${tab === 'absences' ? ' active' : ''}`}
                        onClick={() => setTab('absences')}
                    >
                        <i className="ti ti-calendar-x me-1" />
                        Absences ({absences.absences.length})
                    </button>
                </li>
                {aHistorique && (
                    <li className="me-2 mb-2" role="presentation">
                        <button
                            type="button"
                            className={`nav-link rounded${tab === 'historique' ? ' active' : ''}`}
                            onClick={() => setTab('historique')}
                        >
                            <i className="ti ti-history me-1" />
                            Historique
                        </button>
                    </li>
                )}
            </ul>

            {tab === 'absences' && (
                <AbsencesPanel
                    data={absences}
                    scope={`Absences de ${student.nomComplet} à ${student.centre ?? 'son centre'} uniquement, tous groupes de ce centre.${
                        student.historiqueTransfert.length > 0
                            ? " Celles du centre précédent ne sont pas comptées ici : elles sont dans l'onglet Historique."
                            : ''
                    }${student.statut === 'Transféré' ? ' Fiche close : ce sont les appels faits dans ce centre avant le transfert.' : ''}`}
                />
            )}

            {tab === 'historique' && aHistorique && (
                <>
                    {student.historiqueTransfert.map((h) => (
                        <Card
                            key={h.id}
                            title={`Historique - ${h.centre ?? 'centre précédent'}`}
                            tools={
                                <span className="badge badge-soft-secondary">
                                    <i className="ti ti-arrows-exchange me-1" />
                                    Fiche d'origine <code className="ms-1">{h.reference}</code>
                                </span>
                            }
                        >
                            <p className="text-muted fs-13 mb-3">Dossiers et appels du centre de départ. Consultation seule.</p>

                            <div className="d-flex flex-wrap gap-2 mb-3">
                                <span className="badge badge-soft-dark fs-13">{h.presencesTotal} appel(s)</span>
                                {Object.entries(h.compteurs).map(([statut, n]) => (
                                    <span key={statut} className={`badge badge-soft-${presenceVariant(statut)} fs-13`}>
                                        {statut} : {n}
                                    </span>
                                ))}
                            </div>

                            <h6 className="mb-2">Dossiers</h6>
                            <RelatedRecordsTable
                                isEmpty={h.inscriptions.length === 0}
                                emptyTitle="Aucun dossier dans ce centre"
                                emptyIcon="ti ti-clipboard-list"
                                head={
                                    <tr>
                                        <th>Référence</th>
                                        <th>Groupe</th>
                                        <th>Année</th>
                                        <th>Période</th>
                                        <th>Statut</th>
                                    </tr>
                                }
                            >
                                {h.inscriptions.map((insc) => (
                                    <tr key={insc.reference}>
                                        <td>
                                            <code>{insc.reference}</code>
                                        </td>
                                        <td>{insc.groupe ?? '-'}</td>
                                        <td>{insc.anneeScolaire ?? '-'}</td>
                                        <td>
                                            {insc.dateDebut ?? '-'}
                                            {insc.dateFin ? ` → ${insc.dateFin}` : ''}
                                        </td>
                                        <td>
                                            <StatusBadge label={insc.statut} variant={statutVariant(insc.statut)} />
                                        </td>
                                    </tr>
                                ))}
                            </RelatedRecordsTable>

                            <h6 className="mt-4 mb-2">Présences et absences</h6>
                            <div style={{ maxHeight: 420, overflowY: 'auto' }}>
                                <RelatedRecordsTable
                                    isEmpty={h.presences.length === 0}
                                    emptyTitle="Aucun appel enregistré dans ce centre"
                                    emptyIcon="ti ti-calendar-check"
                                    head={
                                        <tr>
                                            <th>Date</th>
                                            <th>Heure</th>
                                            <th>Groupe</th>
                                            <th>Statut</th>
                                            <th>Note</th>
                                        </tr>
                                    }
                                >
                                    {h.presences.map((p) => (
                                        <tr key={p.id}>
                                            <td>{p.date}</td>
                                            <td>{p.heure ?? '-'}</td>
                                            <td>{p.groupe ?? '-'}</td>
                                            <td>
                                                <StatusBadge label={p.statut} variant={presenceVariant(p.statut)} dot />
                                            </td>
                                            <td className="text-normal-case">{p.note ?? '-'}</td>
                                        </tr>
                                    ))}
                                </RelatedRecordsTable>
                            </div>
                        </Card>
                    ))}

                </>
            )}

            {/* Tout le contenu « Informations » — plusieurs .row : un seul conteneur masqué. */}
            <div hidden={tab !== 'infos'}>
            <div className="row">
                <div className="col-xl-4">
                    <Card>
                        <div className="text-center mb-3">
                            <span className="avatar avatar-xxl rounded-circle bg-primary-transparent d-inline-flex align-items-center justify-content-center overflow-hidden mb-2">
                                {student.photoUrl ? (
                                    <img src={student.photoUrl} alt="" className="w-100 h-100" style={{ objectFit: 'cover' }} />
                                ) : (
                                    <span className="fs-24 fw-bold text-primary">{student.prenom.charAt(0).toUpperCase()}</span>
                                )}
                            </span>
                            <h5 className="mb-1">{student.nomComplet}</h5>
                            <p className="text-muted mb-2">
                                <code>{student.reference}</code>
                            </p>
                            {student.niveau && (
                                <>
                                    <StatusBadge label={student.niveau} />
                                    {student.orientation && (
                                        <span className="badge badge-soft-secondary ms-1">{student.orientation}</span>
                                    )}
                                </>
                            )}
                        </div>
                        <div className="border-top pt-3">
                            <DetailRow label="Genre" value={<SexeLabel sexe={student.sexe} />} />
                            <DetailRow label="Date de naissance" value={student.dateNaissance} />
                            <DetailRow label="CIN" value={student.cin} />
                            <DetailRow label="Téléphone" value={student.telephone} />
                            <DetailRow label="WhatsApp" value={student.whatsapp} />
                            <DetailRow label="Email" value={student.email} />
                            <DetailRow label="Adresse" value={student.adresse} />
                            <DetailRow label="Centre" value={student.centre} />
                        </div>
                        {student.parent && (
                            <div className="border-top pt-3 mt-1">
                                <h6 className="text-muted mb-2">Parent / tuteur</h6>
                                <DetailRow label="Catégorie" value={student.parent.relation} />
                                <DetailRow label="Nom du parent" value={student.parent.nom} />
                                <DetailRow
                                    label="Genre"
                                    value={
                                        student.parent.sexe ? (student.parent.sexe === 'Homme' ? 'Masculin' : 'Féminin') : null
                                    }
                                />
                                <DetailRow label="CIN" value={student.parent.cin} />
                                <DetailRow label="Téléphone du parent" value={student.parent.telephone} />
                            </div>
                        )}
                    </Card>
                </div>

                <div className="col-xl-8">
                    {student.inscriptionsParAnnee.length === 0 && (
                        <Card title="Inscriptions" tools={<span className="badge badge-soft-secondary">0</span>}>
                            <RelatedRecordsTable
                                isEmpty
                                emptyTitle="Aucune inscription"
                                emptyIcon="ti ti-clipboard-list"
                                head={<tr />}
                            >
                                {null}
                            </RelatedRecordsTable>
                        </Card>
                    )}

                    {student.inscriptionsParAnnee.map((groupe) => (
                        <Card
                            key={groupe.annee ?? 'sans-annee'}
                            title={`Inscriptions - ${groupe.annee ?? 'Sans année scolaire'}`}
                            tools={<span className="badge badge-soft-secondary">{groupe.inscriptions.length}</span>}
                        >
                            <RelatedRecordsTable
                                isEmpty={groupe.inscriptions.length === 0}
                                emptyTitle="Aucune inscription"
                                emptyIcon="ti ti-clipboard-list"
                                head={
                                    <tr>
                                        <th>Référence</th>
                                        <th>Groupe</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Statut</th>
                                    </tr>
                                }
                            >
                                {groupe.inscriptions.map((insc) => (
                                    <tr key={insc.reference}>
                                        <td>
                                            <code>{insc.reference}</code>
                                        </td>
                                        <td>{insc.groupe ?? '-'}</td>
                                        <td>{insc.date ?? '-'}</td>
                                        <td>{insc.total ? `${Number(insc.total).toFixed(2)} MAD` : '-'}</td>
                                        <td>
                                            <StatusBadge label={insc.statut} />
                                        </td>
                                    </tr>
                                ))}
                            </RelatedRecordsTable>
                        </Card>
                    ))}

                    {student.paiementsTransferes && (
                        <Card
                            title={`Paiements transférés vers ${student.paiementsTransferes.centre ?? 'le nouveau centre'}`}
                            tools={
                                <span className="badge badge-soft-info">
                                    {Number(student.paiementsTransferes.total).toFixed(2)} MAD
                                </span>
                            }
                        >
                            <p className="text-muted fs-13 mb-3">
                                Ces paiements ont suivi l'étudiant lors du transfert. Ils figurent désormais sur sa nouvelle fiche
                                {student.paiementsTransferes.vers && (
                                    <>
                                        {' '}
                                        <code>{student.paiementsTransferes.vers}</code>
                                    </>
                                )}
                                , avec la même date, la même méthode et la même caisse. Consultation seule.
                            </p>
                            <RelatedRecordsTable
                                isEmpty={student.paiementsTransferes.lignes.length === 0}
                                emptyTitle="Aucun paiement n'a été transféré"
                                emptyIcon="ti ti-report-money"
                                head={
                                    <tr>
                                        <th>Référence</th>
                                        <th>Type</th>
                                        <th>Montant</th>
                                        <th>Méthode</th>
                                        <th>Date</th>
                                        <th>Caisse</th>
                                    </tr>
                                }
                            >
                                {student.paiementsTransferes.lignes.map((payment) => (
                                    <tr key={payment.reference}>
                                        <td>
                                            <code>{payment.reference}</code>
                                        </td>
                                        <td>{payment.type}</td>
                                        <td className="fw-medium">{Number(payment.montant).toFixed(2)} MAD</td>
                                        <td>{payment.methode}</td>
                                        <td>{payment.date ?? '-'}</td>
                                        <td>{payment.caisse ?? '-'}</td>
                                    </tr>
                                ))}
                            </RelatedRecordsTable>
                        </Card>
                    )}

                    {!(student.paiementsTransferes && student.paiements.length === 0) && (
                        <Card
                            title={
                                student.paiementsScope
                                    ? `Paiements - inscription ${student.paiementsScope.toLowerCase()}`
                                    : 'Paiements'
                            }
                            tools={
                                <span className="badge badge-soft-success">{Number(student.paiementsTotal).toFixed(2)} MAD</span>
                            }
                        >
                            <RelatedRecordsTable
                                isEmpty={student.paiements.length === 0}
                                emptyTitle="Aucun paiement"
                                emptyIcon="ti ti-report-money"
                                head={
                                    <tr>
                                        <th>Référence</th>
                                        <th>Montant</th>
                                        <th>Méthode</th>
                                        <th>Date</th>
                                        <th>Caisse</th>
                                    </tr>
                                }
                            >
                                {student.paiements.map((payment) => (
                                    <tr key={payment.reference}>
                                        <td>
                                            <code>{payment.reference}</code>
                                        </td>
                                        <td className="fw-medium">{Number(payment.montant).toFixed(2)} MAD</td>
                                        <td>{payment.methode}</td>
                                        <td>{payment.date ?? '-'}</td>
                                        <td>{payment.caisse ?? '-'}</td>
                                    </tr>
                                ))}
                            </RelatedRecordsTable>
                        </Card>
                    )}
                </div>
            </div>
            </div>
        </BackofficeLayout>
    );
}
