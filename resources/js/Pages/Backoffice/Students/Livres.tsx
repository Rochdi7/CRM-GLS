import { router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import PageTabs from '@/Components/Navigation/PageTabs';
import { STUDENTS_TABS } from '@/Config/pageTabs';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import SelectField from '@/Components/Forms/SelectField';
import StatusBadge from '@/Components/Details/StatusBadge';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { statutVariant } from '@/Lib/inscriptionStatut';
import { t } from '@/Lib/i18n';
import type { SelectOption } from '@/Types';

/**
 * Onglet « Livres » des Étudiants (12/10/2026) — remettre UN livre à tout un
 * groupe : choisir le groupe puis le livre, cocher tout le monde ou un par un.
 *
 * Chaque ligne montre TOUS les livres déjà remis au dossier, pour voir d'un
 * coup d'œil qu'un étudiant a déjà un autre livre. Un dossier qui a déjà le
 * livre choisi est affiché mais NON cochable (jamais deux sorties de stock) —
 * `aDejaCeLivre` vient du serveur, qui le revérifie à l'écriture.
 */

interface LivreOption {
    id: number;
    nom: string;
    quantite: number;
}

interface LigneLivre {
    id: number;
    reference: string;
    student: string | null;
    studentReference: string | null;
    studentShowUrl: string | null;
    statut: string;
    livres: Array<{ id: number; nom: string | null; date: string | null }>;
    aDejaCeLivre: boolean;
}

interface Filters {
    group_id: number | null;
    livre_id: number | null;
    statut: string;
}

interface Props {
    tabCounts?: Record<string, number>;
    filters: Filters;
    groupOptions: Array<{ id: number; label: string; statut: string }>;
    statuts: string[];
    livres: LivreOption[];
    lignes: LigneLivre[];
}

export default function StudentLivres({ tabCounts, filters, groupOptions, statuts, livres, lignes }: Props) {
    const isLoading = useInertiaLoading();
    const { errors } = usePage<{ errors: Record<string, string> }>().props;
    const [selection, setSelection] = useState<number[]>([]);
    const [processing, setProcessing] = useState(false);

    // Changer de groupe, de livre ou de statut vide la sélection : des cases
    // cochées pour un autre groupe n'ont plus de sens.
    useEffect(() => {
        setSelection([]);
    }, [filters.group_id, filters.livre_id, filters.statut]);

    const livre = livres.find((l) => l.id === filters.livre_id) ?? null;
    const selectionnables = useMemo(() => lignes.filter((l) => !l.aDejaCeLivre), [lignes]);
    const dejaServis = lignes.length - selectionnables.length;
    const toutCoche = selectionnables.length > 0 && selection.length === selectionnables.length;
    const stockInsuffisant = livre !== null && selection.length > livre.quantite;

    const groupSelectOptions: SelectOption[] = groupOptions.map((g) => ({ value: g.id, label: g.label }));
    const livreSelectOptions: SelectOption[] = livres.map((l) => ({
        value: l.id,
        label: `${l.nom} — ${t('stock')} ${l.quantite}`,
    }));
    const statutOptions: SelectOption[] = statuts.map((s) => ({ value: s, label: s }));

    function reload(next: Partial<Filters>) {
        router.get(
            '/backoffice/students/livres',
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function toggle(id: number) {
        setSelection((current) => (current.includes(id) ? current.filter((x) => x !== id) : [...current, id]));
    }

    function toggleTout() {
        setSelection(toutCoche ? [] : selectionnables.map((l) => l.id));
    }

    function submit() {
        if (!livre || selection.length === 0) {
            return;
        }
        router.post(
            '/backoffice/students/livres',
            { group_id: filters.group_id, stock_article_id: livre.id, inscription_ids: selection },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => setSelection([]),
            },
        );
    }

    const erreur = errors.inscription_ids ?? errors.stock_article_id ?? errors.group_id;

    return (
        <BackofficeLayout
            title={t('Assign books')}
            breadcrumbs={[{ label: t('Students'), href: '/backoffice/students' }, { label: t('Assign books') }]}
        >
            <PageTabs tabs={STUDENTS_TABS} counts={tabCounts} />

            <Card>
                <div className="d-flex flex-wrap gap-3 align-items-end mb-3">
                    <div style={{ width: 320 }}>
                        <label className="form-label" htmlFor="liv-group">
                            {t('Group')}
                        </label>
                        <SelectField
                            id="liv-group"
                            options={groupSelectOptions}
                            placeholder={t('Select a group')}
                            searchable
                            value={filters.group_id ?? ''}
                            onChange={(event) =>
                                reload({ group_id: event.target.value ? Number(event.target.value) : null, livre_id: null })
                            }
                        />
                    </div>
                    <div style={{ width: 320 }}>
                        <label className="form-label" htmlFor="liv-livre">
                            {t('Book')}
                        </label>
                        <SelectField
                            id="liv-livre"
                            options={livreSelectOptions}
                            placeholder={filters.group_id ? t('Select a book') : t('Select a group first')}
                            disabled={!filters.group_id}
                            value={filters.livre_id ?? ''}
                            onChange={(event) =>
                                reload({ livre_id: event.target.value ? Number(event.target.value) : null })
                            }
                        />
                    </div>
                    <div style={{ width: 200 }}>
                        <label className="form-label" htmlFor="liv-statut">
                            {t('Registration status')}
                        </label>
                        <SelectField
                            id="liv-statut"
                            options={statutOptions}
                            placeholder={t('All')}
                            disabled={!filters.group_id}
                            value={filters.statut}
                            onChange={(event) => reload({ statut: event.target.value })}
                        />
                    </div>
                </div>

                {erreur && <div className="alert alert-danger">{erreur}</div>}

                {!filters.group_id ? (
                    <EmptyState
                        icon="ti ti-book"
                        title={t('Select a group')}
                        message={t('Choose a group, then the book to hand out, and tick the students who receive it.')}
                    />
                ) : lignes.length === 0 ? (
                    <EmptyState icon="ti ti-users" title={t('No registration in this group')} />
                ) : (
                    <>
                        <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <div className="text-muted">
                                {livre ? (
                                    <>
                                        <strong className="text-dark">{selection.length}</strong> {t('selected')} ·{' '}
                                        {t('stock')} <strong className="text-dark">{livre.quantite}</strong>
                                        {dejaServis > 0 && (
                                            <>
                                                {' '}
                                                · {dejaServis} {t('already have this book')}
                                            </>
                                        )}
                                    </>
                                ) : (
                                    t('Select a book to tick the students.')
                                )}
                            </div>
                            <button
                                type="button"
                                className="btn btn-primary"
                                disabled={!livre || selection.length === 0 || stockInsuffisant || processing}
                                onClick={submit}
                            >
                                <i className="ti ti-book me-1" />
                                {processing
                                    ? t('Saving...')
                                    : `${t('Assign the book')} (${selection.length})`}
                            </button>
                        </div>

                        {stockInsuffisant && livre && (
                            <div className="alert alert-warning">
                                {t('Not enough stock')} : {selection.length} {t('selected')}, {livre.quantite}{' '}
                                {t('in stock')}. {t('Record a stock entry first in Stock management.')}
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
                                            aria-label={t('Select all')}
                                            checked={toutCoche}
                                            disabled={!livre || selectionnables.length === 0}
                                            onChange={toggleTout}
                                        />
                                    </th>
                                    <th>{t('Registration')}</th>
                                    <th>{t('Student')}</th>
                                    <th>{t('Status')}</th>
                                    <th>{t('Books already given')}</th>
                                </tr>
                            }
                        >
                            {lignes.map((ligne) => (
                                <tr key={ligne.id} className={ligne.aDejaCeLivre ? 'opacity-75' : undefined}>
                                    <td>
                                        <input
                                            type="checkbox"
                                            className="form-check-input"
                                            aria-label={ligne.student ?? ligne.reference}
                                            checked={selection.includes(ligne.id)}
                                            disabled={!livre || ligne.aDejaCeLivre}
                                            onChange={() => toggle(ligne.id)}
                                        />
                                    </td>
                                    <td>
                                        <code>{ligne.reference}</code>
                                    </td>
                                    <td>
                                        {ligne.studentShowUrl ? (
                                            <a href={ligne.studentShowUrl}>{ligne.student}</a>
                                        ) : (
                                            (ligne.student ?? '-')
                                        )}
                                    </td>
                                    <td>
                                        <StatusBadge label={ligne.statut} variant={statutVariant(ligne.statut)} />
                                    </td>
                                    <td>
                                        {ligne.livres.length === 0 ? (
                                            <span className="badge badge-soft-secondary">{t('No book')}</span>
                                        ) : (
                                            ligne.livres.map((l, index) => (
                                                <span
                                                    key={index}
                                                    className={`badge me-1 mb-1 ${l.id === filters.livre_id ? 'badge-soft-success' : 'badge-soft-info'}`}
                                                    title={l.date ? `${t('Given on')} ${l.date}` : undefined}
                                                >
                                                    <i className="ti ti-book me-1" />
                                                    {l.nom ?? '-'}
                                                    {l.id === filters.livre_id && <i className="ti ti-check ms-1" />}
                                                </span>
                                            ))
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                    </>
                )}
            </Card>
        </BackofficeLayout>
    );
}
