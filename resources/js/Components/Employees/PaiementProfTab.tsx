import { useState } from 'react';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import { t } from '@/Lib/i18n';
import type { SelectOption, TauxMensuelRow } from '@/Types';

export type ModePaiementProf = '' | 'horaire' | 'gls' | 'win_win';

interface Props {
    mode: ModePaiementProf;
    tauxHoraire: string;
    montantParEtudiant: string;
    tauxMensuels: TauxMensuelRow[];
    /** Groupes de l'enseignant (vide à la création : il n'en tient encore aucun). */
    groupes: SelectOption[];
    errors: Record<string, string | undefined>;
    onModeChange: (mode: ModePaiementProf) => void;
    onTauxHoraireChange: (value: string) => void;
    onMontantChange: (value: string) => void;
    onTauxMensuelsChange: (rows: TauxMensuelRow[]) => void;
}

/**
 * Onglet « Paiement prof » de la fiche employé (22/09/2026) — la
 * configuration de PAIE d'un enseignant.
 *
 * Trois modes, et l'écran ne montre QUE les champs du mode choisi :
 *
 *  - Par heure    : un taux horaire ; les heures sont saisies au calcul.
 *  - Système GLS  : un montant par étudiant, divisé par les séances du
 *                   mois (22 max) et multiplié par les présences.
 *  - Win-win      : même formule, mais le montant par étudiant change
 *                   CHAQUE MOIS (400, 420, 450… jusqu'à 600) — un tableau
 *                   mois → montant, saisi ici à la main. Chaque ligne
 *                   peut viser UN groupe (29/09/2026) : elle prime alors
 *                   sur la ligne « Tous les groupes » du même mois.
 *
 * Le contrat (quel champ est requis pour quel mode) est tenu côté serveur
 * par PaiementProfEnseignantRules ; ce composant ne fait que le refléter.
 */
