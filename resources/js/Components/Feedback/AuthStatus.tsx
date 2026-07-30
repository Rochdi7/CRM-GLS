interface AuthStatusProps {
    status?: string | null;
}

/** Matches the `@if (session('status'))` alert used by the existing auth Blade pages. */
export default function AuthStatus({ status }: AuthStatusProps) {
    if (!status) {
        return null;
    }

    return (
        <div className="alert alert-success" role="alert">
            {status}
        </div>
    );
}
