import { router } from '@inertiajs/react';
import { useMemo } from 'react';
import BackofficeLayout from '@/Layouts/BackofficeLayout';
import Card from '@/Components/Shared/Card';
import DateField from '@/Components/Forms/DateField';
import SelectField from '@/Components/Forms/SelectField';
import { useInertiaLoading } from '@/Hooks/useInertiaLoading';
import { t } from '@/Lib/i18n';
import type { RapportFiltreKey, RapportsPageProps, SelectOption } from '@/Types';

/**
 * Gestion des rapports — édition de documents : un sélecteur « Rapport », les
 * filtres de ce rapport, et les deux boutons de téléchargement.
 *
 * ⚠ La barre d'onglets est de retour (10/09/2026), parce qu'elle a désormais
 * un sens : deux domaines servent des rapports (Inscriptions, Finance &
 * Paiements). Elle avait été retirée quand un seul en servait — six onglets
 * qui ne mènent nulle part sont du décor.
 *
 * Elle ne dessine QUE les domaines qui servent au moins un rapport
 * (`rapports.length > 0`), et elle disparaît d'elle-même s'il n'en reste
 * qu'un : un onglet vide ne se clique pas, et une barre à un seul onglet
 * n'oriente rien. Le jour où « Caisse » ou « Dépenses » reçoit son rapport,
 * son onglet apparaît SANS toucher à ce composant — c'est le catalogue
 * serveur (RapportCatalogue) qui décide, ici comme pour les filtres.
 *
 * Les rapports proposés viennent du SERVEUR : ni les onglets ni le sélecteur
 * ne peuvent offrir un rapport que le contrôleur ne sert pas.
 *
 * Les téléchargements sont des GET vers `rapports.pdf` / `rapports.excel` avec
 * les MÊMES filtres que l'aperçu — même requête Domain côté serveur, donc le
 * document ne peut pas différer du compteur affiché. Ce sont des navigations
 * de fichier, pas des visites Inertia : `window.open` / `location.assign`, sinon
 * Inertia tenterait de lire un PDF comme une réponse de page.
 */
/**
 * L'icône de chaque onglet, par clé de domaine du catalogue serveur. Un
 * domaine sans entrée retombe sur une icône générique plutôt que de casser la
 * barre — la clé vient du serveur, ce composant ne peut pas la garantir.
 */
const ONGLET_ICONES: Record<string, string> = {
    inscriptions: 'ti ti-file-text',
    admissions: 'ti ti-user-plus',
    finance: 'ti ti-cash-banknote',
    caisse: 'ti ti-wallet',
    depenses: 'ti ti-receipt',
    'vie-scolaire': 'ti ti-school',
    employes: 'ti ti-users',
};

