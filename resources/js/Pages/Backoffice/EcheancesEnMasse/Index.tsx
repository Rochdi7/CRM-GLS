import { router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import DateField from '@/Components/Forms/DateField';
import SelectField from '@/Components/Forms/SelectField';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { useFilterReset } from '@/Hooks/useFilterReset';
import { t } from '@/Lib/i18n';
import type { EcheancesEnMassePageProps, SelectOption } from '@/Types';

/**
 * « Échéances en masse » — choisir un groupe, choisir un frais, cocher les
 * étudiants qui partagent la même échéance, appliquer la date d'un coup.
 *
 * Écran atteint par son lien direct (/backoffice/bulk-echeance) : il
 * n'a volontairement PAS d'entrée dans la barre latérale (demande métier du
 * 07/09/2026). La sélection vit en état React local et se vide dès que la
 * liste change de contenu — appliquer une date à des lignes qui ne sont plus
 * à l'écran serait une écriture invisible.
 *
 * La liste n'est PAS paginée : elle est déjà bornée à un groupe et un frais,
 * soit quelques dizaines de lignes au plus. C'est aussi ce qui permet à
 * « Tout cocher » de signifier vraiment « tout », sans la promesse trompeuse
 * d'une case qui ne couvrirait que la page visible.
 */
export default function EcheancesEnMasseIndex({
    lignes,
    filters,
    groupOptions,
    fraisOptions,
    statuts,
}: EcheancesEnMassePageProps) {
    const isLoading = useInertiaLoading();
    const [selection, setSelection] = useState<number[]>([]);

    const groupSelectOptions: SelectOption[] = groupOptions;
    const fraisSelectOptions: SelectOption[] = fraisOptions;
    const statutSelectOptions: SelectOption[] = statuts.map((s) => ({ value: s, label: s }));

    const form = useForm<{ fee_ids: number[]; date_echeance: string }>({
        fee_ids: [],
        date_echeance: '',
    });

    function reload(nextFilters: Partial<typeof filters>) {
        // Toute reconstruction de la liste vide la sélection : les cases
        // cochées désignaient des lignes de l'ancien filtre.
        setSelection([]);
        router.get(
            '/backoffice/bulk-echeance',
            { ...filters, ...nextFilters },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    const filterReset = useFilterReset(filters, reload, { statutFilter: '' });

    // Les lignes qui partagent déjà une même date, pour la pastille de
    // regroupement : c'est le repère qui permet de cocher « les 12 du 05/10 »
    // sans les relire une par une.
    const countByDate = useMemo(() => {
        const counts: Record<string, number> = {};
        for (const ligne of lignes) {
            const key = ligne.dateEcheance ?? '';
            counts[key] = (counts[key] ?? 0) + 1;
        }
        return counts;
    }, [lignes]);

    const allChecked = lignes.length > 0 && selection.length === lignes.length;

    function toggleAll() {
        setSelection(allChecked ? [] : lignes.map((ligne) => ligne.feeId));
    }

    function toggleOne(feeId: number) {
        setSelection((current) =>
            current.includes(feeId) ? current.filter((id) => id !== feeId) : [...current, feeId],
        );
    }

    /** Cocher d'un geste toutes les lignes portant la même date qu'une ligne donnée. */
    function selectSameDate(date: string | null) {
        setSelection(lignes.filter((ligne) => (ligne.dateEcheance ?? '') === (date ?? '')).map((l) => l.feeId));
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.transform(() => ({ fee_ids: selection, date_echeance: form.data.date_echeance }));
        form.post('/backoffice/bulk-echeance', {
            preserveScroll: true,
            onSuccess: () => {
                setSelection([]);
                form.setData('date_echeance', '');
            },
        });
    }

    const groupChosen = filters.groupFilter !== '';
    const fraisChosen = filters.fraisFilter !== '';
    // Date + bouton ne s'affichent qu'avec des lignes a l'ecran : autrement
    // ils occupent la ligne de filtres sans avoir quoi que ce soit a appliquer.
    const canPickDate = groupChosen && fraisChosen && lignes.length > 0;
    const canSubmit = selection.length > 0 && form.data.date_echeance !== '' && !form.processing;

    return (
        <BackofficeLayout
            title={t('Bulk due dates')}
            breadcrumbs={[
                { label: t('Dashboard'), href: '/backoffice/dashboard' },
                { label: t('Bulk due dates') },
            ]}
        >
            <Card title={t('Bulk due dates')} bodyClassName="p-0 py-3">
                {/* Le <form> enveloppe AUSSI la barre de filtres : « Nouvelle
                    echeance » et « Appliquer » sont deux controles de plus sur
                    la meme ligne que Groupe / Frais / Etat, au lieu d'un bloc
                    separe qui retombait sous les filtres. Les selects de
                    filtre ne soumettent rien (ils rechargent via router.get),
                    donc les inclure est sans effet de bord. */}
                <form onSubmit={submit}>
                <div className="px-3 pt-2">
                    <TableToolbar onReset={filterReset.reset} resetActive={filterReset.active}>
                        <div style={{ width: 260 }}>
                            <label className="form-label" htmlFor="ech-f-groupe">
                                {t('Group')}
                            </label>
                            <SelectField
                                id="ech-f-groupe"
                                options={groupSelectOptions}
                                placeholder={t('Choose a group')}
                                value={filters.groupFilter}
                                onChange={(event) =>
                                    // Changer de groupe invalide le frais choisi :
                                    // le serveur le laisse tomber s'il ne s'applique
                                    // plus, on ne le renvoie donc pas ici.
                                    reload({ groupFilter: event.target.value, fraisFilter: '' })
                                }
                            />
                        </div>
                        <div style={{ width: 260 }}>
                            <label className="form-label" htmlFor="ech-f-frais">
                                {t('Fee')}
                            </label>
                            <SelectField
                                id="ech-f-frais"
                                options={fraisSelectOptions}
                                placeholder={groupChosen ? t('Choose an item') : t('Choose a group first')}
                                disabled={!groupChosen}
                                value={filters.fraisFilter}
                                onChange={(event) => reload({ fraisFilter: event.target.value })}
                            />
                        </div>
                        <div style={{ width: 220 }}>
                            <label className="form-label" htmlFor="ech-f-statut">
                                {t('Registration status')}
                            </label>
                            <SelectField
                                id="ech-f-statut"
                                options={statutSelectOptions}
                                placeholder={t('All statuses')}
                                value={filters.statutFilter}
                                onChange={(event) => reload({ statutFilter: event.target.value })}
                            />
                        </div>
                        {canPickDate && (
                            <>
                                <div style={{ width: 190 }}>
                                    <label className="form-label" htmlFor="ech-date">
                                        {t('New due date')}
                                    </label>
                                    <DateField
                                        id="ech-date"
                                        value={form.data.date_echeance}
                                        onChange={(event) => form.setData('date_echeance', event.target.value)}
                                        error={form.errors.date_echeance}
                                        required
                                    />
                                </div>
                                <div>
                                    <button type="submit" className="btn btn-primary" disabled={!canSubmit}>
                                        <i className="ti ti-calendar-check me-1" />
                                        {form.processing
                                            ? t('Applying...')
                                            : `${t('Apply to selection')} (${selection.length})`}
                                    </button>
                                </div>
                            </>
                        )}
                    </TableToolbar>
                </div>

                {!groupChosen || !fraisChosen ? (
                    <EmptyState
                        title={t('Choose a group and a fee to list its due dates')}
                        icon="ti ti-calendar-event"
                    />
                ) : lignes.length === 0 ? (
                    <EmptyState title={t('No fee line found for this group and fee')} icon="ti ti-alert-circle" />
                ) : (
                    <>
                        {/* Le compteur de selection vit sous les filtres : il
                            doit rester lisible en meme temps que les cases
                            cochees, sans repousser le tableau. */}
                        <div className="px-3 mb-3 text-muted">
                            {selection.length === 0
                                ? t('Tick the students who share the same due date.')
                                : `${selection.length} / ${lignes.length} ${t('lines selected')}`}
                        </div>

                        {form.errors.fee_ids && (
                            <div className="px-3 mb-3">
                                <div className="alert alert-danger mb-0">{form.errors.fee_ids}</div>
                            </div>
                        )}

                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th style={{ width: 40 }}>
                                        <input
                                            type="checkbox"
                                            className="form-check-input"
                                            checked={allChecked}
                                            onChange={toggleAll}
                                            aria-label={t('Select all')}
                                        />
                                    </th>
                                    <th>{t('Student')}</th>
                                    <th>{t('Reference')}</th>
                                    <th>{t('Registration status')}</th>
                                    <th>{t('Fee')}</th>
                                    <th>{t('Amount')}</th>
                                    <th>{t('Paid')}</th>
                                    <th>{t('Current due date')}</th>
                                </tr>
                            }
                        >
                            {lignes.map((ligne) => {
                                const checked = selection.includes(ligne.feeId);
                                const sameDateCount = countByDate[ligne.dateEcheance ?? ''] ?? 0;

                                return (
                                    <tr key={ligne.feeId} className={checked ? 'table-active' : undefined}>
                                        <td>
                                            <input
                                                type="checkbox"
                                                className="form-check-input"
                                                checked={checked}
                                                onChange={() => toggleOne(ligne.feeId)}
                                                aria-label={ligne.studentNom}
                                            />
                                        </td>
                                        <td className="fw-medium">{ligne.studentNom}</td>
                                        <td>
                                            <code className="text-normal-case">{ligne.reference ?? '—'}</code>
                                        </td>
                                        <td>
                                            <StatusBadge
                                                label={ligne.statut}
                                                variant={ligne.statut === 'Active' ? 'success' : 'secondary'}
                                            />
                                        </td>
                                        <td>{ligne.feeNom}</td>
                                        <td>{ligne.montant} DH</td>
                                        <td>{ligne.montantPaye} DH</td>
                                        <td>
                                            <div className="d-flex align-items-center gap-2">
                                                <span>{ligne.dateEcheance ?? '—'}</span>
                                                {sameDateCount > 1 && (
                                                    <button
                                                        type="button"
                                                        className="badge badge-soft-info border-0"
                                                        onClick={() => selectSameDate(ligne.dateEcheance)}
                                                        title={t('Select every line sharing this due date')}
                                                    >
                                                        {sameDateCount}
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </DataTable>
                    </>
                )}
                </form>
            </Card>
        </BackofficeLayout>
    );
}
