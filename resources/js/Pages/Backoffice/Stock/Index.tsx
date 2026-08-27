import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import FormField from '@/Components/Forms/FormField';
import SelectField from '@/Components/Forms/SelectField';
import TextareaField from '@/Components/Forms/TextareaField';
import FormActions from '@/Components/Forms/FormActions';
import RelatedRecordsTable from '@/Components/Details/RelatedRecordsTable';
import StatusBadge from '@/Components/Details/StatusBadge';
import Pagination from '@/Components/Tables/Pagination';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import SearchInput from '@/Components/Tables/SearchInput';
import TableToolbar from '@/Components/Tables/TableToolbar';
import DateField from '@/Components/Forms/DateField';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import type {
    CrudPermissions,
    PaginatedData,
    SelectOption,
    StockArticleForm,
    StockArticleRow,
    StockMouvementForm,
    StockMouvementRow,
    StockTypeForm,
    StockTypeRow,
} from '@/Types';

interface StockTypeOption {
    id: number;
    nom: string;
}

interface StockIndexProps {
    tab: 'articles' | 'mouvements' | 'types';
    articles: PaginatedData<StockArticleRow>;
    mouvements: PaginatedData<StockMouvementRow>;
    stockTypesList: PaginatedData<StockTypeRow>;
    filters: {
        search: string;
        stockTypeFilter: string;
        etablissementFilter: string;
        statutFilter: string;
        alerteFilter: string;
        articleFilter: string;
        typeFilter: string;
        dateFrom: string;
        dateTo: string;
        perPage: number;
    };
    typeFilters: { typeSearch: string; typePerPage: number };
    perPageOptions: number[];
    articleOptions: SelectOption[];
    stockTypes: StockTypeOption[];
    statuts: string[];
    mouvementTypes: string[];
    permissions: { create: boolean; update: boolean; delete: boolean; move: boolean };
    typePermissions: CrudPermissions;
    etablissements: { id: number; nom_centre: string }[];
    /** True when the top-bar context is on one center — the Centre filter/column/select are then redundant (CLAUDE.md §5). */
    centerLocked: boolean;
}

const STOCK_TYPE_STATUT_OPTIONS: SelectOption[] = [
    { value: 'Actif', label: 'Actif' },
    { value: 'Inactif', label: 'Inactif' },
];

const EMPTY_ARTICLE_FORM: StockArticleForm = {
    nom: '',
    stock_type_id: '',
    etablissement_id: '',
    seuil_alerte: '',
    statut: 'Actif',
    note: '',
};

const EMPTY_MOUVEMENT_FORM: StockMouvementForm = {
    stock_article_id: '',
    type: 'Entrée',
    quantite: '',
    note: '',
};

const EMPTY_STOCK_TYPE_FORM: StockTypeForm = { nom: '', statut: 'Actif' };

function typeVariant(type: string): 'success' | 'danger' | 'info' {
    if (type === 'Entrée') return 'success';
    if (type === 'Sortie') return 'danger';
    return 'info';
}

/**
 * Gestion du stock — Articles + Mouvements + Types de stock as client-side
 * tabs (Gestion des dépenses pattern). Quantities only change through
 * movements; movements are append-only (no edit/delete — compensating
 * entries, like finance records). Types de stock used to be a separate page
 * reached via a header button — folded in here as a third tab so managing
 * categories doesn't require leaving Gestion du stock.
 */
