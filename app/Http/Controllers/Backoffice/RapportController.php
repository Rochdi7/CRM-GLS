<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Reports\Exports\ExporterRapportExcel;
use App\Domain\Reports\Exports\RapportPdfRenderer;
use App\Domain\Reports\Queries\GetInscriptionsReport;
use App\Domain\Reports\Support\RapportCatalogue;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Inscription;
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
 * (GetInscriptionsReport) : le document téléchargé ne peut donc pas différer
 * de ce que l'utilisateur vient de voir. C'est la règle que suit déjà
 * l'export « Absence par groupe ».
 *
 * Portée : un rapport n'a AUCUN privilège de lecture. `reports.view` ouvre
 * l'écran ; ce qu'il imprime reste borné par les « Centres affectés » et par
 * le contexte actif (centre + année) — la requête Domain applique les deux,
 * comme la liste Inscriptions.
 *
 * Le filtre Groupe est OPTIONNEL (demande métier) : vide, le rapport sort
 * toutes les inscriptions de la fenêtre de dates ; renseigné, il se limite à
 * ce groupe.
 */
final class RapportController extends Controller
{
    public function index(Request $request, GetInscriptionsReport $getInscriptionsReport): Response|RedirectResponse
    {
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
        $lignes = $getInscriptionsReport->count(
            $request->user(),
            $filters['dateFrom'],
            $filters['dateTo'],
            $filters['groupFilter'],
            $filters['statutFilter'],
        );

        return Inertia::render('Backoffice/Rapports/Index', [
            'onglets' => RapportCatalogue::onglets(),
            'filters' => $filters,
            // Closure : un rechargement partiel (only: [...]) déclenché par un
            // changement de filtre n'a pas à reconstruire le catalogue des
            // groupes (CLAUDE.md §17 « Heavy page props are closures »).
            'groupOptions' => fn () => $getInscriptionsReport->groupOptions($request->user()),
            'statutOptions' => array_map(
                static fn (string $s): array => ['value' => $s, 'label' => $s],
                Inscription::STATUTS,
            ),
            'nombreLignes' => $lignes,
        ]);
    }

    /** Le PDF — mêmes filtres, mêmes lignes que l'aperçu. */
    public function pdf(
        Request $request,
        GetInscriptionsReport $getInscriptionsReport,
        RapportPdfRenderer $renderer,
        CurrentContext $context,
    ): \Symfony\Component\HttpFoundation\Response {
        abort_unless($request->user()->can('reports.view'), 403);

        $filters = $this->filters($request);
        $cle = $filters['rapport'];
        $lignes = $this->lignes($request, $getInscriptionsReport, $filters);

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
                $this->groupLabel($filters['groupFilter']),
                $filters['statutFilter'] !== '' ? $filters['statutFilter'] : null,
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
        ExporterRapportExcel $exporter,
        CurrentContext $context,
    ): StreamedResponse {
        abort_unless($request->user()->can('reports.view'), 403);

        $filters = $this->filters($request);
        $cle = $filters['rapport'];
        $lignes = $this->lignes($request, $getInscriptionsReport, $filters);

        // Les mêmes lignes de contexte que le PDF, dans le même ordre : le
        // classeur dit de quel périmètre il parle, exactement comme le
        // document.
        $sousTitres = ['Période : Du '.$this->formatDate($filters['dateFrom']).' au '.$this->formatDate($filters['dateTo'])];

        if (($groupe = $this->groupLabel($filters['groupFilter'])) !== null) {
            $sousTitres[] = 'Groupe : '.$groupe;
        }

        if ($filters['statutFilter'] !== '') {
            $sousTitres[] = 'Statut : '.$filters['statutFilter'];
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
     * Les lignes du rapport demandé. Un seul rapport est implémenté ; le
     * `match` est le point d'extension pour les suivants, et il refuse toute
     * clé inconnue plutôt que de rendre un document vide qui passerait pour
     * « aucune donnée ».
     *
     * @param  array<string, string>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function lignes(Request $request, GetInscriptionsReport $getInscriptionsReport, array $filters): Collection
    {
        return match ($filters['rapport']) {
            GetInscriptionsReport::KEY => $getInscriptionsReport(
                $request->user(),
                $filters['dateFrom'],
                $filters['dateTo'],
                $filters['groupFilter'],
                $filters['statutFilter'],
            ),
        };
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

        return [
            'rapport' => in_array($rapport, RapportCatalogue::clesImplementees(), true)
                ? $rapport
                : GetInscriptionsReport::KEY,
            'groupFilter' => (string) $request->string('groupFilter'),
            'statutFilter' => in_array($statutFilter, Inscription::STATUTS, true) ? $statutFilter : '',
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
