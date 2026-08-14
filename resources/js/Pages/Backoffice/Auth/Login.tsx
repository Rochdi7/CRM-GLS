import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import GuestLayout from '@/Layouts/GuestLayout';
import FormField from '@/Components/Forms/FormField';
import PasswordField from '@/Components/Forms/PasswordField';
import SubmitButton from '@/Components/Forms/SubmitButton';
import AuthStatus from '@/Components/Feedback/AuthStatus';
import type { SharedProps } from '@/Types';

interface LoginForm {
    login: string;
    password: string;
    remember: boolean;
}

/**
 * Markup/behavior matches resources/views/backoffice/auth/login.blade.php
 * exactly (centered single-column card, GLS logo, remember-me checkbox,
 * forgot-password link). Server-side login logic is untouched —
 * LoginController::store() + LoginRequest::authenticate() still own
 * every rule (email-or-username, rate limiting, is_active gate).
 */
export default function Login() {
    const { flash } = usePage<SharedProps>().props;
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        login: '',
        password: '',
        remember: false,
    });

    function submit(event: FormEvent) {
        event.preventDefault();

        post('/backoffice/login', {
            onError: () => reset('password'),
        });
    }

    return (
        <GuestLayout title="Connexion">
            <div className="mb-4">
                <h2 className="mb-2">Bienvenue</h2>
                <p className="mb-0">Veuillez saisir vos identifiants pour vous connecter</p>
            </div>

            <AuthStatus status={flash.status} />

            <form onSubmit={submit}>
                <FormField
                    id="login"
                    label="Email ou nom d'utilisateur"
                    icon="fa fa-user"
                    value={data.login}
                    onChange={(event) => setData('login', event.target.value)}
                    error={errors.login}
                    required
                    autoFocus
                    autoComplete="username"
                />

                <PasswordField
                    id="password"
                    label="Mot de passe"
                    value={data.password}
                    onChange={(event) => setData('password', event.target.value)}
                    error={errors.password}
                    required
                    autoComplete="current-password"
                />

                <div className="form-wrap form-wrap-checkbox mb-3">
                    <div className="d-flex align-items-center">
                        <div className="form-check form-check-md mb-0">
                            <input
                                className="form-check-input mt-0"
                                type="checkbox"
                                id="remember"
                                checked={data.remember}
                                onChange={(event) => setData('remember', event.target.checked)}
                            />
                        </div>
                        <p className="ms-1 mb-0">
                            <label htmlFor="remember">Se souvenir de moi</label>
                        </p>
                    </div>
                    <div className="text-end">
                        <a href="/backoffice/forgot-password" className="link-danger">
                            Mot de passe oublié ?
                        </a>
                    </div>
                </div>

                <div className="mb-3">
                    <SubmitButton processing={processing} processingLabel="Connexion…">
                        Se connecter
                    </SubmitButton>
                </div>
            </form>
        </GuestLayout>
    );
}
