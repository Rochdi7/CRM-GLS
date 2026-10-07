import { useForm } from '@inertiajs/react';
import { useEffect, type ChangeEvent, type FormEvent } from 'react';
import Modal from '@/Components/Modals/Modal';
import DateField from '@/Components/Forms/DateField';
import FormField from '@/Components/Forms/FormField';
import TextareaField from '@/Components/Forms/TextareaField';
import FormActions from '@/Components/Forms/FormActions';
import type { PaymentLine } from '@/Types';

/** Encaissement::METHODE_VIREMENT — la méthode qui ouvre ce modal au lieu d'encaisser. */
export const METHODE_VIREMENT = 'Virement';

/**
 * Demande de VIREMENT (07/10/2026). Choisir « Virement » sur une ligne de
 * frais n'encaisse rien : ça ouvre ce modal qui DÉCLARE le virement — nom du
 * payeur, référence bancaire, justificatif — pour que le comptable le
 * vérifie sur le relevé puis le valide. L'encaissement naît à ce moment-là,
 * daté de `date_operation` : le jour où l'étudiant s'est présenté.
 *
 * Composant PARTAGÉ par les deux modals « Enregistrer un paiement » — page
 * Paiements et page Inscriptions (07/10/2026) : sans lui la méthode
 * manquait sur la page Inscriptions, celle d'où le guichet encaisse le plus
 * souvent, et un virement y devenait impossible à déclarer.
 */
interface VirementFormState {
    student_id: number | '';
    inscription_id: number | '';
    fee_id: number | '';
    montant: string;
    nom_payeur: string;
    reference_virement: string;
    date_operation: string;
    note: string;
    justificatif: File | null;
}

function today(): string {
    return new Date().toISOString().slice(0, 10);
}

function emptyVirementForm(): VirementFormState {
    return {
        student_id: '',
        inscription_id: '',
        fee_id: '',
        montant: '',
        nom_payeur: '',
        reference_virement: '',
        date_operation: today(),
        note: '',
        justificatif: null,
    };
}

/** Reste dû du frais moins les virements déjà en attente — le plafond que DemanderVirement applique. */
export function virementResteDeclarable(line: PaymentLine): number {
    return Math.max(0, Number(line.reste) - Number(line.virementEnAttente ?? 0));
}

/**
 * Tout le reste de la ligne est déjà déclaré par virement : elle attend le
 * comptable, rien ne se paie plus dessus — la ligne le MONTRE (méthode
 * « Virement » verrouillée, badge « à vérifier ») au lieu de retomber sur
 * « Espèces » comme si rien n'avait été fait.
 */
export function virementCouvreLaLigne(line: PaymentLine): boolean {
    return Number(line.virementEnAttente ?? 0) > 0 && virementResteDeclarable(line) <= 0;
}

interface DemandeVirementModalProps {
    /** La ligne de frais déclarée ; `null` = modal fermé. */
    line: PaymentLine | null;
    studentId: number | '';
    inscriptionId: number | '';
    studentLabel: string;
    inscriptionLabel: string;
    /** `payments.update-date` (super-admin) : seul lui choisit la date de l'opération. */
    canUpdateDate: boolean;
    /** Page d'où le modal est ouvert — le serveur y revient (clé de liste blanche). */
    retour?: 'encaissements' | 'inscriptions';
    /** Demande abandonnée. */
    onCancel: () => void;
    /** Demande enregistrée — `montant` déclaré, désormais « en attente ». */
    onDeclared: (montant: number) => void;
}

