import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import Modal from '@/Components/Modals/Modal';
import DateField from '@/Components/Forms/DateField';
import SelectField from '@/Components/Forms/SelectField';
import FormField from '@/Components/Forms/FormField';
import { useFilterReset } from '@/Hooks/useFilterReset';
import { t } from '@/Lib/i18n';
import type { PaiementProfPageProps } from '@/Types';

/**
 * « Calcul paiement prof » — dérive, depuis les appels DÉJÀ SAISIS, le
 * montant dû à un enseignant pour un groupe sur une période.
 *
 * Portage de la logique du portail GLS, à une différence près : le portail
 * lisait un instantané Excel/API et devait le stocker ; ici la donnée nous
 * appartient (`presences` → `seances`), donc l'écran CALCULE À LA LECTURE.
 * Corriger un appel et recharger suffit à obtenir le bon montant.
 *
 * ⚠ Cet écran n'écrit RIEN et ne touche AUCUNE caisse. Il PROPOSE un
 * montant ; « Enregistrer la dépense » ouvre le modal « Paiement prof »
 * habituel pré-rempli (query string `prefill_*`), et c'est là, et seulement
 * là, que l'argent bouge — avec tous ses invariants (§11). Un calcul n'est
 * pas un paiement.
 *
 * La grille reprend le rendu du portail : colonnes de jours collantes,
 * P vert / A rouge, et les 4 semaines de paie à droite. Une semaine JAMAIS
 * ENSEIGNÉE (férié) s'affiche « — » et non « 0 DH » : elle n'est ni gagnée
 * ni perdue, et la peindre en rouge ferait croire à une semaine ratée.
 */
