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
 * Roles Create/Edit form — FULL-WIDTH layout (revised 31/08/2026).
 *
 * The previous version was a 4/8 two-column split: on a wide screen the
 * permission checkboxes were squeezed into a 2-per-row grid inside 2/3 of
 * the page, while the small "Rôle" card owned the other third — leaving
 * metres of empty space between a label and its neighbour. Now the role
 * identity is a compact top card and the permission catalog spans the whole
 * width, each module rendered as its own bordered panel in a responsive
 * grid (1 → 2 → 3 panels per row with screen width).
 *
 * Filtering stays purely client-side (mirrors the old RoleForm::render()
 * permissionSearch behavior); the search box, the global check/uncheck and
 * the save/cancel buttons live in one sticky toolbar so they stay reachable
 * however far down the catalog the user has scrolled.
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

    // Pure client-side display filter — matches the group label OR the
    // permission French label OR its machine name, case-insensitive. Never
    // sent to the server and never affects what gets submitted/validated.
    const visibleGroups = useMemo(() => {
        const needle = permissionSearch.trim().toLowerCase();

        if (needle === '') {
            return permissionGroups;
        }

        const filtered: PermissionGroups = {};

        for (const [group, permissions] of Object.entries(permissionGroups)) {
            const matchingPermissions = group.toLowerCase().includes(needle)
                ? permissions
                : Object.fromEntries(
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

    const totalPermissions = useMemo(
        () => Object.values(permissionGroups).reduce((sum, permissions) => sum + Object.keys(permissions).length, 0),
        [permissionGroups],
    );

    function toggle(permission: string, checked: boolean) {
        if (checked) {
            onSelectedChange(Array.from(new Set([...selected, permission])));
        } else {
            onSelectedChange(selected.filter((value) => value !== permission));
        }
    }

    // Group-level and global bulk actions act on what is CURRENTLY VISIBLE,
    // so a search narrows them too — never on permissions the user filtered
    // out and cannot see.
    function selectGroup(group: string) {
        const names = Object.keys(visibleGroups[group] ?? {});
        onSelectedChange(Array.from(new Set([...selected, ...names])));
    }

    function clearGroup(group: string) {
        const names = new Set(Object.keys(visibleGroups[group] ?? {}));
        onSelectedChange(selected.filter((value) => !names.has(value)));
    }

    function selectAllVisible() {
        const names = groupEntries.flatMap(([, permissions]) => Object.keys(permissions));
        onSelectedChange(Array.from(new Set([...selected, ...names])));
    }

    function clearAllVisible() {
        const names = new Set(groupEntries.flatMap(([, permissions]) => Object.keys(permissions)));
        onSelectedChange(selected.filter((value) => !names.has(value)));
    }

    return (
        <form onSubmit={onSubmit}>
            {/* ============================ Role identity ============================ */}
            <Card title="Rôle">
                <div className="row g-3">
                    <div className="col-xl-4 col-md-6">
                        <FormField
                            id="label"
                            label="Libellé"
                            required
                            value={label}
                            onChange={(event) => onLabelChange(event.target.value)}
                            error={errors.label}
                            placeholder="ex : Comptable"
                        />
                    </div>
                    <div className="col-xl-4 col-md-6">
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
                    </div>
                    <div className="col-xl-4">
                        <div className="border rounded p-3 h-100 d-flex align-items-center">
                            <span className="avatar avatar-md bg-primary-transparent rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0">
                                <i className="ti ti-key fs-18 text-primary" />
                            </span>
                            <span>
                                <span className="text-muted fs-13 d-block">Permissions accordées</span>
                                <span className="fs-20 fw-bold">{selected.length}</span>
                                <span className="text-muted fs-13"> / {totalPermissions}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </Card>

            {/* ========================= Permission catalog ========================= */}
            <div className="card">
                <div className="card-header gls-sticky-toolbar">
                    <div className="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h4 className="mb-0">Permissions</h4>

                        <div className="d-flex align-items-center flex-wrap gap-2">
                            <div className="input-icon-start position-relative">
                                <span className="input-icon-addon">
                                    <i className="ti ti-search" />
                                </span>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={permissionSearch}
                                    onChange={(event) => setPermissionSearch(event.target.value)}
                                    placeholder="Rechercher une permission"
                                />
                            </div>
                            <button type="button" className="btn btn-outline-primary" onClick={selectAllVisible}>
                                <i className="ti ti-checks me-1" />
                                Tout cocher
                            </button>
                            <button type="button" className="btn btn-outline-light" onClick={clearAllVisible}>
                                <i className="ti ti-square-off me-1" />
                                Tout décocher
                            </button>
                            <FormActions onCancel={onCancel} processing={processing} />
                        </div>
                    </div>
                </div>

                <div className="card-body">
                    {errors.permissions && (
                        <div className="alert alert-danger" role="alert">
                            {errors.permissions}
                        </div>
                    )}

                    {groupEntries.length === 0 ? (
                        <EmptyState title="Aucune permission ne correspond à votre recherche" icon="ti ti-key-off" />
                    ) : (
                        <div className="row g-3">
                            {groupEntries.map(([group, permissions]) => {
                                const permissionEntries = Object.entries(permissions);
                                const held = permissionEntries.filter(([permission]) =>
                                    selected.includes(permission),
                                ).length;
                                const all = held === permissionEntries.length;

                                return (
                                    <div className="col-xxl-4 col-lg-6" key={group}>
                                        <div className={`border rounded h-100 p-3${held > 0 ? ' border-primary' : ''}`}>
                                            <div className="d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom pb-2 mb-2">
                                                <h6 className="mb-0 d-flex align-items-center">
                                                    {group}
                                                    <span
                                                        className={`badge ms-2 ${
                                                            held === 0
                                                                ? 'badge-soft-secondary'
                                                                : all
                                                                  ? 'badge-soft-success'
                                                                  : 'badge-soft-primary'
                                                        }`}
                                                    >
                                                        {held}/{permissionEntries.length}
                                                    </span>
                                                </h6>
                                                <div className="btn-group btn-group-sm">
                                                    <button
                                                        type="button"
                                                        className="btn btn-outline-light"
                                                        onClick={() => selectGroup(group)}
                                                    >
                                                        Tout
                                                    </button>
                                                    <button
                                                        type="button"
                                                        className="btn btn-outline-light"
                                                        onClick={() => clearGroup(group)}
                                                    >
                                                        Aucun
                                                    </button>
                                                </div>
                                            </div>

                                            <div className="d-flex flex-column gap-1">
                                                {permissionEntries.map(([permission, permLabel]) => {
                                                    const checked = selected.includes(permission);

                                                    return (
                                                        <label
                                                            key={permission}
                                                            htmlFor={`perm-${permission}`}
                                                            className={`d-flex align-items-start rounded px-2 py-1 mb-0${
                                                                checked ? ' bg-primary-transparent' : ''
                                                            }`}
                                                            style={{ cursor: 'pointer' }}
                                                        >
                                                            <input
                                                                className="form-check-input mt-1 me-2 flex-shrink-0"
                                                                type="checkbox"
                                                                id={`perm-${permission}`}
                                                                value={permission}
                                                                checked={checked}
                                                                onChange={(event) =>
                                                                    toggle(permission, event.target.checked)
                                                                }
                                                            />
                                                            <span className="flex-fill">
                                                                <span className="d-block">{permLabel}</span>
                                                                <span className="text-muted d-block fs-12">
                                                                    <code>{permission}</code>
                                                                </span>
                                                            </span>
                                                        </label>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </div>
        </form>
    );
}
