import { router, useForm } from '@inertiajs/react';
import { useRef, useState, type ChangeEvent, type FormEvent } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import FormField from '@/Components/Forms/FormField';
import PasswordField from '@/Components/Forms/PasswordField';
import PhoneField from '@/Components/Forms/PhoneField';
import SubmitButton from '@/Components/Forms/SubmitButton';
interface EmployeeSummary {
    reference: string;
    categorie: string;
    sexe: string | null;
    email: string | null;
    date_naissance: string | null;
    date_embauche: string | null;
    centre: string | null;
    photo_url: string | null;
}

interface ProfileIndexProps {
    user: {
        name: string;
        email: string;
        roles: string[];
    };
    employee: EmployeeSummary | null;
    phonePays: string;
    telephone: string;
    whatsapp: string;
}

function SexeIcon({ sexe }: { sexe: string | null }) {
    if (sexe === 'Homme') {
        return (
            <span className="d-inline-flex align-items-center">
                <i className="ti ti-man fs-16 text-primary" />
                <span className="ms-1">Homme</span>
            </span>
        );
    }

    if (sexe === 'Femme') {
        return (
            <span className="d-inline-flex align-items-center">
                <i className="ti ti-woman fs-16 text-pink" />
                <span className="ms-1">Femme</span>
            </span>
        );
    }

    return <>—</>;
}

/**
 * Replaces App\Livewire\Backoffice\Profile\ProfilePage — same fields, same
 * validation, same authorization (own profile only, no permission gate).
 * Two independent forms post to ProfileController::updateProfile() and
 * ::updatePassword() (Form Requests own validation, not this component).
 */
