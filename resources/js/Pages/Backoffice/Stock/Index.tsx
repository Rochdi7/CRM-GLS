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
import FilterDropdown from '@/Components/Tables/FilterDropdown';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import type {
    PaginatedData,
    SelectOption,
    StockArticleForm,
    StockArticleRow,
    StockMouvementForm,
    StockMouvementRow,
} from '@/Types';

interface StockIndexProps {
    tab: 'articles' | 'mouvements';
    articles: PaginatedData<StockArticleRow>;
    mouvements: PaginatedData<StockMouvementRow>;
    filters: {
        search: string;
        categorieFilter: string;
        statutFilter: string;
        alerteFilter: string;
        articleFilter: string;
        typeFilter: string;
        dateFrom: string;
        dateTo: string;
        perPage: number;
    };
    perPageOptions: number[];
    articleOptions: SelectOption[];
    categories: string[];
    statuts: string[];
    mouvementTypes: string[];
    permissions: { create: boolean; update: boolean; delete: boolean; move: boolean };
}

const EMPTY_ARTICLE_FORM: StockArticleForm = {
    nom: '',
    categorie: '',
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

function typeVariant(type: string): 'success' | 'danger' | 'info' {
    if (type === 'Entrée') return 'success';
    if (type === 'Sortie') return 'danger';
    return 'info';
}

/**
 * Gestion du stock — Articles + Mouvements as client-side tabs (Gestion des
 * dépenses pattern). Quantities only change through movements; movements are
 * append-only (no edit/delete — compensating entries, like finance records).
 */
export default function StockIndex({
    tab,
    articles,
    mouvements,
    filters,
    perPageOptions,
    articleOptions,
    categories,
    statuts,
    mouvementTypes,
    permissions,
}: StockIndexProps) {
    const [showArticleModal, setShowArticleModal] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [showMouvementModal, setShowMouvementModal] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<StockArticleRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);

    const articleForm = useForm<StockArticleForm>(EMPTY_ARTICLE_FORM);
    const mouvementForm = useForm<StockMouvementForm>(EMPTY_MOUVEMENT_FORM);

    function reload(changes: Partial<Record<string, string | number>>) {
        router.get(
            '/backoffice/stock',
            { ...filters, tab, ...changes, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function switchTab(next: 'articles' | 'mouvements') {
        router.get(
            '/backoffice/stock',
            { ...filters, tab: next, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function openCreateArticle() {
        articleForm.reset();
        articleForm.clearErrors();
        setEditingId(null);
        setShowArticleModal(true);
    }

    function openEditArticle(row: StockArticleRow) {
        articleForm.setData({
            nom: row.nom,
            categorie: row.categorie,
            seuil_alerte: row.seuilAlerte !== null ? String(row.seuilAlerte) : '',
            statut: row.statut,
            note: row.note ?? '',
        });
        articleForm.clearErrors();
        setEditingId(row.id);
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
                    {permissions.move && (
                        <button
                            type="button"
                            className="btn btn-outline-primary d-flex align-items-center"
                            onClick={() => openMouvement()}
                        >
                            <i className="ti ti-arrows-exchange me-2" />
                            Nouveau mouvement
                        </button>
                    )}
                    {permissions.create && (
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
            </ul>

            {tab === 'articles' && (
                <Card
                    title="Articles"
                    bodyClassName="p-0 py-3"
                    tools={
                        <FilterDropdown
                            fields={[
                                {
                                    name: 'categorieFilter',
                                    label: 'Catégorie',
                                    value: filters.categorieFilter,
                                    options: categories.map((categorie) => ({ value: categorie, label: categorie })),
                                    placeholder: 'Toutes les catégories',
                                },
                                {
                                    name: 'statutFilter',
                                    label: 'Statut',
                                    value: filters.statutFilter,
                                    options: statuts.map((statut) => ({ value: statut, label: statut })),
                                    placeholder: 'Tous les statuts',
                                },
                                {
                                    name: 'alerteFilter',
                                    label: 'Alerte stock',
                                    value: filters.alerteFilter,
                                    options: [{ value: '1', label: 'Sous le seuil uniquement' }],
                                    placeholder: 'Tous les articles',
                                },
                            ]}
                            onApply={(values) => reload(values)}
                            onReset={() => reload({ categorieFilter: '', statutFilter: '', alerteFilter: '' })}
                        />
                    }
                >
                    <TableLengthRow
                        perPage={filters.perPage}
                        perPageOptions={perPageOptions}
                        onPerPageChange={(perPage) => reload({ perPage })}
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
                                <td>{row.categorie}</td>
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
                    <Pagination paginator={articles} showJumpToPage />
                </Card>
            )}

            {tab === 'mouvements' && (
                <Card
                    title="Mouvements"
                    bodyClassName="p-0 py-3"
                    tools={
                        <FilterDropdown
                            fields={[
                                {
                                    name: 'articleFilter',
                                    label: 'Article',
                                    value: filters.articleFilter,
                                    options: articleOptions,
                                    placeholder: 'Tous les articles',
                                },
                                {
                                    name: 'typeFilter',
                                    label: 'Type',
                                    value: filters.typeFilter,
                                    options: mouvementTypes.map((type) => ({ value: type, label: type })),
                                    placeholder: 'Tous les types',
                                },
                                { name: 'dateFrom', label: 'Du', type: 'date', value: filters.dateFrom },
                                { name: 'dateTo', label: 'Au', type: 'date', value: filters.dateTo },
                            ]}
                            onApply={(values) => reload(values)}
                            onReset={() => reload({ articleFilter: '', typeFilter: '', dateFrom: '', dateTo: '' })}
                        />
                    }
                >
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
                    <Pagination paginator={mouvements} showJumpToPage />
                </Card>
            )}

            <Modal
                show={showArticleModal}
                title={editingId ? "Modifier l'article" : 'Ajouter un article'}
                onClose={closeArticleModal}
                processing={articleForm.processing}
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
                                id="stock-categorie"
                                label="Catégorie"
                                required
                                options={categories.map((categorie) => ({ value: categorie, label: categorie }))}
                                placeholder="Sélectionner une catégorie"
                                value={articleForm.data.categorie}
                                onChange={(event) => articleForm.setData('categorie', event.target.value)}
                                error={articleForm.errors.categorie}
                            />
                        </div>
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
                                options={mouvementTypes.map((type) => ({ value: type, label: type }))}
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
        </BackofficeLayout>
    );
}