export default function StockIndex({
    tab,
    articles,
    mouvements,
    stockTypesList,
    filters,
    typeFilters,
    articleOptions,
    stockTypes,
    statuts,
    mouvementTypes,
    permissions,
    typePermissions,
    etablissements,
    centerLocked,
}: StockIndexProps) {
    const [showArticleModal, setShowArticleModal] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [editingHasMouvements, setEditingHasMouvements] = useState(false);
    const [showMouvementModal, setShowMouvementModal] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<StockArticleRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const [showTypeModal, setShowTypeModal] = useState(false);
    const [editingTypeId, setEditingTypeId] = useState<number | null>(null);
    const [deleteTypeTarget, setDeleteTypeTarget] = useState<StockTypeRow | null>(null);
    const [deleteTypeError, setDeleteTypeError] = useState<string>();
    const [deletingType, setDeletingType] = useState(false);

    const articleForm = useForm<StockArticleForm>(EMPTY_ARTICLE_FORM);
    const stockTypeForm = useForm<StockTypeForm>(EMPTY_STOCK_TYPE_FORM);
    const mouvementForm = useForm<StockMouvementForm>(EMPTY_MOUVEMENT_FORM);

    function reload(changes: Partial<Record<string, string | number>>) {
        router.get(
            '/backoffice/stock',
            { ...filters, tab, ...changes, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function reloadTypes(changes: Partial<Record<string, string | number>>) {
        router.get(
            '/backoffice/stock',
            { ...typeFilters, tab: 'types', ...changes, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function switchTab(next: 'articles' | 'mouvements' | 'types') {
        router.get(
            '/backoffice/stock',
            { ...filters, ...typeFilters, tab: next, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function openCreateArticle() {
        articleForm.reset();
        articleForm.clearErrors();
        setEditingId(null);
        setEditingHasMouvements(false);
        setShowArticleModal(true);
    }

    function openEditArticle(row: StockArticleRow) {
        articleForm.setData({
            nom: row.nom,
            stock_type_id: row.stockTypeId,
            etablissement_id: row.etablissementId ?? '',
            seuil_alerte: row.seuilAlerte !== null ? String(row.seuilAlerte) : '',
            statut: row.statut,
            note: row.note ?? '',
        });
        articleForm.clearErrors();
        setEditingId(row.id);
        setEditingHasMouvements(row.mouvementsCount > 0);
        setShowArticleModal(true);
    }

    function closeArticleModal() {
        setShowArticleModal(false);
        setEditingId(null);
        articleForm.reset();
        articleForm.clearErrors();
    }

    function submitArticle(event: React.FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => closeArticleModal() };

        if (editingId) {
            articleForm.put(`/backoffice/stock-articles/${editingId}`, options);
        } else {
            articleForm.post('/backoffice/stock-articles', options);
        }
    }

    function openMouvement(articleId?: number) {
        mouvementForm.setData({
            ...EMPTY_MOUVEMENT_FORM,
            stock_article_id: articleId ? String(articleId) : '',
        });
        mouvementForm.clearErrors();
        setShowMouvementModal(true);
    }

    function closeMouvementModal() {
        setShowMouvementModal(false);
        mouvementForm.reset();
        mouvementForm.clearErrors();
    }

    function submitMouvement(event: React.FormEvent) {
        event.preventDefault();
        mouvementForm.post('/backoffice/stock-mouvements', {
            preserveScroll: true,
            onSuccess: () => closeMouvementModal(),
        });
    }

    function confirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        articleForm.transform(() => ({}));
        articleForm.delete(`/backoffice/stock-articles/${deleteTarget.id}`, {
            preserveScroll: true,
            onFinish: () => articleForm.transform((data) => data),
            onSuccess: () => {
                setDeleteTarget(null);
                setDeleting(false);
            },
            onError: (errors: Record<string, string>) => {
                setDeleteError(errors.delete ?? 'Suppression impossible.');
                setDeleting(false);
            },
        });
    }

    function openCreateType() {
        stockTypeForm.reset();
        stockTypeForm.clearErrors();
        setEditingTypeId(null);
        setShowTypeModal(true);
    }

    function openEditType(row: StockTypeRow) {
        stockTypeForm.setData({ nom: row.nom, statut: row.statut });
        stockTypeForm.clearErrors();
        setEditingTypeId(row.id);
        setShowTypeModal(true);
    }

    function closeTypeModal() {
        setShowTypeModal(false);
        setEditingTypeId(null);
        stockTypeForm.reset();
        stockTypeForm.clearErrors();
    }

    function submitType(event: React.FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => closeTypeModal() };

        if (editingTypeId) {
            stockTypeForm.put(`/backoffice/stock-types/${editingTypeId}`, options);
        } else {
            stockTypeForm.post('/backoffice/stock-types', options);
        }
    }

    function confirmDeleteType() {
        if (!deleteTypeTarget) {
            return;
        }

        setDeletingType(true);
        setDeleteTypeError(undefined);

        stockTypeForm.transform(() => ({}));
        stockTypeForm.delete(`/backoffice/stock-types/${deleteTypeTarget.id}`, {
            preserveScroll: true,
            onFinish: () => stockTypeForm.transform((data) => data),
            onSuccess: () => {
                setDeleteTypeTarget(null);
                setDeletingType(false);
            },
            onError: (errors: Record<string, string>) => {
                setDeleteTypeError(errors.delete ?? 'Suppression impossible.');
                setDeletingType(false);
            },
        });
    }

    const isArticleAjustement = mouvementForm.data.type === 'Ajustement';

    return (
        <BackofficeLayout
            title="Gestion du stock"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Gestion du stock' },
            ]}
            actions={
                <div className="d-flex gap-2">
                    {tab === 'types' && typePermissions.create && (
                        <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreateType}>
                            <i className="ti ti-square-rounded-plus me-2" />
                            Ajouter un type de stock
                        </button>
                    )}
                    {tab !== 'types' && permissions.move && (
                        <button
                            type="button"
                            className="btn btn-outline-primary d-flex align-items-center"
                            onClick={() => openMouvement()}
                        >
                            <i className="ti ti-arrows-exchange me-2" />
                            Nouveau mouvement
                        </button>
                    )}
                    {tab !== 'types' && permissions.create && (
                        <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreateArticle}>
                            <i className="ti ti-square-rounded-plus me-2" />
                            Ajouter un article
                        </button>
                    )}
                </div>
            }
        >
            {/* wimschool-style page tabs — same pattern as Gestion des dépenses. */}
            <ul className="nav nav-tabs mb-4" role="tablist">
                <li className="nav-item" role="presentation">
                    <button
                        type="button"
                        className={`nav-link d-inline-flex align-items-center${tab === 'articles' ? ' active' : ''}`}
                        aria-current={tab === 'articles' ? 'page' : undefined}
                        onClick={() => switchTab('articles')}
                    >
                        <i className="ti ti-packages me-2" aria-hidden="true" />
                        Articles
                    </button>
                </li>
                <li className="nav-item" role="presentation">
                    <button
                        type="button"
                        className={`nav-link d-inline-flex align-items-center${tab === 'mouvements' ? ' active' : ''}`}
                        aria-current={tab === 'mouvements' ? 'page' : undefined}
                        onClick={() => switchTab('mouvements')}
                    >
                        <i className="ti ti-arrows-exchange me-2" aria-hidden="true" />
                        Mouvements
                    </button>
                </li>
                {typePermissions.create || typePermissions.update || typePermissions.delete ? (
                    <li className="nav-item" role="presentation">
                        <button
                            type="button"
                            className={`nav-link d-inline-flex align-items-center${tab === 'types' ? ' active' : ''}`}
                            aria-current={tab === 'types' ? 'page' : undefined}
                            onClick={() => switchTab('types')}
                        >
                            <i className="ti ti-category me-2" aria-hidden="true" />
                            Types de stock
                        </button>
                    </li>
                ) : null}
            </ul>

            {tab === 'articles' && (
                <Card title="Articles" bodyClassName="p-0 py-3">
                    <div className="px-3 pt-2">
                        <TableToolbar>
                            <div style={{ width: 200 }}>
                                <label className="form-label" htmlFor="stk-f-type">
                                    Type
                                </label>
                                <SelectField
                                    id="stk-f-type"
                                    options={stockTypes.map((type) => ({ value: type.id, label: type.nom }))}
                                    placeholder="Tous les types"
                                    value={filters.stockTypeFilter}
                                    onChange={(event) => reload({ stockTypeFilter: event.target.value })}
                                />
                            </div>
                            {!centerLocked && (
                                <div style={{ width: 200 }}>
                                    <label className="form-label" htmlFor="stk-f-centre">
                                        Centre
                                    </label>
                                    <SelectField
                                        id="stk-f-centre"
                                        options={etablissements.map((e) => ({ value: e.id, label: e.nom_centre }))}
                                        placeholder="Tous les centres"
                                        value={filters.etablissementFilter}
                                        onChange={(event) => reload({ etablissementFilter: event.target.value })}
                                    />
                                </div>
                            )}
                            <div style={{ width: 180 }}>
                                <label className="form-label" htmlFor="stk-f-statut">
                                    Statut
                                </label>
                                <SelectField
                                    id="stk-f-statut"
                                    options={statuts.map((statut) => ({ value: statut, label: statut }))}
                                    placeholder="Tous les statuts"
                                    value={filters.statutFilter}
                                    onChange={(event) => reload({ statutFilter: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 220 }}>
                                <label className="form-label" htmlFor="stk-f-alerte">
                                    Alerte stock
                                </label>
                                <SelectField
                                    id="stk-f-alerte"
                                    options={[{ value: '1', label: 'Sous le seuil uniquement' }]}
                                    placeholder="Tous les articles"
                                    value={filters.alerteFilter}
                                    onChange={(event) => reload({ alerteFilter: event.target.value })}
                                />
                            </div>
                        </TableToolbar>
                    </div>

                    <TableLengthRow
                        search={
                            <SearchInput
                                value={filters.search}
                                onSearch={(search) => reload({ search })}
                                placeholder="Nom ou référence"
                            />
                        }
                    />

                    <RelatedRecordsTable
                        isEmpty={articles.data.length === 0}
                        emptyTitle="Aucun article de stock pour le moment"
                        emptyIcon="ti ti-packages"
                        head={
                            <tr>
                                <th>Référence</th>
                                <th>Article</th>
                                <th>Catégorie</th>
                                {!centerLocked && <th>Centre</th>}
                                <th>Quantité</th>
                                <th>Seuil</th>
                                <th>Statut</th>
                                <th className="text-end">Action</th>
                            </tr>
                        }
                    >
                        {articles.data.map((row) => (
                            <tr key={row.id}>
                                <td className="text-muted">{row.reference}</td>
                                <td className="fw-medium">{row.nom}</td>
                                <td>{row.stockType ?? '—'}</td>
                                {!centerLocked && <td>{row.etablissement ?? '—'}</td>}
                                <td>
                                    <span className={`badge badge-soft-${row.enAlerte ? 'danger' : 'success'}`}>
                                        {row.quantite}
                                    </span>
                                    {row.enAlerte && (
                                        <i
                                            className="ti ti-alert-triangle text-danger ms-2"
                                            title="Stock sous le seuil d'alerte"
                                            aria-label="Stock sous le seuil d'alerte"
                                        />
                                    )}
                                </td>
                                <td>{row.seuilAlerte ?? '—'}</td>
                                <td>
                                    <StatusBadge
                                        label={row.statut}
                                        variant={row.statut === 'Actif' ? 'success' : 'secondary'}
                                        dot
                                    />
                                </td>
                                <td className="text-end">
                                    <RowActions>
                                        {permissions.move && (
                                            <RowActionItem icon="ti-arrows-exchange" onClick={() => openMouvement(row.id)}>
                                                Mouvement
                                            </RowActionItem>
                                        )}
                                        {permissions.update && (
                                            <RowActionItem icon="ti-edit" onClick={() => openEditArticle(row)}>
                                                Modifier
                                            </RowActionItem>
                                        )}
                                        {permissions.delete && row.mouvementsCount === 0 && (
                                            <RowActionItem
                                                icon="ti-trash"
                                                danger
                                                onClick={() => {
                                                    setDeleteTarget(row);
                                                    setDeleteError(undefined);
                                                }}
                                            >
                                                Supprimer
                                            </RowActionItem>
                                        )}
                                    </RowActions>
                                </td>
                            </tr>
                        ))}
                    </RelatedRecordsTable>
                    <Pagination paginator={articles} />
                </Card>
            )}

            {tab === 'mouvements' && (
                <Card title="Mouvements" bodyClassName="p-0 py-3">
                    <div className="px-3 pt-2">
                        <TableToolbar>
                            <div style={{ width: 220 }}>
                                <label className="form-label" htmlFor="mvt-f-article">
                                    Article
                                </label>
                                <SelectField
                                    id="mvt-f-article"
                                    options={articleOptions}
                                    placeholder="Tous les articles"
                                    value={filters.articleFilter}
                                    onChange={(event) => reload({ articleFilter: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 180 }}>
                                <label className="form-label" htmlFor="mvt-f-type">
                                    Type
                                </label>
                                <SelectField
                                    id="mvt-f-type"
                                    options={mouvementTypes.map((type) => ({ value: type, label: type }))}
                                    placeholder="Tous les types"
                                    value={filters.typeFilter}
                                    onChange={(event) => reload({ typeFilter: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 170 }}>
                                <label className="form-label" htmlFor="mvt-f-du">
                                    Du
                                </label>
                                <DateField
                                    id="mvt-f-du"
                                    value={filters.dateFrom}
                                    onChange={(event) => reload({ dateFrom: event.target.value })}
                                />
                            </div>
                            <div style={{ width: 170 }}>
                                <label className="form-label" htmlFor="mvt-f-au">
                                    Au
                                </label>
                                <DateField
                                    id="mvt-f-au"
                                    value={filters.dateTo}
                                    onChange={(event) => reload({ dateTo: event.target.value })}
                                />
                            </div>
                        </TableToolbar>
                    </div>

                    <RelatedRecordsTable
                        isEmpty={mouvements.data.length === 0}
                        emptyTitle="Aucun mouvement de stock pour le moment"
                        emptyIcon="ti ti-arrows-exchange"
                        head={
                            <tr>
                                <th>Date</th>
                                <th>Article</th>
                                <th>Type</th>
                                <th>Quantité</th>
                                <th>Avant → Après</th>
                                <th>Par</th>
                                <th>Note</th>
                            </tr>
                        }
                    >
                        {mouvements.data.map((row) => (
                            <tr key={row.id}>
                                <td className="text-muted">{row.date}</td>
                                <td className="fw-medium">
                                    {row.articleNom}
                                    <span className="text-muted ms-2">{row.articleReference}</span>
                                </td>
                                <td>
                                    <StatusBadge label={row.type} variant={typeVariant(row.type)} dot />
                                </td>
                                <td>{row.quantite}</td>
                                <td>
                                    {row.quantiteAvant} → {row.quantiteApres}
                                </td>
                                <td>{row.par ?? '—'}</td>
                                <td className="text-muted">{row.note ?? '—'}</td>
                            </tr>
                        ))}
                    </RelatedRecordsTable>
                    <Pagination paginator={mouvements} />
                </Card>
            )}

            {tab === 'types' && (
                <Card title="Types de stock" bodyClassName="p-0 py-3">
                    <TableLengthRow
                        search={
                            <SearchInput
                                value={typeFilters.typeSearch}
                                onSearch={(typeSearch) => reloadTypes({ typeSearch })}
                                placeholder="Rechercher"
                            />
                        }
                    />

                    <RelatedRecordsTable
                        isEmpty={stockTypesList.data.length === 0}
                        emptyTitle="Aucun type de stock pour le moment"
                        emptyIcon="ti ti-category"
                        head={
                            <tr>
                                <th>Nom</th>
                                <th>Articles</th>
                                <th>Statut</th>
                                <th className="text-end">Action</th>
                            </tr>
                        }
                    >
                        {stockTypesList.data.map((row) => (
                            <tr key={row.id}>
                                <td className="fw-medium">
                                    {row.nom}
                                    {row.isSystem && (
                                        <span className="ms-2">
                                            <StatusBadge label="Système" variant="secondary" dot />
                                        </span>
                                    )}
                                </td>
                                <td>
                                    <span className="badge badge-soft-secondary">{row.articlesCount}</span>
                                </td>
                                <td>
                                    <StatusBadge
                                        label={row.statut === 'Actif' ? 'Actif' : 'Inactif'}
                                        variant={row.statut === 'Actif' ? 'success' : 'secondary'}
                                        dot
                                    />
                                </td>
                                <td className="text-end">
                                    {!row.isSystem && (
                                        <RowActions>
                                            {typePermissions.update && (
                                                <RowActionItem icon="ti-edit" onClick={() => openEditType(row)}>
                                                    Modifier
                                                </RowActionItem>
                                            )}
                                            {typePermissions.delete && (
                                                <RowActionItem
                                                    icon="ti-trash"
                                                    danger
                                                    onClick={() => {
                                                        setDeleteTypeTarget(row);
                                                        setDeleteTypeError(undefined);
                                                    }}
                                                >
                                                    Supprimer
                                                </RowActionItem>
                                            )}
                                        </RowActions>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </RelatedRecordsTable>
                    <Pagination paginator={stockTypesList} />
                </Card>
            )}

            <Modal
                show={showArticleModal}
                title={editingId ? "Modifier l'article" : 'Ajouter un article'}
                onClose={closeArticleModal}
                processing={articleForm.processing}
                size="lg"
            >
                <form onSubmit={submitArticle}>
                    <div className="row">
                        <div className="col-md-6">
                            <FormField
                                id="stock-nom"
                                label="Nom"
                                required
                                value={articleForm.data.nom}
                                onChange={(event) => articleForm.setData('nom', event.target.value)}
                                error={articleForm.errors.nom}
                                placeholder="ex : Manuel A1"
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="stock-type"
                                label="Type"
                                required
                                options={stockTypes.map((type) => ({ value: type.id, label: type.nom }))}
                                placeholder="Sélectionner un type"
                                value={articleForm.data.stock_type_id}
                                onChange={(event) => articleForm.setData('stock_type_id', Number(event.target.value))}
                                error={articleForm.errors.stock_type_id}
                            />
                        </div>
                        {!centerLocked && (
                            <div className="col-md-6">
                                <SelectField
                                    id="stock-centre"
                                    label="Centre"
                                    required
                                    options={etablissements.map((e) => ({ value: e.id, label: e.nom_centre }))}
                                    placeholder="Sélectionner un centre"
                                    value={articleForm.data.etablissement_id}
                                    onChange={(event) =>
                                        articleForm.setData(
                                            'etablissement_id',
                                            event.target.value === '' ? '' : Number(event.target.value),
                                        )
                                    }
                                    error={articleForm.errors.etablissement_id}
                                    disabled={editingHasMouvements}
                                />
                                <small className="form-text text-muted">
                                    {editingHasMouvements
                                        ? 'Le centre ne peut plus changer : cet article a déjà des mouvements de stock.'
                                        : 'Chaque centre gère son propre stock de cet article.'}
                                </small>
                            </div>
                        )}
                        <div className="col-md-6">
                            <FormField
                                id="stock-seuil"
                                label="Seuil d'alerte"
                                type="number"
                                min={0}
                                value={articleForm.data.seuil_alerte}
                                onChange={(event) => articleForm.setData('seuil_alerte', event.target.value)}
                                error={articleForm.errors.seuil_alerte}
                                placeholder="ex : 5"
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="stock-statut"
                                label="Statut"
                                required
                                options={statuts.map((statut) => ({ value: statut, label: statut }))}
                                value={articleForm.data.statut}
                                onChange={(event) => articleForm.setData('statut', event.target.value)}
                                error={articleForm.errors.statut}
                            />
                        </div>
                        <div className="col-12">
                            <TextareaField
                                id="stock-note"
                                label="Note"
                                rows={2}
                                value={articleForm.data.note}
                                onChange={(event) => articleForm.setData('note', event.target.value)}
                                error={articleForm.errors.note}
                            />
                        </div>
                    </div>
                    {!editingId && (
                        <p className="text-muted mb-3">
                            La quantité initiale s'enregistre ensuite via un mouvement « Entrée ».
                        </p>
                    )}
                    <div className="d-flex justify-content-end gap-2 mt-3">
                        <FormActions onCancel={closeArticleModal} processing={articleForm.processing} />
                    </div>
                </form>
            </Modal>

            <Modal
                show={showMouvementModal}
                title="Nouveau mouvement de stock"
                onClose={closeMouvementModal}
                processing={mouvementForm.processing}
                size="lg"
            >
                <form onSubmit={submitMouvement}>
                    <div className="row">
                        <div className="col-12">
                            <SelectField
                                id="mvt-article"
                                label="Article"
                                required
                                options={articleOptions}
                                placeholder="Sélectionner un article"
                                value={mouvementForm.data.stock_article_id}
                                onChange={(event) => mouvementForm.setData('stock_article_id', event.target.value)}
                                error={mouvementForm.errors.stock_article_id}
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="mvt-type"
                                label="Type"
                                required
                                // "Ajustement" (inventory recount) stays a valid
                                // backend type — the model/action still support
                                // it — but isn't offered here anymore; staff
                                // corrections go through Entrée/Sortie instead.
                                options={mouvementTypes
                                    .filter((type) => type !== 'Ajustement')
                                    .map((type) => ({ value: type, label: type }))}
                                value={mouvementForm.data.type}
                                onChange={(event) => mouvementForm.setData('type', event.target.value)}
                                error={mouvementForm.errors.type}
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="mvt-quantite"
                                label={isArticleAjustement ? 'Nouvelle quantité totale' : 'Quantité'}
                                type="number"
                                min={isArticleAjustement ? 0 : 1}
                                required
                                value={mouvementForm.data.quantite}
                                onChange={(event) => mouvementForm.setData('quantite', event.target.value)}
                                error={mouvementForm.errors.quantite}
                            />
                        </div>
                        <div className="col-12">
                            <TextareaField
                                id="mvt-note"
                                label="Note"
                                rows={2}
                                value={mouvementForm.data.note}
                                onChange={(event) => mouvementForm.setData('note', event.target.value)}
                                error={mouvementForm.errors.note}
                                placeholder={isArticleAjustement ? "Motif de l'ajustement (inventaire…)" : undefined}
                            />
                        </div>
                    </div>
                    <div className="d-flex justify-content-end gap-2 mt-3">
                        <FormActions onCancel={closeMouvementModal} processing={mouvementForm.processing} />
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={deleteTarget !== null}
                title="Supprimer cet article ?"
                recordLabel={deleteTarget?.nom ?? ''}
                message="Cette action est définitive. Un article avec des mouvements ne peut pas être supprimé — passez-le en Inactif."
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />

            <Modal
                show={showTypeModal}
                title={editingTypeId ? 'Modifier le type de stock' : 'Ajouter un type de stock'}
                onClose={closeTypeModal}
                processing={stockTypeForm.processing}
                size="lg"
            >
                <form onSubmit={submitType}>
                    <div className="row">
                        <div className="col-md-6">
                            <FormField
                                id="st-nom"
                                label="Nom"
                                required
                                value={stockTypeForm.data.nom}
                                onChange={(event) => stockTypeForm.setData('nom', event.target.value)}
                                error={stockTypeForm.errors.nom}
                                placeholder="ex : Livre"
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="st-statut"
                                label="Statut"
                                required
                                options={STOCK_TYPE_STATUT_OPTIONS}
                                value={stockTypeForm.data.statut}
                                onChange={(event) => stockTypeForm.setData('statut', event.target.value)}
                                error={stockTypeForm.errors.statut}
                            />
                        </div>
                    </div>
                    <div className="d-flex justify-content-end gap-2 mt-3">
                        <FormActions onCancel={closeTypeModal} processing={stockTypeForm.processing} />
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={deleteTypeTarget !== null}
                title="Supprimer ce type de stock ?"
                recordLabel={deleteTypeTarget?.nom ?? ''}
                message="Cette action est définitive. Le type sera supprimé s'il n'est plus utilisé par des articles."
                error={deleteTypeError}
                processing={deletingType}
                onConfirm={confirmDeleteType}
                onCancel={() => setDeleteTypeTarget(null)}
            />
        </BackofficeLayout>
    );
}
