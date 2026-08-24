import { useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import ImportScopeFields from '@/Components/Import/ImportScopeFields';
import type { ImportEtablissementOption } from '@/Types/import';

interface PresenceImportUploadProps {
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
    file: File | null;
    etablissement_id: string;
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
function buildMapping(labels: string[], groups: ExistingGroup[], niveaux: string[] = []): GroupeMappingEntry[] {
    const byKey = new Map<string, ExistingGroup[]>();

    groups.forEach((group) => {
        const key = groupKey(group.nom);
        byKey.set(key, [...(byKey.get(key) ?? []), group]);
    });

    return labels.map((label) => {
        const matches = byKey.get(groupKey(label)) ?? [];
        // A same-year group wins; a lone other-year match is still selected —
        // the server then re-affects it to the selected année (see the hint).
        const sameYear = matches.filter((g) => !g.horsAnnee);
        const unique =
            sameYear.length === 1 ? sameYear[0] : sameYear.length === 0 && matches.length === 1 ? matches[0] : null;

        // No existing group → default to "Créer le groupe" with a pre-filled
        // niveau, so a bulk import needs no clicks (same as the other imports).
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
 * code in the label wins ("Groupe A1.2 soir" → A1.2), a bare band ("B2")
 * picks its first sub-level, and with no level at all the first niveau is
 * used so the field never blocks the import — corrected on the group later.
 */
function guessNiveau(label: string, niveaux: string[]): string {
    const upper = label.toUpperCase();
    const exact = [...niveaux].sort((a, b) => b.length - a.length).find((n) => upper.includes(n.toUpperCase()));
    if (exact) {
        return exact;
    }
    const band = upper.match(/([ABC][12])/)?.[1];
    if (band) {
        return niveaux.find((n) => n.toUpperCase().startsWith(band)) ?? niveaux[0] ?? '';
    }

    return niveaux[0] ?? '';
}

/** Option label: the year tag marks a group that will be re-affected to the selected année if mapped. */
function groupOptionLabel(group: ExistingGroup, ambiguous: boolean): string {
    const annee = group.horsAnnee && group.anneeNom ? ` — ${group.anneeNom}` : '';

    return ambiguous ? `${group.nom}${annee} (#${group.id})` : `${group.nom}${annee}`;
}

/**
 * Same two steps as the Inscriptions import: (1) confirm the active
 * context's Centre + Année (from the top-bar switcher — see
 * ImportScopeFields) + pick the file, peek the file's distinct "Groupe"
 * labels scoped to that centre/année; (2) map every label to an existing
 * group or "créer le groupe" before Analyze is allowed — the import plan's
 * mandatory scoping + no-cross-centre-group-mapping rule.
 *
 * The séances are NOT mapped here: they are derived from the file's
 * (groupe, date, horaire) triples at analyze/commit time, and an existing
 * séance is reused rather than duplicated.
 */
export default function PresenceImportUpload({ etablissements, centerLocked }: PresenceImportUploadProps) {
    const [step, setStep] = useState<'scope' | 'mapping'>('scope');
    const [existingGroups, setExistingGroups] = useState<ExistingGroup[]>([]);
    const [niveaux, setNiveaux] = useState<string[]>([]);
    const [peeking, setPeeking] = useState(false);
    const [peekError, setPeekError] = useState<string | null>(null);

    const form = useForm<UploadFormState>({
        file: null,
        etablissement_id: '',
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
        if (form.data.etablissement_id !== '') formData.append('etablissement_id', form.data.etablissement_id);

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
            form.setData('groupe_mapping', buildMapping(json.groupeLabels, json.existingGroups, json.niveaux));
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

    const canPeek = form.data.file !== null && (centerLocked || form.data.etablissement_id !== '');
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
                            {form.processing ? 'Analyse en cours…' : 'Analyser'}
                        </button>
                    </form>
                </Card>
            )}
        </BackofficeLayout>
    );
}
