import { router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import Modal from '@/Components/Modals/Modal';
import SelectField from '@/Components/Forms/SelectField';
import FormField from '@/Components/Forms/FormField';
import { useFilterReset } from '@/Hooks/useFilterReset';
import { t } from '@/Lib/i18n';
import { formatDuree, parseDuree } from '@/Lib/duree';
import type { PaiementProfGroupOptions, PaiementProfPageProps, SelectOption } from '@/Types';

/**
 * « Calcul paiement prof » — dérive, depuis les appels DÉJÀ SAISIS, le
 * montant dû à UN enseignant pour UN groupe sur UN mois de groupe, selon
 * le mode de paie configuré sur SA fiche (onglet « Paiement prof »).
 *
 * Le modal enchaîne : groupe → (le serveur renvoie ses mois, ses profs et
 * les séances sans prof) → enseignant → mois → heures si mode horaire. Le
 * mode et le taux ne se saisissent JAMAIS ici : ils viennent de la fiche,
 * et un prof mal configuré est refusé avec le problème nommé.
 *
 * Les « mois » sont ceux du GROUPE : un groupe parti le 07/09 se paie
 * 07/09 → 06/10, puis 07/10 → 06/11 (MoisDeGroupe côté serveur).
 *
 * ⚠ Cet écran n'écrit RIEN et ne touche AUCUNE caisse. Il PROPOSE un
 * montant ; « Enregistrer la dépense » ouvre le modal « Paiement prof »
 * habituel pré-rempli, et c'est là, et seulement là, que l'argent bouge (§11).
 */
export default function PaiementProfIndex({
    calcul,
    filters,
    groupOptions,
    seancesMaxParMois,
    paiementProfTypeId,
    canCreateDepense,
}: PaiementProfPageProps) {
    // Ajustements manuels — état LOCAL, jamais persisté.
    const [ajustements, setAjustements] = useState<Record<number, string>>({});

    const [showModal, setShowModal] = useState(false);
    const [saisie, setSaisie] = useState(filters);

    // Le total d'heures a-t-il été saisi À LA MAIN ? Tant que non, il suit
    // la multiplication (durée × séances). Dès que l'opérateur le corrige,
    // l'automatisme se tait : se battre contre une valeur qui se réécrit
    // toute seule est le pire comportement possible sur un montant de paie.
    const [heuresManuelles, setHeuresManuelles] = useState(false);

    // Ce que le serveur sait du groupe choisi — chargé à la sélection.
    const [options, setOptions] = useState<PaiementProfGroupOptions | null>(null);
    const [chargement, setChargement] = useState(false);

    function majSaisie(champs: Partial<typeof filters>) {
        setSaisie((precedent) => ({ ...precedent, ...champs }));
    }

    function ouvrirModal() {
        setSaisie(filters);
        // Un nouveau calcul repart en mode AUTOMATIQUE : le drapeau ne doit
        // pas survivre au modal précédent.
        setHeuresManuelles(false);
        setShowModal(true);
    }

    // Groupe choisi ⇒ on demande au serveur ses mois, ses profs et les
    // séances orphelines. Re-demandé quand le mois change, parce que
    // « qui a enseigné » et « séances sans prof » dépendent du mois.
    useEffect(() => {
        if (!showModal || saisie.groupFilter === '') {
            setOptions(null);

            return;
        }

        let annule = false;
        setChargement(true);

        const params = new URLSearchParams(saisie.mois !== '' ? { mois: saisie.mois } : {});
        fetch(`/backoffice/paiement-prof/groupes/${saisie.groupFilter}/options?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        })
            .then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status)))))
            .then((data: PaiementProfGroupOptions) => {
                if (annule) {
                    return;
                }

                setOptions(data);
                setSaisie((precedent) => ({
                    ...precedent,
                    // Le mois par défaut du serveur si l'écran n'en avait pas.
                    mois: precedent.mois !== '' ? precedent.mois : data.mois,
                    // Durée habituelle d'une séance du groupe, déduite des
                    // horaires réels : le champ arrive PRÉ-REMPLI et le total
                    // se calcule tout seul. L'opérateur n'a rien à saisir dans
                    // le cas courant, et garde la main dans les autres.
                    dureeSeance:
                        precedent.dureeSeance !== ''
                            ? precedent.dureeSeance
                            : data.dureeHabituelle !== null
                              ? formatDuree(data.dureeHabituelle)
                              : '',
                    // Le prof pré-sélectionné : celui qui a le plus enseigné ce
                    // mois-ci — seulement si l'écran n'en avait pas déjà un.
                    enseignantFilter:
                        precedent.enseignantFilter !== ''
                            ? precedent.enseignantFilter
                            : data.enseignantParDefaut !== null
                              ? String(data.enseignantParDefaut)
                              : '',
                }));
            })
            .catch(() => {
                if (!annule) {
                    setOptions(null);
                }
            })
            .finally(() => {
                if (!annule) {
                    setChargement(false);
                }
            });

        return () => {
            annule = true;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [showModal, saisie.groupFilter, saisie.mois]);

    const enseignantChoisi = useMemo(
        () => options?.enseignants.find((e) => String(e.value) === saisie.enseignantFilter) ?? null,
        [options, saisie.enseignantFilter],
    );

    const enseignantOptions: SelectOption[] = (options?.enseignants ?? []).map((e) => ({
        value: e.value,
        label: e.seancesCeMois > 0 ? `${e.label} — ${e.seancesCeMois} ${t('sessions')}` : e.label,
        // Un prof mal configuré reste LISTÉ mais désactivé, avec le motif :
        // le retirer ferait croire qu'il n'est pas affecté au groupe.
        disabled: e.probleme !== null,
        disabledReason: e.probleme ?? undefined,
    }));

    const moisOptions: SelectOption[] = (options?.moisOptions ?? []).map((m) => ({ value: m.value, label: m.label }));

    /** Séances du mois pour l'enseignant choisi — le multiplicateur. */
    const seancesDuMois = enseignantChoisi?.seancesCeMois ?? 0;

    const dureeParSeance = useMemo(() => parseDuree(saisie.dureeSeance), [saisie.dureeSeance]);

    const totalHeuresCalcule = useMemo(
        () => (dureeParSeance !== null ? Math.round(dureeParSeance * seancesDuMois * 100) / 100 : 0),
        [dureeParSeance, seancesDuMois],
    );

    useEffect(() => {
        if (heuresManuelles || dureeParSeance === null || seancesDuMois === 0) {
            return;
        }

        const valeur = totalHeuresCalcule.toFixed(2);
        setSaisie((precedent) => (precedent.heures === valeur ? precedent : { ...precedent, heures: valeur }));
    }, [heuresManuelles, dureeParSeance, seancesDuMois, totalHeuresCalcule]);

    const formulaireComplet =
        saisie.groupFilter !== '' &&
        saisie.enseignantFilter !== '' &&
        saisie.mois !== '' &&
        enseignantChoisi !== null &&
        enseignantChoisi.probleme === null &&
        (enseignantChoisi.mode !== 'horaire' || (saisie.heures !== '' && Number(saisie.heures) > 0));

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
        setAjustements({});
        // `dureeSeance` est une aide de saisie : le serveur ne connaît que le
        // total d'heures, l'envoyer polluerait l'URL sans rien décider.
        const { dureeSeance: _, ...payload } = saisie;
        router.get('/backoffice/paiement-prof', { ...payload }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    const { reset: onReset } = useFilterReset(filters, reload);

    const estHoraire = calcul?.enseignant.mode === 'horaire';

    const totalAffiche = useMemo(() => {
        if (calcul === null) {
            return 0;
        }

        if (estHoraire) {
            return calcul.totalHoraire ?? 0;
        }

        return calcul.lignes.reduce((somme, ligne) => {
            const saisi = ajustements[ligne.studentId];
            const valeur = saisi !== undefined && saisi !== '' ? Number(saisi) : ligne.montantEffectif;

            return somme + (Number.isFinite(valeur) ? valeur : ligne.montantEffectif);
        }, 0);
    }, [calcul, ajustements, estHoraire]);

    const nombreAjustements = useMemo(
        () => Object.values(ajustements).filter((v) => v !== undefined && v !== '').length,
        [ajustements],
    );

    /** Premier jour de chaque semaine calendaire — trait visuel, aucune règle. */
    const debutsDeSemaine = useMemo(() => {
        const debuts = new Set<string>();

        if (calcul === null) {
            return debuts;
        }

        let semainePrecedente: string | null = null;

        for (const date of calcul.datesDeCours) {
            const d = new Date(date + 'T00:00:00');
            const lundi = new Date(d);
            lundi.setDate(d.getDate() - ((d.getDay() + 6) % 7));
            const cle = lundi.toISOString().slice(0, 10);

            if (semainePrecedente !== null && cle !== semainePrecedente) {
                debuts.add(date);
            }

            semainePrecedente = cle;
        }

        return debuts;
    }, [calcul]);

    function classeSemaine(date: string): string {
        return debutsDeSemaine.has(date) ? ' pp-wstart' : '';
    }

    function statutCellule(statut: string | undefined): { texte: string; classe: string } {
        if (statut === 'Présent') {
            return { texte: 'P', classe: 'pp-present' };
        }
        if (statut === 'Absent') {
            return { texte: 'A', classe: 'pp-absent' };
        }
        if (statut === 'Retard' || statut === 'Justifié') {
            return { texte: statut === 'Retard' ? 'R' : 'J', classe: 'pp-ignore' };
        }

        return { texte: '·', classe: 'pp-vide' };
    }

    function libelleMode(mode: string): string {
        if (mode === 'horaire') return t('Per hour');
        if (mode === 'gls') return t('GLS system');
        if (mode === 'win_win') return t('Win-win system');

        return mode;
    }

    function creerDepense() {
        if (calcul === null) {
            return;
        }

        // De quoi ROUVRIR ce calcul à l'identique depuis le modal de dépense :
        // relu avant de signer, le détail doit rester à un clic. Query string
        // seule (jamais une URL absolue) — c'est le modal qui la re-préfixe.
        const retour = new URLSearchParams({
            groupFilter: String(calcul.group.id),
            enseignantFilter: String(calcul.enseignant.id),
            mois: calcul.mois,
            ...(calcul.heuresSaisies !== null ? { heures: String(calcul.heuresSaisies) } : {}),
        });

        const params = new URLSearchParams({
            prefill_paiement_prof: '1',
            prefill_group_id: String(calcul.group.id),
            prefill_montant: totalAffiche.toFixed(2),
            prefill_periode_debut: calcul.periode.debut,
            prefill_periode_fin: calcul.periode.fin,
            prefill_description: `${t('Teacher payment')} — ${calcul.enseignant.nom} — ${calcul.group.nom} — ${calcul.periode.libelle}`,
            prefill_retour: `?${retour.toString()}`,
            ...(paiementProfTypeId !== null ? { prefill_type_depense_id: String(paiementProfTypeId) } : {}),
        });

        router.visit(`/backoffice/depenses?${params.toString()}`);
    }

    return (
        <BackofficeLayout title={t('Teacher payment calculation')}>
            <style>{`
                .pp-wrap { overflow: auto; max-height: 70vh; }
                .pp-wrap table { margin: 0; border-collapse: separate; border-spacing: 0; white-space: nowrap; }
                .pp-wrap thead th {
                    position: sticky; top: 0; z-index: 10;
                    background: #F2F4F8; color: #202C4B;
                    padding: 8px 4px; text-align: center; font-size: 12px; font-weight: 600;
                    border-bottom: 1px solid var(--bs-border-color);
                }
                .pp-num  { position: sticky; left: 0;    z-index: 4; width: 40px; background: var(--bs-body-bg); }
                .pp-nom  { position: sticky; left: 40px; z-index: 4; min-width: 190px; max-width: 190px;
                           overflow: hidden; text-overflow: ellipsis; background: var(--bs-body-bg);
                           box-shadow: 4px 0 6px -4px rgba(0,0,0,.2); }
                .pp-wrap thead .pp-num, .pp-wrap thead .pp-nom { z-index: 12; background: #F2F4F8; }
                .pp-compte { min-width: 78px; text-align: center; white-space: nowrap; }
                .pp-jour { width: 38px; min-width: 38px; text-align: center; padding: 4px 0 !important; }
                .pp-pastille { display: inline-flex; align-items: center; justify-content: center;
                               width: 24px; height: 24px; border-radius: 50%;
                               font-size: 11px; font-weight: 600; line-height: 1; }
                .pp-present .pp-pastille { background: #E7F7EF; color: #0F7A43; }
                .pp-absent  .pp-pastille { background: #FDECEE; color: #C32232; }
                .pp-ignore  .pp-pastille { background: #EEF0F3; color: #6C757D; }
                /* Opacity on the dot only: on the <td> it also faded the
                   week divider (border-left), which then alternated bold/pale. */
                .pp-vide .pp-point { color: var(--bs-secondary-color); opacity: .3; }
                .pp-wstart { border-left: 2px solid #E23744 !important; }
                .pp-sem { min-width: 92px; text-align: center; padding: 6px 8px !important; }
                .pp-sem-first { border-left: 2px solid #E23744 !important; }
                .pp-total { position: sticky; right: 0; z-index: 4; min-width: 130px; text-align: right;
                            font-weight: 600; background: var(--bs-body-bg);
                            box-shadow: -4px 0 6px -4px rgba(0,0,0,.2); }
                .pp-wrap thead .pp-total { z-index: 12; background: #F2F4F8; }
                .pp-ajust { width: 100px; text-align: right; font-weight: 600; }
                .pp-ajust.pp-modifie { border-color: var(--bs-warning) !important; background: #FFFBF0; }
                .pp-wrap tbody tr:hover td { background: #F7F9FC; }
                .pp-wrap tbody tr:hover td.pp-num,
                .pp-wrap tbody tr:hover td.pp-nom,
                .pp-wrap tbody tr:hover td.pp-total { background: #F7F9FC; }
                .pp-wrap tfoot td { position: sticky; bottom: 0; z-index: 9;
                                    background: #F2F4F8; font-weight: 600;
                                    border-top: 1px solid var(--bs-border-color); }
                .pp-legende { font-size: 12px; }
            `}</style>

            {/* ── Barre d'action ─────────────────────────────────────── */}
            <div className="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    {calcul !== null && (
                        <span className="text-muted">
                            <strong>{calcul.enseignant.nom}</strong>
                            {' · '}
                            {calcul.group.nom}
                            {' · '}
                            {calcul.periode.libelle}
                            <span className="ms-2 badge badge-soft-info">{libelleMode(calcul.enseignant.mode)}</span>
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
                        {calcul === null ? t('New calculation') : t('Change')}
                    </button>
                </div>
            </div>

            {/* ── Modal de saisie ────────────────────────────────────── */}
            <Modal
                show={showModal}
                title={t('New teacher payment calculation')}
                onClose={() => setShowModal(false)}
                size="lg"
                footer={
                    <>
                        <button type="button" className="btn btn-light" onClick={() => setShowModal(false)}>
                            {t('Cancel')}
                        </button>
                        <button
                            type="button"
                            className="btn btn-primary"
                            disabled={!formulaireComplet || chargement}
                            onClick={lancerCalcul}
                        >
                            <i className="ti ti-calculator me-1" />
                            {t('Calculate')}
                        </button>
                    </>
                }
            >
                <SelectField
                    id="pp-group"
                    label={t('Group')}
                    required
                    value={saisie.groupFilter}
                    options={groupOptions}
                    placeholder={t('Select a group')}
                    onChange={(event) =>
                        // Changer de groupe remet prof et mois à zéro : ils
                        // n'ont de sens que pour LE groupe choisi.
                        majSaisie({ groupFilter: event.target.value, enseignantFilter: '', mois: '', heures: '', dureeSeance: '' })
                    }
                />

                {saisie.groupFilter !== '' && (
                    <>
                        {chargement && options === null && (
                            <p className="text-muted fs-13">
                                <i className="ti ti-loader me-1" />
                                {t('Loading…')}
                            </p>
                        )}

                        {options !== null && (
                            <>
                                {/* Séances sans prof : elles ne paient PERSONNE. On
                                    le dit ici, avant le calcul, avec le lien qui
                                    permet de les ré-affecter. */}
                                {options.seancesSansEnseignant > 0 && (
                                    <div className="alert alert-warning d-flex align-items-start gap-2 fs-13">
                                        <i className="ti ti-alert-triangle mt-1" />
                                        <div>
                                            {t(
                                                ':count completed session(s) of this month have no teacher assigned and will pay nobody.',
                                                { count: String(options.seancesSansEnseignant) },
                                            )}{' '}
                                            <a href={`/backoffice/groups/${saisie.groupFilter}`} className="fw-semibold">
                                                {t('Fix them on the group’s sessions')} →
                                            </a>
                                        </div>
                                    </div>
                                )}

                                <div className="row">
                                    <div className="col-md-6">
                                        <SelectField
                                            id="pp-enseignant"
                                            label={t('Teacher')}
                                            required
                                            value={saisie.enseignantFilter}
                                            options={enseignantOptions}
                                            placeholder={t('Select a teacher')}
                                            onChange={(event) =>
                                                majSaisie({ enseignantFilter: event.target.value, heures: '', dureeSeance: '' })
                                            }
                                        />
                                    </div>
                                    <div className="col-md-6">
                                        <SelectField
                                            id="pp-mois"
                                            label={t('Month')}
                                            required
                                            value={saisie.mois}
                                            options={moisOptions}
                                            placeholder={t('Select a month')}
                                            onChange={(event) => majSaisie({ mois: event.target.value })}
                                            searchable={false}
                                        />
                                        {options.fenetre.ancreSurLeGroupe ? (
                                            <div className="form-text mt-n2 mb-3">
                                                {new Date(options.fenetre.debut + 'T00:00:00').toLocaleDateString('fr-FR')}
                                                {' → '}
                                                {new Date(options.fenetre.fin + 'T00:00:00').toLocaleDateString('fr-FR')}
                                            </div>
                                        ) : (
                                            <div className="form-text mt-n2 mb-3 text-warning">
                                                {t('This group has no start date — calendar month used.')}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {enseignantChoisi !== null && (
                                    <div className="alert alert-light border">
                                        <div className="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div>
                                                <span className="badge badge-soft-info me-2">
                                                    {libelleMode(enseignantChoisi.mode)}
                                                </span>
                                                {enseignantChoisi.probleme === null ? (
                                                    <span>
                                                        {enseignantChoisi.mode === 'horaire'
                                                            ? `${enseignantChoisi.taux.toFixed(2)} MAD / ${t('hour')}`
                                                            : `${enseignantChoisi.taux.toFixed(2)} MAD / ${t('student')}`}
                                                    </span>
                                                ) : (
                                                    <span className="text-danger">{enseignantChoisi.probleme}</span>
                                                )}
                                            </div>
                                            <a href="/backoffice/employees" className="fs-13">
                                                {t('Edit on the employee record')} →
                                            </a>
                                        </div>

                                        {enseignantChoisi.probleme === null && enseignantChoisi.mode !== 'horaire' && (
                                            <div className="fs-13 text-muted mt-2">
                                                {t(
                                                    'Each student earns the rate divided by the number of sessions, times their attendance (:max sessions per month at most).',
                                                ).replace(':max', String(seancesMaxParMois))}
                                            </div>
                                        )}
                                    </div>
                                )}

                                {/* Mode horaire : le seul cas où l'opérateur
                                    saisit quelque chose de plus.

                                    La durée PAR SÉANCE est multipliée par le
                                    nombre de séances du mois : additionner 22
                                    séances à la main est une source d'erreur
                                    inutile. Le total reste modifiable — un mois
                                    avec une séance écourtée ne rentre pas dans
                                    la multiplication. */}
                                {enseignantChoisi?.mode === 'horaire' && enseignantChoisi.probleme === null && (
                                    <div className="row">
                                        <div className="col-md-6">
                                            <FormField
                                                id="pp-duree-seance"
                                                label={t('Duration per session')}
                                                type="text"
                                                inputMode="decimal"
                                                placeholder="2h30"
                                                value={saisie.dureeSeance}
                                                onChange={(event) => majSaisie({ dureeSeance: event.target.value })}
                                            />
                                            <div className="form-text mt-n2 mb-3">
                                                {dureeParSeance !== null ? (
                                                    <span className="text-success">
                                                        {formatDuree(dureeParSeance)} × {seancesDuMois}{' '}
                                                        {t('sessions')} = <strong>{formatDuree(totalHeuresCalcule)}</strong>
                                                    </span>
                                                ) : saisie.dureeSeance !== '' ? (
                                                    <span className="text-danger">
                                                        {t('Unreadable duration — use 2h30, 2:30 or 2.30.')}
                                                    </span>
                                                ) : (
                                                    t('e.g. 2h30 — multiplied by the month’s sessions.')
                                                )}
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <FormField
                                                id="pp-heures"
                                                label={t('Total hours taught in this group this month')}
                                                type="number"
                                                step="0.25"
                                                min="0"
                                                required
                                                value={saisie.heures}
                                                onChange={(event) => {
                                                    // Saisie manuelle : elle PRIME sur la
                                                    // multiplication, qui cesse alors de
                                                    // réécrire le champ.
                                                    setHeuresManuelles(true);
                                                    majSaisie({ heures: event.target.value });
                                                }}
                                            />
                                            <div className="form-text mt-n2 mb-3 d-flex justify-content-between gap-2">
                                                <span>
                                                    {saisie.heures !== '' && Number(saisie.heures) > 0
                                                        ? formatDuree(Number(saisie.heures))
                                                        : t('Editable — overrides the calculation.')}
                                                </span>
                                                {heuresManuelles && dureeParSeance !== null && (
                                                    <button
                                                        type="button"
                                                        className="btn btn-link btn-sm p-0 fs-12 text-decoration-none"
                                                        onClick={() => {
                                                            setHeuresManuelles(false);
                                                            majSaisie({ heures: totalHeuresCalcule.toFixed(2) });
                                                        }}
                                                    >
                                                        <i className="ti ti-arrow-back-up me-1" />
                                                        {t('Recalculate')}
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </>
                )}
            </Modal>

            {/* ── Résultat ───────────────────────────────────────────── */}
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
            ) : calcul.enseignant.probleme !== null ? (
                <Card>
                    <EmptyState
                        title={t('This teacher cannot be paid yet')}
                        message={calcul.enseignant.probleme}
                        icon="ti ti-user-exclamation"
                    >
                        <a href="/backoffice/employees" className="btn btn-primary">
                            {t('Edit on the employee record')}
                        </a>
                    </EmptyState>
                </Card>
            ) : calcul.nombreSeances === 0 ? (
                <Card>
                    <EmptyState
                        title={t('No completed session for this teacher over this month')}
                        message={t(
                            'Only sessions marked « Effectuée » and assigned to this teacher are paid.',
                        )}
                    />
                </Card>
            ) : (
                <>
                    {calcul.seancesSansEnseignant > 0 && (
                        <div className="alert alert-warning d-flex align-items-center gap-2">
                            <i className="ti ti-alert-triangle" />
                            <span>
                                {t(
                                    ':count completed session(s) of this month have no teacher assigned and will pay nobody.',
                                    { count: String(calcul.seancesSansEnseignant) },
                                )}{' '}
                                <a href={`/backoffice/groups/${calcul.group.id}`} className="fw-semibold">
                                    {t('Fix them on the group’s sessions')} →
                                </a>
                            </span>
                        </div>
                    )}

                    {calcul.seancesHoraireInvalide.length > 0 && (
                        <div className="alert alert-warning d-flex align-items-center gap-2">
                            <i className="ti ti-clock-exclamation" />
                            <span>
                                {t(
                                    ':count session(s) have an unusable schedule (missing or reversed) and count as 0 h: :dates',
                                    {
                                        count: String(calcul.seancesHoraireInvalide.length),
                                        dates: calcul.seancesHoraireInvalide
                                            .map((d) => new Date(d + 'T00:00:00').toLocaleDateString('fr-FR'))
                                            .join(', '),
                                    },
                                )}{' '}
                                <a href={`/backoffice/groups/${calcul.group.id}`} className="fw-semibold">
                                    {t('Fix them on the group’s sessions')} →
                                </a>
                            </span>
                        </div>
                    )}

                    {/* ── Récapitulatif ─────────────────────────────── */}
                    <div className="row g-3 mb-3">
                        <div className="col-6 col-md d-flex">
                            <Card className="w-100 h-100">
                                <div className="text-muted fs-12 text-uppercase mb-1">{t('Total payment')}</div>
                                <div className="fs-24 fw-bold text-success">{totalAffiche.toFixed(2)} MAD</div>
                                {nombreAjustements > 0 && (
                                    <small className="text-warning">
                                        <i className="ti ti-pencil me-1" />
                                        {t(':count adjusted line(s) · computed :total', {
                                            count: String(nombreAjustements),
                                            total: calcul.total.toFixed(2),
                                        })}
                                    </small>
                                )}
                            </Card>
                        </div>
                        <div className="col-6 col-md d-flex">
                            <Card className="w-100 h-100">
                                <div className="text-muted fs-12 text-uppercase mb-1">{t('Sessions')}</div>
                                <div className="fs-24 fw-bold">{calcul.nombreSeances}</div>
                                <small className="text-muted">
                                    {calcul.heuresEffectuees > 0 && `${calcul.heuresEffectuees} h`}
                                </small>
                            </Card>
                        </div>
                        {estHoraire ? (
                            <>
                                <div className="col-6 col-md d-flex">
                                    <Card className="w-100 h-100">
                                        <div className="text-muted fs-12 text-uppercase mb-1">{t('Hours retained')}</div>
                                        <div className="fs-24 fw-bold">{(calcul.heuresSaisies ?? 0).toFixed(2)} h</div>
                                    </Card>
                                </div>
                                <div className="col-6 col-md d-flex">
                                    <Card className="w-100 h-100">
                                        <div className="text-muted fs-12 text-uppercase mb-1">{t('Hourly rate')}</div>
                                        <div className="fs-24 fw-bold">
                                            {calcul.enseignant.taux.toFixed(2)} <small className="fs-14">MAD</small>
                                        </div>
                                    </Card>
                                </div>
                            </>
                        ) : (
                            <>
                                <div className="col-6 col-md d-flex">
                                    <Card className="w-100 h-100">
                                        <div className="text-muted fs-12 text-uppercase mb-1">{t('Paying students')}</div>
                                        <div className="fs-24 fw-bold">
                                            {calcul.etudiantsRemunerateurs} / {calcul.lignes.length}
                                        </div>
                                    </Card>
                                </div>
                                <div className="col-6 col-md d-flex">
                                    <Card className="w-100 h-100">
                                        <div className="text-muted fs-12 text-uppercase mb-1">{t('Rate per student')}</div>
                                        <div className="fs-24 fw-bold">
                                            {calcul.montantParEtudiant.toFixed(2)} <small className="fs-14">MAD</small>
                                        </div>
                                        <small className="text-muted">{libelleMode(calcul.enseignant.mode)}</small>
                                    </Card>
                                </div>
                            </>
                        )}
                    </div>

                    {!estHoraire && calcul.nombreSeances > calcul.seancesRemunerees && (
                        <div className="alert alert-info d-flex align-items-center gap-2">
                            <i className="ti ti-info-circle" />
                            <span>
                                {t(
                                    'This period holds :real sessions; the rate is divided by :max (monthly cap), so full attendance earns more than the rate.',
                                )
                                    .replace(':real', String(calcul.nombreSeances))
                                    .replace(':max', String(calcul.seancesRemunerees))}
                            </span>
                        </div>
                    )}

                    {/* Mode horaire : pas de grille par étudiant, le total suffit. */}
                    {estHoraire && (
                        <Card
                            title={`${calcul.enseignant.nom} — ${calcul.group.nom}`}
                            tools={
                                <div className="d-flex align-items-center gap-2">
                                    <span className="badge badge-soft-warning">
                                        <i className="ti ti-file-pencil me-1" />
                                        {t('Draft')}
                                    </span>
                                    {canCreateDepense && totalAffiche > 0 && (
                                        <button type="button" className="btn btn-primary btn-sm" onClick={creerDepense}>
                                            <i className="ti ti-cash me-1" />
                                            {t('Record the expense')}
                                        </button>
                                    )}
                                </div>
                            }
                        >
                            <div className="fs-16">
                                {(calcul.heuresSaisies ?? 0).toFixed(2)} h × {calcul.enseignant.taux.toFixed(2)} MAD ={' '}
                                <strong>{totalAffiche.toFixed(2)} MAD</strong>
                            </div>
                            <div className="text-muted fs-13 mt-1">
                                {t(':count completed sessions over :libelle (:debut → :fin).', {
                                    count: String(calcul.nombreSeances),
                                    libelle: calcul.periode.libelle,
                                    debut: new Date(calcul.periode.debut + 'T00:00:00').toLocaleDateString('fr-FR'),
                                    fin: new Date(calcul.periode.fin + 'T00:00:00').toLocaleDateString('fr-FR'),
                                })}
                            </div>
                        </Card>
                    )}

                    {!estHoraire && (
                    <>
                    {/* ── Grille de présence + paie ─────────────────── */}
                    <Card
                        title={`${calcul.enseignant.nom} — ${calcul.group.nom}`}
                        bodyClassName="p-0 py-3"
                        tools={
                            <div className="d-flex align-items-center gap-2">
                                {/* Ce que l'écran EST : une proposition. Rien
                                    n'est enregistré tant que la dépense n'a pas
                                    été saisie, et le badge doit le dire — un
                                    total affiché se lit sinon comme une paie
                                    déjà actée. */}
                                <span className="badge badge-soft-warning">
                                    <i className="ti ti-file-pencil me-1" />
                                    {t('Draft')}
                                </span>
                                {canCreateDepense && totalAffiche > 0 && (
                                    <button type="button" className="btn btn-primary btn-sm" onClick={creerDepense}>
                                        <i className="ti ti-cash me-1" />
                                        {t('Record the expense')}
                                    </button>
                                )}
                            </div>
                        }
                    >
                        <div className="pp-wrap">
                            <table className="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th className="pp-num">#</th>
                                        <th className="pp-nom text-start ps-2">{t('Student')}</th>
                                        {/* Présences / absences de l'étudiant sur la
                                            période — le détail jour par jour est à
                                            droite, mais le total se lit d'un coup. */}
                                        <th className="pp-compte">P / A</th>
                                        {calcul.datesDeCours.map((date) => {
                                            // ⚠ « T00:00:00 » : sans lui, new Date('2026-06-01')
                                            // est lu en UTC et le jour AFFICHÉ recule d'un cran
                                            // dans un fuseau négatif — l'en-tête annoncerait
                                            // « LUN 31 » au-dessus des appels du mardi 1er.
                                            const d = new Date(date + 'T00:00:00');

                                            return (
                                                <th
                                                    key={date}
                                                    className={`pp-jour${classeSemaine(date)}`}
                                                    title={d.toLocaleDateString('fr-FR')}
                                                >
                                                    {d.toLocaleDateString('fr-FR', { weekday: 'short' })
                                                        .slice(0, 3)
                                                        .toUpperCase()}
                                                    <br />
                                                    <span className="opacity-75">{String(d.getDate()).padStart(2, '0')}</span>
                                                </th>
                                            );
                                        })}
                                        {/* Montant CALCULÉ (présences × part de séance),
                                            puis la correction manuelle à côté : on voit
                                            d'où l'on part avant de déroger. */}
                                        <th className="pp-sem pp-sem-first">{t('Adjustment')}</th>
                                        <th className="pp-total">{t('Total')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {calcul.lignes.map((ligne, index) => {
                                        const appels = calcul.grille[ligne.studentId] ?? {};
                                        const saisi = ajustements[ligne.studentId];
                                        const estModifie = saisi !== undefined && saisi !== '';
                                        const effectif =
                                            estModifie && Number.isFinite(Number(saisi))
                                                ? Number(saisi)
                                                : ligne.montantEffectif;

                                        return (
                                            <tr key={ligne.studentId}>
                                                <td className="pp-num text-center text-muted">{index + 1}</td>
                                                <td className="pp-nom fw-semibold ps-2">{ligne.nom}</td>
                                                <td
                                                    className="pp-compte"
                                                    title={
                                                        ligne.joursIgnores > 0
                                                            ? t(':count ignored (late / excused)', {
                                                                  count: String(ligne.joursIgnores),
                                                              })
                                                            : undefined
                                                    }
                                                >
                                                    <span className="text-success fw-semibold">
                                                        {ligne.joursRetenus}
                                                    </span>
                                                    <span className="text-muted mx-1">/</span>
                                                    <span className="text-danger fw-semibold">
                                                        {ligne.joursAbsents}
                                                    </span>
                                                    {ligne.joursIgnores > 0 && (
                                                        <span className="text-muted ms-1">
                                                            (+{ligne.joursIgnores})
                                                        </span>
                                                    )}
                                                </td>

                                                {calcul.datesDeCours.map((date) => {
                                                    const cellule = statutCellule(appels[date]);
                                                    const statut = appels[date];

                                                    return (
                                                        <td
                                                            key={date}
                                                            className={`pp-jour ${cellule.classe}${classeSemaine(date)}`}
                                                            title={`${new Date(date + 'T00:00:00').toLocaleDateString('fr-FR')} — ${statut ?? t('No roll call')}`}
                                                        >
                                                            {statut !== undefined ? (
                                                                <span className="pp-pastille">{cellule.texte}</span>
                                                            ) : (
                                                                <span className="pp-point">{cellule.texte}</span>
                                                            )}
                                                        </td>
                                                    );
                                                })}

                                                {/* Montant calculé — et COMMENT il l'a été :
                                                    « 18 × 22.73 » rend le chiffre
                                                    vérifiable sans quitter la ligne. */}
                                                <td className="pp-sem pp-sem-first">
                                                    <input
                                                        type="number"
                                                        className={`form-control form-control-sm pp-ajust${
                                                            estModifie ? ' pp-modifie' : ''
                                                        }`}
                                                        step="0.01"
                                                        min="0"
                                                        aria-label={`${t('Adjustment')} — ${ligne.nom}`}
                                                        placeholder={ligne.montantAuto.toFixed(2)}
                                                        value={saisi ?? ''}
                                                        onChange={(event) =>
                                                            setAjustements((previous) => ({
                                                                ...previous,
                                                                [ligne.studentId]: event.target.value,
                                                            }))
                                                        }
                                                    />
                                                    {estModifie && (
                                                        <button
                                                            type="button"
                                                            className="btn btn-link btn-sm p-0 fs-12 text-decoration-none"
                                                            // Revenir au montant CALCULÉ : sans ce
                                                            // retour en arrière, une correction
                                                            // tapée par erreur ne peut plus être
                                                            // annulée qu'en relançant tout le calcul.
                                                            onClick={() =>
                                                                setAjustements((previous) => {
                                                                    const suite = { ...previous };
                                                                    delete suite[ligne.studentId];

                                                                    return suite;
                                                                })
                                                            }
                                                        >
                                                            <i className="ti ti-arrow-back-up me-1" />
                                                            {t('Reset')}
                                                        </button>
                                                    )}
                                                </td>

                                                <td
                                                    className={`pp-total ${effectif > 0 ? '' : 'text-muted'}`}
                                                >
                                                    {effectif.toFixed(2)} MAD
                                                    {estModifie && (
                                                        <div className="fs-12 text-warning fw-normal">
                                                            <i className="ti ti-pencil me-1" />
                                                            {t('adjusted')} ({ligne.montantAuto.toFixed(2)})
                                                        </div>
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
                                        <td className="pp-compte" />
                                        {calcul.datesDeCours.map((date) => (
                                            <td key={date} className={`pp-jour${classeSemaine(date)}`} />
                                        ))}
                                        <td className="pp-sem pp-sem-first" />
                                        <td className="pp-total">{totalAffiche.toFixed(2)} MAD</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </Card>
                    </>
                    )}
                </>
            )}
        </BackofficeLayout>
    );
}
