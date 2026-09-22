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
 *                   mois → montant, saisi ici à la main.
 *
 * Le contrat (quel champ est requis pour quel mode) est tenu côté serveur
 * par PaiementProfEnseignantRules ; ce composant ne fait que le refléter.
 */
export default function PaiementProfTab({
    mode,
    tauxHoraire,
    montantParEtudiant,
    tauxMensuels,
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

    function moisSuivant(): string {
        // Propose le mois qui suit le dernier saisi — la progression win-win
        // se fait mois après mois, l'écran n'a pas à le redemander.
        const dernier = [...tauxMensuels].sort((a, b) => a.mois.localeCompare(b.mois)).at(-1);
        const base = dernier ? new Date(dernier.mois + '-01T00:00:00') : new Date();
        if (dernier) {
            base.setMonth(base.getMonth() + 1);
        }

        return `${base.getFullYear()}-${String(base.getMonth() + 1).padStart(2, '0')}`;
    }

    function ajouterMois() {
        const dernier = [...tauxMensuels].sort((a, b) => a.mois.localeCompare(b.mois)).at(-1);
        onTauxMensuelsChange([
            ...tauxMensuels,
            { mois: moisSuivant(), montant_par_etudiant: dernier?.montant_par_etudiant ?? '' },
        ]);
    }

    function majLigne(index: number, champ: keyof TauxMensuelRow, valeur: string) {
        onTauxMensuelsChange(tauxMensuels.map((row, i) => (i === index ? { ...row, [champ]: valeur } : row)));
    }

    function retirerLigne(index: number) {
        onTauxMensuelsChange(tauxMensuels.filter((_, i) => i !== index));
    }

    const lignesTriees = tauxMensuels
        .map((row, index) => ({ row, index }))
        .sort((a, b) => a.row.mois.localeCompare(b.row.mois));

    return (
        <div>
            <div className="row">
                <div className="col-md-6">
                    <SelectField
                        id="emp-mode-paiement"
                        label={t('Pay mode')}
                        options={modeOptions}
                        placeholder={t('— Not a paid teacher —')}
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

                    <div className="table-responsive" style={{ maxWidth: 560 }}>
                        <table className="table table-sm align-middle mb-2">
                            <thead className="thead-light">
                                <tr>
                                    <th style={{ width: 220 }}>{t('Month')}</th>
                                    <th className="text-end">{t('Amount per student (MAD)')}</th>
                                    {/* Colonne d'action : juste la largeur du bouton. */}
                                    <th style={{ width: 48 }} />
                                </tr>
                            </thead>
                            <tbody>
                                {lignesTriees.length === 0 && (
                                    <tr>
                                        <td colSpan={3} className="text-muted text-center py-3">
                                            {t('No month entered yet.')}
                                        </td>
                                    </tr>
                                )}
                                {lignesTriees.map(({ row, index }) => {
                                    const erreurMois = errors[`taux_mensuels.${index}.mois`];
                                    const erreurMontant = errors[`taux_mensuels.${index}.montant_par_etudiant`];

                                    return (
                                        <tr key={index}>
                                            <td>
                                                {/* Le champ « month » natif affiche DÉJÀ « septembre
                                                    2026 » : répéter le libellé en dessous doublait la
                                                    hauteur de la cellule, et c'est ce qui désalignait
                                                    la ligne avec le montant à côté. */}
                                                <input
                                                    type="month"
                                                    className={`form-control form-control-sm text-normal-case${
                                                        erreurMois ? ' is-invalid' : ''
                                                    }`}
                                                    value={row.mois}
                                                    onChange={(event) => majLigne(index, 'mois', event.target.value)}
                                                    aria-label={t('Month')}
                                                />
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="0.00"
                                                    className={`form-control form-control-sm text-end${
                                                        erreurMontant ? ' is-invalid' : ''
                                                    }`}
                                                    value={row.montant_par_etudiant}
                                                    onChange={(event) =>
                                                        majLigne(index, 'montant_par_etudiant', event.target.value)
                                                    }
                                                    aria-label={t('Amount per student (MAD)')}
                                                />
                                            </td>
                                            <td className="text-end">
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
                                {/* Les erreurs de ligne sont regroupées SOUS le
                                    tableau : les mettre dans la cellule ferait
                                    grandir une ligne et désalignerait la suivante. */}
                                {lignesTriees.map(({ index }) => {
                                    const messages = [
                                        errors[`taux_mensuels.${index}.mois`],
                                        errors[`taux_mensuels.${index}.montant_par_etudiant`],
                                    ].filter((m): m is string => Boolean(m));

                                    if (messages.length === 0) {
                                        return null;
                                    }

                                    return (
                                        <tr key={`err-${index}`}>
                                            <td colSpan={3} className="py-1 border-0">
                                                <div className="text-danger fs-12">{messages.join(' · ')}</div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    <button type="button" className="btn btn-sm btn-outline-primary" onClick={ajouterMois}>
                        <i className="ti ti-plus me-1" />
                        {t('Add a month')}
                    </button>
                </>
            )}
        </div>
    );
}
