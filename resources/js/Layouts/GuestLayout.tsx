import { Head } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';

interface GuestLayoutProps extends PropsWithChildren {
    title: string;
}

/**
 * Backoffice guest shell (login, forgot/reset password) — no sidebar, no
 * authenticated header, no Livewire scripts. Markup mirrors
 * components/backoffice/layout/guest.blade.php + the existing GLS-adapted
 * auth pages exactly (centered single-column card, GLS light/dark logo
 * pair, vh-100 flex column). Reuses the same static PreSkool CSS already
 * loaded by app.blade.php — no separate stylesheet for guest pages.
 */
export default function GuestLayout({ title, children }: GuestLayoutProps) {
    return (
        <div className="account-page">
            <Head title={title} />
            <div className="container">
                <div className="row justify-content-center">
                    <div className="col-md-5 mx-auto">
                        <div className="d-flex flex-column justify-content-between vh-100">
                            <div className="mx-auto p-4 text-center">
                                <img
                                    src="/assets/images/logo/gls-noir.png"
                                    className="img-fluid gls-auth-logo gls-logo-light"
                                    alt="GLS CRM"
                                />
                                <img
                                    src="/assets/images/logo/gls-blanc.webp"
                                    className="img-fluid gls-auth-logo gls-logo-dark"
                                    alt="GLS CRM"
                                />
                            </div>

                            <div className="card">
                                <div className="card-body p-4">{children}</div>
                            </div>

                            <div className="p-4 text-center">
                                <p className="mb-0">Copyright &copy; {new Date().getFullYear()} — GLS CRM</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
