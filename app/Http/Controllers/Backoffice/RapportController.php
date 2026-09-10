<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Reports\Exports\ExporterRapportExcel;
use App\Domain\Reports\Exports\RapportPdfRenderer;
use App\Domain\Reports\Queries\GetEncaissementsReport;
use App\Domain\Reports\Queries\GetInscriptionsReport;
use App\Domain\Reports\Queries\GetStudentsReport;
use App\Domain\Reports\Support\RapportCatalogue;
use App\Http\Controllers\Controller;
use App\Models\Caisse;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\Student;
use App\Services\Context\CurrentContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gestion des rapports — page d'édition de documents (aperçu à l'écran, puis
 * téléchargement PDF ou Excel). Lecture seule : aucune route d'écriture, rien
 * à créer ni à modifier ici.
 *
 * Les trois sorties (aperçu, PDF, Excel) passent par la MÊME requête Domain
 * (GetInscriptionsReport, GetStudentsReport) : le document téléchargé ne peut
 * donc pas différer de ce que l'utilisateur vient de voir. C'est la règle que
 * suit déjà l'export « Absence par groupe ».
 *
 * Portée : un rapport n'a AUCUN privilège de lecture. `reports.view` ouvre
 * l'écran ; ce qu'il imprime reste borné par les « Centres affectés » et par
 * le contexte actif (centre + année) — la requête Domain applique les deux,
 * comme la liste Inscriptions.
 *
 * Tous les filtres propres à un rapport sont OPTIONNELS : vides, le rapport
 * sort toutes les lignes de la fenêtre de dates. QUELS filtres un rapport
 * expose est décidé par RapportCatalogue::filtres(), pas par le composant
 * React — la page ne peut donc pas offrir un filtre que la requête Domain
 * n'applique pas, et l'en-tête du document ne peut pas rappeler un filtre qui
 * n'a rien restreint.
 */
final class RapportController extends Controller
{
    /**
     * Les libellés du filtre « État d'inscription », clé machine => libellé
     * français. Mêmes clés et mêmes libellés que la liste Étudiants : le
     * rapport ne renomme pas un filtre que l'utilisateur connaît déjà.
     *
     * @var list<array{value: string, label: string}>
     */
    private const ETATS_INSCRIPTION_LABELS = [
        ['value' => 'active', 'label' => 'Avec une inscription active'],
        ['value' => 'cancelled', 'label' => 'Inscription annulée ou archivée'],
        ['value' => 'none', 'label' => 'Sans inscription'],
    ];

    /**
     * Les libellés du filtre « Type » du relevé des encaissements, clé machine
     * => libellé français. Mêmes mots que la colonne « Type » du document :
     * filtrer sur « Avance » sort exactement les lignes que le PDF marque
     * « Avance ».
     *
     * @var list<array{value: string, label: string}>
     */
    private const TYPES_ENCAISSEMENT_LABELS = [
        ['value' => GetEncaissementsReport::TYPE_REGLEMENT, 'label' => 'Règlement'],
        ['value' => GetEncaissementsReport::TYPE_AVANCE, 'label' => 'Avance'],
    ];