export default function RapportsIndex({
    onglets,
    filters,
    filtresVisibles,
    groupOptions,
    statutOptions,
    sexeOptions,
    inscriptionOptions,
    methodeOptions,
    caisseOptions,
    typeOptions,
    nombreLignes,
    montantTotal,
}: RapportsPageProps) {
    const isLoading = useInertiaLoading();

    // Les onglets RÉELLEMENT servis : un domaine sans rapport n'en reçoit pas.
    // Filtré ici plutôt que côté serveur parce que le catalogue continue de
    // décrire les domaines visés (ils servent de point d'accroche au prochain
    // rapport) — c'est l'ÉCRAN qui n'a pas à montrer une porte fermée.
    const ongletsServis = useMemo(() => onglets.filter((o) => o.rapports.length > 0), [onglets]);

    // L'onglet actif est déduit du rapport choisi, jamais mémorisé à part :
    // deux sources pourraient diverger, et l'onglet finirait par désigner un
    // domaine dont le rapport affiché ne fait pas partie. Le rapport reste
    // donc l'unique état — il est déjà dans l'URL, donc partageable et
    // rechargeable.
    const ongletActif = useMemo(
        () => ongletsServis.find((o) => o.rapports.some((r) => r.value === filters.rapport)) ?? ongletsServis[0],
        [ongletsServis, filters.rapport],
    );

    // Le sélecteur « Rapport » ne liste que les rapports de l'onglet ouvert —
    // c'est ce qui donne un rôle à la barre : l'onglet choisit le domaine, le
    // sélecteur le rapport à l'intérieur. Les valeurs viennent du catalogue
    // serveur, donc il ne peut pas proposer un rapport que le contrôleur
    // refuserait.
    const rapportOptions = useMemo(
        () => (ongletActif?.rapports ?? []).map((r) => ({ value: r.value, label: r.label })),
        [ongletActif],
    );

    /**
     * La définition de CHAQUE filtre possible : libellé, options, placeholder.
     * Lesquels sont dessinés vient du SERVEUR (`filtresVisibles`), jamais d'un
     * test sur la clé du rapport écrit ici — un rapport dont la requête Domain
     * n'applique pas un filtre ne doit pas pouvoir l'afficher, sinon
     * l'utilisateur croit avoir restreint son document alors qu'il ne l'a pas
     * fait. Ajouter un rapport = une entrée dans RapportCatalogue::filtres(),
     * et ici seulement si son filtre n'existe pas déjà.
     */
    const definitionsFiltres: Record<
        RapportFiltreKey,
        { label: string; placeholder: string; options: SelectOption[] }
    > = {
        groupFilter: {
            label: t('Group'),
            placeholder: t('Choose a course'),
            options: groupOptions,
        },
        statutFilter: {
            label: t('Status'),
            placeholder: t('All statuses'),
            options: statutOptions,
        },
        sexeFilter: {
            label: t('Gender'),
            placeholder: t('Select gender'),
            options: sexeOptions,
        },
        inscriptionFilter: {
            label: t('Registration status'),
            placeholder: t('All'),
            options: inscriptionOptions,
        },
        methodeFilter: {
            label: t('Payment method'),
            placeholder: t('All methods'),
            options: methodeOptions,
        },
        caisseFilter: {
            label: t('Cash register'),
            placeholder: t('All cash registers'),
            options: caisseOptions,
        },
        typeFilter: {
            label: t('Type'),
            placeholder: t('All types'),
            options: typeOptions,
        },
    };

    /**
     * Ce que compte l'aperçu : un rapport d'étudiants ne compte pas des
     * inscriptions. Le mot suit le rapport, sinon le compteur mentirait sur ce
     * que l'utilisateur s'apprête à télécharger. Défaut = inscriptions, pour
     * que le premier rapport garde son libellé exact.
     */
    const messages = (() => {
        if (filters.rapport === 'liste-etudiants') {
            return {
                vide: t('No student matches these filters.'),
                unite: nombreLignes === 1 ? t('student') : t('students'),
            };
        }

        if (filters.rapport === 'releve-encaissements') {
            return {
                vide: t('No payment matches these filters.'),
                unite: nombreLignes === 1 ? t('payment') : t('payments'),
            };
        }

        return {
            vide: t('No registration matches these filters.'),
            unite: nombreLignes === 1 ? t('registration') : t('registrations'),
        };
    })();

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
        // Tous les filtres partent, y compris ceux qu'un autre rapport
        // utilise : le contrôleur n'applique à ce rapport que ceux qu'il
        // déclare (RapportCatalogue::filtres()), donc les autres sont inertes.
        // Les énumérer un par un ici ferait silencieusement tomber le filtre
        // du prochain rapport ajouté, et le document ne correspondrait plus au
        // compteur affiché — la seule chose que cette page promet.
        const params = new URLSearchParams(filters as unknown as Record<string, string>);

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
            {/* La barre d'onglets : un onglet par DOMAINE servi. Elle n'est
                dessinée qu'à partir de deux — à un seul, elle n'oriente rien
                et le sélecteur « Rapport » suffit.

                Changer d'onglet sélectionne le PREMIER rapport du domaine :
                un onglet ouvert sur le rapport d'un autre domaine se
                contredirait lui-même. Les filtres propres à l'ancien rapport
                partent avec lui — le contrôleur n'applique de toute façon que
                ceux que le nouveau rapport déclare
                (RapportCatalogue::filtres()), mais les laisser dans l'URL
                ferait revenir un filtre invisible si l'utilisateur revenait
                sur ses pas. La fenêtre de dates, elle, est commune à tous les
                rapports : elle SURVIT au changement d'onglet, sinon la période
                que l'utilisateur vient de choisir serait perdue à chaque
                clic. */}
            {ongletsServis.length > 1 && (
                <ul className="nav nav-tabs p-0 border-bottom rounded-0 mb-4" role="tablist">
                    {ongletsServis.map((onglet) => (
                        <li className="nav-item" key={onglet.key} role="presentation">
                            <button
                                type="button"
                                className={`nav-link d-inline-flex align-items-center${
                                    ongletActif?.key === onglet.key ? ' active' : ''
                                }`}
                                aria-current={ongletActif?.key === onglet.key ? 'page' : undefined}
                                onClick={() =>
                                    reload({
                                        rapport: onglet.rapports[0].value,
                                        groupFilter: '',
                                        statutFilter: '',
                                        sexeFilter: '',
                                        inscriptionFilter: '',
                                        methodeFilter: '',
                                        caisseFilter: '',
                                        typeFilter: '',
                                    })
                                }
                            >
                                <i className={`${ONGLET_ICONES[onglet.key] ?? 'ti ti-report'} me-2`} aria-hidden="true" />
                                {onglet.label}
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            <Card title={ongletActif?.label ?? t('Reports management')}>
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
                    {/* Les filtres du rapport choisi, dans l'ordre décidé par
                        le serveur. Tous OPTIONNELS : vides, le rapport sort
                        toutes les lignes de la période. */}
                    {filtresVisibles.map((cle) => (
                        <div className="col-12 col-md-6 col-xl-2" key={cle}>
                            <SelectField
                                id={cle}
                                label={definitionsFiltres[cle].label}
                                placeholder={definitionsFiltres[cle].placeholder}
                                options={definitionsFiltres[cle].options}
                                value={filters[cle]}
                                onChange={(e) => reload({ [cle]: e.target.value })}
                            />
                        </div>
                    ))}
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
                        {nombreLignes === 0 ? messages.vide : `${nombreLignes} ${messages.unite}`}
                        {/* Le total vient du SERVEUR, calculé sur tout
                            l'ensemble filtré — jamais additionné à partir de
                            lignes rendues ici (la page n'en reçoit aucune).
                            C'est le MÊME chiffre que le pied du PDF et que
                            l'en-tête du classeur, donc l'utilisateur sait
                            avant de cliquer ce que son document totalisera.
                            Vide pour un rapport sans colonne monétaire. */}
                        {montantTotal !== '' && nombreLignes > 0 && (
                            <span className="ms-2 fw-semibold text-dark">
                                — {t('Total collected')} : {montantTotal}
                            </span>
                        )}
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
