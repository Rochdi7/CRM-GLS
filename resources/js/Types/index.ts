export interface AuthUser {
    id: number;
    name: string;
    email: string | null;
}

export interface Context {
    anneeScolaireId: number | null;
    etablissementId: number | null;
    isAllCenters: boolean;
    canSwitchCenter: boolean;
}

export interface SharedProps {
    auth: {
        user: AuthUser | null;
        permissions: string[];
    };
    context: Context | null;
    flash: {
        success: string | null;
        error: string | null;
    };
    locale: string;
    [key: string]: unknown;
}
