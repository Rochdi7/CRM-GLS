import { router } from '@inertiajs/react';
import { useMemo } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DateField from '@/Components/Forms/DateField';
import SelectField from '@/Components/Forms/SelectField';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { t } from '@/Lib/i18n';
import type { RapportsPageProps } from '@/Types';

/**
 * Gestion des rapports — édition de documents : un sélecteur « Rapport », les
 * filtres de ce rapport, et les deux boutons de téléchargement.
 *
 * Pas de barre d'onglets : un seul domaine (Inscriptions) a un rapport, et six
 * onglets qui ne mènent nulle part sont du décor. Le catalogue serveur
 * (RapportCatalogue) porte toujours les autres domaines — le jour où l'un
 * d'eux reçoit un rapport, il apparaîtra dans le sélecteur sans changer ce
 * composant, et la barre d'onglets pourra revenir si elle a alors un sens.
 *
 * Les rapports proposés viennent du SERVEUR : le sélecteur ne peut jamais
 * offrir un rapport que le contrôleur ne sert pas.
 *
 * Les téléchargements sont des GET vers `rapports.pdf` / `rapports.excel` avec
 * les MÊMES filtres que l'aperçu — même requête Domain côté serveur, donc le
 * document ne peut pas différer du compteur affiché. Ce sont des navigations
 * de fichier, pas des visites Inertia : `window.open` / `location.assign`, sinon
 * Inertia tenterait de lire un PDF comme une réponse de page.
 */
