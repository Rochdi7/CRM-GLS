import { useForm } from '@inertiajs/react';
import { type FormEvent, useRef } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import ImportScopeFields from '@/Components/Import/ImportScopeFields';
import type { ImportEtablissementOption } from '@/Types/import';

interface StudentImportUploadProps {
    etablissements: ImportEtablissementOption[];
    centerLocked: boolean;
}

interface UploadFormState {
    file: File | null;
    etablissement_id: string;
}

/**
 * The batch's Centre + Année scolaire come from the ACTIVE top-bar context
 * (shown by ImportScopeFields), never from free dropdowns here — the server
 * derives them via ResolvesImportScope. Only in « Tous les centres » mode
 * does a Centre select appear.
 */
export default function StudentImportUpload({ etablissements, centerLocked }: StudentImportUploadProps) {
    const fileInputRef = useRef<HTMLInputElement>(null);

    const form = useForm<UploadFormState>({
        file: null,
        etablissement_id: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();

        form.post('/backoffice/import/students/analyze', {
            forceFormData: true,
        });
    }

    const canAnalyze = form.data.file !== null && (centerLocked || form.data.etablissement_id !== '');

    return (
        <BackofficeLayout
            title="Import Étudiants"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Import de données', href: '/backoffice/import' },
                { label: 'Étudiants' },
            ]}
        >
            <Card title="Importer des étudiants depuis l'ancien CRM">
                <form onSubmit={submit}>
                    <ImportScopeFields
                        etablissements={etablissements}
                        centerLocked={centerLocked}
                        etablissementId={form.data.etablissement_id}
                        error={form.errors.etablissement_id}
                        onChange={(value) => form.setData('etablissement_id', value)}
                    />

                    <div className="mb-3">
                        <label className="form-label" htmlFor="import-file">
                            Fichier Excel (.xlsx)
                            <span className="text-danger ms-1">*</span>
                        </label>
                        <input
                            ref={fileInputRef}
                            id="import-file"
                            type="file"
                            accept=".xlsx"
                            className={`form-control${form.errors.file ? ' is-invalid' : ''}`}
                            onChange={(e) => form.setData('file', e.target.files?.[0] ?? null)}
                        />
                        {form.errors.file && <div className="invalid-feedback">{form.errors.file}</div>}
                    </div>

                    <button type="submit" className="btn btn-primary" disabled={!canAnalyze || form.processing}>
                        {form.processing ? 'Analyse en cours…' : 'Analyser'}
                    </button>
                </form>
            </Card>
        </BackofficeLayout>
    );
}
