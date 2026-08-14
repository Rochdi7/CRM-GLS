import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import FormField from '@/Components/Forms/FormField';
import PasswordField from '@/Components/Forms/PasswordField';
import SubmitButton from '@/Components/Forms/SubmitButton';

interface ResetPasswordProps {
    token: string;
    email: string;
}

interface ResetPasswordForm {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
}

/**
 * Matches resources/views/backoffice/auth/reset-password.blade.php exactly.
 * ResetPasswordController::store() is unchanged — Laravel's password broker
 * still owns token validation, password confirmation, and the
 * must_change_password/remember_token reset side effects.
 */
export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors } = useForm<ResetPasswordForm>({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        post('/backoffice/reset-password');
    }

    return (
        <GuestLayout title="Réinitialiser le mot de passe">
            <div className="mb-4">
                <h2 className="mb-2">Réinitialiser le mot de passe</h2>
                <p className="mb-0">Choisissez un nouveau mot de passe pour votre compte.</p>
            </div>

            <form onSubmit={submit}>
                <input type="hidden" name="token" value={data.token} />

                <FormField
                    id="email"
                    label="Adresse email"
                    type="email"
                    icon="ti ti-mail"
                    value={data.email}
                    onChange={(event) => setData('email', event.target.value)}
                    error={errors.email}
                    required
                    autoComplete="username"
                />

                <PasswordField
                    id="password"
                    label="Nouveau mot de passe"
                    value={data.password}
                    onChange={(event) => setData('password', event.target.value)}
                    error={errors.password}
                    required
                    autoComplete="new-password"
                />

                <PasswordField
                    id="password_confirmation"
                    label="Confirmer le mot de passe"
                    value={data.password_confirmation}
                    onChange={(event) => setData('password_confirmation', event.target.value)}
                    error={errors.password_confirmation}
                    required
                    autoComplete="new-password"
                />

                <div className="mb-3">
                    <SubmitButton processing={processing} processingLabel="Réinitialisation…">
                        Réinitialiser le mot de passe
                    </SubmitButton>
                </div>

                <div className="text-center">
                    <h6 className="fw-normal text-dark mb-0">
                        <a href="/backoffice/login" className="hover-a">
                            <i className="ti ti-arrow-left me-1" />
                            Retour à la connexion
                        </a>
                    </h6>
                </div>
            </form>
        </GuestLayout>
    );
}