    public function index(
        Request $request,
        GetInscriptionsReport $getInscriptionsReport,
        GetStudentsReport $getStudentsReport,
        GetEncaissementsReport $getEncaissementsReport,
    ): Response|RedirectResponse {
        abort_unless($request->user()->can('reports.view'), 403);

        // Même règle que EncaissementController / RecouvrementController : la
        // fenêtre de dates par défaut n'est posée que sur une PREMIÈRE visite
        // nue, comme UNE redirection vers l'URL canonique qui la porte
        // explicitement. Toute requête suivante lit les valeurs littérales
        // qu'elle envoie — sinon vider les dates puis changer un filtre
        // réappliquerait le défaut.
        if ($request->query() === []) {
            return redirect()->route('backoffice.rapports.index', [
                'dateFrom' => now()->subMonth()->toDateString(),
                'dateTo' => now()->toDateString(),
            ]);
        }

        $filters = $this->filters($request);

        // L'aperçu ne rapatrie PAS les lignes : sur une longue période un
        // rapport fait des milliers de lignes, qui n'ont aucune raison de
        // traverser Inertia pour afficher un compteur. Les lignes ne sont
        // lues que par le téléchargement, qui en a besoin.
        $lignes = match ($filters['rapport']) {
            GetInscriptionsReport::KEY => $getInscriptionsReport->count(
                $request->user(),
                $filters['dateFrom'],
                $filters['dateTo'],
                $filters['groupFilter'],
                $filters['statutFilter'],
            ),
            GetStudentsReport::KEY => $getStudentsReport->count(
                $request->user(),
                $filters['dateFrom'],
                $filters['dateTo'],
                $filters['sexeFilter'],
                $filters['inscriptionFilter'],
            ),
            GetEncaissementsReport::KEY => $getEncaissementsReport->count(
                $request->user(),
                $filters['dateFrom'],
                $filters['dateTo'],
                $filters['methodeFilter'],
                $filters['caisseFilter'],
                $filters['typeFilter'],
            ),
        };

        return Inertia::render('Backoffice/Rapports/Index', [
            'onglets' => RapportCatalogue::onglets(),
            'filters' => $filters,
            // Les filtres que la page doit dessiner pour le rapport choisi —
            // décidés par le catalogue serveur, jamais par le composant : la
            // page ne peut donc pas offrir un filtre que la requête Domain
            // n'applique pas (un filtre inerte trompe l'utilisateur, qui croit
            // avoir restreint son document).
            'filtresVisibles' => RapportCatalogue::filtres($filters['rapport']),
            // Closure : un rechargement partiel (only: [...]) déclenché par un
            // changement de filtre n'a pas à reconstruire le catalogue des
            // groupes (CLAUDE.md §17 « Heavy page props are closures »).
            'groupOptions' => fn () => $getInscriptionsReport->groupOptions($request->user()),
            'statutOptions' => array_map(
                static fn (string $s): array => ['value' => $s, 'label' => $s],
                Inscription::STATUTS,
            ),
            'sexeOptions' => array_map(
                static fn (string $s): array => ['value' => $s, 'label' => $s],
                Student::SEXES,
            ),
            // Mêmes clés machine et mêmes libellés que la liste Étudiants —
            // le rapport et la liste nomment le même filtre pareil.
            'inscriptionOptions' => self::ETATS_INSCRIPTION_LABELS,
            'methodeOptions' => array_map(
                static fn (string $m): array => ['value' => $m, 'label' => $m],
                Encaissement::METHODES,
            ),
            // Closure : un rechargement partiel déclenché par un changement de
            // filtre n'a pas à relire le catalogue des caisses.
            'caisseOptions' => fn () => $getEncaissementsReport->caisseOptions($request->user()),
            'typeOptions' => self::TYPES_ENCAISSEMENT_LABELS,
            'nombreLignes' => $lignes,
            // Le total n'est calculé QUE pour le rapport qui l'imprime : les
            // deux autres n'ont pas de colonne monétaire, et une chaîne vide
            // dit à la page de ne rien afficher plutôt que « 0,00 DH », qu'on
            // lirait comme « rien encaissé ».
            'montantTotal' => $filters['rapport'] === GetEncaissementsReport::KEY
                ? $getEncaissementsReport->total(
                    $request->user(),
                    $filters['dateFrom'],
                    $filters['dateTo'],
                    $filters['methodeFilter'],
                    $filters['caisseFilter'],
                    $filters['typeFilter'],
                )
                : '',
        ]);
    }

