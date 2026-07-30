interface FormErrorProps {
    message?: string;
}

/** Matches the existing `@error` `.text-danger.pt-2`/`.invalid-feedback` convention used across Blade auth/profile forms. */
export default function FormError({ message }: FormErrorProps) {
    if (!message) {
        return null;
    }

    return (
        <div className="text-danger pt-2" role="alert">
            {message}
        </div>
    );
}
