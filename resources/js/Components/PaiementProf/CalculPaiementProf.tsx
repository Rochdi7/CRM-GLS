import { useEffect, useMemo, useState } from 'react';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import Modal from '@/Components/Modals/Modal';
import SelectField from '@/Components/Forms/SelectField';
import FormField from '@/Components/Forms/FormField';
import { t } from '@/Lib/i18n';
import { formatDuree, parseDuree } from '@/Lib/duree';
import type { PaiementProfCalcul, PaiementProfFilters, PaiementProfGroupOptions, SelectOption } from '@/Types';

/**
 * Couleur de la cellule « Étudiant » selon le statut du dossier — la MÊME
 * répartition que « Absence par groupe » (Seances/AbsenceParGroupe.tsx
 * ROW_CLASS) : rien tant que l'inscription est Active, rouge pour une
 * annulation, gris pour tout autre dossier clos ou sans inscription.
 */
function classeNom(statut: string | null): string {
    if (statut === 'Active') return '';
    if (statut === 'Annulée') return 'pp-annulee';

    return 'pp-clos';
}

/**
 * Présences minimales d'un palier (`paliersPaie` = { présences min: semaines }).
 * Le barème vient du serveur (`CalculerPaiementProfParPaliers::PALIERS`) :
 * l'écran ne le recopie pas, il le lit.
 */
function seuilPalier(paliers: Record<string, number>, semaines: number): number {
    const entree = Object.entries(paliers).find(([, s]) => s === semaines);

    return entree ? Number(entree[0]) : 0;
}

/** Libellé du palier atteint par une ligne (0, 1, 2 ou 4 semaines). */
function libellePalier(semaines: number): string {
    if (semaines >= 4) return t('Full month');
    if (semaines === 0) return t('Below the minimum');

    return t(':count week(s)', { count: String(semaines) });
}

/** Les 4 paramètres qui identifient un calcul — ce que l'hôte met dans son URL. */
export interface PaiementProfCalculParams {
    groupFilter: string;
    enseignantFilter: string;
    mois: string;
    heures: string;
}

/**
 * Ce que « Enregistrer la dépense » PROPOSE au modal « Paiement prof » —
 * un pré-remplissage, jamais une écriture. `retour` permet à l'hôte de
 * ROUVRIR ce calcul à l'identique (relu avant de signer).
 */
export interface PaiementProfPrefill {
    groupId: number;
    enseignantId: number;
    montant: string;
    periodeDebut: string;
    periodeFin: string;
    description: string;
    retour: PaiementProfCalculParams;
}

interface CalculPaiementProfProps {
    calcul: PaiementProfCalcul | null;
    filters: PaiementProfFilters;
    groupOptions: SelectOption[];
    paliersPaie: Record<string, number>;
    canCreateDepense: boolean;
    /**
     * (Re)charger le calcul : l'hôte met les paramètres dans SA propre URL
     * (l'écran dédié tels quels, l'onglet Dépenses préfixés `pp`). `null`
     * = effacer le calcul.
     */
    onNavigate: (params: PaiementProfCalculParams | null) => void;
    /** « Enregistrer la dépense » — l'hôte ouvre le modal « Paiement prof » pré-rempli. */
    onEnregistrer: (prefill: PaiementProfPrefill) => void;
    /**
     * Compteur : chaque incrément ouvre le modal de saisie — pour que le
     * bouton de l'hôte (« Ajouter un paiement prof ») lance le calcul.
     */
    openRequest?: number;
    /**
     * Intégré dans un autre écran (onglet Paiements prof) : pas de bouton
     * « Nouveau calcul » ni d'état vide propres — l'hôte porte le bouton et
     * la liste des paiements enregistrés est juste en dessous.
     */
    embedded?: boolean;
}

