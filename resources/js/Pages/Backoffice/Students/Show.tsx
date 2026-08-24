import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DetailRow from '@/Components/Details/DetailRow';
import StatusBadge from '@/Components/Details/StatusBadge';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import type { StudentDetails } from '@/Types';

interface StudentShowProps {
    student: StudentDetails;
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
    return <>—</>;
}

/**
 * Replaces resources/views/backoffice/students/show.blade.php exactly —
 * identity card, parent/guardian section (only when present), registrations
 * table, payments table with total. No edit controls (none existed on this
 * page). Read-only.
 */
export default function StudentShow({ student }: StudentShowProps) {
    return (
        <BackofficeLayout
            title={student.nomComplet}
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Étudiants', href: '/backoffice/students' },
                { label: student.nomComplet },
            ]}
        >
            <div className="row">
                <div className="col-xl-4">
                    <Card>
                        <div className="text-center mb-3">
                            <span className="avatar avatar-xxl rounded-circle bg-primary-transparent d-inline-flex align-items-center justify-content-center overflow-hidden mb-2">
                                {student.photoUrl ? (
                                    <img
                                        src={student.photoUrl}
                                        alt=""
                                        className="w-100 h-100"
                                        style={{ objectFit: 'cover' }}
                                    />
                                ) : (
                                    <span className="fs-24 fw-bold text-primary">
                                        {student.prenom.charAt(0).toUpperCase()}
                                    </span>
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
                                        student.parent.sexe
                                            ? student.parent.sexe === 'Homme'
                                                ? 'Masculin'
                                                : 'Féminin'
                                            : null
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
                            title={`Inscriptions — ${groupe.annee ?? 'Sans année scolaire'}`}
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
                                        <td>{insc.groupe ?? '—'}</td>
                                        <td>{insc.date ?? '—'}</td>
                                        <td>{insc.total ? `${Number(insc.total).toFixed(2)} MAD` : '—'}</td>
                                        <td>
                                            <StatusBadge label={insc.statut} />
                                        </td>
                                    </tr>
                                ))}
                            </RelatedRecordsTable>
                        </Card>
                    ))}

                    <Card
                        title={
                            student.paiementsScope
                                ? `Paiements — inscription ${student.paiementsScope.toLowerCase()}`
                                : 'Paiements'
                        }
                        tools={
                            <span className="badge badge-soft-success">
                                {Number(student.paiementsTotal).toFixed(2)} MAD
                            </span>
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
                                    <td>{payment.date ?? '—'}</td>
                                    <td>{payment.caisse ?? '—'}</td>
                                </tr>
                            ))}
                        </RelatedRecordsTable>
                    </Card>
                </div>
            </div>
        </BackofficeLayout>
    );
}