export default function PaiementProfIndex({
    calcul,
    filters,
    groupOptions,
    seuilParDefaut,
    paiementProfTypeId,
    canCreateDepense,
}: PaiementProfPageProps) {
    // Ajustements manuels — état LOCAL, jamais persisté : ils ne valent que
    // pour le montant qu'on s'apprête à enregistrer. Les stocker donnerait
    // l'illusion d'une paie enregistrée alors qu'aucune dépense n'existe.
    const [ajustements, setAjustements] = useState<Record<number, string>>({});

    // Saisie du modal — état LOCAL tant que « Calculer » n'a pas été cliqué.
    // Le calcul ne part qu'au clic : on COMPOSE une paie, on ne parcourt pas
    // une liste, donc rien ne doit se recharger pendant qu'on remplit.
    const [showModal, setShowModal] = useState(false);
    const [saisie, setSaisie] = useState(filters);

    function majSaisie(champs: Partial<typeof filters>) {
        setSaisie((precedent) => ({ ...precedent, ...champs }));
    }

    function ouvrirModal() {
        // Repartir de ce qui est à l'écran : « Changer la période » doit
        // rouvrir le modal pré-rempli, pas vide.
        setSaisie(filters);
        setShowModal(true);
    }

    // Le taux du groupe CHOISI DANS LE MODAL — pas celui du calcul affiché,
    // qui peut porter sur un autre groupe tant qu'on n'a pas validé.
    const tauxDuGroupe = useMemo(() => {
        if (saisie.groupFilter === '' || calcul === null) {
            return null;
        }

        return Number(saisie.groupFilter) === calcul.group.id
            ? calcul.group.montantParEtudiantDefaut
            : null;
    }, [saisie.groupFilter, calcul]);

    const formulaireComplet =
        saisie.groupFilter !== '' && saisie.dateDebut !== '' && saisie.dateFin !== '';

    function reload(nextFilters: Partial<typeof filters>) {
        router.get('/backoffice/paiement-prof', { ...filters, ...nextFilters }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function lancerCalcul() {
        if (!formulaireComplet) {
            return;
        }

        setShowModal(false);
        // Remplacement COMPLET des filtres (pas un merge) : un champ vidé dans
        // le modal doit être vidé dans l'URL, sinon l'ancienne valeur survit.
        router.get('/backoffice/paiement-prof', { ...saisie }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    const { reset: onReset } = useFilterReset(filters, reload);

    // Total effectif = le calcul serveur, corrigé des ajustements saisis ici.
    // Le serveur reste l'autorité sur chaque ligne ; l'écran n'applique que
    // ce que l'opérateur vient de taper.
    const totalAffiche = useMemo(() => {
        if (calcul === null) {
            return 0;
        }

        return calcul.lignes.reduce((somme, ligne) => {
            const saisi = ajustements[ligne.studentId];
            const valeur = saisi !== undefined && saisi !== '' ? Number(saisi) : ligne.montantEffectif;

            return somme + (Number.isFinite(valeur) ? valeur : ligne.montantEffectif);
        }, 0);
    }, [calcul, ajustements]);

    const semaines = [1, 2, 3, 4];

    function statutCellule(statut: string | undefined): { texte: string; classe: string } {
        if (statut === 'Présent') {
            return { texte: 'P', classe: 'pp-present' };
        }
        if (statut === 'Absent') {
            return { texte: 'A', classe: 'pp-absent' };
        }
        if (statut === 'Retard' || statut === 'Justifié') {
            // Écarté du calcul : montré, mais visiblement neutre.
            return { texte: statut === 'Retard' ? 'R' : 'J', classe: 'pp-ignore' };
        }

        return { texte: '·', classe: 'pp-vide' };
    }

    function creerDepense() {
        if (calcul === null) {
            return;
        }

        const params = new URLSearchParams({
            prefill_paiement_prof: '1',
            prefill_group_id: String(calcul.group.id),
            prefill_montant: totalAffiche.toFixed(2),
            prefill_periode_debut: calcul.periode.debut,
            prefill_periode_fin: calcul.periode.fin,
            prefill_description: t('Teacher payment') + ' — ' + calcul.group.nom,
            ...(paiementProfTypeId !== null ? { prefill_type_depense_id: String(paiementProfTypeId) } : {}),
        });

        router.visit(`/backoffice/depenses?${params.toString()}`);
    }

    return (
        <BackofficeLayout title={t('Teacher payment calculation')}>
            <style>{`
                .pp-wrap { overflow: auto; max-height: 68vh; border: 1px solid var(--bs-border-color); border-radius: .5rem; }
                .pp-wrap table { margin: 0; border-collapse: separate; border-spacing: 0; font-size: .82rem; white-space: nowrap; }
                .pp-wrap thead th { position: sticky; top: 0; z-index: 10; background: var(--bs-dark); color: #fff; padding: 6px 4px; text-align: center; font-size: .7rem; }
                .pp-num  { position: sticky; left: 0;     z-index: 4; width: 38px; background: var(--bs-body-bg); }
                .pp-nom  { position: sticky; left: 38px;  z-index: 4; min-width: 180px; max-width: 180px; overflow: hidden; text-overflow: ellipsis;
                           background: var(--bs-body-bg); box-shadow: 3px 0 6px -3px rgba(0,0,0,.18); }
                .pp-wrap thead .pp-num, .pp-wrap thead .pp-nom { z-index: 12; background: var(--bs-dark); }
                .pp-jour { width: 34px; min-width: 34px; text-align: center; padding: 2px 0; font-weight: 700; font-size: .72rem; }
                .pp-present { background: rgba(25,135,84,.16); color: var(--bs-success); }
                .pp-absent  { background: rgba(220,53,69,.14); color: var(--bs-danger); }
                .pp-ignore  { background: rgba(108,117,125,.12); color: var(--bs-secondary); }
                .pp-vide    { color: var(--bs-secondary-color); opacity: .5; }
                .pp-sem { min-width: 96px; text-align: center; padding: 4px 6px; border-left: 1px solid var(--bs-border-color); }
                .pp-total { position: sticky; right: 0; z-index: 4; min-width: 120px; text-align: right; font-weight: 700;
                            background: var(--bs-body-bg); box-shadow: -3px 0 6px -3px rgba(0,0,0,.18); }
                .pp-wrap thead .pp-total { z-index: 12; background: var(--bs-dark); }
                .pp-ajust { width: 92px; text-align: right; }
            `}</style>

            {/* Barre d'action — le calcul se LANCE depuis un modal (« Nouveau
                calcul ») plutôt que depuis une barre de filtres : on ne
                parcourt pas une liste, on COMPOSE une paie. Tant que rien
                n'a été demandé, il n'y a aucun filtre à réinitialiser. */}
            <div className="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    {calcul !== null && (
                        <span className="text-muted">
                            {calcul.group.nom}
                            {' · '}
                            {new Date(calcul.periode.debut).toLocaleDateString('fr-FR')}
                            {' → '}
                            {new Date(calcul.periode.fin).toLocaleDateString('fr-FR')}
                        </span>
                    )}
                </div>
                <div className="d-flex gap-2">
                    {calcul !== null && (
                        <button type="button" className="btn btn-outline-secondary" onClick={onReset}>
                            <i className="ti ti-filter-off me-1" />
                            {t('Clear')}
                        </button>
                    )}
                    <button type="button" className="btn btn-primary" onClick={ouvrirModal}>
                        <i className="ti ti-calculator me-1" />
                        {calcul === null ? t('New calculation') : t('Change the period')}
                    </button>
                </div>
            </div>

            {/* ── Modal de saisie ──────────────────────────────────── */}
            <Modal
                show={showModal}
                title={t('New teacher payment calculation')}
                onClose={() => setShowModal(false)}
                size="lg"
                footer={
                    <>
                        <button
                            type="button"
                            className="btn btn-light"
                            onClick={() => setShowModal(false)}
                        >
                            {t('Cancel')}
                        </button>
                        <button
                            type="button"
                            className="btn btn-primary"
                            disabled={!formulaireComplet}
                            onClick={lancerCalcul}
                        >
                            <i className="ti ti-calculator me-1" />
                            {t('Calculate')}
                        </button>
                    </>
                }
            >
                <p className="text-muted mb-3">
                    {t(
                        'The payment is computed from the roll-call already recorded for that group — nothing is imported.',
                    )}
                </p>

                <SelectField
                    id="pp-group"
                    label={t('Group')}
                    required
                    value={saisie.groupFilter}
                    options={groupOptions}
                    placeholder={t('Select a group')}
                    onChange={(event) => majSaisie({ groupFilter: event.target.value })}
                />

                <div className="row">
                    <div className="col-md-6">
                        <DateField
                            id="pp-date-debut"
                            label={t('Start date')}
                            required
                            value={saisie.dateDebut}
                            onChange={(event) => majSaisie({ dateDebut: event.target.value })}
                        />
                    </div>
                    <div className="col-md-6">
                        <DateField
                            id="pp-date-fin"
                            label={t('End date')}
                            required
                            value={saisie.dateFin}
                            onChange={(event) => majSaisie({ dateFin: event.target.value })}
                        />
                    </div>
                </div>

                <div className="row">
                    <div className="col-md-6">
                        <FormField
                            id="pp-montant"
                            label={t('Amount per student')}
                            type="number"
                            step="0.01"
                            min="0"
                            value={saisie.montantParEtudiant}
                            // Vide = on prend le taux du groupe ; le placeholder
                            // le NOMME pour que le champ vide ne se lise pas
                            // comme « zéro dirham ».
                            placeholder={tauxDuGroupe !== null ? tauxDuGroupe.toFixed(2) : '0.00'}
                            onChange={(event) => majSaisie({ montantParEtudiant: event.target.value })}
                        />
                        <div className="form-text mt-n2 mb-3">
                            {tauxDuGroupe !== null
                                ? t('Leave empty to use the group rate (:rate MAD).').replace(
                                      ':rate',
                                      tauxDuGroupe.toFixed(2),
                                  )
                                : t('This group has no rate set — enter one here.')}
                        </div>
                    </div>
                    <div className="col-md-6">
                        <FormField
                            id="pp-seuil"
                            label={t('Days required per week')}
                            type="number"
                            min="1"
                            max="5"
                            value={saisie.seuil}
                            placeholder={String(seuilParDefaut)}
                            onChange={(event) => majSaisie({ seuil: event.target.value })}
                        />
                        <div className="form-text mt-n2 mb-3">
                            {t('A week counts when the student attended at least this many days.')}
                        </div>
                    </div>
                </div>
            </Modal>

            {calcul === null ? (
                <Card>
                    <EmptyState
                        title={t('No calculation yet')}
                        message={t(
                            'The payment is computed from the roll-call already recorded for that group — nothing is imported.',
                        )}
                    >
                        <button type="button" className="btn btn-primary" onClick={ouvrirModal}>
                            <i className="ti ti-calculator me-1" />
                            {t('New calculation')}
                        </button>
                    </EmptyState>
                </Card>
            ) : calcul.lignes.length === 0 ? (
                <Card>
                    <EmptyState
                        title={t('No completed session over this period')}
                        message={t(
                            'Only sessions marked « Effectuée » are paid: a planned or cancelled session taught nothing.',
                        )}
                    />
                </Card>
            ) : (
                <>
                    {/* ── Récapitulatif ─────────────────────────────── */}
                    <div className="row g-3 mb-3">
                        <div className="col-6 col-md-3">
                            <Card>
                                <div className="text-muted fs-12 text-uppercase mb-1">{t('Total payment')}</div>
                                <div className="fs-24 fw-bold text-success">
                                    {totalAffiche.toFixed(2)} MAD
                                </div>
                            </Card>
                        </div>
                        <div className="col-6 col-md-3">
                            <Card>
                                <div className="text-muted fs-12 text-uppercase mb-1">{t('Paying students')}</div>
                                <div className="fs-24 fw-bold">
                                    {calcul.etudiantsRemunerateurs} / {calcul.lignes.length}
                                </div>
                            </Card>
                        </div>
                        <div className="col-6 col-md-3">
                            <Card>
                                <div className="text-muted fs-12 text-uppercase mb-1">{t('Per qualifying week')}</div>
                                <div className="fs-24 fw-bold">{calcul.montantSemaine.toFixed(2)} MAD</div>
                                <small className="text-muted">
                                    {calcul.montantParEtudiant.toFixed(2)} MAD / {t('student')}
                                </small>
                            </Card>
                        </div>
                        <div className="col-6 col-md-3">
                            <Card>
                                <div className="text-muted fs-12 text-uppercase mb-1">{t('Qualifying threshold')}</div>
                                <div className="fs-24 fw-bold">
                                    {calcul.seuil} <small className="fs-14">{t('days/week')}</small>
                                </div>
                                <small className="text-muted">
                                    {calcul.nombreJoursDeCours} {t('course days')} · {calcul.nombreSeances}{' '}
                                    {t('sessions')}
                                </small>
                            </Card>
                        </div>
                    </div>

                    {/* Une période amputée d'un férié n'a pas 4 semaines
                        enseignées : le dire, sinon l'écran semble en perdre une. */}
                    {calcul.bucketsOccupes.length < 4 && (
                        <div className="alert alert-info d-flex align-items-center gap-2">
                            <i className="ti ti-info-circle" />
                            <span>
                                {t(
                                    'Only :count weeks were actually taught over this period — the remaining slots are neither earned nor lost.',
                                ).replace(':count', String(calcul.bucketsOccupes.length))}
                            </span>
                        </div>
                    )}

                    {calcul.montantParEtudiant <= 0 && (
                        <div className="alert alert-warning d-flex align-items-center gap-2">
                            <i className="ti ti-alert-triangle" />
                            <span>
                                {t(
                                    'No rate set for this group — fill « Amount per student » above, or set it on the group.',
                                )}
                            </span>
                        </div>
                    )}

                    {/* ── Grille de présence + paie ─────────────────── */}
                    <Card
                        title={`${calcul.group.nom} — ${calcul.group.enseignantNom ?? t('No teacher assigned')}`}
                        bodyClassName="p-0 py-3"
                        tools={
                            canCreateDepense && totalAffiche > 0 ? (
                                <button type="button" className="btn btn-primary btn-sm" onClick={creerDepense}>
                                    <i className="ti ti-cash me-1" />
                                    {t('Record the expense')}
                                </button>
                            ) : null
                        }
                    >
                        <div className="pp-wrap">
                            <table className="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th className="pp-num">#</th>
                                        <th className="pp-nom text-start ps-2">{t('Student')}</th>
                                        {calcul.datesDeCours.map((date) => {
                                            const d = new Date(date);

                                            return (
                                                <th key={date} className="pp-jour" title={date}>
                                                    {d.toLocaleDateString('fr-FR', { weekday: 'short' })
                                                        .slice(0, 3)
                                                        .toUpperCase()}
                                                    <br />
                                                    <span className="opacity-75">{String(d.getDate()).padStart(2, '0')}</span>
                                                </th>
                                            );
                                        })}
                                        {semaines.map((s) => (
                                            <th key={s} className="pp-sem">
                                                {t('W')}
                                                {s}
                                            </th>
                                        ))}
                                        <th className="pp-sem">{t('Adjustment')}</th>
                                        <th className="pp-total">{t('Total')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {calcul.lignes.map((ligne, index) => {
                                        const appels = calcul.grille[ligne.studentId] ?? {};
                                        const saisi = ajustements[ligne.studentId];
                                        const effectif =
                                            saisi !== undefined && saisi !== '' && Number.isFinite(Number(saisi))
                                                ? Number(saisi)
                                                : ligne.montantEffectif;

                                        return (
                                            <tr key={ligne.studentId}>
                                                <td className="pp-num text-center text-muted">{index + 1}</td>
                                                <td className="pp-nom fw-semibold ps-2">{ligne.nom}</td>

                                                {calcul.datesDeCours.map((date) => {
                                                    const cellule = statutCellule(appels[date]);

                                                    return (
                                                        <td
                                                            key={date}
                                                            className={`pp-jour ${cellule.classe}`}
                                                            title={`${date} — ${appels[date] ?? t('No roll call')}`}
                                                        >
                                                            {cellule.texte}
                                                        </td>
                                                    );
                                                })}

                                                {semaines.map((s) => {
                                                    const montant = ligne.montantsParSemaine[s];
                                                    const jours = ligne.joursParSemaine[s] ?? 0;

                                                    // Semaine jamais enseignée : « — », jamais 0 DH.
                                                    if (montant === null || montant === undefined) {
                                                        return (
                                                            <td
                                                                key={s}
                                                                className="pp-sem text-muted"
                                                                title={t('Week not taught')}
                                                            >
                                                                —
                                                            </td>
                                                        );
                                                    }

                                                    return (
                                                        <td key={s} className="pp-sem">
                                                            <span
                                                                className={`badge ${
                                                                    montant > 0
                                                                        ? 'bg-success-subtle text-success'
                                                                        : 'bg-danger-subtle text-danger'
                                                                }`}
                                                            >
                                                                {jours} {t('d')}
                                                            </span>
                                                            <div className="fs-12 mt-1">{montant.toFixed(2)}</div>
                                                        </td>
                                                    );
                                                })}

                                                <td className="pp-sem">
                                                    <input
                                                        type="number"
                                                        className="form-control form-control-sm pp-ajust"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder={ligne.montantAuto.toFixed(2)}
                                                        value={saisi ?? ''}
                                                        onChange={(event) =>
                                                            setAjustements((previous) => ({
                                                                ...previous,
                                                                [ligne.studentId]: event.target.value,
                                                            }))
                                                        }
                                                    />
                                                </td>

                                                <td className="pp-total">
                                                    {effectif.toFixed(2)} MAD
                                                    {saisi !== undefined && saisi !== '' && (
                                                        <div className="fs-12 text-warning">{t('adjusted')}</div>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                                <tfoot>
                                    <tr className="fw-bold">
                                        <td className="pp-num" />
                                        <td className="pp-nom ps-2">{t('Total')}</td>
                                        {calcul.datesDeCours.map((date) => (
                                            <td key={date} className="pp-jour" />
                                        ))}
                                        {semaines.map((s) => (
                                            <td key={s} className="pp-sem" />
                                        ))}
                                        <td className="pp-sem" />
                                        <td className="pp-total">{totalAffiche.toFixed(2)} MAD</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </Card>
                </>
            )}
        </BackofficeLayout>
    );
}
