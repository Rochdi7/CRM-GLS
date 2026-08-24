import { usePage } from '@inertiajs/react';
import SelectField from '@/Components/Forms/SelectField';
import type { SharedProps } from '@/Types';
import type { ImportEtablissementOption } from '@/Types/import';

interface ImportScopeFieldsProps {
    etablissements: ImportEtablissementOption[];
    /** True when a specific centre is active in the top bar — the Centre select is then hidden, exactly like the list pages' centre filter. */
    centerLocked: boolean;
    etablissementId: string;
    error?: string;
    onChange: (value: string) => void;
}

/**
 * The batch's Année scolaire (and Centre, when one is active) comes from the
 * top-bar context switcher — never from a free dropdown on this form. The
 * scope is shown as read-only badges so the operator sees exactly where the
 * imported rows will land; switching it is the top bar's job. Only a global
 * user working in « Tous les centres » still picks the Centre here.
 */
export default function ImportScopeFields({
    etablissements,
    centerLocked,
    etablissementId,
    error,
    onChange,
}: ImportScopeFieldsProps) {
    const { context } = usePage<SharedProps>().props;

    return (
        <>
            <div className="mb-3 d-flex align-items-center flex-wrap gap-2">
                <span className="text-muted">Import dans :</span>
                <span className="badge bg-primary-transparent text-primary fs-13">
                    <i className="ti ti-calendar me-1" />
                    {context?.currentAcademicYear?.name ?? '—'}
                </span>
                {centerLocked && (
                    <span className="badge bg-primary-transparent text-primary fs-13">
                        <i className="ti ti-building me-1" />
                        {context?.currentCenter?.name ?? '—'}
                    </span>
                )}
                <small className="text-muted w-100">
                    L&apos;année scolaire{centerLocked ? ' et le centre' : ''} d&apos;import{' '}
                    {centerLocked ? 'sont ceux sélectionnés' : 'est celle sélectionnée'} dans la barre supérieure.
                </small>
            </div>

            {!centerLocked && (
                <div className="row">
                    <div className="col-md-6">
                        <SelectField
                            id="import-etablissement"
                            label="Centre"
                            required
                            placeholder="Choisir un centre…"
                            options={etablissements.map((e) => ({ value: e.id, label: e.nom_centre }))}
                            value={etablissementId}
                            error={error}
                            onChange={(e) => onChange(e.target.value)}
                        />
                    </div>
                </div>
            )}
        </>
    );
}
