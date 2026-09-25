import { router } from '@inertiajs/react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import EmptyState from '@/Components/Shared/EmptyState';
import FilterTextInput from '@/Components/Tables/FilterTextInput';
import Pagination from '@/Components/Tables/Pagination';
import SelectField from '@/Components/Forms/SelectField';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { t } from '@/Lib/i18n';
import type { MesEtudiantsPageProps } from '@/Types';

/** wa.me wants digits only (country code included, no « + »). */
function whatsappUrl(numero: string): string {
    return `https://wa.me/${numero.replace(/\D/g, '')}`;
}

/**
 * « Mes étudiants » — the students list as an ENSEIGNANT sees it (portée
 * `groups.view-own`, PorteeEnseignant): cards of the students holding an
 * Active inscription in one of his groups, read-only. The server sends a
 * reduced row (no CIN, address or parent data) and never a detail-page link —
 * the student file carries payments.
 */
export default function MesEtudiants({ students, filters, groupOptions }: MesEtudiantsPageProps) {
    const isLoading = useInertiaLoading();

    function reload(next: Partial<typeof filters>) {
        router.get(
            '/backoffice/students',
            { ...filters, ...next, page: undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    return (
        <BackofficeLayout
            title={t('My students')}
            breadcrumbs={[{ label: 'Tableau de bord', href: '/backoffice/dashboard' }, { label: t('My students') }]}
        >
            <div className="card">
                <div className="card-body pb-1">
                    <div className="row g-2 align-items-end mb-2">
                        <div className="col-md-5">
                            <label className="form-label" htmlFor="mes-etudiants-recherche">
                                {t('Search a student')}
                            </label>
                            <FilterTextInput
                                id="mes-etudiants-recherche"
                                value={filters.search}
                                onChange={(value) => reload({ search: value })}
                                placeholder={t('Name, reference, phone…')}
                            />
                        </div>
                        <div className="col-md-4">
                            <SelectField
                                id="mes-etudiants-groupe"
                                label={t('Group')}
                                options={groupOptions}
                                placeholder={t('All my groups')}
                                value={filters.groupeFilter}
                                onChange={(event) => reload({ groupeFilter: event.target.value })}
                            />
                        </div>
                        <div className="col-md-3 text-md-end">
                            <span className="badge badge-soft-primary fs-13 mb-2">
                                <i className="ti ti-school me-1" />
                                {students.total} {t('students')}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {students.data.length === 0 ? (
                <div className="card">
                    <div className="card-body">
                        <EmptyState title={t('No student')} icon="ti ti-school" />
                    </div>
                </div>
            ) : (
                <div className={`row${isLoading ? ' opacity-50' : ''}`}>
                    {students.data.map((student) => (
                        <div className="col-xxl-3 col-xl-4 col-md-6 d-flex" key={student.id}>
                            <div className="card flex-fill">
                                <div className="card-body d-flex flex-column">
                                    <div className="d-flex align-items-center mb-3">
                                        <span className="avatar avatar-xl rounded-circle bg-primary-transparent me-3 flex-shrink-0 d-inline-flex align-items-center justify-content-center overflow-hidden">
                                            {student.photoThumbUrl ? (
                                                <img
                                                    src={student.photoThumbUrl}
                                                    alt=""
                                                    className="w-100 h-100"
                                                    style={{ objectFit: 'cover' }}
                                                    loading="lazy"
                                                />
                                            ) : (
                                                <span className="fw-bold text-primary fs-20">{student.prenom.charAt(0).toUpperCase()}</span>
                                            )}
                                        </span>
                                        <div className="overflow-hidden">
                                            <h6 className="mb-1 text-truncate text-uppercase">{student.nomComplet}</h6>
                                            <code className="fs-13">{student.reference}</code>
                                        </div>
                                    </div>

                                    <div className="d-flex flex-wrap gap-2 mb-3">
                                        {student.niveau && <span className="badge badge-soft-info">{student.niveau}</span>}
                                        {student.age !== null && (
                                            <span className="badge badge-soft-secondary">
                                                {student.age} {t('years')}
                                            </span>
                                        )}
                                    </div>

                                    {student.groupes.length > 0 && (
                                        <div className="mb-3">
                                            {student.groupes.map((groupe) => (
                                                <span key={groupe.id} className="badge badge-soft-primary me-1 mb-1 text-uppercase">
                                                    <i className="ti ti-users-group me-1" />
                                                    {groupe.nom}
                                                </span>
                                            ))}
                                        </div>
                                    )}

                                    <div className="d-flex flex-wrap gap-2 mt-auto pt-2 border-top">
                                        {student.telephone ? (
                                            <a href={`tel:${student.telephone}`} className="btn btn-light btn-sm d-inline-flex align-items-center">
                                                <i className="ti ti-phone me-1" />
                                                <span dir="ltr">{student.telephone}</span>
                                            </a>
                                        ) : (
                                            <span className="fs-13 text-muted">{t('No phone')}</span>
                                        )}
                                        {student.whatsapp && (
                                            <a
                                                href={whatsappUrl(student.whatsapp)}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="btn btn-sm btn-success d-inline-flex align-items-center"
                                                aria-label="WhatsApp"
                                            >
                                                <i className="ti ti-brand-whatsapp" />
                                            </a>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            <Pagination paginator={students} />
        </BackofficeLayout>
    );
}
