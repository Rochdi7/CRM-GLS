import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import Card from '@/Components/Shared/Card';
import FormField from '@/Components/Forms/FormField';
import FormActions from '@/Components/Forms/FormActions';
import EmptyState from '@/Components/Shared/EmptyState';
import type { LaravelValidationErrors, PermissionGroups } from '@/Types';

interface RolePermissionsFormProps {
    /** Present only when editing — disables the machine-name field and shows the read-only hint. */
    editing?: boolean;
    label: string;
    onLabelChange: (value: string) => void;
    /** Raw, user-typed machine name. Ignored (still rendered, disabled) when editing — the backend never reads it back (UpdateRoleRequest). */
    name: string;
    onNameChange: (value: string) => void;
    selected: string[];
    onSelectedChange: (next: string[]) => void;
    permissionGroups: PermissionGroups;
    errors: LaravelValidationErrors;
    processing: boolean;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    onCancel: () => void;
}

/**
 * Shared two-column layout for Roles Create/Edit — left card (label +
 * machine name + save/cancel), right card (permission groups with
 * select-all/clear + a client-side-only search filter), matching
 * resources/views/livewire/backoffice/roles/role-form.blade.php exactly.
 * Not a modal: rendered inline on its own full page by both
 * Pages/Backoffice/Roles/{Create,Edit}.tsx.
 */
export default function RolePermissionsForm({
    editing = false,
    label,
    onLabelChange,
    name,
    onNameChange,
    selected,
    onSelectedChange,
    permissionGroups,
    errors,
    processing,
    onSubmit,
    onCancel,
}: RolePermissionsFormProps) {
    const [permissionSearch, setPermissionSearch] = useState('');

    // Pure client-side display filter — mirrors RoleForm::render()'s
    // permissionSearch behavior exactly (matches group label OR permission
    // French label OR machine name, case-insensitive). Never sent to the
    // server and never affects what gets submitted/validated.
    const visibleGroups = useMemo(() => {
        const needle = permissionSearch.trim().toLowerCase();

        if (needle === '') {
            return permissionGroups;
        }

        const filtered: PermissionGroups = {};

        for (const [group, permissions] of Object.entries(permissionGroups)) {
            const matchingPermissions = Object.fromEntries(
                Object.entries(permissions).filter(
                    ([permission, permLabel]) =>
                        permLabel.toLowerCase().includes(needle) || permission.toLowerCase().includes(needle),
                ),
            );

            if (Object.keys(matchingPermissions).length > 0) {
                filtered[group] = matchingPermissions;
            }
        }

        return filtered;
    }, [permissionGroups, permissionSearch]);

    const groupEntries = Object.entries(visibleGroups);

    function toggle(permission: string, checked: boolean) {
        if (checked) {
            onSelectedChange(Array.from(new Set([...selected, permission])));
        } else {
            onSelectedChange(selected.filter((value) => value !== permission));
        }
    }

    function selectGroup(group: string) {
        const names = Object.keys(permissionGroups[group] ?? {});
        onSelectedChange(Array.from(new Set([...selected, ...names])));
    }

    function clearGroup(group: string) {
        const names = new Set(Object.keys(permissionGroups[group] ?? {}));
        onSelectedChange(selected.filter((value) => !names.has(value)));
    }

    return (
        <form onSubmit={onSubmit}>
            <div className="row">
                <div className="col-lg-4">
                    <Card title="Rôle">
                        <FormField
                            id="label"
                            label="Libellé"
                            required
                            value={label}
                            onChange={(event) => onLabelChange(event.target.value)}
                            error={errors.label}
                            placeholder="ex : Comptable"
                        />

                        <div className="mb-3">
                            <label className="form-label" htmlFor="name">
                                Nom technique<span className="text-danger ms-1">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                className={`form-control${errors.name ? ' is-invalid' : ''}`}
                                value={name}
                                onChange={(event) => onNameChange(event.target.value)}
                                disabled={editing}
                                placeholder="ex : comptable"
                            />
                            {errors.name && <div className="invalid-feedback">{errors.name}</div>}
                            <div className="form-text">
                                {editing
                                    ? 'Le nom technique ne peut pas être modifié après la création.'
                                    : 'Lettres minuscules, chiffres et tirets uniquement.'}
                            </div>
                        </div>

                        <div className="d-flex align-items-center gap-2">
                            <FormActions onCancel={onCancel} processing={processing} />
                        </div>
                    </Card>
                </div>

                <div className="col-lg-8">
                    <Card
                        title="Permissions"
                        tools={
                            <div className="input-icon-start position-relative">
                                <span className="input-icon-addon">
                                    <i className="fa fa-magnifying-glass" />
                                </span>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={permissionSearch}
                                    onChange={(event) => setPermissionSearch(event.target.value)}
                                    placeholder="Rechercher une permission"
                                />
                            </div>
                        }
                    >
                        {errors.permissions && (
                            <div className="alert alert-danger" role="alert">
                                {errors.permissions}
                            </div>
                        )}

                        {groupEntries.length === 0 ? (
                            <EmptyState title="Aucune permission ne correspond à votre recherche" icon="fa fa-key" />
                        ) : (
                            groupEntries.map(([group, permissions]) => (
                                <div className="border rounded p-3 mb-3" key={group}>
                                    <div className="d-flex align-items-center justify-content-between flex-wrap mb-2">
                                        <h6 className="mb-0">{group}</h6>
                                        <div>
                                            <button
                                                type="button"
                                                className="btn btn-link btn-sm p-0 me-2"
                                                onClick={() => selectGroup(group)}
                                            >
                                                Tout sélectionner
                                            </button>
                                            <button
                                                type="button"
                                                className="btn btn-link btn-sm p-0 text-muted"
                                                onClick={() => clearGroup(group)}
                                            >
                                                Effacer
                                            </button>
                                        </div>
                                    </div>
                                    <div className="row">
                                        {Object.entries(permissions).map(([permission, permLabel]) => (
                                            <div className="col-md-6" key={permission}>
                                                <div className="form-check mb-1">
                                                    <input
                                                        className="form-check-input"
                                                        type="checkbox"
                                                        id={`perm-${permission}`}
                                                        value={permission}
                                                        checked={selected.includes(permission)}
                                                        onChange={(event) => toggle(permission, event.target.checked)}
                                                    />
                                                    <label className="form-check-label" htmlFor={`perm-${permission}`}>
                                                        {permLabel}
                                                        <span className="text-muted d-block fs-12">
                                                            <code>{permission}</code>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))
                        )}
                    </Card>
                </div>
            </div>
        </form>
    );
}
