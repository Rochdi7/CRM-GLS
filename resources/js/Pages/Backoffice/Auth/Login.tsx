import { useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent, type KeyboardEvent } from 'react';
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
    // A refused password is most often Caps Lock: say so before the attempt
    // counts against the 5-try rate limit.
    const [capsLock, setCapsLock] = useState(false);

    function trackCapsLock(event: KeyboardEvent<HTMLInputElement>) {
        setCapsLock(event.getModifierState('CapsLock'));
    }

    function submit(event: FormEvent) {
        event.preventDefault();

        post('/backoffice/login', {
            onError: () => reset('password'),
        });
    }

    return (
        <GuestLayout title="Connexion">
            <div className="gls-auth-head">
                <span className="gls-auth-head-icon" aria-hidden="true">
                    <i className="ti ti-lock-open" />
                </span>
                <div>
                    <h2 className="gls-auth-title">Bienvenue</h2>
                    <p className="mb-0">Veuillez saisir vos identifiants pour vous connecter</p>
                </div>
            </div>

            <AuthStatus status={flash.status} />

            <form onSubmit={submit}>
                <FormField
                    id="login"
                    label="Email ou nom d'utilisateur"
                    icon="ti ti-user"
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
                    onKeyDown={trackCapsLock}
                    onKeyUp={trackCapsLock}
                    onBlur={() => setCapsLock(false)}
                />

                {capsLock && (
                    <p className="gls-auth-caps" role="status">
                        <i className="ti ti-alert-triangle me-1" />
                        La touche Verr. Maj est activée
                    </p>
                )}

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

                <div className="mb-1">
                    <SubmitButton className="btn btn-primary w-100 gls-auth-submit" processing={processing} processingLabel="Connexion…">
                        Se connecter
                    </SubmitButton>
                </div>
            </form>
        </GuestLayout>
    );
}
