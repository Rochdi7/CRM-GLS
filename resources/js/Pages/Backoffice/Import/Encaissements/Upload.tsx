import { useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import SelectField from '@/Components/Forms/SelectField';
import type { ImportAnneeScolaireOption, ImportEtablissementOption } from '@/Types/import';

interface EncaissementImportUploadProps {
    etablissements: ImportEtablissementOption[];
    anneesScolaires: ImportAnneeScolaireOption[];
}

interface Readiness {
    students: number;
    withInscription: number;
    missing: number;
}

interface EmployeeOption {
    id: number;
    nom: string;
    prenom: string;
}

interface OperateurMappingEntry {
    label: string;
    employee_id: string;
}

interface UploadFormState {
    file: File | null;
    etablissement_id: string;
    annee_scolaire_id: string;
    operateur_mapping: OperateurMappingEntry[];
    include_inactive_inscriptions: boolean;
}

/**
 * Two steps: (1) pick Centre + Année + file, peek the file's distinct
 * "Opérateur" labels; (2) map every label to an existing employee (no
 * "create employee" option, out of scope) before Analyze is allowed. No
 * Caisse dropdown — the caisse is always derived from the mapped employee's
 * own till (import plan's Caisse-dropdown correction).
 */
export default function EncaissementImportUpload({ etablissements, anneesScolaires }: EncaissementImportUploadProps) {
    const defaultAnnee = anneesScolaires.find((a) => a.par_defaut) ?? anneesScolaires[0];
    const [step, setStep] = useState<'scope' | 'mapping'>('scope');
    const [employees, setEmployees] = useState<EmployeeOption[]>([]);
    const [readiness, setReadiness] = useState<Readiness | null>(null);
    const [peeking, setPeeking] = useState(false);
    const [peekError, setPeekError] = useState<string | null>(null);

    const form = useForm<UploadFormState>({
        file: null,
        etablissement_id: '',
        annee_scolaire_id: defaultAnnee ? String(defaultAnnee.id) : '',
        operateur_mapping: [],
        // Off by default: money only attaches to a live enrolment unless
        // the operator deliberately says otherwise.
        include_inactive_inscriptions: false,
    });

    async function handlePeekSubmit(event: FormEvent) {
        event.preventDefault();
        setPeekError(null);
        setPeeking(true);

        const formData = new FormData();
        if (form.data.file) formData.append('file', form.data.file);
        formData.append('etablissement_id', form.data.etablissement_id);
        formData.append('annee_scolaire_id', form.data.annee_scolaire_id);

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const response = await fetch('/backoffice/import/encaissements/peek-operateurs', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: formData,
            });

            if (!response.ok) {
                setPeekError('Impossible de lire le fichier.');
                return;
            }

            const json = (await response.json()) as {
                operateurLabels: string[];
                employees: EmployeeOption[];
                readiness: Readiness;
            };

            setEmployees(json.employees);
            setReadiness(json.readiness);
            form.setData(
                'operateur_mapping',
                json.operateurLabels.map((label) => ({ label, employee_id: '' }))
            );
            setStep('mapping');
        } finally {
            setPeeking(false);
        }
    }

    function updateMapping(index: number, employeeId: string) {
        form.setData(
            'operateur_mapping',
            form.data.operateur_mapping.map((entry, i) => (i === index ? { ...entry, employee_id: employeeId } : entry))
        );
    }

    function submitAnalyze(event: FormEvent) {
        event.preventDefault();
        form.post('/backoffice/import/encaissements/analyze', { forceFormData: true });
    }

    const canPeek = form.data.file !== null && form.data.etablissement_id !== '' && form.data.annee_scolaire_id !== '';
    const canAnalyze = form.data.operateur_mapping.every((e) => e.employee_id !== '');

    return (
        <BackofficeLayout
            title="Import Encaissements"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Import de données', href: '/backoffice/import' },
                { label: 'Encaissements' },
            ]}
        >
            {step === 'scope' && (
                <Card title="Importer des encaissements depuis l'ancien CRM">
                    <form onSubmit={handlePeekSubmit}>
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
                                id="import-file"
                                type="file"
                                accept=".xlsx"
                                className={`form-control${form.errors.file ? ' is-invalid' : ''}`}
                                onChange={(e) => form.setData('file', e.target.files?.[0] ?? null)}
                            />
                            {form.errors.file && <div className="invalid-feedback">{form.errors.file}</div>}
                        </div>

                        {peekError && <div className="alert alert-danger">{peekError}</div>}

                        <button type="submit" className="btn btn-primary" disabled={!canPeek || peeking}>
                            {peeking ? 'Lecture du fichier…' : 'Continuer'}
                        </button>
                    </form>
                </Card>
            )}

            {step === 'mapping' && (
                <Card title="Associer les opérateurs">
                    {readiness !== null && readiness.missing > 0 && (
                        <div className="alert alert-warning">
                            <strong>{readiness.missing} étudiant(s) sans inscription active</strong> pour ce centre et
                            cette année scolaire ({readiness.withInscription} sur {readiness.students} en ont une).
                            <br />
                            Un paiement doit être rattaché à une ligne de frais d'une inscription active : les
                            paiements de ces étudiants resteront en conflit.{' '}
                            <strong>Importez d'abord les inscriptions</strong> pour éviter cela.
                        </div>
                    )}
                    <form onSubmit={submitAnalyze}>
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Opérateur (fichier)</th>
                                    <th>Employé</th>
                                </tr>
                            </thead>
                            <tbody>
                                {form.data.operateur_mapping.map((entry, index) => (
                                    <tr key={entry.label}>
                                        <td>{entry.label}</td>
                                        <td>
                                            <select
                                                className="form-select"
                                                value={entry.employee_id}
                                                onChange={(e) => updateMapping(index, e.target.value)}
                                            >
                                                <option value="">Choisir…</option>
                                                {employees.map((emp) => (
                                                    <option key={emp.id} value={emp.id}>
                                                        {emp.prenom} {emp.nom}
                                                    </option>
                                                ))}
                                            </select>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div className="form-check mb-3">
                            <input
                                className="form-check-input"
                                type="checkbox"
                                id="include_inactive_inscriptions"
                                title="Accepter les inscriptions annulées / changement"
                                checked={form.data.include_inactive_inscriptions}
                                onChange={(e) => form.setData('include_inactive_inscriptions', e.target.checked)}
                            />
                            <label
                                className="form-check-label visually-hidden"
                                htmlFor="include_inactive_inscriptions"
                                title="Accepter les inscriptions annulées / changement"
                            >
                                Accepter les inscriptions annulées / changement
                            </label>
                        </div>

                        <button type="button" className="btn btn-outline-secondary me-2" onClick={() => setStep('scope')}>
                            Retour
                        </button>
                        <button type="submit" className="btn btn-primary" disabled={!canAnalyze || form.processing}>
                            {form.processing ? 'Analyse en cours…' : 'Analyser'}
                        </button>
                    </form>
                </Card>
            )}
        </BackofficeLayout>
    );
}
