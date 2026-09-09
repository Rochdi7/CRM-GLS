import { Link, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import TableToolbar from '@/Components/Tables/TableToolbar';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import SelectField from '@/Components/Forms/SelectField';
import FormActions from '@/Components/Forms/FormActions';
import Modal from '@/Components/Modals/Modal';
import ConfirmDialog from '@/Components/Modals/ConfirmDialog';
import { useFilterReset } from '@/Hooks/useFilterReset';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { t } from '@/Lib/i18n';
import type { DatabaseColumn, DatabaseRow, DatabaseTableFilters, DatabaseTablePageProps, SelectOption } from '@/Types';

/** Cells longer than this are truncated in the grid — the edit modal shows the full value. */
const PREVIEW_LENGTH = 80;

const PER_PAGE_OPTIONS: SelectOption[] = ['10', '25', '50', '100', '200'].map((n) => ({ value: n, label: n }));

const FILTER_DEFAULTS: Partial<DatabaseTableFilters> = { direction: 'asc', perPage: '25' };

const BOOLEAN_OPTIONS: SelectOption[] = [
    { value: '', label: 'NULL' },
    { value: 'true', label: 'true' },
    { value: 'false', label: 'false' },
];

type RowForm = {
    key: Record<string, string | null>;
    values: Record<string, string>;
};

function formatCount(value: number): string {
    return new Intl.NumberFormat('fr-FR').format(value);
}

function preview(value: string | null): string {
    if (value === null) {
        return '';
    }

    return value.length > PREVIEW_LENGTH ? `${value.slice(0, PREVIEW_LENGTH)}…` : value;
}

function describeKey(key: Record<string, string | null>): string {
    return Object.entries(key)
        .map(([column, value]) => `${column} = ${value ?? 'NULL'}`)
        .join(', ');
}

function placeholderFor(column: DatabaseColumn): string {
    switch (column.input) {
        case 'date':
            return 'AAAA-MM-JJ';
        case 'datetime':
            return 'AAAA-MM-JJ HH:MM:SS';
        case 'time':
            return 'HH:MM:SS';
        case 'json':
            return '{ "clé": "valeur" }';
        case 'integer':
            return '0';
        case 'decimal':
            return '0.00';
        default:
            return '';
    }
}

/**
 * « Gestion de la base de données » — one table: paginated / searched /
 * sorted rows (server-side, §5), plus the button-driven writes: add a row,
 * edit a row, delete a row, empty the table, export CSV.
 *
 * Maintenance screen reserved to the maintenance account (identity, not
 * permission — §16; DatabaseManagementController). Every value is shown
 * exactly as stored (`text-normal-case`, no uppercase transform): this is
 * raw data, not a CRM list.
 *
 * Empty field = NULL for a nullable column; on insert an empty field on a
 * column with a default lets PostgreSQL apply that default (identity ids,
 * timestamps, statut). The database's own refusal (type, constraint, FK) is
 * shown verbatim inside the modal.
 */
export default function DatabaseTable({ table, columns, rows, filters }: DatabaseTablePageProps) {
    const loading = useInertiaLoading();
    const baseUrl = `/backoffice/database-management/${encodeURIComponent(table.name)}`;
    const canEdit = !table.readOnly && table.primaryKey.length > 0;
    const canWrite = !table.readOnly;

    const [showStructure, setShowStructure] = useState(false);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<DatabaseRow | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<DatabaseRow | null>(null);
    const [deleteError, setDeleteError] = useState<string>();
    const [deleting, setDeleting] = useState(false);
    const [showTruncate, setShowTruncate] = useState(false);

    const emptyValues = useMemo(() => Object.fromEntries(columns.map((c) => [c.name, ''])), [columns]);

    const form = useForm<RowForm>({ key: {}, values: emptyValues });
    const truncateForm = useForm({ confirmation: '' });

    function reload(next: Partial<DatabaseTableFilters>) {
        router.get(baseUrl, { ...filters, ...next }, { preserveState: true, preserveScroll: true, replace: true });
    }

    const { reset, active } = useFilterReset(filters, reload, FILTER_DEFAULTS);

    function toggleSort(column: string) {
        if (filters.sort === column) {
            reload({ direction: filters.direction === 'asc' ? 'desc' : 'asc' });
        } else {
            reload({ sort: column, direction: 'asc' });
        }
    }

    function openCreate() {
        form.setData({ key: {}, values: emptyValues });
        form.clearErrors();
        setEditing(null);
        setShowModal(true);
    }

    function openEdit(row: DatabaseRow) {
        form.setData({
            key: row.key,
            values: Object.fromEntries(columns.map((c) => [c.name, row.values[c.name] ?? ''])),
        });
        form.clearErrors();
        setEditing(row);
        setShowModal(true);
    }

    function closeModal() {
        setShowModal(false);
        setEditing(null);
        form.clearErrors();
    }

    function setValue(column: string, value: string) {
        form.setData('values', { ...form.data.values, [column]: value });
    }

    function handleSubmit(event: FormEvent) {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => closeModal() };

        if (editing) {
            form.put(`${baseUrl}/rows`, options);
        } else {
            form.post(`${baseUrl}/rows`, options);
        }
    }

    function confirmDelete() {
        if (!deleteTarget) {
            return;
        }

        setDeleting(true);
        setDeleteError(undefined);

        router.delete(`${baseUrl}/rows`, {
            data: { key: deleteTarget.key },
            preserveScroll: true,
            onSuccess: () => {
                setDeleteTarget(null);
                setDeleting(false);
            },
            onError: (errors: Record<string, string>) => {
                setDeleteError(errors.database ?? errors.key ?? t('Deletion failed.'));
                setDeleting(false);
            },
        });
    }

    function confirmTruncate() {
        truncateForm.post(`${baseUrl}/truncate`, {
            preserveScroll: true,
            onSuccess: () => {
                setShowTruncate(false);
                truncateForm.reset();
            },
        });
    }

    const sortOptions: SelectOption[] = [
        { value: '', label: t('Primary key') },
        ...columns.map((c) => ({ value: c.name, label: c.name })),
    ];

    const directionOptions: SelectOption[] = [
        { value: 'asc', label: t('Ascending') },
        { value: 'desc', label: t('Descending') },
    ];

    const databaseError = (form.errors as Record<string, string>).database;
    const truncateError = (truncateForm.errors as Record<string, string>).database ?? truncateForm.errors.confirmation;

    function renderField(column: DatabaseColumn) {
        const id = `db-${column.name}`;
        const value = form.data.values[column.name] ?? '';
        // Sequence-assigned ids are never typed: automatic on insert, frozen on edit.
        const locked = column.autoIncrement;
        const error = (form.errors as Record<string, string>)[`values.${column.name}`];

        const hint = [
            column.type,
            column.nullable ? t('nullable') : 'NOT NULL',
            column.default !== null ? `${t('default')}: ${column.default}` : null,
            column.references ? `→ ${column.references.table}.${column.references.column}` : null,
        ]
            .filter(Boolean)
            .join(' · ');

        let control;

        if (column.input === 'boolean') {
            control = (
                <SelectField
                    id={id}
                    options={BOOLEAN_OPTIONS}
                    value={value}
                    disabled={locked}
                    searchable={false}
                    onChange={(e) => setValue(column.name, e.target.value)}
                />
            );
        } else if (column.input === 'text' || column.input === 'json') {
            control = (
                <textarea
                    id={id}
                    className={`form-control text-normal-case${column.input === 'json' ? ' font-monospace' : ''}${error ? ' is-invalid' : ''}`}
                    rows={column.input === 'json' ? 4 : 3}
                    value={value}
                    disabled={locked}
                    placeholder={placeholderFor(column)}
                    onChange={(e) => setValue(column.name, e.target.value)}
                />
            );
        } else {
            control = (
                <input
                    id={id}
                    type={column.input === 'date' ? 'date' : 'text'}
                    inputMode={column.input === 'integer' || column.input === 'decimal' ? 'decimal' : undefined}
                    className={`form-control text-normal-case${error ? ' is-invalid' : ''}`}
                    value={value}
                    disabled={locked}
                    placeholder={locked && !editing ? t('automatic') : placeholderFor(column)}
                    onChange={(e) => setValue(column.name, e.target.value)}
                />
            );
        }

        return (
            <div className="col-md-6 mb-3" key={column.name}>
                <label htmlFor={id} className="form-label d-flex align-items-center gap-2 text-normal-case">
                    <span className="fw-medium">{column.name}</span>
                    {column.primary && <span className="badge badge-soft-primary">PK</span>}
                    {column.references && <span className="badge badge-soft-info">FK</span>}
                </label>
                {control}
                {error && <div className="invalid-feedback d-block">{error}</div>}
                <div className="form-text fs-12 text-normal-case">{hint}</div>
            </div>
        );
    }

    return (
        <BackofficeLayout
            title={table.name}
            breadcrumbs={[
                { label: t('Dashboard'), href: '/backoffice/dashboard' },
                { label: t('Database management'), href: '/backoffice/database-management' },
                { label: table.name },
            ]}
            actions={
                <div className="d-flex flex-wrap gap-2">
                    <Link href="/backoffice/database-management" className="btn btn-outline-secondary d-flex align-items-center">
                        <i className="ti ti-arrow-left me-2" />
                        {t('All tables')}
                    </Link>
                    <a href={`${baseUrl}/export`} className="btn btn-outline-secondary d-flex align-items-center">
                        <i className="ti ti-download me-2" />
                        {t('Export CSV')}
                    </a>
                    {canWrite && (
                        <button
                            type="button"
                            className="btn btn-outline-danger d-flex align-items-center"
                            disabled={table.total === 0}
                            onClick={() => {
                                truncateForm.reset();
                                truncateForm.clearErrors();
                                setShowTruncate(true);
                            }}
                        >
                            <i className="ti ti-trash-x me-2" />
                            {t('Empty the table')}
                        </button>
                    )}
                    {canEdit && (
                        <button type="button" className="btn btn-primary d-flex align-items-center" onClick={openCreate}>
                            <i className="ti ti-square-rounded-plus me-2" />
                            {t('Add a row')}
                        </button>
                    )}
                </div>
            }
        >
            {table.readOnly && (
                <div className="alert alert-secondary border fs-13 mb-4">
                    <i className="ti ti-lock me-1" />
                    {t('This table is read-only here: the audit journal and the migrations list are never rewritten by hand.')}
                </div>
            )}
            {!table.readOnly && table.primaryKey.length === 0 && (
                <div className="alert alert-warning border fs-13 mb-4">
                    <i className="ti ti-key-off me-1" />
                    {t('This table has no primary key: rows cannot be edited one by one.')}
                </div>
            )}

            <Card
                title={`${table.name} — ${formatCount(table.total)} ${t('rows')}`}
                bodyClassName="p-0 py-3"
                tools={
                    <button
                        type="button"
                        className="btn btn-sm btn-outline-secondary mb-3"
                        onClick={() => setShowStructure((v) => !v)}
                    >
                        <i className={`ti ${showStructure ? 'ti-chevron-up' : 'ti-list-details'} me-1`} />
                        {t('Structure')}
                    </button>
                }
            >
                {showStructure && (
                    <div className="px-3 pb-3">
                        <div className="table-responsive border rounded">
                            <table className="table table-sm mb-0">
                                <thead className="thead-light">
                                    <tr>
                                        <th>{t('Column')}</th>
                                        <th>{t('Type')}</th>
                                        <th>{t('Nullable')}</th>
                                        <th>{t('Default')}</th>
                                        <th>{t('Reference')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {columns.map((c) => (
                                        <tr key={c.name}>
                                            <td className="text-normal-case fw-medium">
                                                {c.name}
                                                {c.primary && <span className="badge badge-soft-primary ms-2">PK</span>}
                                            </td>
                                            <td className="text-normal-case">{c.type}</td>
                                            <td>{c.nullable ? t('Yes') : t('No')}</td>
                                            <td className="text-normal-case text-muted">{c.default ?? '—'}</td>
                                            <td className="text-normal-case">
                                                {c.references ? (
                                                    <Link href={`/backoffice/database-management/${encodeURIComponent(c.references.table)}`}>
                                                        {c.references.table}.{c.references.column}
                                                    </Link>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                <div className="px-3">
                    <TableToolbar
                        search={<SearchInput value={filters.search} onSearch={(search) => reload({ search })} placeholder={t('Search in every column')} />}
                        onReset={reset}
                        resetActive={active}
                    >
                        <div style={{ width: 200 }}>
                            <SelectField
                                id="db-sort"
                                label={t('Sort by')}
                                options={sortOptions}
                                value={filters.sort}
                                onChange={(e) => reload({ sort: e.target.value })}
                            />
                        </div>
                        <div style={{ width: 150 }}>
                            <SelectField
                                id="db-direction"
                                label={t('Direction')}
                                options={directionOptions}
                                value={filters.direction}
                                searchable={false}
                                onChange={(e) => reload({ direction: e.target.value })}
                            />
                        </div>
                        <div style={{ width: 110 }}>
                            <SelectField
                                id="db-per-page"
                                label={t('Per page')}
                                options={PER_PAGE_OPTIONS}
                                value={filters.perPage}
                                searchable={false}
                                onChange={(e) => reload({ perPage: e.target.value })}
                            />
                        </div>
                    </TableToolbar>
                </div>

                {rows.data.length === 0 ? (
                    <EmptyState title={filters.search ? t('No row matches this search.') : t('This table is empty.')} icon="ti ti-table-off" />
                ) : (
                    <DataTable
                        loading={loading}
                        head={
                            <tr>
                                {columns.map((c) => (
                                    <th
                                        key={c.name}
                                        className="text-normal-case"
                                        style={{ whiteSpace: 'nowrap', cursor: 'pointer' }}
                                        onClick={() => toggleSort(c.name)}
                                        title={t('Sort by :column', { column: c.name })}
                                    >
                                        {c.name}
                                        {c.primary && <i className="ti ti-key ms-1 text-muted" />}
                                        {filters.sort === c.name && (
                                            <i className={`ti ${filters.direction === 'desc' ? 'ti-arrow-down' : 'ti-arrow-up'} ms-1`} />
                                        )}
                                    </th>
                                ))}
                                {canEdit && <th className="text-end">{t('Action')}</th>}
                            </tr>
                        }
                    >
                        {rows.data.map((row, index) => (
                            <tr key={`${describeKey(row.key)}-${index}`}>
                                {columns.map((c) => {
                                    const value = row.values[c.name] ?? null;

                                    return (
                                        <td
                                            key={c.name}
                                            className="text-normal-case"
                                            style={{ whiteSpace: 'nowrap', maxWidth: 360, overflow: 'hidden', textOverflow: 'ellipsis' }}
                                            title={value ?? 'NULL'}
                                        >
                                            {value === null ? (
                                                <span className="text-muted fst-italic">NULL</span>
                                            ) : value === '' ? (
                                                <span className="text-muted fst-italic">{t('empty')}</span>
                                            ) : (
                                                preview(value)
                                            )}
                                        </td>
                                    );
                                })}
                                {canEdit && (
                                    <td className="text-end">
                                        <RowActions>
                                            <RowActionItem icon="ti-edit" onClick={() => openEdit(row)}>
                                                {t('Edit')}
                                            </RowActionItem>
                                            <RowActionItem
                                                icon="ti-trash"
                                                danger
                                                onClick={() => {
                                                    setDeleteTarget(row);
                                                    setDeleteError(undefined);
                                                }}
                                            >
                                                {t('Delete')}
                                            </RowActionItem>
                                        </RowActions>
                                    </td>
                                )}
                            </tr>
                        ))}
                    </DataTable>
                )}

                <Pagination paginator={rows} />
            </Card>

            <Modal
                show={showModal}
                title={editing ? `${t('Edit row')} — ${describeKey(editing.key)}` : `${t('Add a row')} — ${table.name}`}
                onClose={closeModal}
                processing={form.processing}
                size="xl"
            >
                <form onSubmit={handleSubmit}>
                    {databaseError && (
                        <div className="alert alert-danger fs-13 text-normal-case" role="alert">
                            <i className="ti ti-alert-circle me-1" />
                            {databaseError}
                        </div>
                    )}
                    <p className="text-muted fs-13 mb-3">
                        {t('An empty field is stored as NULL (or the column default when adding a row). Booleans are true/false, JSON must be valid.')}
                    </p>
                    <div className="row">{columns.map(renderField)}</div>
                    <div className="d-flex justify-content-end gap-2 mt-2">
                        <FormActions onCancel={closeModal} processing={form.processing} />
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                show={deleteTarget !== null}
                title={t('Delete this row?')}
                recordLabel={deleteTarget ? `${table.name} · ${describeKey(deleteTarget.key)}` : ''}
                message={t('The row is removed from the table immediately, without any application rule running. This cannot be undone.')}
                error={deleteError}
                processing={deleting}
                onConfirm={confirmDelete}
                onCancel={() => setDeleteTarget(null)}
            />

            <ConfirmDialog
                show={showTruncate}
                title={t('Empty the whole table?')}
                recordLabel={`${table.name} · ${formatCount(table.total)} ${t('rows')}`}
                message={t('Every row will be deleted. Foreign keys pointing at this table still apply. Type the table name to confirm.')}
                error={truncateError}
                processing={truncateForm.processing}
                confirmLabel={t('Empty the table')}
                processingLabel={t('Emptying…')}
                icon="ti ti-trash-x"
                onConfirm={confirmTruncate}
                onCancel={() => setShowTruncate(false)}
            >
                <input
                    type="text"
                    className="form-control text-normal-case text-center"
                    placeholder={table.name}
                    value={truncateForm.data.confirmation}
                    onChange={(e) => truncateForm.setData('confirmation', e.target.value)}
                    autoComplete="off"
                />
            </ConfirmDialog>
        </BackofficeLayout>
    );
}