export default function DemandeVirementModal({
    line,
    studentId,
    inscriptionId,
    studentLabel,
    inscriptionLabel,
    canUpdateDate,
    retour = 'encaissements',
    onCancel,
    onDeclared,
}: DemandeVirementModalProps) {
    const form = useForm<VirementFormState>(emptyVirementForm());

    // Chaque ouverture repart de la ligne choisie.
    useEffect(() => {
        if (line === null) {
            return;
        }

        form.clearErrors();
        form.setData({
            ...emptyVirementForm(),
            student_id: studentId,
            inscription_id: inscriptionId,
            fee_id: line.feeId,
            // Pré-rempli avec ce que la ligne porte déjà, sinon ce qu'il
            // reste à payer MOINS les virements déjà déclarés dessus.
            montant: line.montant !== '' ? line.montant : virementResteDeclarable(line).toFixed(2),
            // VirementController@store force le jour même pour qui n'a pas
            // `payments.update-date`.
            date_operation: (canUpdateDate ? line.datePaiement : '') || today(),
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [line]);

    function close() {
        form.reset();
        form.clearErrors();
        onCancel();
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        form.transform((data) => ({ ...data, retour }));
        form.post('/backoffice/virements', {
            preserveScroll: true,
            // Le modal « Enregistrer un paiement » reste ouvert derrière :
            // la ligne déclarée y passe « en attente ».
            preserveState: true,
            forceFormData: true,
            onSuccess: () => {
                const montant = Number(form.data.montant || 0);
                form.reset();
                form.clearErrors();
                onDeclared(montant);
            },
        });
    }

    return (
        <Modal
            show={line !== null}
            title="Déclarer un virement"
            onClose={close}
            processing={form.processing}
            size="lg"
            footer={
                <FormActions
                    form="virement-form"
                    onCancel={close}
                    processing={form.processing}
                    submitLabel="Envoyer la demande"
                    processingLabel="Envoi…"
                />
            }
        >
            {line && (
                <form id="virement-form" onSubmit={submit}>
                    <div className="row">
                        <div className="col-md-6 mb-3">
                            <div className="text-muted small mb-1">Étudiant</div>
                            <div className="fw-medium text-uppercase">{studentLabel || '-'}</div>
                        </div>
                        <div className="col-md-6 mb-3">
                            <div className="text-muted small mb-1">Inscription</div>
                            <div className="fw-medium">{inscriptionLabel || '-'}</div>
                        </div>
                        <div className="col-md-6 mb-3">
                            <div className="text-muted small mb-1">Frais réglé</div>
                            <div className="fw-medium text-uppercase">
                                {line.nom}
                                {line.dateEcheance && (
                                    <span className="text-muted fw-normal text-normal-case ms-2">
                                        (échéance {line.dateEcheance})
                                    </span>
                                )}
                            </div>
                        </div>
                        <div className="col-md-6 mb-3">
                            <div className="text-muted small mb-1">Reste à payer</div>
                            <div className="fw-medium">
                                {Number(line.reste).toFixed(2)} MAD
                                {Number(line.virementEnAttente ?? 0) > 0 && (
                                    <span className="text-warning fw-normal text-normal-case ms-2 fs-12">
                                        dont {Number(line.virementEnAttente).toFixed(2)} MAD déjà déclarés par virement
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                    <div className="row">
                        <div className="col-md-6">
                            <FormField
                                id="vir-montant"
                                label="Montant du virement (MAD)"
                                type="number"
                                step="0.01"
                                min="0.01"
                                max={virementResteDeclarable(line)}
                                required
                                value={form.data.montant}
                                onChange={(e) => form.setData('montant', e.target.value)}
                                error={form.errors.montant}
                            />
                        </div>
                        <div className="col-md-6">
                            {/*
                              * Aujourd'hui par défaut ; seul le super-admin
                              * (`payments.update-date`) la modifie.
                              * VirementController@store force la date du
                              * jour pour tous les autres.
                              */}
                            <DateField
                                id="vir-date-operation"
                                label="Date de l'opération"
                                required
                                disabled={!canUpdateDate}
                                value={form.data.date_operation}
                                onChange={(e) => form.setData('date_operation', e.target.value)}
                                error={form.errors.date_operation}
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="vir-nom-payeur"
                                label="Nom du payeur"
                                required
                                maxLength={150}
                                placeholder="Tel qu'il figure sur l'ordre de virement"
                                value={form.data.nom_payeur}
                                onChange={(e) => form.setData('nom_payeur', e.target.value)}
                                error={form.errors.nom_payeur}
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="vir-reference"
                                label="Référence du virement"
                                required
                                maxLength={100}
                                placeholder="Référence bancaire de l'opération"
                                value={form.data.reference_virement}
                                onChange={(e) => form.setData('reference_virement', e.target.value)}
                                error={form.errors.reference_virement}
                            />
                        </div>
                        <div className="col-12 mb-3">
                            <label className="form-label" htmlFor="vir-justificatif">
                                Justificatif du virement<span className="text-danger ms-1">*</span>
                            </label>
                            <input
                                id="vir-justificatif"
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp,.pdf"
                                required
                                className={`form-control${form.errors.justificatif ? ' is-invalid' : ''}`}
                                onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                    form.setData('justificatif', event.target.files?.[0] ?? null)
                                }
                            />
                            {form.errors.justificatif && (
                                <div className="invalid-feedback d-block">{form.errors.justificatif}</div>
                            )}
                            <div className="form-text text-normal-case">Capture ou scan de l'ordre de virement (image ou PDF, 5 Mo max).</div>
                        </div>
                        <div className="col-12">
                            <TextareaField
                                id="vir-note"
                                label="Note"
                                rows={2}
                                value={form.data.note}
                                onChange={(e) => form.setData('note', e.target.value)}
                                error={form.errors.note}
                            />
                        </div>
                    </div>
                    {(form.errors.fee_id || form.errors.inscription_id || form.errors.student_id) && (
                        <div className="alert alert-danger text-normal-case mb-3">
                            {form.errors.fee_id ?? form.errors.inscription_id ?? form.errors.student_id}
                        </div>
                    )}
                    <div className="alert alert-info text-normal-case mb-0">
                        <i className="ti ti-info-circle me-1" aria-hidden="true" />
                        Rien n'est encaissé maintenant : le comptable vérifie le virement sur le relevé bancaire,
                        puis le valide. Le paiement sera alors enregistré à la date de l'opération ci-dessus.
                    </div>
                </form>
            )}
        </Modal>
    );
}