    /** Le PDF — mêmes filtres, mêmes lignes que l'aperçu. */
    public function pdf(
        Request $request,
        GetInscriptionsReport $getInscriptionsReport,
        GetStudentsReport $getStudentsReport,
        GetEncaissementsReport $getEncaissementsReport,
        RapportPdfRenderer $renderer,
        CurrentContext $context,
    ): \Symfony\Component\HttpFoundation\Response {
        abort_unless($request->user()->can('reports.view'), 403);

        $filters = $this->filters($request);
        $cle = $filters['rapport'];
        $lignes = $this->lignes($request, $getInscriptionsReport, $getStudentsReport, $getEncaissementsReport, $filters);

        $pdf = $renderer->render(
            RapportCatalogue::vuePdf($cle),
            $lignes,
            $renderer->entete(
                RapportCatalogue::titre($cle),
                // « Tous les centres » n'a pas d'en-tête de centre : le
                // document porterait l'identité d'un établissement alors qu'il
                // agrège plusieurs. On laisse null, le gabarit retombe sur
                // « GLS ».
                $context->etablissement(),
                $this->formatDate($filters['dateFrom']),
                $this->formatDate($filters['dateTo']),
                $this->filtresAppliques($filters),
                // Le « Total encaissé » du relevé — calculé en SQL sur tout
                // l'ensemble filtré, jamais réadditionné par le gabarit.
                $this->supplementsPdf($request, $getEncaissementsReport, $filters),
            ),
        );

        $filename = RapportCatalogue::filename($cle, 'pdf', $filters['dateFrom'], $filters['dateTo']);

        // `inline` et non `attachment` : l'utilisateur relit son document dans
        // l'onglet avant de l'imprimer, comme pour un reçu.
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /** Le classeur Excel — mêmes filtres, mêmes lignes que l'aperçu et que le PDF. */
    public function excel(
        Request $request,
        GetInscriptionsReport $getInscriptionsReport,
        GetStudentsReport $getStudentsReport,
        GetEncaissementsReport $getEncaissementsReport,
        ExporterRapportExcel $exporter,
        CurrentContext $context,
    ): StreamedResponse {
        abort_unless($request->user()->can('reports.view'), 403);

        $filters = $this->filters($request);
        $cle = $filters['rapport'];
        $lignes = $this->lignes($request, $getInscriptionsReport, $getStudentsReport, $getEncaissementsReport, $filters);

        // Les mêmes lignes de contexte que le PDF, dans le même ordre, et
        // construites depuis le MÊME dictionnaire : le classeur ne peut pas
        // rappeler un périmètre différent de celui du document.
        $sousTitres = ['Période : Du '.$this->formatDate($filters['dateFrom']).' au '.$this->formatDate($filters['dateTo'])];

        foreach ($this->filtresAppliques($filters) as $libelle => $valeur) {
            $sousTitres[] = $libelle.' : '.$valeur;
        }

        // Le total encaissé est rappelé dans le bloc d'en-tête du classeur :
        // il n'a pas de ligne de pied comme le PDF, et sans lui le lecteur
        // devrait faire la somme lui-même — donc obtenir un chiffre que rien
        // ne garantit égal à celui de l'écran.
        if ($cle === GetEncaissementsReport::KEY) {
            $sousTitres[] = 'Total encaissé : '.$getEncaissementsReport->total(
                $request->user(),
                $filters['dateFrom'],
                $filters['dateTo'],
                $filters['methodeFilter'],
                $filters['caisseFilter'],
                $filters['typeFilter'],
            );
        }

        return $exporter(
            RapportCatalogue::titre($cle),
            $context->etablissement(),
            $lignes,
            RapportCatalogue::colonnesExcel($cle),
            $sousTitres,
            RapportCatalogue::filename($cle, 'xlsx', $filters['dateFrom'], $filters['dateTo']),
        );
    }

    /**
     * Les lignes du rapport demandé. Le `match` est le point d'extension des
     * rapports suivants, et il refuse toute clé inconnue plutôt que de rendre
     * un document vide qui passerait pour « aucune donnée ».
     *
     * @param  array<string, string>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function lignes(
        Request $request,
        GetInscriptionsReport $getInscriptionsReport,
        GetStudentsReport $getStudentsReport,
        GetEncaissementsReport $getEncaissementsReport,
        array $filters,
    ): Collection {
        return match ($filters['rapport']) {
            GetInscriptionsReport::KEY => $getInscriptionsReport(
                $request->user(),
                $filters['dateFrom'],
                $filters['dateTo'],
                $filters['groupFilter'],
                $filters['statutFilter'],
            ),
            GetStudentsReport::KEY => $getStudentsReport(
                $request->user(),
                $filters['dateFrom'],
                $filters['dateTo'],
                $filters['sexeFilter'],
                $filters['inscriptionFilter'],
            ),
            GetEncaissementsReport::KEY => $getEncaissementsReport(
                $request->user(),
                $filters['dateFrom'],
                $filters['dateTo'],
                $filters['methodeFilter'],
                $filters['caisseFilter'],
                $filters['typeFilter'],
            ),
        };
    }

    /**
     * Ce qu'un rapport imprime EN PLUS du tronc commun de l'en-tête.
     *
     * Aujourd'hui le seul supplément est le « Total encaissé » du relevé, et
     * il n'est calculé que pour ce rapport : lancer la requête d'agrégat pour
     * un rapport qui n'a pas de colonne monétaire serait une requête pour
     * rien.
     *
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function supplementsPdf(
        Request $request,
        GetEncaissementsReport $getEncaissementsReport,
        array $filters,
    ): array {
        if ($filters['rapport'] !== GetEncaissementsReport::KEY) {
            return [];
        }

        return [
            'totalMontant' => $getEncaissementsReport->total(
                $request->user(),
                $filters['dateFrom'],
                $filters['dateTo'],
                $filters['methodeFilter'],
                $filters['caisseFilter'],
                $filters['typeFilter'],
            ),
        ];
    }

    /**
     * Les filtres RÉELLEMENT appliqués, nommés en français : libellé => valeur.
     *
     * Une seule construction pour le PDF et pour le classeur, donc les deux
     * documents rappellent le périmètre à l'identique — et un filtre laissé
     * vide n'y apparaît pas du tout, plutôt que d'écrire « Groupe : — » et de
     * laisser croire qu'un filtre a été posé.
     *
     * ⚠ Seuls les filtres que le rapport applique VRAIMENT sont listés
     * (RapportCatalogue::filtres()) : sans cette garde, une URL forgée portant
     * `groupFilter` sur le rapport des étudiants ferait imprimer « Groupe : … »
     * en tête d'un document que ce filtre n'a jamais restreint.
     *
     * @param  array<string, string>  $filters
     * @return array<string, string>
     */
    private function filtresAppliques(array $filters): array
    {
        $visibles = RapportCatalogue::filtres($filters['rapport']);
        $libelles = [];

        if (in_array('groupFilter', $visibles, true) && ($groupe = $this->groupLabel($filters['groupFilter'])) !== null) {
            $libelles['Groupe'] = $groupe;
        }

        if (in_array('statutFilter', $visibles, true) && $filters['statutFilter'] !== '') {
            $libelles['Statut'] = $filters['statutFilter'];
        }

        if (in_array('sexeFilter', $visibles, true) && $filters['sexeFilter'] !== '') {
            $libelles['Sexe'] = $filters['sexeFilter'];
        }

        if (in_array('inscriptionFilter', $visibles, true) && $filters['inscriptionFilter'] !== '') {
            $libelles["État d'inscription"] = $this->etatInscriptionLabel($filters['inscriptionFilter']);
        }

        if (in_array('methodeFilter', $visibles, true) && $filters['methodeFilter'] !== '') {
            $libelles['Méthode'] = $filters['methodeFilter'];
        }

        if (in_array('caisseFilter', $visibles, true) && ($caisse = $this->caisseLabel($filters['caisseFilter'])) !== null) {
            $libelles['Caisse'] = $caisse;
        }

        if (in_array('typeFilter', $visibles, true) && $filters['typeFilter'] !== '') {
            $libelles['Type'] = $this->typeEncaissementLabel($filters['typeFilter']);
        }

        return $libelles;
    }

    /**
     * Le libellé français d'un type d'encaissement — « avance » en clé machine
     * ne s'imprime pas en tête d'un document signé.
     */
    private function typeEncaissementLabel(string $type): string
    {
        foreach (self::TYPES_ENCAISSEMENT_LABELS as $option) {
            if ($option['value'] === $type) {
                return $option['label'];
            }
        }

        return $type;
    }

    /**
     * Le NOM de la caisse filtrée, pour le rappeler en tête du document. Lu
     * sans scoping supplémentaire, comme groupLabel() : la requête du rapport
     * a déjà borné les lignes, donc une caisse hors portée ne sort aucune
     * ligne et l'en-tête ne nommerait qu'un document vide.
     */
    private function caisseLabel(string $caisseFilter): ?string
    {
        if ($caisseFilter === '') {
            return null;
        }

        return Caisse::query()->whereKey((int) $caisseFilter)->value('nom');
    }

    /** Le libellé français d'un état d'inscription — la clé machine seule ne se lit pas. */
    private function etatInscriptionLabel(string $etat): string
    {
        foreach (self::ETATS_INSCRIPTION_LABELS as $option) {
            if ($option['value'] === $etat) {
                return $option['label'];
            }
        }

        return $etat;
    }

    /**
     * '-' est le marqueur « filtre vidé » que pose la page : Inertia retire
     * les chaînes vides de la query string, donc sans lui une date effacée
     * rendrait l'URL nue et le contrôleur y réinjecterait la fenêtre par
     * défaut — le filtre reviendrait tout seul (même garde que la page
     * Encaissements). Toute autre valeur est littérale.
     */
    private static function filterValue(mixed $value): string
    {
        $value = (string) $value;

        return $value === '-' ? '' : $value;
    }

    /**
     * Les filtres lus de la requête, chacun validé contre son domaine de
     * valeurs — une valeur forgée retombe sur le défaut plutôt que d'atteindre
     * la requête SQL.
     *
     * @return array<string, string>
     */
    private function filters(Request $request): array
    {
        $rapport = (string) $request->string('rapport');
        $statutFilter = (string) $request->string('statutFilter');
        $sexeFilter = (string) $request->string('sexeFilter');
        $inscriptionFilter = (string) $request->string('inscriptionFilter');
        $methodeFilter = (string) $request->string('methodeFilter');
        $typeFilter = (string) $request->string('typeFilter');

        return [
            'rapport' => in_array($rapport, RapportCatalogue::clesImplementees(), true)
                ? $rapport
                : GetInscriptionsReport::KEY,
            'groupFilter' => (string) $request->string('groupFilter'),
            'statutFilter' => in_array($statutFilter, Inscription::STATUTS, true) ? $statutFilter : '',
            'sexeFilter' => in_array($sexeFilter, Student::SEXES, true) ? $sexeFilter : '',
            'inscriptionFilter' => in_array($inscriptionFilter, GetStudentsReport::ETATS_INSCRIPTION, true)
                ? $inscriptionFilter
                : '',
            'methodeFilter' => in_array($methodeFilter, Encaissement::METHODES, true) ? $methodeFilter : '',
            'caisseFilter' => (string) $request->string('caisseFilter'),
            'typeFilter' => in_array($typeFilter, GetEncaissementsReport::TYPES, true) ? $typeFilter : '',
            'dateFrom' => self::filterValue($request->string('dateFrom')),
            'dateTo' => self::filterValue($request->string('dateTo')),
        ];
    }

    /**
     * Le NOM du groupe filtré, pour le rappeler en tête du document. Lu sans
     * scoping supplémentaire : la requête du rapport a déjà borné les lignes
     * au centre/à l'année, donc un id hors contexte ne sort aucune ligne — et
     * l'en-tête ne servirait alors qu'à nommer un groupe vide.
     */
    private function groupLabel(string $groupFilter): ?string
    {
        if ($groupFilter === '') {
            return null;
        }

        return Group::query()->whereKey((int) $groupFilter)->value('nom');
    }

    private function formatDate(string $date): string
    {
        return $date !== '' && ($parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date)) !== false
            ? $parsed->format('d/m/Y')
            : '—';
    }
}