/**
 * « Calcul paiement prof » — dérive, depuis les appels DÉJÀ SAISIS, le
 * montant dû à UN enseignant pour UN groupe sur UN mois de groupe, selon
 * le mode de paie configuré sur SA fiche (onglet « Paiement prof »).
 *
 * Composant PARTAGÉ (30/09/2026) par l'écran dédié
 * (`Pages/Backoffice/PaiementProf/Index.tsx`, toujours servi par sa route)
 * et par l'onglet « Paiements prof » de Gestion des dépenses, qui est
 * désormais l'entrée normale — la barre latérale ne pointe plus vers
 * l'écran dédié.
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
export default function CalculPaiementProf({
    calcul,
    filters,
    groupOptions,
    paliersPaie,
    canCreateDepense,
    onNavigate,
    onEnregistrer,
    openRequest = 0,
    embedded = false,
}: CalculPaiementProfProps) {
    // Ajustements manuels — état LOCAL, jamais persisté.
    const [ajustements, setAjustements] = useState<Record<number, string>>({});
    // Base « Détails paiement » (Système GLS seulement) — décochée par défaut :
    // le calcul par séances reste la référence, celui-ci est un contrôle.
    const [parPaiements, setParPaiements] = useState(false);

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

    // Le bouton de l'hôte : chaque incrément du compteur ouvre le modal.
    useEffect(() => {
        if (openRequest > 0) {
            ouvrirModal();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [openRequest]);

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
        label: e.seancesCeMois > 0 ? `${e.label} - ${e.seancesCeMois} ${t('sessions')}` : e.label,
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

    function lancerCalcul() {
        if (!formulaireComplet) {
            return;
        }

        setShowModal(false);
        setAjustements({});
        setParPaiements(false);
        // `dureeSeance` est une aide de saisie : le serveur ne connaît que le
        // total d'heures, l'envoyer polluerait l'URL sans rien décider.
        const { dureeSeance: _, ...payload } = saisie;
        onNavigate(payload);
    }

    function onReset() {
        setAjustements({});
        setParPaiements(false);
        onNavigate(null);
    }

    const estHoraire = calcul?.enseignant.mode === 'horaire';

    const verification = calcul?.verificationPaiements ?? null;
    const baseePaiements = parPaiements && verification !== null;

    const totalSeances = useMemo(() => {
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

    // Le total AFFICHÉ — et celui que « Enregistrer la dépense » pré-remplit —
    // suit la base choisie par la case à cocher.
    const totalAffiche = baseePaiements && verification !== null ? verification.total : totalSeances;

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

        onEnregistrer({
            groupId: calcul.group.id,
            // L'enseignant PAYÉ, tel que le calcul l'a désigné — figé sur la
            // dépense, jamais redéduit du groupe (23/09/2026).
            enseignantId: calcul.enseignant.id,
            montant: totalAffiche.toFixed(2),
            periodeDebut: calcul.periode.debut,
            periodeFin: calcul.periode.fin,
            // Pas de « Paiement prof » en tête : la colonne Type le dit déjà.
            // Séparateur « - » simple, jamais le tiret cadratin.
            description: [calcul.enseignant.nom, calcul.group.nom, calcul.periode.libelle].join(' - '),
            // De quoi ROUVRIR ce calcul à l'identique depuis le modal de
            // dépense : relu avant de signer, le détail doit rester à un clic.
            retour: {
                groupFilter: String(calcul.group.id),
                enseignantFilter: String(calcul.enseignant.id),
                mois: calcul.mois,
                heures: calcul.heuresSaisies !== null ? String(calcul.heuresSaisies) : '',
            },
        });
    }

    return (
        <>
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
                .pp-reste { text-transform: none; font-weight: 500; cursor: help; margin-top: 2px; }
                .pp-ajust { width: 100px; text-align: right; font-weight: 600; }
                .pp-ajust.pp-modifie { border-color: var(--bs-warning) !important; background: #FFFBF0; }
                .pp-wrap tbody tr:hover td { background: #F7F9FC; }
                .pp-wrap tbody tr:hover td.pp-num,
                .pp-wrap tbody tr:hover td.pp-nom,
                .pp-wrap tbody tr:hover td.pp-total { background: #F7F9FC; }
                /* Dossiers clos : mêmes couleurs que « Absence par groupe »
                   (gris = changement / expirée / archivée, rouge = annulée). */
                .pp-wrap tbody td.pp-nom.pp-clos,
                .pp-wrap tbody tr:hover td.pp-nom.pp-clos { background: rgb(170, 170, 170); color: #1a1a1a; }
                .pp-wrap tbody td.pp-nom.pp-annulee,
                .pp-wrap tbody tr:hover td.pp-nom.pp-annulee { background: rgb(246, 45, 81); color: #fff; }
                .pp-wrap tfoot td { position: sticky; bottom: 0; z-index: 9;
                                    background: #F2F4F8; font-weight: 600;
                                    border-top: 1px solid var(--bs-border-color); }
                .pp-legende { font-size: 12px; }
            `}</style>

            {/* ── Barre d'action ─────────────────────────────────────── */}
            {(!embedded || calcul !== null) && (
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
            )}

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
                                                {t('This group has no start date - calendar month used.')}
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
                                                    'Each student is paid by attendance tier: fewer than :un present → 0; :un–:deuxMoins1 → 1 week; :deux–:completMoins1 → 2 weeks; :complet or more → the full rate (1 week = rate ÷ 4).',
                                                    {
                                                        un: String(seuilPalier(paliersPaie, 1)),
                                                        deuxMoins1: String(seuilPalier(paliersPaie, 2) - 1),
                                                        deux: String(seuilPalier(paliersPaie, 2)),
                                                        completMoins1: String(seuilPalier(paliersPaie, 4) - 1),
                                                        complet: String(seuilPalier(paliersPaie, 4)),
                                                    },
                                                )}
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
                                                        {t('Unreadable duration - use 2h30, 2:30 or 2.30.')}
                                                    </span>
                                                ) : (
                                                    t('e.g. 2h30 - multiplied by the month’s sessions.')
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
                                                        : t('Editable - overrides the calculation.')}
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
                embedded ? null : (
                <Card>
                    <EmptyState
                        title={t('No calculation yet')}
                        message={t(
                            'The payment is computed from the roll-call already recorded for that group - nothing is imported.',
                        )}
                    >
                        <button type="button" className="btn btn-primary" onClick={ouvrirModal}>
                            <i className="ti ti-calculator me-1" />
                            {t('New calculation')}
                        </button>
                    </EmptyState>
                </Card>
                )
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
                                {baseePaiements && (
                                    <small className="text-info">
                                        <i className="ti ti-receipt me-1" />
                                        {t('Based on payment details')}
                                    </small>
                                )}
                                {!baseePaiements && nombreAjustements > 0 && (
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
                                            {baseePaiements && verification !== null
                                                ? `${verification.etudiantsPayants} / ${verification.lignes.length}`
                                                : `${calcul.etudiantsRemunerateurs} / ${calcul.lignes.length}`}
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

                    {/* ── Contrôle par les Détails paiement (Système GLS) ── */}
                    {verification !== null && (
                        <div className="card mb-3">
                            <div className="card-body py-2 d-flex flex-wrap align-items-center gap-3">
                                <div className="form-check form-switch mb-0">
                                    <input
                                        id="pp-par-paiements"
                                        type="checkbox"
                                        className="form-check-input"
                                        checked={parPaiements}
                                        disabled={verification.frais.length === 0}
                                        onChange={(event) => setParPaiements(event.target.checked)}
                                    />
                                    <label htmlFor="pp-par-paiements" className="form-check-label fw-semibold">
                                        {t('Calculate from payment details')}
                                    </label>
                                </div>
                                {verification.frais.length === 0 ? (
                                    <span className="text-warning fs-13">
                                        <i className="ti ti-alert-triangle me-1" />
                                        {t('No fee for :mois is assigned to this group.', {
                                            mois: calcul.periode.libelle,
                                        })}
                                    </span>
                                ) : (
                                    <span className="text-muted fs-13">
                                        {verification.frais.join(', ')}
                                        {' · '}
                                        {t('By sessions')} : <strong>{totalSeances.toFixed(2)} MAD</strong>
                                        {' · '}
                                        {t('By payments')} : <strong>{verification.total.toFixed(2)} MAD</strong>
                                        {' · '}
                                        {t('Difference')} :{' '}
                                        <strong
                                            className={
                                                Math.abs(verification.total - totalSeances) < 0.005
                                                    ? 'text-success'
                                                    : 'text-danger'
                                            }
                                        >
                                            {(verification.total - totalSeances).toFixed(2)} MAD
                                        </strong>
                                    </span>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Mode horaire : pas de grille par étudiant, le total suffit. */}
                    {estHoraire && (
                        <Card
                            title={`${calcul.enseignant.nom} - ${calcul.group.nom}`}
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

                    {/* ── Base « Détails paiement » : une ligne par dossier ── */}
                    {baseePaiements && verification !== null && (
                        <Card
                            title={`${calcul.enseignant.nom} - ${calcul.group.nom}`}
                            bodyClassName="p-0"
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
                            <div className="px-3 pt-3 text-muted fs-13">
                                {t('Rate × paid ÷ due on :frais (a fully paid fee earns the full rate).', {
                                    frais: verification.frais.join(', '),
                                })}
                            </div>
                            <div className="table-responsive">
                                <table className="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{t('Student')}</th>
                                            <th>{t('Status')}</th>
                                            <th className="text-end">{t('Due')}</th>
                                            <th className="text-end">{t('Paid')}</th>
                                            <th className="text-end">{t('Total')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {verification.lignes.length === 0 && (
                                            <tr>
                                                <td colSpan={6} className="text-center text-muted py-3">
                                                    {t('No student carries this fee.')}
                                                </td>
                                            </tr>
                                        )}
                                        {verification.lignes.map((ligne, index) => {
                                            const solde = ligne.du > 0 && ligne.paye >= ligne.du - 0.004;
                                            const classe = solde
                                                ? 'text-success'
                                                : ligne.paye > 0.004
                                                  ? 'text-warning'
                                                  : 'text-danger';

                                            return (
                                                <tr key={`${ligne.studentId}-${index}`}>
                                                    <td className="text-muted">{index + 1}</td>
                                                    <td className="fw-semibold">{ligne.nom}</td>
                                                    <td>{ligne.statut}</td>
                                                    <td className="text-end">{ligne.du.toFixed(2)} MAD</td>
                                                    <td className={`text-end fw-semibold ${classe}`}>
                                                        {ligne.paye.toFixed(2)} MAD
                                                    </td>
                                                    <td className={`text-end fw-bold${ligne.montant > 0 ? '' : ' text-muted'}`}>
                                                        {ligne.montant.toFixed(2)} MAD
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                    <tfoot>
                                        <tr className="fw-bold">
                                            <td />
                                            <td colSpan={4}>{t('Total')}</td>
                                            <td className="text-end">{verification.total.toFixed(2)} MAD</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </Card>
                    )}

                    {!estHoraire && !baseePaiements && (
                    <>
                    {/* ── Grille de présence + paie ─────────────────── */}
                    <Card
                        title={`${calcul.enseignant.nom} - ${calcul.group.nom}`}
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
                                                <td
                                                    className={`pp-nom fw-semibold ps-2 ${classeNom(ligne.inscriptionStatut)}`}
                                                    title={
                                                        ligne.inscriptionStatut === 'Active'
                                                            ? undefined
                                                            : ligne.inscriptionStatut === null
                                                              ? t('Not enrolled in this group')
                                                              : `Inscription ${ligne.inscriptionStatut}`
                                                    }
                                                >
                                                    {ligne.nom}
                                                </td>
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
                                                            title={`${new Date(date + 'T00:00:00').toLocaleDateString('fr-FR')} - ${statut ?? t('No roll call')}`}
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
                                                        aria-label={`${t('Adjustment')} - ${ligne.nom}`}
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
                                                    {estModifie ? (
                                                        <div className="fs-12 text-warning fw-normal">
                                                            <i className="ti ti-pencil me-1" />
                                                            {t('adjusted')} ({ligne.montantAuto.toFixed(2)})
                                                        </div>
                                                    ) : (
                                                        <div className="fs-12 text-muted fw-normal">
                                                            {libellePalier(ligne.semainesPayees)}
                                                        </div>
                                                    )}
                                                    {calcul.retardsPaiement[ligne.studentId] && (
                                                        <div>
                                                            <span
                                                                className="badge bg-danger pp-reste"
                                                                title={t(
                                                                    'Unpaid balance in this group (due date: :date). Check before paying the full amount.',
                                                                    {
                                                                        date: calcul.retardsPaiement[ligne.studentId]
                                                                            .dateEcheance,
                                                                    },
                                                                )}
                                                            >
                                                                <i className="ti ti-alert-triangle me-1" />
                                                                {t('Balance due: :montant MAD', {
                                                                    montant:
                                                                        calcul.retardsPaiement[ligne.studentId].montant,
                                                                })}
                                                            </span>
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
        </>
    );
}
