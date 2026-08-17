export type ImportRowStatus = 'NOUVEAU' | 'DOUBLON' | 'ERREUR' | 'CONFLIT' | 'INSERE' | 'IGNORE' | 'ECHEC_COMMIT';

export interface ImportRowError {
    field: string | null;
    code: string;
    message: string;
}

export interface ImportRow {
    id: number;
    import_batch_id: number;
    source_row_number: number;
    raw: Record<string, unknown>;
    status: ImportRowStatus;
    errors: ImportRowError[] | null;
    resolution: Record<string, unknown> | null;
    legacy_ref: string | null;
    created_model_type: string | null;
    created_model_id: number | null;
    selected: boolean;
}

export interface ImportBatch {
    id: number;
    module: 'students' | 'inscriptions' | 'encaissements';
    original_filename: string;
    etablissement_id: number;
    annee_scolaire_id: number;
    status: 'analyzed' | 'committing' | 'committed' | 'committed_with_errors';
    total_rows: number;
    inserted_rows: number;
    skipped_rows: number;
    error_rows: number;
    etablissement?: { id: number; nom_centre: string };
    annee_scolaire?: { id: number; nom: string };
}

export interface ImportEtablissementOption {
    id: number;
    nom_centre: string;
}

export interface ImportAnneeScolaireOption {
    id: number;
    nom: string;
    par_defaut: boolean;
}
