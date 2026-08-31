import { Head, Link, router } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import { t } from '@/Lib/i18n';

/**
 * Error page for Inertia requests (bootstrap/app.php → $exceptions->respond).
 *
 * Reported 31/08/2026: a bare « 500 Erreur serveur » made users tell the office
 * the SERVER WAS DOWN when a single action had failed. Rendering the error
 * INSIDE BackofficeLayout is most of the answer — the sidebar, the header and
 * the centre switcher are all still there, so the app visibly still works and
 * the user can click straight on to something else instead of reaching for the
 * phone. The wording carries the rest: what failed, what is unaffected, and
 * whether anything was saved.
 */
interface ErrorPageProps {
    status: number;
    errorId?: string | null;
}

type Copy = {
    title: string;
    heading: string;
    message: string;
    reassurance: string;
    icon: string;
    tone: string;
};

function copyFor(status: number): Copy {
    switch (status) {
        case 403:
            return {
                title: t('Access denied'),
                heading: t('You do not have access to this page'),
                message: t('Your account does not hold the permission required for this section.'),
                reassurance: t('This is not a malfunction. If you need this access, ask your administrator to grant it.'),
                icon: 'ti-lock',
                tone: 'warning',
            };
        case 404:
            return {
                title: t('Page not found'),
                heading: t('This page does not exist'),
                message: t('The address is incorrect, or the record you are looking for has been moved or deleted.'),
                reassurance: t('The application is working normally — only this address is invalid.'),
                icon: 'ti-file-off',
                tone: 'info',
            };
        case 429:
            return {
                title: t('Too many attempts'),
                heading: t('Too many attempts'),
                message: t('Too many requests were sent in a short time. This protection is temporary.'),
                reassurance: t('Wait a moment, then try again.'),
                icon: 'ti-hourglass',
                tone: 'warning',
            };
        case 503:
            return {
                title: t('Maintenance in progress'),
                heading: t('Maintenance in progress'),
                message: t('The application is temporarily unavailable while an update is being installed.'),
                reassurance: t('This is a planned operation and usually takes only a few minutes. Your data is safe.'),
                icon: 'ti-tool',
                tone: 'info',
            };
        default:
            return {
                title: t('Action failed'),
                heading: t('This action could not be completed'),
                message: t('An unexpected error interrupted this operation. Nothing was saved: the action was cancelled in full.'),
                reassurance: t('The application is still running — only this action failed. You can go back and continue working.'),
                icon: 'ti-alert-triangle',
                tone: 'danger',
            };
    }
}

export default function Error({ status, errorId }: ErrorPageProps) {
    const copy = copyFor(status);

    return (
        <BackofficeLayout title={copy.title}>
            <Head title={copy.title} />

            <div className="row justify-content-center">
                <div className="col-lg-7 col-md-9">
                    <div className="card mt-4">
                        <div className="card-body p-4 text-center">
                            <div className={`avatar avatar-xl bg-${copy.tone}-transparent text-${copy.tone} rounded-circle mx-auto mb-3`}>
                                <i className={`ti ${copy.icon} fs-24`} />
                            </div>

                            <h2 className="fs-20 fw-bold mb-2">{copy.heading}</h2>
                            <p className="fs-14 text-muted mb-1">{copy.message}</p>
                            <p className="fs-14 text-muted mb-4">{copy.reassurance}</p>

                            <div className="d-flex flex-wrap gap-2 justify-content-center">
                                <button
                                    type="button"
                                    className="btn btn-primary d-inline-flex align-items-center"
                                    onClick={() => window.history.back()}
                                >
                                    <i className="ti ti-arrow-left me-2" />
                                    {t('Go back')}
                                </button>
                                <Link
                                    href="/backoffice/dashboard"
                                    className="btn btn-outline-secondary d-inline-flex align-items-center"
                                >
                                    <i className="ti ti-home me-2" />
                                    {t('Back to dashboard')}
                                </Link>
                                {status === 503 && (
                                    <button
                                        type="button"
                                        className="btn btn-outline-secondary d-inline-flex align-items-center"
                                        onClick={() => router.reload()}
                                    >
                                        <i className="ti ti-refresh me-2" />
                                        {t('Retry')}
                                    </button>
                                )}
                            </div>

                            {errorId && (
                                <div className="mt-4">
                                    <p className="fs-13 text-muted mb-1">
                                        {t('If it happens again, report this reference to your administrator:')}
                                    </p>
                                    <code className="fs-14 text-normal-case">{errorId}</code>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </BackofficeLayout>
    );
}