export default function ProfileIndex({ user, employee, phonePays, telephone, whatsapp }: ProfileIndexProps) {
    const profileForm = useForm({
        name: user.name,
        email: user.email,
        phone_pays: phonePays,
        telephone,
        whatsapp,
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    // Avatar upload — its own tiny form, posted the moment a file is picked
    // (no "Enregistrer" step, matching the pic-2 camera-badge interaction).
    // It writes to the linked Employee's `photo` media collection, so the
    // header avatar refreshes on the same Inertia response.
    const photoInputRef = useRef<HTMLInputElement>(null);
    const [photoPreview, setPhotoPreview] = useState<string | null>(null);
    const [photoBusy, setPhotoBusy] = useState(false);
    const [photoError, setPhotoError] = useState<string | null>(null);

    const currentPhotoUrl = photoPreview ?? employee?.photo_url ?? null;

    function handlePhotoChange(event: ChangeEvent<HTMLInputElement>) {
        const file = event.target.files?.[0];

        // Reset the input right away so re-picking the SAME file still fires
        // onChange (a browser quirk: identical value ⇒ no event).
        event.target.value = '';

        if (!file) {
            return;
        }

        setPhotoError(null);

        const reader = new FileReader();
        reader.onload = () => setPhotoPreview(reader.result as string);
        reader.readAsDataURL(file);

        setPhotoBusy(true);
        router.post(
            '/backoffice/profile/photo',
            { photo: file },
            {
                preserveScroll: true,
                forceFormData: true,
                onError: (errors) => {
                    // Upload refused (too large / not an image) — drop the
                    // optimistic preview so the UI never lies about what
                    // was actually stored.
                    setPhotoPreview(null);
                    setPhotoError(errors.photo ?? 'Le téléversement de la photo a échoué.');
                },
                onFinish: () => setPhotoBusy(false),
            },
        );
    }

    function removePhoto() {
        setPhotoError(null);
        setPhotoBusy(true);
        router.delete('/backoffice/profile/photo', {
            preserveScroll: true,
            onSuccess: () => setPhotoPreview(null),
            onFinish: () => setPhotoBusy(false),
        });
    }

    function submitProfile(event: FormEvent) {
        event.preventDefault();
        profileForm.post('/backoffice/profile', { preserveScroll: true });
    }

    function submitPassword(event: FormEvent) {
        event.preventDefault();
        passwordForm.post('/backoffice/profile/password', {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    }

    return (
        <BackofficeLayout
            title="Mon profil"
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: 'Mon profil' }]}
        >
            <div className="row">
                <div className="col-xl-4">
                    <Card>
                        <div className="text-center">
                            <div className="position-relative d-inline-block mb-3">
                                <span className="avatar avatar-xxl bg-primary-transparent rounded-circle d-inline-flex align-items-center justify-content-center overflow-hidden border border-2 border-white shadow-sm">
                                    {currentPhotoUrl ? (
                                        <img
                                            src={currentPhotoUrl}
                                            alt={user.name}
                                            className="w-100 h-100"
                                            style={{ objectFit: 'cover' }}
                                        />
                                    ) : (
                                        <span className="fs-24 fw-bold text-primary">
                                            {user.name.charAt(0).toUpperCase()}
                                        </span>
                                    )}
                                </span>

                                {employee && (
                                    <>
                                        <button
                                            type="button"
                                            className="btn btn-primary btn-sm rounded-circle position-absolute d-inline-flex align-items-center justify-content-center p-0 border border-2 border-white"
                                            style={{ width: 34, height: 34, right: 0, bottom: 4 }}
                                            onClick={() => photoInputRef.current?.click()}
                                            disabled={photoBusy}
                                            title="Changer la photo"
                                            aria-label="Changer la photo"
                                        >
                                            {photoBusy ? (
                                                <span
                                                    className="spinner-border spinner-border-sm"
                                                    role="status"
                                                    aria-hidden="true"
                                                />
                                            ) : (
                                                <i className="ti ti-camera fs-16" />
                                            )}
                                        </button>
                                        <input
                                            ref={photoInputRef}
                                            type="file"
                                            accept="image/*"
                                            className="d-none"
                                            onChange={handlePhotoChange}
                                        />
                                    </>
                                )}
                            </div>

                            {photoError && <div className="text-danger fs-13 mb-2">{photoError}</div>}

                            {employee?.photo_url && !photoBusy && (
                                <div className="mb-2">
                                    <button
                                        type="button"
                                        className="btn btn-link btn-sm text-danger p-0 fs-13"
                                        onClick={removePhoto}
                                    >
                                        Supprimer la photo
                                    </button>
                                </div>
                            )}

                            <h5 className="mb-1">{user.name}</h5>
                            <p className="text-muted mb-2">{user.email}</p>
                            {user.roles.map((role) => (
                                <span className="badge badge-soft-info" key={role}>
                                    {role}
                                </span>
                            ))}
                        </div>
                        {employee && (
                            <div className="border-top mt-3 pt-3">
                                <div className="d-flex justify-content-between mb-2">
                                    <span className="text-muted">Référence</span>
                                    <code>{employee.reference}</code>
                                </div>
                                <div className="d-flex justify-content-between mb-2">
                                    <span className="text-muted">Catégorie</span>
                                    <span className="fw-medium">{employee.categorie}</span>
                                </div>
                                <div className="d-flex justify-content-between mb-2">
                                    <span className="text-muted">Genre</span>
                                    <span className="fw-medium">
                                        <SexeIcon sexe={employee.sexe} />
                                    </span>
                                </div>
                                <div className="d-flex justify-content-between mb-2">
                                    <span className="text-muted">Email</span>
                                    <span className="fw-medium text-end text-truncate ms-2">{employee.email ?? '—'}</span>
                                </div>
                                <div className="d-flex justify-content-between mb-2">
                                    <span className="text-muted">Date de naissance</span>
                                    <span className="fw-medium">{employee.date_naissance ?? '—'}</span>
                                </div>
                                <div className="d-flex justify-content-between mb-2">
                                    <span className="text-muted">Date d'embauche</span>
                                    <span className="fw-medium">{employee.date_embauche ?? '—'}</span>
                                </div>
                                <div className="d-flex justify-content-between">
                                    <span className="text-muted">Centre</span>
                                    <span className="fw-medium text-end">{employee.centre ?? 'Aucun centre (global)'}</span>
                                </div>
                            </div>
                        )}
                    </Card>
                </div>

                <div className="col-xl-8">
                    <Card title="Informations du profil">
                        <form onSubmit={submitProfile}>
                            <div className="row">
                                <div className="col-md-6">
                                    <FormField
                                        id="p-name"
                                        label="Nom"
                                        required
                                        value={profileForm.data.name}
                                        onChange={(event) => profileForm.setData('name', event.target.value)}
                                        error={profileForm.errors.name}
                                        placeholder="ex : Mohammed Rafik"
                                    />
                                </div>
                                <div className="col-md-6">
                                    <FormField
                                        id="p-email"
                                        label="Email"
                                        type="email"
                                        required
                                        value={profileForm.data.email}
                                        onChange={(event) => profileForm.setData('email', event.target.value)}
                                        error={profileForm.errors.email}
                                        placeholder="ex : nom@domaine.com"
                                    />
                                </div>
                                {employee && (
                                    <>
                                        {/* Shared PhoneField (flag + dial code + phone icon) —
                                            the country picked on either field drives both, the
                                            same one-selector-per-form behavior the Employees
                                            form has. */}
                                        <div className="col-md-6">
                                            <PhoneField
                                                id="p-tel"
                                                label="Téléphone"
                                                countryIso={profileForm.data.phone_pays}
                                                national={profileForm.data.telephone}
                                                onCountryChange={(iso) => profileForm.setData('phone_pays', iso)}
                                                onNationalChange={(value) => profileForm.setData('telephone', value)}
                                                error={profileForm.errors.telephone ?? profileForm.errors.phone_pays}
                                            />
                                        </div>
                                        <div className="col-md-6">
                                            <PhoneField
                                                id="p-wa"
                                                label="WhatsApp"
                                                countryIso={profileForm.data.phone_pays}
                                                national={profileForm.data.whatsapp}
                                                onCountryChange={(iso) => profileForm.setData('phone_pays', iso)}
                                                onNationalChange={(value) => profileForm.setData('whatsapp', value)}
                                                error={profileForm.errors.whatsapp}
                                            />
                                        </div>
                                    </>
                                )}
                            </div>
                            <SubmitButton
                                processing={profileForm.processing}
                                className="btn btn-primary"
                                processingLabel="Enregistrement…"
                            >
                                Enregistrer
                            </SubmitButton>
                        </form>
                    </Card>

                    <Card title="Changer le mot de passe">
                        <form onSubmit={submitPassword}>
                            <PasswordField
                                id="p-cur"
                                label="Mot de passe actuel"
                                required
                                autoComplete="current-password"
                                value={passwordForm.data.current_password}
                                onChange={(event) => passwordForm.setData('current_password', event.target.value)}
                                error={passwordForm.errors.current_password}
                            />
                            <div className="row">
                                <div className="col-md-6">
                                    <PasswordField
                                        id="p-new"
                                        label="Nouveau mot de passe"
                                        required
                                        autoComplete="new-password"
                                        value={passwordForm.data.password}
                                        onChange={(event) => passwordForm.setData('password', event.target.value)}
                                        error={passwordForm.errors.password}
                                    />
                                </div>
                                <div className="col-md-6">
                                    <PasswordField
                                        id="p-conf"
                                        label="Confirmer le mot de passe"
                                        required
                                        autoComplete="new-password"
                                        value={passwordForm.data.password_confirmation}
                                        onChange={(event) => passwordForm.setData('password_confirmation', event.target.value)}
                                    />
                                </div>
                            </div>
                            <SubmitButton
                                processing={passwordForm.processing}
                                className="btn btn-primary"
                                processingLabel="Enregistrement…"
                            >
                                Changer le mot de passe
                            </SubmitButton>
                        </form>
                    </Card>
                </div>
            </div>
        </BackofficeLayout>
    );
}
