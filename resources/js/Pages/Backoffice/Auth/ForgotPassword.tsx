import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import FormField from '@/Components/Forms/FormField';
import SubmitButton from '@/Components/Forms/SubmitButton';
import AuthStatus from '@/Components/Feedback/AuthStatus';
import type { SharedProps } from '@/Types';

interface ForgotPasswordForm {
    email: string;
}

/**
 * Matches resources/views/backoffice/auth/forgot-password.blade.php exactly.
 * ForgotPasswordController::store() is unchanged — the same generic status
 * message reaches this page whether or not the address exists (no account
 * enumeration), via the shared `flash.status` prop.
 */
export default function ForgotPassword() {
    const { flash } = usePage<SharedProps>().props;
    const { data, setData, post, processing, errors } = useForm<ForgotPasswordForm>({
        email: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        post('/backoffice/forgot-password');
    }

    return (
        <GuestLayout title="Mot de passe oublié">
            <div className="mb-4">
                <h2 className="mb-2">Mot de passe oublié ?</h2>
                <p className="mb-0">Saisissez votre email et nous vous enverrons un lien de réinitialisation.</p>
            </div>

            <AuthStatus status={flash.status} />

            <form onSubmit={submit}>
                <FormField
                    id="email"
                    label="Adresse email"
                    type="email"
                    icon="fa fa-envelope"
                    value={data.email}
                    onChange={(event) => setData('email', event.target.value)}
                    error={errors.email}
                    required
                    autoFocus
                    autoComplete="username"
                />

                <div className="mb-3">
                    <SubmitButton processing={processing} processingLabel="Envoi…">
                        Envoyer le lien
                    </SubmitButton>
                </div>

                <div className="text-center">
                    <h6 className="fw-normal text-dark mb-0">
                        <a href="/backoffice/login" className="hover-a">
                            <i className="fa fa-arrow-left me-1" />
                            Retour à la connexion
                        </a>
                    </h6>
                </div>
            </form>
        </GuestLayout>
    );
}