export default function PaiementProfTab({
    mode,
    tauxHoraire,
    montantParEtudiant,
    tauxMensuels,
    groupes,
    errors,
    onModeChange,
    onTauxHoraireChange,
    onMontantChange,
    onTauxMensuelsChange,
}: Props) {
    const modeOptions: SelectOption[] = [
        { value: 'horaire', label: t('Per hour') },
        { value: 'gls', label: t('GLS system') },
        { value: 'win_win', label: t('Win-win system') },
    ];

    const libelleGroupe = (id: string) =>
        id === '' ? t('All groups') : (groupes.find((g) => String(g.value) === id)?.label ?? `#${id}`);

    // Win-win : on choisit D'ABORD le ou les groupes à configurer, puis on
    // saisit leurs mois (29/09/2026). Plusieurs groupes s'ouvrent à la fois —
    // une carte par groupe. '' = « Tous les groupes ». Pré-sélectionnés : les
    // groupes déjà configurés, pour qu'une fiche existante s'ouvre
    // directement sur ses montants.
    const [groupesChoisis, setGroupesChoisis] = useState<string[]>(() =>
        Array.from(new Set(tauxMensuels.map((r) => r.group_id))),
    );

    function basculerGroupe(id: string) {
        setGroupesChoisis((actuels) => (actuels.includes(id) ? actuels.filter((g) => g !== id) : [...actuels, id]));
    }

    const ligneEnErreur = (i: number) =>
        Boolean(
            errors[`taux_mensuels.${i}.group_id`] ||
                errors[`taux_mensuels.${i}.mois`] ||
                errors[`taux_mensuels.${i}.montant_par_etudiant`],
        );

    // Toutes les puces : « Tous les groupes », chaque groupe de l'enseignant,
    // puis un groupe qui porterait des lignes sans figurer dans la liste
    // (jamais cacher une ligne saisie). Rouge = une ligne refusée par le
    // serveur, visible même quand la carte est fermée.
    const idsPuces = Array.from(
        new Set(['', ...groupes.map((g) => String(g.value)), ...tauxMensuels.map((r) => r.group_id)]),
    );
    const puces = idsPuces.map((id) => ({
        id,
        count: tauxMensuels.filter((r) => r.group_id === id).length,
        enErreur: tauxMensuels.some((r, i) => r.group_id === id && ligneEnErreur(i)),
    }));

    // Cartes ouvertes, dans l'ordre des puces.
    const cartes = idsPuces.filter((id) => groupesChoisis.includes(id));

    const lignesDuGroupe = (groupId: string) =>
        tauxMensuels
            .map((row, index) => ({ row, index }))
            .filter(({ row }) => row.group_id === groupId)
            .sort((a, b) => a.row.mois.localeCompare(b.row.mois));

    function ajouterMois(groupId: string) {
        // Propose le mois qui suit la dernière ligne de CE groupe — la
        // progression win-win se fait mois après mois ; l'écran n'a pas à le
        // redemander.
        const dernierDuGroupe = tauxMensuels
            .filter((row) => row.group_id === groupId)
            .sort((a, b) => a.mois.localeCompare(b.mois))
            .at(-1);
        const base = dernierDuGroupe ? new Date(dernierDuGroupe.mois + '-01T00:00:00') : new Date();
        if (dernierDuGroupe) {
            base.setMonth(base.getMonth() + 1);
        }
        const mois = `${base.getFullYear()}-${String(base.getMonth() + 1).padStart(2, '0')}`;

        onTauxMensuelsChange([
            ...tauxMensuels,
            { group_id: groupId, mois, montant_par_etudiant: dernierDuGroupe?.montant_par_etudiant ?? '' },
        ]);
    }

    function majLigne(index: number, champ: keyof TauxMensuelRow, valeur: string) {
        onTauxMensuelsChange(tauxMensuels.map((row, i) => (i === index ? { ...row, [champ]: valeur } : row)));
    }

    function retirerLigne(index: number) {
        onTauxMensuelsChange(tauxMensuels.filter((_, i) => i !== index));
    }

    return (
        <div>
            <div className="row">
                <div className="col-md-6">
                    <SelectField
                        id="emp-mode-paiement"
                        label={t('Pay mode')}
                        options={modeOptions}
                        placeholder={t('- Not a paid teacher -')}
                        value={mode}
                        onChange={(event) => onModeChange(event.target.value as ModePaiementProf)}
                        error={errors.mode_paiement_prof}
                        searchable={false}
                    />
                </div>

                {mode === 'horaire' && (
                    <div className="col-md-6">
                        <FormField
                            id="emp-taux-horaire"
                            label={t('Hourly rate (MAD)')}
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            value={tauxHoraire}
                            onChange={(event) => onTauxHoraireChange(event.target.value)}
                            error={errors.taux_horaire_prof}
                        />
                    </div>
                )}

                {mode === 'gls' && (
                    <div className="col-md-6">
                        <FormField
                            id="emp-montant-etudiant"
                            label={t('Amount per student (MAD)')}
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            value={montantParEtudiant}
                            onChange={(event) => onMontantChange(event.target.value)}
                            error={errors.montant_par_etudiant_prof}
                        />
                    </div>
                )}
            </div>

            {mode === '' && (
                <p className="text-muted fs-13 mb-0">
                    <i className="ti ti-info-circle me-1" />
                    {t('Choose a pay mode to make this teacher payable from « Calcul paiement prof ».')}
                </p>
            )}

            {mode === 'win_win' && (
                <>
                    {errors.taux_mensuels && <div className="text-danger fs-13 mb-2">{errors.taux_mensuels}</div>}

                    {/* ── Étape 1 : cocher le ou les groupes à configurer ── */}
                    <label className="form-label">{t('Groups to configure')}</label>
                    <div className="d-flex flex-wrap gap-2 mb-3" role="group" aria-label={t('Groups to configure')}>
                        {puces.map((g) => {
                            const actif = groupesChoisis.includes(g.id);

                            return (
                                <button
                                    key={g.id === '' ? 'tous' : g.id}
                                    type="button"
                                    aria-pressed={actif}
                                    className={`btn btn-sm d-inline-flex align-items-center ${
                                        actif
                                            ? 'btn-primary'
                                            : g.enErreur
                                              ? 'btn-outline-danger'
                                              : 'btn-outline-light text-dark border'
                                    }`}
                                    onClick={() => basculerGroupe(g.id)}
                                >
                                    <i className={`ti ${actif ? 'ti-square-check' : 'ti-square'} me-1`} />
                                    {g.enErreur && <i className="ti ti-alert-circle me-1" />}
                                    {libelleGroupe(g.id)}
                                    {g.count > 0 && (
                                        <span className={`badge ms-2 ${actif ? 'bg-white text-primary' : 'bg-light text-dark'}`}>
                                            {g.count} {t('month(s)')}
                                        </span>
                                    )}
                                </button>
                            );
                        })}
                    </div>

                    {cartes.length === 0 && (
                        <div className="border rounded p-4 text-center text-muted bg-light">
                            <i className="ti ti-users-group fs-24 d-block mb-2" />
                            {t('Check one or more groups to enter their monthly amounts.')}
                        </div>
                    )}

                    {/* ── Étape 2 : une carte par groupe coché ─────────── */}
                    {cartes.map((groupeChoisi) => (
                        <div key={groupeChoisi === '' ? 'tous' : groupeChoisi} className="card border mb-3">
                            <div className="card-header d-flex align-items-center justify-content-between flex-wrap gap-2 py-2">
                                <div>
                                    <h6 className="mb-0">
                                        <i className="ti ti-calendar-dollar me-1 text-primary" />
                                        {libelleGroupe(groupeChoisi)}
                                    </h6>
                                    <small className="text-muted">
                                        {groupeChoisi === ''
                                            ? t('Used for every group that has no row of its own for that month.')
                                            : t('Takes priority over « All groups » for the same month.')}
                                    </small>
                                </div>
                                <div className="d-flex gap-2">
                                    <button
                                        type="button"
                                        className="btn btn-sm btn-primary"
                                        onClick={() => ajouterMois(groupeChoisi)}
                                    >
                                        <i className="ti ti-plus me-1" />
                                        {t('Add a month')}
                                    </button>
                                    {/* Fermer la carte = décocher le groupe. Ses mois
                                        restent saisis : rien n'est supprimé. */}
                                    <button
                                        type="button"
                                        className="btn btn-sm btn-icon btn-light"
                                        onClick={() => basculerGroupe(groupeChoisi)}
                                        aria-label={t('Close')}
                                        title={t('Close')}
                                    >
                                        <i className="ti ti-x" />
                                    </button>
                                </div>
                            </div>
                            <div className="card-body p-0">
                                {lignesDuGroupe(groupeChoisi).length === 0 ? (
                                    <div className="text-muted text-center py-4">{t('No month entered yet.')}</div>
                                ) : (
                                    <table className="table table-sm align-middle mb-0">
                                        <thead className="thead-light">
                                            <tr>
                                                <th className="ps-3">{t('Month')}</th>
                                                <th className="text-end">{t('Amount per student (MAD)')}</th>
                                                <th style={{ width: 56 }} />
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {lignesDuGroupe(groupeChoisi).map(({ row, index }) => {
                                                const messages = [
                                                    errors[`taux_mensuels.${index}.group_id`],
                                                    errors[`taux_mensuels.${index}.mois`],
                                                    errors[`taux_mensuels.${index}.montant_par_etudiant`],
                                                ].filter((m): m is string => Boolean(m));

                                                return (
                                                    <tr key={index}>
                                                        <td className="ps-3" style={{ width: 240 }}>
                                                            <input
                                                                type="month"
                                                                className={`form-control form-control-sm text-normal-case${
                                                                    errors[`taux_mensuels.${index}.mois`] ? ' is-invalid' : ''
                                                                }`}
                                                                value={row.mois}
                                                                onChange={(event) => majLigne(index, 'mois', event.target.value)}
                                                                aria-label={t('Month')}
                                                            />
                                                            {messages.length > 0 && (
                                                                <div className="text-danger fs-12 mt-1 text-normal-case">
                                                                    {messages.join(' · ')}
                                                                </div>
                                                            )}
                                                        </td>
                                                        <td>
                                                            <div className="input-group input-group-sm ms-auto" style={{ maxWidth: 220 }}>
                                                                <input
                                                                    type="number"
                                                                    step="0.01"
                                                                    min="0"
                                                                    placeholder="0.00"
                                                                    className={`form-control text-end${
                                                                        errors[`taux_mensuels.${index}.montant_par_etudiant`]
                                                                            ? ' is-invalid'
                                                                            : ''
                                                                    }`}
                                                                    value={row.montant_par_etudiant}
                                                                    onChange={(event) =>
                                                                        majLigne(index, 'montant_par_etudiant', event.target.value)
                                                                    }
                                                                    aria-label={t('Amount per student (MAD)')}
                                                                />
                                                                <span className="input-group-text">MAD</span>
                                                            </div>
                                                        </td>
                                                        <td className="text-end pe-3">
                                                            <button
                                                                type="button"
                                                                className="btn btn-sm btn-icon btn-light text-danger"
                                                                onClick={() => retirerLigne(index)}
                                                                aria-label={t('Remove')}
                                                                title={t('Remove')}
                                                            >
                                                                <i className="ti ti-trash" />
                                                            </button>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                )}
                            </div>
                        </div>
                    ))}
                </>
            )}
        </div>
    );
}