export default function RapportsIndex({
    onglets,
    filters,
    groupOptions,
    statutOptions,
    nombreLignes,
}: RapportsPageProps) {
    const isLoading = useInertiaLoading();

    // Le sélecteur « Rapport » liste les rapports RÉELLEMENT servis, pris dans
    // le catalogue serveur (RapportCatalogue) plutôt qu'écrits en dur ici : il
    // ne peut donc pas proposer un rapport que le contrôleur refuserait.
    const rapportOptions = useMemo(
        () => onglets.flatMap((o) => o.rapports).map((r) => ({ value: r.value, label: r.label })),
        [onglets],
    );

    function reload(nextFilters: Partial<typeof filters>) {
        const next = { ...filters, ...nextFilters };

        // Inertia retire les chaînes vides de la query string : tout vider
        // produirait une URL NUE, que le contrôleur lit comme une première
        // visite et à laquelle il répond en réinjectant la fenêtre par défaut
        // — le filtre effacé reviendrait aussitôt. Un marqueur garde « vidé »
        // distinct de « jamais renseigné » (même garde que la page
        // Encaissements, 27/08/2026).
        router.get(
            '/backoffice/rapports',
            {
                ...next,
                dateFrom: next.dateFrom === '' ? '-' : next.dateFrom,
                dateTo: next.dateTo === '' ? '-' : next.dateTo,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    /** Ouvre le document dans un nouvel onglet (PDF) ou déclenche le téléchargement (Excel). */
    function telecharger(format: 'pdf' | 'excel') {
        const params = new URLSearchParams({
            rapport: filters.rapport,
            groupFilter: filters.groupFilter,
            statutFilter: filters.statutFilter,
            dateFrom: filters.dateFrom,
            dateTo: filters.dateTo,
        });

        const url = `/backoffice/rapports/${format}?${params.toString()}`;

        if (format === 'pdf') {
            // Le PDF est servi `inline` : l'utilisateur le relit avant
            // d'imprimer, comme un reçu.
            window.open(url, '_blank', 'noopener');
        } else {
            window.location.assign(url);
        }
    }

    return (
        <BackofficeLayout
            title={t('Reports management')}
            breadcrumbs={[
                { label: t('Dashboard'), href: '/backoffice/dashboard' },
                { label: t('Reports management') },
            ]}
        >
            <Card title={t('Reports management')}>
                {/* Les cinq filtres tiennent sur UNE ligne en grand écran : la
                    grille précédente les empilait 2 par 2 en `col-lg-4` et
                    laissait tout le tiers droit de la carte vide. Les colonnes
                    sont dimensionnées selon ce que chaque champ doit afficher —
                    un nom de groupe est long, une date fait dix caractères —
                    plutôt qu'en douzièmes égaux.

                    ⚠ Les cinq champs totalisent 11 colonnes, pas 12 : la
                    douzième est laissée au bouton Actualiser (`col-xl-auto`).
                    À 12 il ne restait plus rien et le bouton retombait seul sur
                    la ligne suivante.

                    `align-items-end` l'aligne sur le bas des champs, sinon il
                    remonterait au niveau des libellés ; gouttière HORIZONTALE
                    seule (`gx-3`) car chaque champ porte déjà son `mb-3` — un
                    `g-3` complet doublait l'espace vertical au retour à la
                    ligne. */}
                <div className="row gx-3 align-items-end">
                    <div className="col-12 col-md-6 col-xl-3">
                        <SelectField
                            id="rapport"
                            label={t('Report')}
                            required
                            options={rapportOptions}
                            value={filters.rapport}
                            onChange={(e) => reload({ rapport: e.target.value })}
                        />
                    </div>
                    {/* Le groupe est OPTIONNEL : vide, le rapport sort
                        toutes les inscriptions de la période. */}
                    <div className="col-12 col-md-6 col-xl-2">
                        <SelectField
                            id="groupFilter"
                            label={t('Group')}
                            placeholder={t('Choose a course')}
                            options={groupOptions}
                            value={filters.groupFilter}
                            onChange={(e) => reload({ groupFilter: e.target.value })}
                        />
                    </div>
                    <div className="col-12 col-md-6 col-xl-2">
                        <SelectField
                            id="statutFilter"
                            label={t('Status')}
                            placeholder={t('All statuses')}
                            options={statutOptions}
                            value={filters.statutFilter}
                            onChange={(e) => reload({ statutFilter: e.target.value })}
                        />
                    </div>
                    <div className="col-6 col-md-3 col-xl-2">
                        <DateField
                            id="dateFrom"
                            label={t('Start date')}
                            value={filters.dateFrom}
                            onChange={(e) => reload({ dateFrom: e.target.value })}
                        />
                    </div>
                    <div className="col-6 col-md-3 col-xl-2">
                        <DateField
                            id="dateTo"
                            label={t('End date')}
                            value={filters.dateTo}
                            onChange={(e) => reload({ dateTo: e.target.value })}
                            panelAlign="right"
                        />
                    </div>
                    {/* `d-grid` en dessous de xl étire le bouton sur toute la
                        largeur (il est alors seul sur sa ligne, comme les
                        champs) ; `d-xl-block` le rend à sa taille naturelle dès
                        qu'il rejoint la ligne des filtres. C'est l'idiome
                        Bootstrap pour ça — `w-xl-auto` n'existe pas, les
                        utilitaires de largeur ne sont pas responsive. */}
                    <div className="col-12 col-xl-auto mb-3 d-grid d-xl-block">
                        <button
                            type="button"
                            className="btn btn-dark"
                            title={t('Refresh')}
                            onClick={() => reload({})}
                            disabled={isLoading}
                        >
                            <i className="ti ti-refresh" aria-hidden="true" />
                            <span className="visually-hidden">{t('Refresh')}</span>
                        </button>
                    </div>
                </div>

                {/* Pas de sélecteur « Langue du PDF » : les rapports
                    sortent en français, la langue de toute l'app
                    (APP_LOCALE=fr). Le gabarit PDF sait déjà rendre en
                    arabe (mPDF façonne les glyphes RTL nativement) —
                    l'option a été retirée de l'écran, pas le support,
                    pour qu'un futur besoin ne reparte pas de zéro. */}

                <hr />

                <div className="d-flex align-items-center justify-content-between flex-wrap">
                    {/* Compté par le serveur sur TOUT l'ensemble filtré :
                        l'utilisateur sait ce qu'il télécharge avant de
                        cliquer, et un rapport vide se voit ici plutôt
                        que dans un PDF d'une page blanche. */}
                    <p className="text-muted mb-3 me-3">
                        {nombreLignes === 0
                            ? t('No registration matches these filters.')
                            : `${nombreLignes} ${
                                  nombreLignes === 1 ? t('registration') : t('registrations')
                              }`}
                    </p>
                    {/* Rouge pour le PDF, vert pour l'Excel : les couleurs des
                        deux formats eux-mêmes, ce qui permet de viser le bon
                        bouton sans lire le libellé. `btn-success` pour un
                        export tableur suit déjà la page « Absence par groupe ». */}
                    <div className="mb-3">
                        <button
                            type="button"
                            className="btn btn-danger me-2"
                            onClick={() => telecharger('pdf')}
                            disabled={isLoading}
                        >
                            <i className="ti ti-file-type-pdf me-1" aria-hidden="true" />
                            {t('Download PDF')}
                        </button>
                        <button
                            type="button"
                            className="btn btn-success"
                            onClick={() => telecharger('excel')}
                            disabled={isLoading}
                        >
                            <i className="ti ti-file-type-xls me-1" aria-hidden="true" />
                            {t('Download EXCEL')}
                        </button>
                    </div>
                </div>
            </Card>
        </BackofficeLayout>
    );
}
