import type { ImportRowStatus } from '@/Types/import';

const LABELS: Record<ImportRowStatus, string> = {
    NOUVEAU: 'Nouveau',
    DOUBLON: 'Doublon',
    ERREUR: 'Erreur',
    CONFLIT: 'Conflit',
    INSERE: 'Inséré',
    IGNORE: 'Ignoré',
    ECHEC_COMMIT: 'Échec',
};

const VARIANTS: Record<ImportRowStatus, 'success' | 'secondary' | 'danger' | 'warning' | 'info'> = {
    NOUVEAU: 'success',
    DOUBLON: 'secondary',
    ERREUR: 'danger',
    CONFLIT: 'warning',
    INSERE: 'success',
    IGNORE: 'secondary',
    ECHEC_COMMIT: 'danger',
};

export default function ImportRowStatusBadge({ status }: { status: ImportRowStatus }) {
    return <span className={`badge badge-soft-${VARIANTS[status]}`}>{LABELS[status]}</span>;
}
