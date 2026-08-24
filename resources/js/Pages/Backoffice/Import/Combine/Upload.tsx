import { useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import ImportScopeFields from '@/Components/Import/ImportScopeFields';
import type { ImportEtablissementOption } from '@/Types/import';

interface CombinedImportUploadProps {
    etablissements: ImportEtablissementOption[];
    centerLocked: boolean;
}

interface ExistingGroup {
    id: number;
    nom: string;
    /** The année scolaire the group currently belongs to. */
    anneeNom: string | null;
    /** True when that année differs from the import's selected one — mapping it re-affects the group (server-side). */
    horsAnnee: boolean;
}

interface GroupeMappingEntry {
    label: string;
    action: 'map' | 'create';
    group_id: string;
    nom: string;
    niveau: string;
}

interface UploadFormState {
    students_file: File | null;
    /** One inscriptions export per checked statut — the old CRM ships them as separate files. */
    inscriptions_files: Record<string, File | null>;
    etablissement_id: string;
    statuts: string[];
    groupe_mapping: GroupeMappingEntry[];
}

/** Post-translation statut values (the file's "Archivée" imports as "Changement"). */
const STATUT_OPTIONS = [
    { value: 'Active', label: 'Active', fileLabel: 'Fichier Inscriptions — Actives (.xlsx)' },
    { value: 'Annulée', label: 'Annulée', fileLabel: 'Fichier Inscriptions — Annulées (.xlsx)' },
    {
        value: 'Changement',
        label: 'Changement (« Archivée » dans l’ancien CRM)',
        fileLabel: 'Fichier Inscriptions — Archivées (.xlsx)',
    },
] as const;

/** Case/accent/space-insensitive key so "Herr  Driss 13h" matches "Herr Driss 13h". */
function groupKey(name: string): string {
    return name
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();
}

/** Same auto-matching as the standalone Inscriptions import (same-year group wins, lone other-year match is re-affected server-side). */
function buildMapping(labels: string[], groups: ExistingGroup[], niveaux: string[] = []): GroupeMappingEntry[] {
    const byKey = new Map<string, ExistingGroup[]>();

    groups.forEach((group) => {
        const key = groupKey(group.nom);
        byKey.set(key, [...(byKey.get(key) ?? []), group]);
    });

    return labels.map((label) => {
        const matches = byKey.get(groupKey(label)) ?? [];
        const sameYear = matches.filter((g) => !g.horsAnnee);
        const unique =
            sameYear.length === 1 ? sameYear[0] : sameYear.length === 0 && matches.length === 1 ? matches[0] : null;

        // No existing group to attach to → default to "Créer le groupe" with
        // the niveau guessed from the label, so the common case (a brand-new
        // group per file label) needs no clicks. A unique match still maps.
        return {
            label,
            action: unique ? 'map' : 'create',
            group_id: unique ? String(unique.id) : '',
            nom: label,
            niveau: unique ? '' : guessNiveau(label, niveaux),
        };
    });
}

/**
 * Pre-fill the niveau of a group to create from its label: an exact CEFR
 * code in the label wins ("Groupe A1.2 soir" → A1.2), otherwise a bare
 * level ("A1", "B2") picks the first sub-level of that band (A1 → A1.1).
 */
function guessNiveau(label: string, niveaux: string[]): string {
    const upper = label.toUpperCase();
    const exact = [...niveaux].sort((a, b) => b.length - a.length).find((n) => upper.includes(n.toUpperCase()));
    if (exact) {
        return exact;
    }
    const band = upper.match(/([ABC][12])/)?.[1];
    if (band) {
        return niveaux.find((n) => n.toUpperCase().startsWith(band)) ?? '';
    }

    return '';
}

/** Option label: the year tag marks a group that will be re-affected to the selected année if mapped. */
function groupOptionLabel(group: ExistingGroup, ambiguous: boolean): string {
    const annee = group.horsAnnee && group.anneeNom ? ` — ${group.anneeNom}` : '';

    return ambiguous ? `${group.nom}${annee} (#${group.id})` : `${group.nom}${annee}`;
}

/**
 * Combined Étudiants + Inscriptions import: BOTH legacy files in one flow.
 * Step 1: scope (from the top-bar context) + the two files + statut filter;
 * the group-mapping peek runs on the inscriptions file via the standalone
 * endpoint. Step 2: map the groups, then Analyze — the server imports the
 * students first (auto-commit of every clean row), analyzes the
 * inscriptions against them, and lands on the standard inscriptions
 * preview.
 */
export default function CombinedImportUpload({ etablissements, centerLocked }: CombinedImportUploadProps) {
    const [step, setStep] = useState<'scope' | 'mapping'>('scope');
    const [existingGroups, setExistingGroups] = useState<ExistingGroup[]>([]);
    const [niveaux, setNiveaux] = useState<string[]>([]);
    const [peeking, setPeeking] = useState(false);
    const [peekError, setPeekError] = useState<string | null>(null);

    const form = useForm<UploadFormState>({
        students_file: null,
        inscriptions_files: {},
        etablissement_id: '',
        statuts: STATUT_OPTIONS.map((s) => s.value),
        groupe_mapping: [],
    });

    function toggleStatut(value: string) {
        const removing = form.data.statuts.includes(value);

        form.setData({
            ...form.data,
            statuts: removing ? form.data.statuts.filter((s) => s !== value) : [...form.data.statuts, value],
            // Unchecking a statut also drops its file, so nothing hidden is posted.
            inscriptions_files: removing
                ? Object.fromEntries(Object.entries(form.data.inscriptions_files).filter(([k]) => k !== value))
                : form.data.inscriptions_files,
        });
    }

    function setInscriptionsFile(statut: string, file: File | null) {
        form.setData('inscriptions_files', { ...form.data.inscriptions_files, [statut]: file });
    }

    /** The files actually selected for the checked statuts, in option order. */
    function selectedFiles(): File[] {
        return STATUT_OPTIONS.filter((o) => form.data.statuts.includes(o.value))
            .map((o) => form.data.inscriptions_files[o.value])
            .filter((f): f is File => f instanceof File);
    }

    // Peek reads each INSCRIPTIONS file's distinct "Groupe" labels through the
    // standalone inscriptions peek endpoint (plain fetch, not an Inertia
    // visit), one call per file, and merges the labels — the existing-groups
    // list is the same for every call.
    async function handlePeekSubmit(event: FormEvent) {
        event.preventDefault();
        setPeekError(null);
        setPeeking(true);

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const labels = new Set<string>();
            let groups: ExistingGroup[] = [];
            let niveauxList: string[] = [];

            for (const file of selectedFiles()) {
                const formData = new FormData();
                formData.append('file', file);
                if (form.data.etablissement_id !== '') formData.append('etablissement_id', form.data.etablissement_id);

                const response = await fetch('/backoffice/import/inscriptions/peek-groupes', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                    body: formData,
                });

                if (!response.ok) {
                    setPeekError(`Impossible de lire le fichier d'inscriptions « ${file.name} ».`);
                    return;
                }

                const json = (await response.json()) as {
                    groupeLabels: string[];
                    existingGroups: ExistingGroup[];
                    niveaux: string[];
                };

                json.groupeLabels.forEach((label) => labels.add(label));
                groups = json.existingGroups;
                niveauxList = json.niveaux;
            }

            setExistingGroups(groups);
            setNiveaux(niveauxList);
            form.setData('groupe_mapping', buildMapping([...labels], groups, niveauxList));
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
        form.post('/backoffice/import/combine/analyze', { forceFormData: true });
    }

    const duplicateNames = new Set(
        existingGroups.map((g) => groupKey(g.nom)).filter((key, index, all) => all.indexOf(key) !== index)
    );

    const checkedOptions = STATUT_OPTIONS.filter((o) => form.data.statuts.includes(o.value));
    const canPeek =
        form.data.students_file !== null &&
        (centerLocked || form.data.etablissement_id !== '') &&
        checkedOptions.length > 0 &&
        checkedOptions.every((o) => form.data.inscriptions_files[o.value] instanceof File);
    const canAnalyze = form.data.groupe_mapping.every((e) =>
        e.action === 'map' ? e.group_id !== '' : e.nom !== '' && e.niveau !== ''
    );

    return (
        <BackofficeLayout
            title="Import Étudiants + Inscriptions"
            breadcrumbs={[
                { label: 'Tableau de bord', href: '/backoffice/dashboard' },
                { label: 'Import de données', href: '/backoffice/import' },
                { label: 'Étudiants + Inscriptions' },
            ]}
        >
            {step === 'scope' && (
                <Card title="Importer étudiants et inscriptions en une seule fois">
                    <div className="alert alert-info">
                        Les <strong>étudiants sont importés d&apos;abord</strong> (les lignes propres sont insérées
                        automatiquement), puis les <strong>inscriptions sont résolues contre eux</strong> dans la même
                        opération — plus de conflits « étudiant introuvable » entre deux imports séparés.
                    </div>
                    <form onSubmit={handlePeekSubmit}>
                        <ImportScopeFields
                            etablissements={etablissements}
                            centerLocked={centerLocked}
                            etablissementId={form.data.etablissement_id}
                            error={form.errors.etablissement_id}
                            onChange={(value) => form.setData('etablissement_id', value)}
                        />

                        <div className="mb-3">
                            <label className="form-label" htmlFor="students-file">
                                Fichier Étudiants (.xlsx)
                                <span className="text-danger ms-1">*</span>
                            </label>
                            <input
                                id="students-file"
                                type="file"
                                accept=".xlsx"
                                className={`form-control${form.errors.students_file ? ' is-invalid' : ''}`}
                                onChange={(e) => form.setData('students_file', e.target.files?.[0] ?? null)}
                            />
                            {form.errors.students_file && (
                                <div className="invalid-feedback">{form.errors.students_file}</div>
                            )}
                        </div>

                        <div className="mb-3">
                            <label className="form-label d-block">
                                Statuts d&apos;inscription à importer dans cette année
                                <span className="text-danger ms-1">*</span>
                            </label>
                            {STATUT_OPTIONS.map((option) => (
                                <div className="form-check form-check-inline" key={option.value}>
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id={`statut-${option.value}`}
                                        checked={form.data.statuts.includes(option.value)}
                                        onChange={() => toggleStatut(option.value)}
                                    />
                                    <label className="form-check-label" htmlFor={`statut-${option.value}`}>
                                        {option.label}
                                    </label>
                                </div>
                            ))}
                            <small className="text-muted d-block mt-1">
                                L&apos;ancien CRM exporte une liste par statut : un fichier est demandé pour chaque
                                statut coché, et tous sont analysés ensemble dans un seul lot. Historique de
                                l&apos;ancienne année : cochez Annulée + Changement avec la barre supérieure sur
                                l&apos;année passée. Données courantes : cochez Active avec la barre sur l&apos;année en
                                cours. Les lignes d&apos;un autre statut sont comptées comme ignorées, jamais perdues.
                            </small>
                        </div>

                        <div className="row">
                            {checkedOptions.map((option) => (
                                <div className="col-md-4" key={option.value}>
                                    <div className="mb-3">
                                        <label className="form-label" htmlFor={`inscriptions-file-${option.value}`}>
                                            {option.fileLabel}
                                            <span className="text-danger ms-1">*</span>
                                        </label>
                                        <input
                                            id={`inscriptions-file-${option.value}`}
                                            type="file"
                                            accept=".xlsx"
                                            className={`form-control${
                                                form.errors[`inscriptions_files.${option.value}` as keyof typeof form.errors]
                                                    ? ' is-invalid'
                                                    : ''
                                            }`}
                                            onChange={(e) =>
                                                setInscriptionsFile(option.value, e.target.files?.[0] ?? null)
                                            }
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                        {form.errors.inscriptions_files && (
                            <div className="alert alert-danger">{form.errors.inscriptions_files}</div>
                        )}

                        {peekError && <div className="alert alert-danger">{peekError}</div>}

                        <button type="submit" className="btn btn-primary" disabled={!canPeek || peeking}>
                            {peeking ? 'Lecture du fichier…' : 'Continuer'}
                        </button>
                    </form>
                </Card>
            )}

            {step === 'mapping' && (
                <Card title="Associer les groupes">
                    <div className="alert alert-info">
                        Associer un groupe marqué d&apos;une autre année (ex. « — 2026/2027 ») le
                        <strong> réaffecte automatiquement à l&apos;année sélectionnée</strong>, avec ses inscriptions
                        et séances — rien ne reste réparti sur deux années.
                    </div>
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
                                                                {groupOptionLabel(g, duplicateNames.has(groupKey(g.nom)))}
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
                            {form.processing ? 'Import des étudiants et analyse…' : 'Importer étudiants + analyser inscriptions'}
                        </button>
                    </form>
                </Card>
            )}
        </BackofficeLayout>
    );
}
