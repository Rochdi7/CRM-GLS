import { router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DataTable from '@/Components/Tables/DataTable';
import EmptyState from '@/Components/Shared/EmptyState';
import TableLengthRow from '@/Components/Tables/TableLengthRow';
import SearchInput from '@/Components/Tables/SearchInput';
import Pagination from '@/Components/Tables/Pagination';
import RowActions, { RowActionItem } from '@/Components/Tables/RowActions';
import StatusBadge from '@/Components/Details/StatusBadge';
import Modal from '@/Components/Modals/Modal';
import FormField from '@/Components/Forms/FormField';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import type { SharedProps, UserEditForm, UserRow, UsersIndexPageProps } from '@/Types';

/**
 * Replaces App\Livewire\Backoffice\Users\UsersIndex — same columns, same
 * center-aware scoping (server-side, already baked into GetUsersList), same
 * search-only filter bar (no dropdowns per the original), same edit modal +
 * one-time password regeneration flow. Users are NEVER created here (they
 * come exclusively from Employee creation via EmployeeObserver) — no
 * "New user" action exists anywhere on this page.
 */
export default function UsersIndex({ users, filters, perPageOptions, centerLocked }: UsersIndexPageProps) {
    const { flash } = usePage<SharedProps>().props;
    const isLoading = useInertiaLoading();

    const [editingUser, setEditingUser] = useState<UserRow | null>(null);
    const [confirmingRegenerate, setConfirmingRegenerate] = useState(false);
    const [regeneratedPasswordShown, setRegeneratedPasswordShown] = useState<string | null>(null);
    const [regenerating, setRegenerating] = useState(false);

    const form = useForm<UserEditForm>({
        name: '',
        email: '',
        username: '',
        is_active: true,
    });

    function search(value: string) {
        router.get(
            '/backoffice/users',
            { search: value, perPage: filters.perPage },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function changePerPage(value: number) {
        router.get(
            '/backoffice/users',
            { search: filters.search, perPage: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    function openEdit(user: UserRow) {
        setEditingUser(user);
        setRegeneratedPasswordShown(null);
        form.setData({
            name: user.name,
            email: user.email,
            username: user.username ?? '',
            is_active: user.isActive,
        });
        form.clearErrors();
    }

    function closeModal() {
        setEditingUser(null);
        setRegeneratedPasswordShown(null);
        setConfirmingRegenerate(false);
        form.reset();
        form.clearErrors();
    }

    function submit(event: FormEvent) {
        event.preventDefault();

        if (!editingUser) {
            return;
        }

        form.put(`/backoffice/users/${editingUser.id}`, {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }

    /**
     * Replicates the Livewire original's `wire:confirm` (a native browser
     * confirm() dialog) — a small confirm step is used here instead of
     * silently regenerating, matching the same "are you sure?" gate before
     * invalidating the current password.
     */
    function requestRegenerate() {
        setConfirmingRegenerate(true);
    }

    function confirmRegenerate() {
        if (!editingUser) {
            return;
        }

        setRegenerating(true);

        router.post(
            `/backoffice/users/${editingUser.id}/regenerate-password`,
            {},
            {
                preserveScroll: true,
                // The plaintext password arrives via the shared
                // flash.regeneratedPassword prop (HandleInertiaRequests) —
                // picked up below, right after this visit lands, and kept in
                // local state so it survives further edits in the same modal.
                onSuccess: () => setConfirmingRegenerate(false),
                onFinish: () => setRegenerating(false),
            },
        );
    }

    // The one-time password is delivered via the shared `flash.regeneratedPassword`
    // prop (see HandleInertiaRequests). It is captured into local state here
    // so a further edit to name/email afterwards doesn't hide it — matching
    // the Livewire original where $regeneratedPassword stays set until the
    // modal is closed, letting the admin still Save/Cancel after
    // regenerating.
    useEffect(() => {
        if (flash.regeneratedPassword && editingUser) {
            setRegeneratedPasswordShown(flash.regeneratedPassword);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flash.regeneratedPassword]);

    return (
        <BackofficeLayout
            title="Utilisateurs"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Utilisateurs' }]}
        >
            <Card title="Utilisateurs" bodyClassName="p-0 py-3">
                <TableLengthRow
                    perPage={filters.perPage}
                    perPageOptions={perPageOptions}
                    onPerPageChange={changePerPage}
                    search={<SearchInput value={filters.search} onSearch={search} placeholder="Rechercher" />}
                />

                {users.data.length === 0 ? (
                    <EmptyState title="Aucun utilisateur trouvé" icon="ti ti-user-off" />
                ) : (
                    <>
                        <DataTable
                            loading={isLoading}
                            head={
                                <tr>
                                    <th>Nom</th>
                                    <th>Nom d'utilisateur</th>
                                    <th>Email</th>
                                    {!centerLocked && <th>Centre</th>}
                                    <th>Rôles</th>
                                    <th>Statut</th>
                                    <th className="text-end">Action</th>
                                </tr>
                            }
                        >
                            {users.data.map((user) => (
                                <tr key={user.id}>
                                    <td className="fw-medium">{user.name}</td>
                                    {/* Login identifiers stay verbatim — see the table-uppercase rule in app.css. */}
                                    <td className="text-normal-case">{user.username ? `@${user.username}` : '—'}</td>
                                    <td className="text-normal-case">{user.email}</td>
                                    {!centerLocked && <td>{user.employee?.etablissement ?? '—'}</td>}
                                    <td>
                                        {user.roles.length === 0 ? (
                                            <span className="text-muted">Aucun rôle</span>
                                        ) : (
                                            user.roles.map((role) => (
                                                <span className="badge badge-soft-info me-1" key={role}>
                                                    {role}
                                                </span>
                                            ))
                                        )}
                                    </td>
                                    <td>
                                        <StatusBadge
                                            label={user.isActive ? 'Actif' : 'Inactif'}
                                            variant={user.isActive ? 'success' : 'secondary'}
                                            dot
                                        />
                                    </td>
                                    <td className="text-end">
                                        <RowActions>
                                            <RowActionItem icon="ti-edit" onClick={() => openEdit(user)}>
                                                Modifier
                                            </RowActionItem>
                                            <RowActionItem icon="ti-shield-cog" href={`/backoffice/users/${user.id}/authorization`}>
                                                Gérer les autorisations
                                            </RowActionItem>
                                        </RowActions>
                                    </td>
                                </tr>
                            ))}
                        </DataTable>
                        <Pagination paginator={users} />
                    </>
                )}
            </Card>

            <Modal
                show={editingUser !== null}
                title="Modifier l'utilisateur"
                onClose={closeModal}
                processing={form.processing || regenerating}
                size="lg"
                footer={
                    <>
                        <button type="button" className="btn btn-light" onClick={closeModal} disabled={form.processing}>
                            Annuler
                        </button>
                        {/* form="user-edit-form" associates this button with the <form> below even
                            though the footer renders in a separate DOM subtree (.modal-footer is a
                            sibling of .modal-body, not a descendant of the form) — native HTML form
                            association, no extra wiring needed. FormActions (the usual shared
                            footer component) can't express this cross-tree association, so this
                            footer is hand-rolled to match its exact visual output instead. */}
                        <button type="submit" form="user-edit-form" className="btn btn-primary" disabled={form.processing}>
                            {form.processing ? (
                                <>
                                    <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" />
                                    Enregistrement…
                                </>
                            ) : (
                                'Enregistrer'
                            )}
                        </button>
                    </>
                }
            >
                <form onSubmit={submit} id="user-edit-form">
                    <div className="row">
                        <div className="col-md-6">
                            <FormField
                                id="u-name"
                                label="Nom"
                                required
                                value={form.data.name}
                                onChange={(event) => form.setData('name', event.target.value)}
                                error={form.errors.name}
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="u-email"
                                label="Email"
                                type="email"
                                required
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                                error={form.errors.email}
                            />
                        </div>
                        <div className="col-md-6">
                            <FormField
                                id="u-username"
                                label="Nom d'utilisateur"
                                value={form.data.username}
                                onChange={(event) => form.setData('username', event.target.value)}
                                error={form.errors.username}
                            />
                        </div>
                    </div>
                    <div className="form-check form-switch mb-3">
                        <input
                            className="form-check-input"
                            type="checkbox"
                            id="u-active"
                            checked={form.data.is_active}
                            onChange={(event) => form.setData('is_active', event.target.checked)}
                        />
                        <label className="form-check-label" htmlFor="u-active">
                            Compte actif (peut se connecter)
                        </label>
                    </div>

                    {/* Password regeneration */}
                    <div className="border-top pt-3">
                        {regeneratedPasswordShown ? (
                            <>
                                <div className="alert alert-info d-flex justify-content-between align-items-center mb-0">
                                    <span>
                                        Nouveau mot de passe : <code className="fs-14">{regeneratedPasswordShown}</code>
                                    </span>
                                    <span className="badge badge-soft-warning">Unique</span>
                                </div>
                                <p className="text-muted fs-12 mt-2 mb-0">
                                    Communiquez-le maintenant — il ne sera plus jamais affiché. L'utilisateur devra le changer à sa
                                    prochaine connexion.
                                </p>
                            </>
                        ) : (
                            <button
                                type="button"
                                className="btn btn-outline-warning btn-sm"
                                onClick={requestRegenerate}
                                disabled={regenerating}
                            >
                                {regenerating ? (
                                    <span className="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" />
                                ) : (
                                    <i className="ti ti-key me-1" />
                                )}
                                Régénérer le mot de passe
                            </button>
                        )}
                    </div>
                </form>
            </Modal>

            {/* Confirm step replicating the Livewire original's wire:confirm native dialog */}
            <Modal
                show={confirmingRegenerate}
                title="Confirmer la régénération"
                onClose={() => setConfirmingRegenerate(false)}
                processing={regenerating}
                footer={
                    <>
                        <button
                            type="button"
                            className="btn btn-light"
                            onClick={() => setConfirmingRegenerate(false)}
                            disabled={regenerating}
                        >
                            Annuler
                        </button>
                        <button type="button" className="btn btn-warning" onClick={confirmRegenerate} disabled={regenerating}>
                            {regenerating ? (
                                <>
                                    <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" />
                                    Génération…
                                </>
                            ) : (
                                'Générer un nouveau mot de passe'
                            )}
                        </button>
                    </>
                }
            >
                <p className="mb-0">Générer un nouveau mot de passe à usage unique pour cet utilisateur ?</p>
            </Modal>
        </BackofficeLayout>
    );
}
