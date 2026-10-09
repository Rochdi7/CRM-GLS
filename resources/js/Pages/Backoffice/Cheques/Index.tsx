import { router, useForm } from '@inertiajs/react';
import { useState, type ChangeEvent, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import FilterTextInput from '@/Components/Tables/FilterTextInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionDivider, RowActionItem } from '@/Components/Tables/RowActions';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import SelectField from '@/Components/Forms/SelectField';
import DateField from '@/Components/Forms/DateField';
import FormField from '@/Components/Forms/FormField';
import TextareaField from '@/Components/Forms/TextareaField';
import FormActions from '@/Components/Forms/FormActions';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { useFilterReset } from '@/Hooks/useFilterReset';
import { enCapitales } from '@/Lib/nom';
import type { ChequeRow, ChequesPageProps, PaiementRemplacantOption, SelectOption } from '@/Types';

interface ChequeFormState {
    source: string;
    student_id: number | '';
    proprietaire_nom: string;
    numero_cheque: string;
    montant: string;
    banque: string;
    date_reception: string;
    type: string;
    date_echeance: string;
    note: string;
    /** Photo / scan du chèque — obligatoire à la saisie, facultatif en modification. */
    photo: File | null;
}

interface RemiseFormState {
    date_remise: string;
    justificatif: File | null;
}

function todayIso(): string {
    return new Date().toISOString().slice(0, 10);
}

function emptyForm(): ChequeFormState {
    return {
        source: 'Étudiant',
        student_id: '',
        proprietaire_nom: '',
        numero_cheque: '',
        montant: '',
        banque: '',
        date_reception: new Date().toISOString().slice(0, 10),
        type: 'Garantie (À encaisser)',
        date_echeance: '',
        note: '',
        photo: null,
    };
}

/**
 * Builds the note pre-filled on a remboursement created from a rejected
 * chèque. Money records are never deleted (CLAUDE.md §11) — corrections are
 * compensating entries — so the refund row itself has to carry enough
 * context to be auditable on its own. Optional fields are skipped rather
 * than rendered as "null".
 */
function rejectedChequeNote(cheque: ChequeRow): string {
    const parts = [`Remboursement suite au rejet du chèque n° ${cheque.numeroCheque}`];

    if (cheque.banque) {
        parts.push(`banque : ${cheque.banque}`);
    }

    if (cheque.dateEcheance) {
        parts.push(`échéance : ${cheque.dateEcheance}`);
    }

    parts.push(`montant du chèque : ${cheque.montant} DH`);
    parts.push(`réf. chèque : ${cheque.reference}`);

    return `${parts.join(' - ')}.`;
}

/**
 * Libellé AFFICHÉ d'un type de chèque. « Garantie (À encaisser) » est la
 * valeur STOCKÉE — elle est en base sur chaque ligne, dans le journal
 * d'audit et dans les exports, et n'est pas réécrite (§11 : on ne
 * réinterprète pas une valeur stockée). Seul l'écran raccourcit, parce que
 * « (À encaisser) » n'apprend rien à qui lit la colonne Type : un chèque de
 * garantie est par définition là pour être encaissé si l'étudiant ne paie
 * pas. La valeur soumise par le formulaire reste la vraie.
 */
function typeLabel(type: string): string {
    return type === 'Garantie (À encaisser)' ? 'Garantie' : type;
}

function statutVariant(statut: string): 'primary' | 'warning' | 'success' | 'danger' | 'secondary' {
    if (statut === 'Annulé') return 'secondary';
    if (statut === 'Déposé') return 'warning';
    if (statut === 'Encaissé') return 'success';
    if (statut === 'Rejeté') return 'danger';
    // Le papier a quitté l'école et le dossier est clos : vert, comme
    // « Encaissé ». Il ne reste rien à faire sur cette ligne.
    if (statut === 'Restitué') return 'success';
    return 'primary';
}

/**
 * Chèques en main — off-ledger inventory of physical checks received
 * (garantie / à déposer). A chèque here never moves money by itself:
 * paying with one happens in the Encaissements page ("Payer avec un
 * chèque"), which creates a normal Encaissement linked back via cheque_id
 * — its montant sum drives the "Reste" column. Lifecycle: En possession
 * -> Déposé (Remise à la banque) -> Encaissé | Rejeté.
 */
export default function ChequesIndex({
    cheques,
    montantTotal,
    filters,
    sources,
    types,
    statuts,
    banques,
    students,
    parents,
    canCreate,
    canUpdate,
    canDeposit,
    canDelete,
    canValidateDeposit,
    canViewVirements,
    canCancel,
    chequeMimes,
    chequeMaxKb,
}: ChequesPageProps) {
    const isLoading = useInertiaLoading();
    const [showModal, setShowModal] = useState(false);
    const [editingCheque, setEditingCheque] = useState<ChequeRow | null>(null);
    const [statutTarget, setStatutTarget] = useState<{ cheque: ChequeRow; statut: 'Rejeté' | 'Déposé' } | null>(null);
    const [statutError, setStatutError] = useState<string | undefined>(undefined);
    const [statutProcessing, setStatutProcessing] = useState(false);
    // After a chèque is marked Rejeté, offer to open the refund form —
    // pre-filled only when it funded exactly one encaissement (the common
    // case); with zero or several linked payments the user is pointed to
    // record the refund(s) manually from the Remboursements tab instead.
    const [rejectedCheque, setRejectedCheque] = useState<ChequeRow | null>(null);
    const [retourTarget, setRetourTarget] = useState<ChequeRow | null>(null);
    const [retourError, setRetourError] = useState<string | undefined>(undefined);
    const [retourProcessing, setRetourProcessing] = useState(false);
    // Restitution d'un chèque de GARANTIE réglé autrement (espèces / TPE /
    // virement). Distinct de retourTarget ci-dessus, qui rend un chèque
    // REJETÉ : deux faits différents, deux conditions différentes, qui
    // écrivent les mêmes colonnes off-ledger.
    const [garantieTarget, setGarantieTarget] = useState<ChequeRow | null>(null);
    const [garantieMotif, setGarantieMotif] = useState('');
    const [garantieError, setGarantieError] = useState<string | undefined>(undefined);
    const [garantieProcessing, setGarantieProcessing] = useState(false);
    const [detailsCheque, setDetailsCheque] = useState<ChequeRow | null>(null);
    // Garantie : le paiement (espèces / TPE / virement) qui la remplace.
    // La liste vient du SERVEUR (même requête que l'action) — sans paiement
    // de remplacement, la garantie reste en main.
    const [garantiePaiements, setGarantiePaiements] = useState<PaiementRemplacantOption[] | null>(null);
    const [garantiePaiementId, setGarantiePaiementId] = useState<number | ''>('');
    // Remise à la banque (tous les rôles) : compte bancaire + date + reçu.
    const [remiseTarget, setRemiseTarget] = useState<ChequeRow | null>(null);
    const remiseForm = useForm<RemiseFormState>({ date_remise: todayIso(), justificatif: null });
    // Décision du comptable sur une remise.
    const [validationTarget, setValidationTarget] = useState<ChequeRow | null>(null);
    const [validationError, setValidationError] = useState<string | undefined>(undefined);
    const [validationProcessing, setValidationProcessing] = useState(false);
    // Suppression (super-admin) : le serveur refuse un chèque qui a financé
    // un encaissement, et l'erreur s'affiche dans le dialogue.
    // Annulation du chèque (comptable) : statut « Annulé », motif obligatoire.
    const [annulTarget, setAnnulTarget] = useState<ChequeRow | null>(null);
    const [annulMotif, setAnnulMotif] = useState('');
    const [annulError, setAnnulError] = useState<string | undefined>(undefined);
    const [annulProcessing, setAnnulProcessing] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<ChequeRow | null>(null);
    const [deleteError, setDeleteError] = useState<string | undefined>(undefined);
    const [deleteProcessing, setDeleteProcessing] = useState(false);

    const form = useForm<ChequeFormState>(emptyForm());

    const sourceOptions: SelectOption[] = sources.map((s) => ({ value: s, label: s }));
    // La VALEUR reste celle de la base (c'est elle qui est soumise et
    // filtrée) ; seul le libellé est raccourci.
    const typeOptions: SelectOption[] = types.map((t) => ({ value: t, label: typeLabel(t) }));
    const acceptMedia = chequeMimes.map((m) => `.${m}`).join(',');

    const statutFilterOptions: SelectOption[] = statuts.map((s) => ({ value: s, label: s }));
    const banqueOptions: SelectOption[] = banques.map((b) => ({ value: b, label: b }));
    const studentOptions: SelectOption[] = students.map((s) => ({ value: s.id, label: s.nom }));
    const parentOptions: SelectOption[] = parents.map((p) => ({
        value: p.parentNom,
        label: `${p.parentNom}${p.parentRelation ? ` (${p.parentRelation})` : ''} - ${p.studentNom}`,
    }));

    function reload(nextFilters: Partial<typeof filters>) {
        router.get(
            '/backoffice/cheques',
            { ...filters, ...nextFilters, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const filterReset = useFilterReset(filters, reload, { perPage: filters.perPage });

    function openCreate() {
        setEditingCheque(null);
        form.reset();
        form.clearErrors();
        form.setData(emptyForm());
        setShowModal(true);
    }

    function openEdit(cheque: ChequeRow) {
        setEditingCheque(cheque);
        form.clearErrors();
        form.setData({
            source: cheque.source,
            student_id: cheque.studentId ?? '',
            proprietaire_nom: cheque.proprietaireNom ?? '',
            numero_cheque: cheque.numeroCheque,
            montant: cheque.montant,
            banque: cheque.banque ?? '',
            date_reception: cheque.dateReception ?? '',
            type: cheque.type,
            date_echeance: cheque.dateEcheance ?? '',
            note: cheque.note,
            photo: null,
        });
        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditingCheque(null);
        form.reset();
        form.clearErrors();
    }

    function handleSourceChange(source: string) {
        form.setData((data) => ({
            ...data,
            source,
            student_id: source === 'Étudiant' ? data.student_id : '',
            proprietaire_nom: source === 'Étudiant' ? '' : data.proprietaire_nom,
        }));
    }

    function submit(event: FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => closeModal() };

        // Multipart (photo) : un PUT passe par POST + _method, comme Dépenses.
        if (editingCheque) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(`/backoffice/cheques/${editingCheque.id}`, {
                ...options,
                forceFormData: true,
                onFinish: () => form.transform((data) => data),
            });
        } else {
            form.post('/backoffice/cheques', { ...options, forceFormData: true });
        }
    }

    function openAnnuler(cheque: ChequeRow) {
        setAnnulTarget(cheque);
        setAnnulMotif('');
        setAnnulError(undefined);
    }

    function closeAnnuler() {
        setAnnulTarget(null);
        setAnnulMotif('');
        setAnnulError(undefined);
        setAnnulProcessing(false);
    }

    function runAnnuler() {
        if (!annulTarget) {
            return;
        }

        setAnnulProcessing(true);
        setAnnulError(undefined);
        router.patch(
            `/backoffice/cheques/${annulTarget.id}/annuler`,
            { motif: annulMotif },
            {
                preserveScroll: true,
                onSuccess: () => closeAnnuler(),
                onError: (errors) => setAnnulError(Object.values(errors)[0] ?? 'Action impossible.'),
                onFinish: () => setAnnulProcessing(false),
            },
        );
    }

    function confirmStatut(cheque: ChequeRow, statut: 'Rejeté' | 'Déposé') {
        setStatutTarget({ cheque, statut });
        setStatutError(undefined);
    }

    function handleStatutConfirm() {
        if (!statutTarget) {
            return;
        }

        const target = statutTarget;
        setStatutProcessing(true);
        router.patch(
            `/backoffice/cheques/${target.cheque.id}/statut`,
            { statut: target.statut },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setStatutTarget(null);
                    setStatutError(undefined);
                    if (target.statut === 'Rejeté') {
                        setRejectedCheque(target.cheque);
                    }
                },
                onError: (errors) => {
                    setStatutError(errors.statut ?? 'Action impossible.');
                },
                onFinish: () => setStatutProcessing(false),
            },
        );
    }

    /** Jumps to the Remboursements tab, pre-filled when the chèque funded exactly one encaissement. */
    function goToRemboursement(cheque: ChequeRow) {
        const params = new URLSearchParams({ tab: 'remboursements' });
        const single = cheque.encaissements.length === 1 ? cheque.encaissements[0] : null;

        if (single) {
            params.set('prefill_beneficiaire_id', String(single.studentId ?? ''));
            params.set('prefill_encaissement_id', String(single.id));
            params.set('prefill_montant', single.montant);
            params.set('prefill_motif', `Chèque ${cheque.numeroCheque} rejeté`);
            // The motif is the short reason; the note carries the traceable
            // detail so the refund still explains itself months later, without
            // the reader having to go find the chèque.
            params.set('prefill_note', rejectedChequeNote(cheque));
        }

        setRejectedCheque(null);
        router.get(`/backoffice/depenses?${params.toString()}`);
    }

    function confirmRetour(cheque: ChequeRow) {
        setRetourTarget(cheque);
        setRetourError(undefined);
    }

    function handleRetourConfirm() {
        if (!retourTarget) {
            return;
        }

        setRetourProcessing(true);
        router.patch(
            `/backoffice/cheques/${retourTarget.id}/retour`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRetourTarget(null);
                    setRetourError(undefined);
                },
                onError: (errors) => {
                    setRetourError(errors.statut ?? 'Action impossible.');
                },
                onFinish: () => setRetourProcessing(false),
            },
        );
    }

    function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        setDeleteProcessing(true);
        router.delete(`/backoffice/cheques/${deleteTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteTarget(null);
                setDeleteError(undefined);
            },
            onError: (errors) => {
                setDeleteError(errors.cheque ?? 'Suppression impossible.');
            },
            onFinish: () => setDeleteProcessing(false),
        });
    }

    // --- Restitution d'un chèque de GARANTIE (l'étudiant a réglé autrement) ---
    // Aucun argent ne bouge : un chèque est un inventaire off-ledger, et le
    // paiement qui le remplace est un encaissement ordinaire enregistré à
    // part, avec sa propre méthode. Le motif est OBLIGATOIRE — c'est ce que
    // le journal conservera pour expliquer pourquoi la garantie est sortie.
    function openRestituerGarantie(cheque: ChequeRow) {
        setGarantieTarget(cheque);
        setGarantieMotif('');
        setGarantieError(undefined);
        setGarantiePaiements(null);
        setGarantiePaiementId('');

        fetch(`/backoffice/cheques/${cheque.id}/remplacements`, { headers: { Accept: 'application/json' } })
            .then((response) => (response.ok ? response.json() : Promise.reject(response)))
            .then((data: { paiements: PaiementRemplacantOption[] }) => {
                setGarantiePaiements(data.paiements);
                if (data.paiements.length === 1) {
                    setGarantiePaiementId(data.paiements[0].id);
                }
            })
            .catch(() => {
                setGarantiePaiements([]);
                setGarantieError('Impossible de charger les paiements de cet étudiant.');
            });
    }

    function closeRestituerGarantie() {
        setGarantieTarget(null);
        setGarantieMotif('');
        setGarantieError(undefined);
        setGarantieProcessing(false);
        setGarantiePaiements(null);
        setGarantiePaiementId('');
    }

    function runRestituerGarantie() {
        if (!garantieTarget) {
            return;
        }

        setGarantieProcessing(true);
        setGarantieError(undefined);

        router.patch(
            `/backoffice/cheques/${garantieTarget.id}/restituer-garantie`,
            { motif: garantieMotif, encaissement_id: garantiePaiementId === '' ? null : garantiePaiementId },
            {
                preserveScroll: true,
                onSuccess: () => closeRestituerGarantie(),
                onError: (errors) => {
                    setGarantieProcessing(false);
                    setGarantieError(Object.values(errors)[0] ?? "L'opération a échoué.");
                },
                onFinish: () => setGarantieProcessing(false),
            },
        );
    }

    // --- Remise à la banque (tous les rôles) -----------------------------------
    // Aucun argent ne bouge : c'est une DEMANDE, que le comptable accepte ou
    // rejette. Le reçu de dépôt est obligatoire.
    function openRemise(cheque: ChequeRow) {
        setRemiseTarget(cheque);
        remiseForm.clearErrors();
        remiseForm.setData({
            date_remise: todayIso(),
            justificatif: null,
        });
    }

    function closeRemise() {
        setRemiseTarget(null);
        remiseForm.reset();
        remiseForm.clearErrors();
    }

    function submitRemise(event: FormEvent) {
        event.preventDefault();
        if (!remiseTarget) {
            return;
        }

        remiseForm.post(`/backoffice/cheques/${remiseTarget.id}/remise-banque`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => closeRemise(),
        });
    }

    // --- Décision du comptable ---------------------------------------------
    function openValidation(cheque: ChequeRow) {
        setValidationTarget(cheque);
        setValidationError(undefined);
    }

    function closeValidation() {
        setValidationTarget(null);
        setValidationError(undefined);
        setValidationProcessing(false);
    }

    function decideValidation(statut: 'Encaissé' | 'Rejeté') {
        if (!validationTarget) {
            return;
        }

        setValidationProcessing(true);
        setValidationError(undefined);
        router.patch(
            `/backoffice/cheques/${validationTarget.id}/statut`,
            { statut },
            {
                preserveScroll: true,
                onSuccess: () => {
                    const cheque = validationTarget;
                    closeValidation();
                    if (statut === 'Rejeté') {
                        setRejectedCheque(cheque);
                    }
                },
                onError: (errors) => setValidationError(Object.values(errors)[0] ?? 'Action impossible.'),
                onFinish: () => setValidationProcessing(false),
            },
        );
    }

    const statutCopy: Record<'Rejeté' | 'Déposé', { title: string; message: string; icon: string; variant: 'primary' | 'danger'; confirmLabel: string; processingLabel: string }> = {
        // « Rejeté » posé par erreur : le chèque revient « Déposé ».
        'Déposé': {
            title: 'Annuler le rejet',
            message: 'Le chèque revient à « Déposé » et attend de nouveau la décision.',
            icon: 'ti-arrow-back-up',
            variant: 'primary',
            confirmLabel: 'Oui, annuler le rejet',
            processingLabel: 'Enregistrement…',
        },
        'Rejeté': {
            title: 'Marquer comme rejeté',
            message: 'Confirmer que ce chèque a été rejeté (impayé) par la banque ?',
            icon: 'ti-x',
            variant: 'danger',
            confirmLabel: 'Oui, rejeté',
            processingLabel: 'Enregistrement…',
        },
    };

    return (
        <BackofficeLayout
            title="Chèques"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Chèques' }]}
            actions={
                canCreate ? (
                    <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                        <i className="ti ti-square-rounded-plus me-2" />
                        Ajouter un chèque
                    </button>
                ) : undefined
            }
        >
            {/* Cross-links back into Encaissements, mirroring that page's own tab bar exactly (Encaissements /
                Avances / Chèques) — Avances is a view=avance filter on the Encaissements page, not its own
                route, so it links there with the query string. */}
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
                    <button
                        type="button"
                        className="nav-link d-inline-flex align-items-center active"
                        aria-current="page"
                    >
                        <i className="ti ti-building-bank me-2" aria-hidden="true" />
                        Chèques
                    </button>
                </li>
                {canViewVirements && (
                    <li className="nav-item" role="presentation">
                        <a href="/backoffice/virements" className="nav-link d-inline-flex align-items-center">
                            <i className="ti ti-transfer-in me-2" aria-hidden="true" />
                            Virements
                        </a>
                    </li>
                )}
            </ul>

            <Card title="Chèques" bodyClassName="p-0 py-3">
                <div className="px-3 pt-2">
                    <TableToolbar onReset={filterReset.reset} resetActive={filterReset.active}>
                        <div style={{ width: 160 }}>
                            <label className="form-label" htmlFor="chq-f-numero">
                                Num Chèque
                            </label>
                            <FilterTextInput
                                id="chq-f-numero"
                                value={filters.numeroFilter}
                                onChange={(value) => reload({ numeroFilter: value })}
                                placeholder="ex : A12445"
                            />
                        </div>
                        <div style={{ width: 180 }}>
                            <label className="form-label" htmlFor="chq-f-proprietaire">
                                Propriétaire
                            </label>
                            <FilterTextInput
                                id="chq-f-proprietaire"
                                value={filters.proprietaireFilter}
                                onChange={(value) => reload({ proprietaireFilter: value })}
                                placeholder="ex : Alaoui"
                            />
                        </div>
                        <div style={{ width: 200 }}>
                            <label className="form-label" htmlFor="chq-f-banque">
                                Banque
                            </label>
                            <SelectField
                                id="chq-f-banque"
                                options={banqueOptions}
                                placeholder="Toutes les banques"
                                value={filters.banqueFilter}
                                onChange={(event) => reload({ banqueFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 200 }}>
                            <label className="form-label" htmlFor="chq-f-type">
                                Type
                            </label>
                            <SelectField
                                id="chq-f-type"
                                options={typeOptions}
                                placeholder="Tous les types"
                                value={filters.typeFilter}
                                onChange={(event) => reload({ typeFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 180 }}>
                            <label className="form-label" htmlFor="chq-f-statut">
                                Statut
                            </label>
                            <SelectField
                                id="chq-f-statut"
                                options={statutFilterOptions}
                                placeholder="Tous les statuts"
                                value={filters.statutFilter}
                                onChange={(event) => reload({ statutFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="chq-f-echeance-from">
                                Échéance du
                            </label>
                            <DateField
                                id="chq-f-echeance-from"
                                value={filters.dateEcheanceFrom}
                                onChange={(event) => reload({ dateEcheanceFrom: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 170 }}>
                            <label className="form-label" htmlFor="chq-f-echeance-to">
                                Échéance au
                            </label>
                            <DateField
                                id="chq-f-echeance-to"
                                value={filters.dateEcheanceTo}
                                onChange={(event) => reload({ dateEcheanceTo: event.target.value })}
                            />
                        </div>
                    </TableToolbar>
                </div>


                {/* ⚠ Le total exclut les chèques RESTITUÉS : ce sont des
                    papiers que l'école n'a plus. Il faut le DIRE, sinon le
                    chiffre se lit comme « la somme de toutes les lignes
                    affichées » et l'utilisateur ne peut pas comprendre
                    pourquoi une ligne visible n'y pèse pas (§11 : signaler
                    plutôt que masquer). */}
                <p className="fw-medium px-3 mb-3">
                    Montant total : {Number(montantTotal).toFixed(2)} MAD
                    {filters.statutFilter !== 'Restitué' && (
                        <span className="text-muted fw-normal fs-13 ms-2 text-normal-case">
                            (chèques en main - hors restitués)
                        </span>
                    )}
                </p>

                {cheques.data.length === 0 ? (
                    <EmptyState title="Aucun chèque" message="Ajoutez votre premier chèque pour commencer." icon="ti ti-file-invoice" />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Num Chèque</th>
                                    <th>Propriétaire</th>
                                    <th>Téléphone</th>
                                    <th>Montant</th>
                                    <th>Reste</th>
                                    <th>Banque</th>
                                    <th>Type</th>
                                    <th>Date d'échéance</th>
                                    <th>Statut</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {cheques.data.map((cheque) => (
                                <tr key={cheque.id} className={cheque.statut === 'Annulé' ? 'text-muted' : undefined}>
                                    <td>
                                        <div className="d-flex align-items-center gap-2">
                                            <code>{cheque.numeroCheque}</code>
                                            {cheque.photoUrl && (
                                                <a
                                                    href={cheque.photoUrl}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    title="Voir la photo du chèque"
                                                >
                                                    <i className="ti ti-photo" />
                                                </a>
                                            )}
                                        </div>
                                    </td>
                                    <td className="fw-medium">{cheque.proprietaire ?? '-'}</td>
                                    <td>
                                        {cheque.telephone ? (
                                            <a href={`tel:${cheque.telephone}`} className="d-inline-flex align-items-center">
                                                <i className="ti ti-phone me-1" />
                                                {cheque.telephone}
                                            </a>
                                        ) : (
                                            '-'
                                        )}
                                    </td>
                                    {/* Annulé : le montant reste lisible mais barré — ce
                                        n'est plus de l'argent en main. */}
                                    <td className={cheque.statut === 'Annulé' ? 'text-muted text-decoration-line-through' : undefined}>
                                        {Number(cheque.montant).toFixed(2)} DH
                                    </td>
                                    <td className={`fw-medium${cheque.statut === 'Annulé' ? ' text-muted text-decoration-line-through' : ''}`}>
                                        {Number(cheque.reste).toFixed(2)} DH
                                    </td>
                                    <td>{cheque.banque ?? '-'}</td>
                                    <td>{typeLabel(cheque.type)}</td>
                                    <td>{cheque.dateEcheance ?? '-'}</td>
                                    <td>
                                        <div className="d-flex align-items-center gap-2">
                                            {/* ⚠ `statutAffiche`, pas `statut` : un chèque de
                                                garantie rendu au client n'est plus « En
                                                possession », et l'afficher ainsi ferait mentir
                                                l'écran. Le verdict vient du SERVEUR (§5) ; la
                                                colonne stockée, elle, ne connaît pas « Restitué »
                                                — aucun statut n'a été ajouté en base. */}
                                            <StatusBadge
                                                label={cheque.statutAffiche}
                                                variant={statutVariant(cheque.statutAffiche)}
                                                dot
                                            />
                                            <button
                                                type="button"
                                                className="btn btn-link p-0"
                                                title="Historique du chèque"
                                                onClick={() => setDetailsCheque(cheque)}
                                            >
                                                <i className={`ti ${cheque.retourneLe ? 'ti-circle-check text-success' : 'ti-info-circle text-muted'}`} />
                                            </button>
                                        </div>
                                    </td>
                                    <td className="text-end">
                                        <RowActions>
                                            {canUpdate && cheque.statut !== 'Annulé' && (
                                                <RowActionItem icon="ti-edit" onClick={() => openEdit(cheque)}>
                                                    Modifier
                                                </RowActionItem>
                                            )}
                                            {canDeposit && cheque.statut === 'En possession' && !cheque.retourneLe && (
                                                <>
                                                    {canUpdate && <RowActionDivider />}
                                                    {/* Une GARANTIE ne va jamais à la banque : elle se
                                                        restitue contre un autre règlement. Un « À déposer »
                                                        bloqué garde l'action, le modal dit pourquoi. */}
                                                    {cheque.type !== 'Garantie (À encaisser)' && (
                                                        <RowActionItem icon="ti-building-bank" onClick={() => openRemise(cheque)}>
                                                            Remise à la banque
                                                        </RowActionItem>
                                                    )}
                                                    {/* L'étudiant a réglé en espèces / TPE / virement : il
                                                        repart avec sa garantie. `restituable` vient du
                                                        SERVEUR (GetChequesList) — la page n'a pas à savoir
                                                        que la règle exige un chèque de type Garantie
                                                        n'ayant financé aucun paiement (§5). */}
                                                    {cheque.restituable && (
                                                        <RowActionItem icon="ti-corner-up-left" onClick={() => openRestituerGarantie(cheque)}>
                                                            Restituer au client
                                                        </RowActionItem>
                                                    )}
                                                </>
                                            )}
                                            {/* Décision du COMPTABLE sur une remise à la banque. */}
                                            {canValidateDeposit && cheque.statut === 'Déposé' && (
                                                <>
                                                    {canUpdate && <RowActionDivider />}
                                                    <RowActionItem icon="ti-checks" onClick={() => openValidation(cheque)}>
                                                        Valider la remise
                                                    </RowActionItem>
                                                </>
                                            )}
                                            {/* Ancien « Encaissé » (avant le 30/09/2026, aucun argent
                                                passé à la banque) : un rejet reste saisissable.
                                                `rejetable` vient du serveur. */}
                                            {canValidateDeposit && cheque.statut === 'Encaissé' && cheque.rejetable && (
                                                <>
                                                    {canUpdate && <RowActionDivider />}
                                                    <RowActionItem icon="ti-x" danger onClick={() => confirmStatut(cheque, 'Rejeté')}>
                                                        Marquer rejeté
                                                    </RowActionItem>
                                                </>
                                            )}
                                            {canDeposit && cheque.statut === 'Rejeté' && (
                                                <>
                                                    {canUpdate && <RowActionDivider />}
                                                    {cheque.encaissements.length > 0 && (
                                                        <RowActionItem icon="ti-arrow-back-up" onClick={() => goToRemboursement(cheque)}>
                                                            Rembourser
                                                        </RowActionItem>
                                                    )}
                                                    {!cheque.retourneLe && (
                                                        <RowActionItem icon="ti-corner-up-left" onClick={() => confirmRetour(cheque)}>
                                                            Marquer comme restitué
                                                        </RowActionItem>
                                                    )}
                                                </>
                                            )}
                                            {/* « Rejeté » cliqué par erreur → retour à « Déposé ».
                                                `rejetAnnulable` vient du serveur. */}
                                            {canValidateDeposit && cheque.statut === 'Rejeté' && cheque.rejetAnnulable && (
                                                <RowActionItem icon="ti-arrow-back-up" onClick={() => confirmStatut(cheque, 'Déposé')}>
                                                    Annuler le rejet
                                                </RowActionItem>
                                            )}
                                            {/* Annuler le CHÈQUE (comptable). L'action reste visible
                                                même quand le serveur refuserait : le dialogue dit pourquoi. */}
                                            {canCancel && cheque.statut !== 'Annulé' && (
                                                <>
                                                    <RowActionDivider />
                                                    <RowActionItem icon="ti-ban" danger onClick={() => openAnnuler(cheque)}>
                                                        Annuler le chèque
                                                    </RowActionItem>
                                                </>
                                            )}
                                            {canDelete && (
                                                <>
                                                    {(canUpdate || canDeposit || canValidateDeposit) && <RowActionDivider />}
                                                    <RowActionItem
                                                        icon="ti-trash"
                                                        danger
                                                        onClick={() => {
                                                            setDeleteError(undefined);
                                                            setDeleteTarget(cheque);
                                                        }}
                                                    >
                                                        Supprimer
                                                    </RowActionItem>
                                                </>
                                            )}
                                        </RowActions>
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                        <Pagination paginator={cheques} />
                    </>
                )}
            </Card>

            <Modal
                show={showModal}
                title={editingCheque ? 'Modifier le chèque' : 'Ajouter un chèque'}
                onClose={closeModal}
                processing={form.processing}
                size="lg"
                footer={<FormActions form="cheque-form" onCancel={closeModal} processing={form.processing} />}
            >
                <form id="cheque-form" onSubmit={submit}>
                    <div className="row">
                        <div className="col-md-6">
                            <SelectField
                                id="chq-source"
                                label="Source"
                                required
                                options={sourceOptions}
                                value={form.data.source}
                                onChange={(event) => handleSourceChange(event.target.value)}
                                error={form.errors.source}
                            />
                        </div>
                        <div className="col-md-6">
                            {form.data.source === 'Étudiant' ? (
                                <SelectField
                                    id="chq-student"
                                    label="Propriétaire"
                                    required
                                    options={studentOptions}
                                    uppercase
                                    placeholder="Choisir un étudiant"
                                    value={form.data.student_id}
                                    onChange={(event) =>
                                        form.setData('student_id', event.target.value ? Number(event.target.value) : '')
                                    }
                                    error={form.errors.student_id}
                                />
                            ) : (
                                <SelectField
                                    id="chq-proprietaire-parent"
                                    label="Propriétaire"
                                    required
                                    options={parentOptions}
                                    uppercase
                                    placeholder="Choisir un parent"
                                    value={form.data.proprietaire_nom}
                                    onChange={(event) => form.setData('proprietaire_nom', event.target.value)}
                                    error={form.errors.proprietaire_nom}
                                />
                            )}
                        </div>
                        <div className="col-md-4">
                            <FormField
                                id="chq-numero"
                                label="Num Chèque"
                                required
                                value={form.data.numero_cheque}
                                onChange={(event) => form.setData('numero_cheque', event.target.value)}
                                error={form.errors.numero_cheque}
                                placeholder="ex : A12445"
                            />
                        </div>
                        <div className="col-md-4">
                            <FormField
                                id="chq-montant"
                                label="Montant"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                value={form.data.montant}
                                onChange={(event) => form.setData('montant', event.target.value)}
                                error={form.errors.montant}
                                placeholder="ex : 500"
                            />
                        </div>
                        <div className="col-md-4">
                            <SelectField
                                id="chq-banque"
                                label="Banque"
                                options={banqueOptions}
                                placeholder="Choisir un élément"
                                value={form.data.banque}
                                onChange={(event) => form.setData('banque', event.target.value)}
                                error={form.errors.banque}
                            />
                        </div>
                        <div className="col-md-4">
                            <DateField
                                id="chq-reception"
                                label="Date de réception"
                                required
                                value={form.data.date_reception}
                                onChange={(event) => form.setData('date_reception', event.target.value)}
                                error={form.errors.date_reception}
                            />
                        </div>
                        <div className="col-md-4">
                            <SelectField
                                id="chq-type"
                                label="Type"
                                required
                                options={typeOptions}
                                value={form.data.type}
                                onChange={(event) => form.setData('type', event.target.value)}
                                error={form.errors.type}
                            />
                        </div>
                        <div className="col-md-4">
                            <DateField
                                id="chq-echeance"
                                label="Date d'échéance"
                                value={form.data.date_echeance}
                                onChange={(event) => form.setData('date_echeance', event.target.value)}
                                error={form.errors.date_echeance}
                            />
                        </div>
                        <div className="col-12 mb-3">
                            <label className="form-label" htmlFor="chq-photo">
                                Photo du chèque{!editingCheque && <span className="text-danger ms-1">*</span>}
                            </label>
                            <input
                                id="chq-photo"
                                type="file"
                                accept={acceptMedia}
                                className={`form-control${form.errors.photo ? ' is-invalid' : ''}`}
                                onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                    form.setData('photo', event.target.files?.[0] ?? null)
                                }
                            />
                            {form.errors.photo && <div className="invalid-feedback d-block">{form.errors.photo}</div>}
                            <div className="form-text">
                                Formats acceptés : {chequeMimes.join(', ')} - max {Math.round(chequeMaxKb / 1024)} Mo
                                {editingCheque?.photoUrl && (
                                    <>
                                        {' - '}
                                        <a href={editingCheque.photoUrl} target="_blank" rel="noreferrer">
                                            voir la photo actuelle
                                        </a>
                                        {' (laisser vide pour la garder)'}
                                    </>
                                )}
                            </div>
                        </div>
                        <div className="col-12">
                            <TextareaField
                                id="chq-note"
                                label="Note"
                                rows={3}
                                value={form.data.note}
                                onChange={(event) => form.setData('note', event.target.value)}
                                error={form.errors.note}
                            />
                        </div>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={statutTarget !== null}
                title={statutTarget ? statutCopy[statutTarget.statut].title : ''}
                recordLabel={statutTarget?.cheque.numeroCheque ?? ''}
                message={statutTarget ? statutCopy[statutTarget.statut].message : ''}
                icon={statutTarget ? statutCopy[statutTarget.statut].icon : undefined}
                variant={statutTarget ? statutCopy[statutTarget.statut].variant : undefined}
                confirmLabel={statutTarget ? statutCopy[statutTarget.statut].confirmLabel : undefined}
                processingLabel={statutTarget ? statutCopy[statutTarget.statut].processingLabel : undefined}
                error={statutError}
                processing={statutProcessing}
                onConfirm={handleStatutConfirm}
                onCancel={() => {
                    setStatutTarget(null);
                    setStatutError(undefined);
                }}
            />

            <ConfirmDialog
                show={annulTarget !== null}
                title="Annuler le chèque"
                recordLabel={
                    annulTarget
                        ? `${annulTarget.numeroCheque} - ${Number(annulTarget.montant).toFixed(2)} MAD`
                          + (annulTarget.proprietaire ? ` - ${enCapitales(annulTarget.proprietaire)}` : '')
                        : ''
                }
                message={annulTarget?.annulationBlocker ?? 'Le chèque reste listé avec le statut « Annulé ».'}
                icon="ti-ban"
                confirmLabel="Annuler le chèque"
                processingLabel="Enregistrement…"
                error={annulError}
                processing={annulProcessing}
                onConfirm={runAnnuler}
                onCancel={closeAnnuler}
                confirmDisabled={Boolean(annulTarget?.annulationBlocker) || annulMotif.trim() === ''}
            >
                {!annulTarget?.annulationBlocker && (
                    <TextareaField
                        id="chq-motif-annulation"
                        label="Motif (obligatoire)"
                        value={annulMotif}
                        onChange={(e) => setAnnulMotif(e.target.value)}
                    />
                )}
            </ConfirmDialog>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer le chèque"
                recordLabel={
                    deleteTarget
                        ? `${deleteTarget.numeroCheque} - ${Number(deleteTarget.montant).toFixed(2)} MAD`
                        : ''
                }
                message={
                    deleteTarget && deleteTarget.encaissements.length > 0
                        ? `Suppression définitive. Ses ${deleteTarget.encaissements.length} paiement(s) sont conservés.`
                        : 'Suppression définitive.'
                }
                error={deleteError}
                processing={deleteProcessing}
                onConfirm={handleDeleteConfirm}
                onCancel={() => {
                    setDeleteTarget(null);
                    setDeleteError(undefined);
                }}
            />

            <ConfirmDialog
                show={retourTarget !== null}
                title="Restitution du chèque"
                recordLabel={retourTarget?.numeroCheque ?? ''}
                message="Confirmer que ce chèque a été restitué à son propriétaire ?"
                icon="ti-corner-up-left"
                variant="primary"
                confirmLabel="Oui, restitué"
                processingLabel="Enregistrement…"
                error={retourError}
                processing={retourProcessing}
                onConfirm={handleRetourConfirm}
                onCancel={() => {
                    setRetourTarget(null);
                    setRetourError(undefined);
                }}
            />

            {/* Restitution d'un chèque de GARANTIE : l'étudiant avait laissé
                le chèque en garantie, il revient régler en espèces / TPE /
                virement et repart avec son papier.

                ⚠ Aucun argent ne bouge ici. Le chèque est un inventaire
                OFF-LEDGER : la caisse n'a pas bougé quand il est entré, elle
                ne bouge pas quand il sort. Le paiement qui le remplace est un
                encaissement ORDINAIRE, enregistré séparément avec sa propre
                méthode — il n'est pas lié à ce chèque, et c'est tout l'objet
                de l'opération.

                Le motif est OBLIGATOIRE : c'est ce que le journal d'audit et
                la note conserveront pour expliquer, dans six mois, pourquoi
                une garantie de ce montant a quitté l'école. */}
            <ConfirmDialog
                show={garantieTarget !== null}
                title="Restituer le chèque de garantie"
                recordLabel={
                    garantieTarget
                        ? `${garantieTarget.numeroCheque} - ${Number(garantieTarget.montant).toFixed(2)} MAD`
                          + (garantieTarget.proprietaire ? ` - ${enCapitales(garantieTarget.proprietaire)}` : '')
                        : ''
                }
                message="Choisissez le paiement qui remplace la garantie."
                icon="ti-corner-up-left"
                variant="primary"
                confirmLabel="Restituer au client"
                processingLabel="Enregistrement…"
                error={garantieError}
                processing={garantieProcessing}
                onConfirm={runRestituerGarantie}
                onCancel={closeRestituerGarantie}
                confirmDisabled={garantiePaiementId === ''}
            >
                <div className="mb-3">
                    {garantiePaiements === null ? (
                        <div className="text-muted">Chargement des paiements…</div>
                    ) : garantiePaiements.length === 0 ? (
                        <div className="alert alert-warning mb-0 d-flex align-items-center justify-content-between gap-2">
                            <span>Aucun paiement à associer : encaissez d'abord.</span>
                            {/* Ouvre Encaissements avec l'étudiant déjà choisi ; la
                                garantie reste en main tant que le paiement n'existe pas. */}
                            <a
                                href={`/backoffice/encaissements?nouveau=1${garantieTarget?.studentId ? `&etudiant=${garantieTarget.studentId}` : ''}`}
                                className="btn btn-sm btn-primary text-nowrap"
                            >
                                Encaisser
                            </a>
                        </div>
                    ) : (
                        <SelectField
                            id="chq-paiement-remplacant"
                            label="Paiement qui remplace la garantie"
                            required
                            options={garantiePaiements.map((p) => ({
                                value: p.id,
                                label: `${p.reference} - ${p.methode} - ${Number(p.montant).toFixed(2)} MAD${p.datePaiement ? ` - ${p.datePaiement}` : ''}`,
                            }))}
                            placeholder="Choisir un paiement"
                            value={garantiePaiementId}
                            onChange={(event) =>
                                setGarantiePaiementId(event.target.value ? Number(event.target.value) : '')
                            }
                        />
                    )}
                </div>
                <TextareaField
                    id="chq-motif-restitution"
                    label="Motif de la restitution (obligatoire)"
                    value={garantieMotif}
                    onChange={(e) => setGarantieMotif(e.target.value)}
                />
            </ConfirmDialog>

            {/* Remise à la banque — le compte bancaire DU CENTRE du chèque, la
                date et le reçu de dépôt (obligatoire). Rien ne bouge : le
                comptable accepte ou rejette ensuite. */}
            <Modal
                show={remiseTarget !== null}
                title="Remise à la banque"
                onClose={closeRemise}
                processing={remiseForm.processing}
                size="lg"
                footer={
                    remiseTarget?.depotBlocker ? (
                        <button type="button" className="btn btn-light" onClick={closeRemise}>
                            Fermer
                        </button>
                    ) : (
                        <FormActions form="cheque-remise-form" onCancel={closeRemise} processing={remiseForm.processing} />
                    )
                }
            >
                {remiseTarget && (
                    <form id="cheque-remise-form" onSubmit={submitRemise}>
                        <div className="row">
                            <div className="col-md-6 mb-3">
                                <div className="text-muted small mb-1">Propriétaire</div>
                                <div className="fw-medium text-uppercase">{remiseTarget.proprietaire ?? '-'}</div>
                            </div>
                            <div className="col-md-6 mb-3">
                                <div className="text-muted small mb-1">Banque</div>
                                <div className="fw-medium">{remiseTarget.banque ?? '-'}</div>
                            </div>
                            <div className="col-md-6 mb-3">
                                <div className="text-muted small mb-1">Num Chèque</div>
                                <div className="fw-medium">
                                    <code>{remiseTarget.numeroCheque}</code>
                                </div>
                            </div>
                            <div className="col-md-6 mb-3">
                                <div className="text-muted small mb-1">Montant</div>
                                <div className="fw-medium">{Number(remiseTarget.montant).toFixed(2)} MAD</div>
                            </div>
                        </div>
                        {remiseTarget.depotBlocker ? (
                            <div className="alert alert-warning mb-0">{remiseTarget.depotBlocker}</div>
                        ) : (
                            <div className="row">
                                <div className="col-md-6 mb-3">
                                    <DateField
                                        id="chq-remise-date"
                                        label="Date remise banque"
                                        required
                                        value={remiseForm.data.date_remise}
                                        onChange={(event) => remiseForm.setData('date_remise', event.target.value)}
                                        error={remiseForm.errors.date_remise}
                                    />
                                </div>
                                <div className="col-12">
                                    <label className="form-label" htmlFor="chq-remise-recu">
                                        Reçu de dépôt<span className="text-danger ms-1">*</span>
                                    </label>
                                    <input
                                        id="chq-remise-recu"
                                        type="file"
                                        accept={acceptMedia}
                                        className={`form-control${remiseForm.errors.justificatif ? ' is-invalid' : ''}`}
                                        onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                            remiseForm.setData('justificatif', event.target.files?.[0] ?? null)
                                        }
                                    />
                                    {remiseForm.errors.justificatif && (
                                        <div className="invalid-feedback d-block">{remiseForm.errors.justificatif}</div>
                                    )}
                                    <div className="form-text">Photo ou scan du bordereau.</div>
                                </div>
                            </div>
                        )}
                    </form>
                )}
            </Modal>

            {/* Décision du COMPTABLE — « Encaissé » = la banque a accepté le
                chèque, « Rejeté » = elle l'a refusé. Aucun argent ne bouge. */}
            <Modal
                show={validationTarget !== null}
                title="Valider la remise à la banque"
                onClose={closeValidation}
                processing={validationProcessing}
                size="lg"
                footer={
                    <>
                        <button type="button" className="btn btn-light" onClick={closeValidation} disabled={validationProcessing}>
                            Annuler
                        </button>
                        <button
                            type="button"
                            className="btn btn-danger"
                            onClick={() => decideValidation('Rejeté')}
                            disabled={validationProcessing}
                        >
                            Rejeter
                        </button>
                        <button
                            type="button"
                            className="btn btn-primary"
                            onClick={() => decideValidation('Encaissé')}
                            disabled={validationProcessing}
                        >
                            {validationProcessing ? 'Enregistrement…' : 'Valider (encaissé)'}
                        </button>
                    </>
                }
            >
                {validationTarget && (
                    <div className="row">
                        <div className="col-md-6 mb-3">
                            <div className="text-muted small mb-1">Chèque</div>
                            <div className="fw-medium">
                                <code>{validationTarget.numeroCheque}</code> - <span className="text-uppercase">{validationTarget.proprietaire ?? '-'}</span>
                            </div>
                        </div>
                        <div className="col-md-6 mb-3">
                            <div className="text-muted small mb-1">Montant</div>
                            <div className="fw-medium">{Number(validationTarget.montant).toFixed(2)} MAD</div>
                        </div>
                        <div className="col-md-6 mb-3">
                            <div className="text-muted small mb-1">Remis à la banque par</div>
                            <div className="fw-medium">
                                {validationTarget.deposeParNom ?? '-'}
                                {validationTarget.dateRemise && ` le ${validationTarget.dateRemise}`}
                            </div>
                        </div>
                        <div className="col-md-6 mb-3">
                            <div className="text-muted small mb-1">Pièces</div>
                            <div className="d-flex gap-3">
                                {validationTarget.justificatifDepotUrl ? (
                                    <a href={validationTarget.justificatifDepotUrl} target="_blank" rel="noreferrer">
                                        <i className="ti ti-receipt me-1" />
                                        Reçu de dépôt
                                    </a>
                                ) : (
                                    <span className="text-muted">Aucun reçu</span>
                                )}
                                {validationTarget.photoUrl && (
                                    <a href={validationTarget.photoUrl} target="_blank" rel="noreferrer">
                                        <i className="ti ti-photo me-1" />
                                        Photo du chèque
                                    </a>
                                )}
                            </div>
                        </div>
                        {validationError && <div className="col-12 mt-3 alert alert-danger mb-0">{validationError}</div>}
                    </div>
                )}
            </Modal>

            {/* After marking a chèque Rejeté: offer the refund follow-up. Never
                auto-created — EnregistrerRemboursement always stays a reviewed,
                user-submitted form (§11 money invariants); this only jumps
                there, pre-filled when there's exactly one linked encaissement. */}
            <Modal
                show={rejectedCheque !== null}
                title="Chèque rejeté"
                onClose={() => setRejectedCheque(null)}
                footer={
                    <>
                        <button type="button" className="btn btn-light" onClick={() => setRejectedCheque(null)}>
                            Plus tard
                        </button>
                        {rejectedCheque && rejectedCheque.encaissements.length > 0 && (
                            <button type="button" className="btn btn-primary" onClick={() => goToRemboursement(rejectedCheque)}>
                                <i className="ti ti-arrow-back-up me-2" />
                                Rembourser maintenant
                            </button>
                        )}
                    </>
                }
            >
                {rejectedCheque && rejectedCheque.encaissements.length === 0 && (
                    <p className="mb-0">
                        Ce chèque n'est lié à aucun encaissement - aucun remboursement n'est nécessaire.
                    </p>
                )}
                {rejectedCheque && rejectedCheque.encaissements.length === 1 && (
                    <p className="mb-0">
                        Ce chèque a été utilisé pour payer l'encaissement{' '}
                        <code>{rejectedCheque.encaissements[0].reference}</code>
                        {' '}({Number(rejectedCheque.encaissements[0].montant).toFixed(2)} DH). Un remboursement est
                        essentiel pour régulariser ce paiement.
                    </p>
                )}
                {rejectedCheque && rejectedCheque.encaissements.length > 1 && (
                    <p className="mb-0">
                        Ce chèque a été utilisé pour payer {rejectedCheque.encaissements.length} encaissements
                        différents. Un remboursement est essentiel pour chacun - enregistrez-les un par un depuis
                        l'onglet Remboursements.
                    </p>
                )}
            </Modal>

            {/* Responsibility popup for a rejected chèque: who originally
                received it (agent_id) and who returned it to its owner, if
                already done — both already tracked (agent_id at creation,
                retourne_par_id via the "Marquer comme restitué" action). */}
            <Modal
                show={detailsCheque !== null}
                title="Historique du chèque"
                onClose={() => setDetailsCheque(null)}
                footer={
                    <button type="button" className="btn btn-light" onClick={() => setDetailsCheque(null)}>
                        Fermer
                    </button>
                }
            >
                {detailsCheque && (
                    <div className="d-flex flex-column gap-3">
                        <div>
                            <div className="text-muted small mb-1">Chèque</div>
                            <div className="fw-medium">
                                <code>{detailsCheque.numeroCheque}</code> - <span className="text-uppercase">{detailsCheque.proprietaire ?? '-'}</span>
                            </div>
                        </div>
                        <div>
                            <div className="text-muted small mb-1">Reçu par</div>
                            <div className="fw-medium">
                                {detailsCheque.agentNom ?? '-'}
                                {detailsCheque.dateReception && ` le ${detailsCheque.dateReception}`}
                                {detailsCheque.photoUrl && (
                                    <a href={detailsCheque.photoUrl} target="_blank" rel="noreferrer" className="ms-2">
                                        <i className="ti ti-photo me-1" />
                                        Photo
                                    </a>
                                )}
                            </div>
                        </div>
                        {detailsCheque.dateRemise && (
                            <div>
                                <div className="text-muted small mb-1">Remise à la banque</div>
                                <div className="fw-medium">
                                    Par {detailsCheque.deposeParNom ?? '-'} le {detailsCheque.dateRemise}
                                    {detailsCheque.justificatifDepotUrl && (
                                        <a href={detailsCheque.justificatifDepotUrl} target="_blank" rel="noreferrer" className="ms-2">
                                            <i className="ti ti-receipt me-1" />
                                            Reçu
                                        </a>
                                    )}
                                </div>
                            </div>
                        )}
                        {detailsCheque.depotValideLe && (
                            <div>
                                <div className="text-muted small mb-1">Remise validée</div>
                                <div className="fw-medium">
                                    <i className="ti ti-circle-check text-success me-1" />
                                    Par {detailsCheque.depotValideParNom ?? '-'} le {detailsCheque.depotValideLe}
                                </div>
                            </div>
                        )}
                        {detailsCheque.restitutionPaiement && (
                            <div>
                                <div className="text-muted small mb-1">Garantie remplacée par</div>
                                <div className="fw-medium">
                                    {detailsCheque.restitutionPaiement.reference} - {detailsCheque.restitutionPaiement.methode} -{' '}
                                    {Number(detailsCheque.restitutionPaiement.montant).toFixed(2)} MAD
                                </div>
                            </div>
                        )}
                        <div>
                            <div className="text-muted small mb-1">Restitué au propriétaire</div>
                            {detailsCheque.retourneLe ? (
                                <div className="fw-medium">
                                    <i className="ti ti-circle-check text-success me-1" />
                                    Par {detailsCheque.retourneParNom ?? '-'} le {detailsCheque.retourneLe}
                                </div>
                            ) : (
                                <div className="text-muted">
                                    <i className="ti ti-alert-circle me-1" />
                                    Pas encore restitué
                                </div>
                            )}
                        </div>
                        {/* La note porte les motifs ([ANNULÉ], [RESTITUÉ]) : c'est ici
                            qu'on lit pourquoi un chèque a été annulé, et par qui. */}
                        {detailsCheque.note && (
                            <div>
                                <div className="text-muted small mb-1">Note</div>
                                <div className="text-normal-case" style={{ whiteSpace: 'pre-line' }}>{detailsCheque.note}</div>
                            </div>
                        )}
                    </div>
                )}
            </Modal>
        </BackofficeLayout>
    );
}
