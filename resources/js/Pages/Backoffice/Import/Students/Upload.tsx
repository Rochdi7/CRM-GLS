import { useForm } from '@inertiajs/react';
import { type FormEvent, useRef } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import SelectField from '@/Components/Forms/SelectField';
import type { ImportAnneeScolaireOption, ImportEtablissementOption } from '@/Types/import';

interface StudentImportUploadProps {
    etablissements: ImportEtablissementOption[];
    anneesScolaires: ImportAnneeScolaireOption[];
}

interface UploadFormState {
    file: File | null;
    etablissement_id: string;
    annee_scolaire_id: string;
}

/**
 * Centre + Année scolaire are mandatory here — no silent default-only path
 * (import plan's "Mandatory Centre + Année scolaire scope"). The Année
 * dropdown pre-fills to the par_defaut year for convenience only; the user
 * still explicitly confirms it, and the server re-validates independently.
 */
export default function StudentImportUpload({ etablissements, anneesScolaires }: StudentImportUploadProps) {
    const defaultAnnee = anneesScolaires.find((a) => a.par_defaut) ?? anneesScolaires[0];
    const fileInputRef = useRef<HTMLInputElement>(null);

    const form = useForm<UploadFormState>({
        file: null,
        etablissement_id: '',
        annee_scolaire_id: defaultAnnee ? String(defaultAnnee.id) : '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();

        form.post('/backoffice/import/students/analyze', {
            forceFormData: true,
        });
    }

    const canAnalyze = form.data.file !== null && form.data.etablissement_id !== '' && form.data.annee_scolaire_id !== '';

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
                    <div className="row">
                        <div className="col-md-6">
                            <SelectField
                                id="import-etablissement"
                                label="Centre"
                                required
                                placeholder="Choisir un centre…"
                                options={etablissements.map((e) => ({ value: e.id, label: e.nom_centre }))}
                                value={form.data.etablissement_id}
                                error={form.errors.etablissement_id}
                                onChange={(e) => form.setData('etablissement_id', e.target.value)}
                            />
                        </div>
                        <div className="col-md-6">
                            <SelectField
                                id="import-annee-scolaire"
                                label="Année scolaire"
                                required
                                placeholder="Choisir une année scolaire…"
                                options={anneesScolaires.map((a) => ({ value: a.id, label: a.nom }))}
                                value={form.data.annee_scolaire_id}
                                error={form.errors.annee_scolaire_id}
                                onChange={(e) => form.setData('annee_scolaire_id', e.target.value)}
                            />
                        </div>
                    </div>

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
