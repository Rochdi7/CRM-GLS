import { useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import SelectField from '@/Components/Forms/SelectField';
import type { ImportAnneeScolaireOption, ImportEtablissementOption } from '@/Types/import';

interface PresenceImportUploadProps {
    etablissements: ImportEtablissementOption[];
    anneesScolaires: ImportAnneeScolaireOption[];
}

interface ExistingGroup {
    id: number;
    nom: string;
}

interface GroupeMappingEntry {
    label: string;
    action: 'map' | 'create';
    group_id: string;
    nom: string;
    niveau: string;
}

interface UploadFormState {
    file: File | null;
    etablissement_id: string;
    annee_scolaire_id: string;
    groupe_mapping: GroupeMappingEntry[];
}

/** Case/accent/space-insensitive key so "Herr  Driss 13h" matches "Herr Driss 13h". */
function groupKey(name: string): string {
    return name
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();
}

/**
 * Pre-selects the existing group whose name matches the file's label, so an
 * already-imported group never has to be picked by hand (and is never
 * accidentally re-created as a duplicate).
 *
 * A label matching SEVERAL existing groups is deliberately left unselected:
 * the centre genuinely has duplicates with that name, and guessing which one
 * the enrolments belong to would silently attach students to the wrong group.
 */
function buildMapping(labels: string[], groups: ExistingGroup[]): GroupeMappingEntry[] {
    const byKey = new Map<string, ExistingGroup[]>();

    groups.forEach((group) => {
        const key = groupKey(group.nom);
        byKey.set(key, [...(byKey.get(key) ?? []), group]);
    });

    return labels.map((label) => {
        const matches = byKey.get(groupKey(label)) ?? [];
        const unique = matches.length === 1 ? matches[0] : null;

        return {
            label,
            action: 'map',
            group_id: unique ? String(unique.id) : '',
            nom: label,
            niveau: '',
        };
    });
}

/**
 * Same two steps as the Inscriptions import: (1) pick Centre + Année +
 * file, peek the file's distinct "Groupe" labels scoped to that
 * centre/année; (2) map every label to an existing group or "créer le
 * groupe" before Analyze is allowed — the import plan's mandatory scoping +
 * no-cross-centre-group-mapping rule.
 *
 * The séances are NOT mapped here: they are derived from the file's
 * (groupe, date, horaire) triples at analyze/commit time, and an existing
 * séance is reused rather than duplicated.
 */
export default function PresenceImportUpload({ etablissements, anneesScolaires }: PresenceImportUploadProps) {
    const defaultAnnee = anneesScolaires.find((a) => a.par_defaut) ?? anneesScolaires[0];
    const [step, setStep] = useState<'scope' | 'mapping'>('scope');
    const [existingGroups, setExistingGroups] = useState<ExistingGroup[]>([]);
    const [niveaux, setNiveaux] = useState<string[]>([]);
    const [peeking, setPeeking] = useState(false);
    const [peekError, setPeekError] = useState<string | null>(null);

    const form = useForm<UploadFormState>({
        file: null,
        etablissement_id: '',
        annee_scolaire_id: defaultAnnee ? String(defaultAnnee.id) : '',
        groupe_mapping: [],
    });

    // This is a synchronous "peek" call whose response is read directly
    // (existing groups + distinct labels), not an Inertia page visit — a
    // plain fetch is simpler here than router.post, which expects a redirect.
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
            const response = await fetch('/backoffice/import/presences/peek-groupes', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: formData,
            });

            if (!response.ok) {
                setPeekError('Impossible de lire le fichier.');
                return;
            }

            const json = (await response.json()) as {
                groupeLabels: string[];
                existingGroups: ExistingGroup[];
                niveaux: string[];
            };

            setExistingGroups(json.existingGroups);
            setNiveaux(json.niveaux);
            form.setData('groupe_mapping', buildMapping(json.groupeLabels, json.existingGroups));
            setStep('mapping');
        } finally {
            setPeeking(false);
        }
    }

    function updateMapping(index: number, patch: Partial<GroupeMappingEntry>) {
        form.setData(
            'groupe_mapping',
            form.data.groupe_mapping.map((entry, i) => (i === index ? { ...entry, ...patch } : entry))
        );
    }

    function submitAnalyze(event: FormEvent) {
        event.preventDefault();
        form.post('/backoffice/import/presences/analyze', { forceFormData: true });
    }

    // Group names are not unique in practice (two "Herr Driss 13h" exist), so
    // ambiguous ones are labelled with their id and never auto-selected.
    const duplicateNames = new Set(
        existingGroups
            .map((g) => groupKey(g.nom))
            .filter((key, index, all) => all.indexOf(key) !== index)
    );

    const canPeek = form.data.file !== null && form.data.etablissement_id !== '' && form.data.annee_scolaire_id !== '';
    const canAnalyze = form.data.groupe_mapping.every((e) =>
        e.action === 'map' ? e.group_id !== '' : e.nom !== '' && e.niveau !== ''
    );

    return (
        <BackofficeLayout
            title="Import Présences & séances"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Import de données', href: '/backoffice/import' },
                { label: 'Présences & séances' },
            ]}
        >
            {step === 'scope' && (
                <Card title="Importer un registre des présences">
                    <div className="alert alert-info">
                        Importer les <strong>étudiants</strong> et les <strong>inscriptions</strong> avant ce module :
                        l'appel est rattaché à des élèves et à des groupes qui doivent déjà exister. Les séances
                        manquantes sont créées automatiquement à partir des colonnes Groupe, Date et Horaire ; une
                        séance déjà enregistrée est réutilisée, jamais dupliquée.
                    </div>
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
                <Card title="Associer les groupes">
                    <form onSubmit={submitAnalyze}>
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Groupe (fichier)</th>
                                    <th>Action</th>
                                    <th>Groupe existant</th>
                                    <th>Nom</th>
                                    <th>Niveau</th>
                                </tr>
                            </thead>
                            <tbody>
                                {form.data.groupe_mapping.map((entry, index) => (
                                    <tr key={entry.label}>
                                        <td>{entry.label}</td>
                                        <td>
                                            <select
                                                className="form-select"
                                                value={entry.action}
                                                onChange={(e) =>
                                                    updateMapping(index, { action: e.target.value as 'map' | 'create' })
                                                }
                                            >
                                                <option value="map">Associer à un groupe existant</option>
                                                <option value="create">Créer le groupe</option>
                                            </select>
                                        </td>
                                        <td>
                                            {entry.action === 'map' && (
                                                <>
                                                    <select
                                                        className="form-select"
                                                        value={entry.group_id}
                                                        onChange={(e) => updateMapping(index, { group_id: e.target.value })}
                                                    >
                                                        <option value="">Choisir…</option>
                                                        {existingGroups.map((g) => (
                                                            <option key={g.id} value={g.id}>
                                                                {duplicateNames.has(groupKey(g.nom))
                                                                    ? `${g.nom} (#${g.id})`
                                                                    : g.nom}
                                                            </option>
                                                        ))}
                                                    </select>
                                                    {entry.group_id === '' && duplicateNames.has(groupKey(entry.label)) && (
                                                        <small className="text-warning d-block mt-1">
                                                            Plusieurs groupes portent ce nom — choisir lequel.
                                                        </small>
                                                    )}
                                                </>
                                            )}
                                        </td>
                                        <td>
                                            {entry.action === 'create' && (
                                                <input
                                                    type="text"
                                                    className="form-control"
                                                    value={entry.nom}
                                                    onChange={(e) => updateMapping(index, { nom: e.target.value })}
                                                />
                                            )}
                                        </td>
                                        <td>
                                            {entry.action === 'create' && (
                                                <select
                                                    className="form-select"
                                                    value={entry.niveau}
                                                    onChange={(e) => updateMapping(index, { niveau: e.target.value })}
                                                >
                                                    <option value="">Choisir…</option>
                                                    {niveaux.map((n) => (
                                                        <option key={n} value={n}>
                                                            {n}
                                                        </option>
                                                    ))}
                                                </select>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

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
