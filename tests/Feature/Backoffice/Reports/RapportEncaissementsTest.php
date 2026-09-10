<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Reports;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Gestion des rapports — « Relevé des encaissements » (onglet Finance &
 * Paiements) : la page, ses filtres, et les deux téléchargements.
 *
 * Le test le plus important du fichier est
 * test_an_avance_is_printed_and_reads_as_an_avance : une avance est de
 * l'argent REÇU, simplement pas encore affecté à un frais. L'omettre ferait
 * un relevé dont le total est inférieur à ce que la caisse a encaissé — signé
 * et tamponné comme s'il était complet.
 *
 * Son pendant est
 * test_an_advance_application_row_is_never_printed : la ligne qui REPOSE une
 * avance sur un frais ne fait bouger aucune caisse, donc l'imprimer
 * compterait le même dirham deux fois.
 */
final class RapportEncaissementsTest extends TestCase
{
    use RefreshDatabase;

    private const CLE = 'releve-encaissements';

    /** Compteurs pour rester dans les varchar de référence. */
    private static int $refSeq = 0;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    private Group $group;

    private Caisse $caisse;

    private Employee $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();
        $this->group = Group::factory()->create([
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);
        $this->caisse = Caisse::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();

        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    /** @return array<string, string> */
    private function window(array $extra = []): array
    {
        return [
            'rapport' => self::CLE,
            'dateFrom' => '2025-09-01',
            'dateTo' => '2026-08-31',
            ...$extra,
        ];
    }

    private function student(string $prenom): Student
    {
        return Student::factory()->create([
            'prenom' => $prenom,
            'nom' => 'Test',
            'etablissement_id' => $this->centre->id,
        ]);
    }

    private function inscription(Student $student): Inscription
    {
        return Inscription::create([
            'reference' => 'INS-R'.(++self::$refSeq),
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2025-09-15',
        ]);
    }

    private function fee(Inscription $inscription, float $montant, string $nom = "Frais d'inscription"): InscriptionFee
    {
        $frais = Frais::firstOrCreate(['nom' => $nom], ['statut' => 'Actif']);

        return InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'frais_id' => $frais->id,
            'nom' => $nom,
            'montant_initial' => $montant,
            'montant' => $montant,
            'date_echeance' => '2026-01-01',
            'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
    }

    /** Un règlement ordinaire : de l'argent posé sur un frais. */
    private function reglement(Student $student, InscriptionFee $fee, float $montant, string $date = '2025-10-01'): Encaissement
    {
        return Encaissement::create([
            'reference' => 'ENC-R'.(++self::$refSeq),
            'student_id' => $student->id,
            'inscription_fee_id' => $fee->id,
            'caisse_id' => $this->caisse->id,
            'agent_id' => $this->agent->id,
            'montant' => $montant,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => $date,
        ]);
    }

    /** Une avance : de l'argent reçu, sans frais attaché. */
    private function avance(Student $student, float $montant, string $date = '2025-10-02'): Encaissement
    {
        return Encaissement::create([
            'reference' => 'ENC-A'.(++self::$refSeq),
            'student_id' => $student->id,
            'inscription_fee_id' => null,
            'caisse_id' => $this->caisse->id,
            'agent_id' => $this->agent->id,
            'montant' => $montant,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => $date,
        ]);
    }

    // ─────────────────────────── Accès ───────────────────────────

    public function test_it_requires_the_reports_permission(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertForbidden();
    }

    public function test_downloads_require_the_reports_permission_too(): void
    {
        $user = $this->userWith('dashboard.view');

        $this->actingAs($user)->get(route('backoffice.rapports.pdf', $this->window()))->assertForbidden();
        $this->actingAs($user)->get(route('backoffice.rapports.excel', $this->window()))->assertForbidden();
    }

    // ─────────────────────── Le rapport existe ───────────────────────

    /**
     * L'onglet Finance & Paiements sert désormais un rapport : le sélecteur ne
     * peut proposer que ce que le serveur sert (RapportCatalogue), donc c'est
     * le catalogue qui doit le porter.
     */
    public function test_the_finance_tab_offers_the_payments_report(): void
    {
        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.rapport', self::CLE)
                ->where('onglets.2.key', 'finance')
                ->where('onglets.2.rapports.0.value', self::CLE));
    }

    /** Les filtres dessinés sont décidés par le serveur, jamais par le composant. */
    public function test_it_exposes_its_own_filters(): void
    {
        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filtresVisibles', ['methodeFilter', 'caisseFilter', 'typeFilter']));
    }

    // ─────────────────────── Les avances (le cœur) ───────────────────────

    /**
     * ⚠ LE test du fichier. Une avance est de l'argent reçu : elle est
     * imprimée, et sa colonne « Type » la nomme « Avance » — sinon un lecteur
     * la prendrait pour un règlement, ou ne la verrait pas du tout.
     */
    public function test_an_avance_is_printed_and_reads_as_an_avance(): void
    {
        $student = $this->student('Salma');
        $this->avance($student, 1200.00);

        $lignes = $this->lignes();

        $this->assertCount(1, $lignes, "L'avance doit figurer au relevé : c'est de l'argent reçu.");
        $this->assertSame('Avance', $lignes[0]['type']);
        // La colonne « Frais » ne peut rien nommer : elle le DIT, plutôt que
        // de laisser un blanc qu'on lirait comme une donnée manquante.
        $this->assertSame('Avance', $lignes[0]['frais']);
    }

    /** Un règlement ordinaire se lit « Règlement » et nomme son frais. */
    public function test_an_ordinary_payment_reads_as_a_settlement(): void
    {
        $student = $this->student('Mourad');
        $fee = $this->fee($this->inscription($student), 300.00);
        $this->reglement($student, $fee, 300.00);

        $lignes = $this->lignes();

        $this->assertCount(1, $lignes);
        $this->assertSame('Règlement', $lignes[0]['type']);
        $this->assertSame("Frais d'inscription", $lignes[0]['frais']);
    }

    /**
     * ⚠ Le pendant de l'avance. La ligne qui REPOSE une avance sur un frais ne
     * fait bouger aucune caisse : l'imprimer compterait deux fois le même
     * dirham (1 200 d'avance + 1 200 appliqués = 2 400 au bas d'un relevé qui
     * n'a encaissé que 1 200).
     */
    public function test_an_advance_application_row_is_never_printed(): void
    {
        $student = $this->student('Ibtissame');
        $inscription = $this->inscription($student);
        $fee = $this->fee($inscription, 1200.00);

        $avance = $this->avance($student, 1200.00);

        // La ligne d'application : elle porte un frais ET un parent.
        Encaissement::create([
            'reference' => 'ENC-AP'.(++self::$refSeq),
            'student_id' => $student->id,
            'inscription_fee_id' => $fee->id,
            'applied_from_encaissement_id' => $avance->id,
            'caisse_id' => $this->caisse->id,
            'agent_id' => $this->agent->id,
            'montant' => 1200.00,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2025-10-05',
        ]);

        $lignes = $this->lignes();

        $this->assertCount(1, $lignes, "Seule l'avance parente est un encaissement.");
        $this->assertSame('Avance', $lignes[0]['type']);

        // Et le total ne double pas.
        $this->assertSame('1 200,00 DH', $this->montantTotal());
    }

    /**
     * Le total du document est l'argent RÉELLEMENT entré en caisse : la somme
     * des lignes imprimées, sans les applications d'avance.
     */
    public function test_the_total_is_the_money_actually_received(): void
    {
        $salma = $this->student('Salma');
        $fee = $this->fee($this->inscription($salma), 300.00);
        $this->reglement($salma, $fee, 300.00);
        $this->avance($this->student('Loubna'), 1200.00);

        $this->assertSame('1 500,00 DH', $this->montantTotal());
    }

    // ─────────────────────────── Filtres ───────────────────────────

    /** Le filtre « Type » sort exactement les lignes que le document marque ainsi. */
    public function test_the_type_filter_isolates_advances(): void
    {
        $salma = $this->student('Salma');
        $fee = $this->fee($this->inscription($salma), 300.00);
        $this->reglement($salma, $fee, 300.00);
        $this->avance($this->student('Loubna'), 1200.00);

        $avances = $this->lignes(['typeFilter' => 'avance']);
        $this->assertCount(1, $avances);
        $this->assertSame('Avance', $avances[0]['type']);

        $reglements = $this->lignes(['typeFilter' => 'reglement']);
        $this->assertCount(1, $reglements);
        $this->assertSame('Règlement', $reglements[0]['type']);
    }

    public function test_the_method_filter_restricts_the_document(): void
    {
        $student = $this->student('Almahdi');
        $this->avance($student, 500.00);

        Encaissement::create([
            'reference' => 'ENC-V'.(++self::$refSeq),
            'student_id' => $student->id,
            'caisse_id' => $this->caisse->id,
            'agent_id' => $this->agent->id,
            'montant' => 700.00,
            'methode' => Encaissement::METHODE_VIREMENT,
            'date_paiement' => '2025-10-03',
        ]);

        $this->assertCount(2, $this->lignes());
        $this->assertCount(1, $this->lignes(['methodeFilter' => Encaissement::METHODE_VIREMENT]));
    }

    /**
     * La fenêtre de dates borne le document. Une avance y reste soumise — ce
     * qui l'exempte, c'est la fenêtre d'ANNÉE (elle n'a pas de frais, donc pas
     * d'année), pas le filtre explicite de l'utilisateur.
     */
    public function test_the_date_window_bounds_the_document(): void
    {
        $this->avance($this->student('Btissame'), 300.00, '2025-10-10');

        $this->assertCount(1, $this->lignes(['dateFrom' => '2025-10-01', 'dateTo' => '2025-10-31']));
        $this->assertCount(0, $this->lignes(['dateFrom' => '2025-11-01', 'dateTo' => '2025-11-30']));
    }

    /**
     * ⚠ Une avance n'a pas d'année (pas de frais, donc pas d'inscription) :
     * elle reste au relevé quelle que soit l'année active, comme dans la liste
     * Encaissements. La masquer ferait disparaître d'un document signé de
     * l'argent bien réel que personne n'a encore affecté.
     */
    public function test_an_avance_survives_the_year_window(): void
    {
        // Une seconde année, ACTIVE, à laquelle l'avance n'appartient pas.
        $autre = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => false, 'inscription_ouverte' => true,
        ]);

        $this->avance($this->student('Razane'), 1200.00, '2025-10-02');

        $user = $this->userWith('reports.view');

        $this->actingAs($user)->post(route('backoffice.context.update'), [
            'annee_scolaire_id' => $autre->id,
        ]);

        $this->assertCount(1, $this->lignes(as: $user), "Une avance n'a pas d'année : elle reste au relevé.");
    }

    // ─────────────────────────── Documents ───────────────────────────

    public function test_it_downloads_a_pdf(): void
    {
        $this->avance($this->student('Salma'), 1200.00);

        $response = $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.pdf', $this->window()));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        // Réponse ordinaire (le contenu binaire est construit en mémoire par
        // mPDF), pas un flux : on lit donc getContent() directement.
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /**
     * ⚠ La MISE EN PAGE du document, verrouillée sur le PDF réel.
     *
     * Trois choses se sont cassées en une seule fois le 10/09/2026, et aucune
     * n'apparaît dans un test qui vérifie seulement « c'est bien un PDF » :
     *
     *  1. **Les largeurs de colonnes doivent totaliser 100 %.** À 106 %, la
     *     grille est incohérente (mPDF renormalise, mais le gabarit ment sur
     *     ce qu'il demande, et la colonne qui déborde change au fil des
     *     ajouts).
     *  2. **Le tableau tient sur UNE page pour un lot ordinaire.** Le vrai
     *     symptôme était là : « Frais d'inscription A1/A2/B1 » passait à la
     *     ligne dans une colonne trop étroite, chaque ligne du tableau faisait
     *     deux lignes de haut, et le document débordait sur une 2ᵉ page dès 15
     *     lignes. Le logo se redessinant sur chaque page (c'est normal), la
     *     2ᵉ page donnait l'illusion d'un logo en double.
     *  3. **Pas de `<tfoot>`.** mPDF met le pied de tableau en tampon ; le
     *     total est donc la dernière ligne du `<tbody>`.
     */
    public function test_the_document_layout_stays_on_one_page(): void
    {
        $gabarit = file_get_contents(resource_path('views/backoffice/rapports/encaissements-pdf.blade.php'));

        preg_match_all('/width:(\d+)%/', (string) $gabarit, $largeurs);
        $this->assertSame(
            100,
            array_sum(array_map('intval', $largeurs[1])),
            'Les largeurs de colonnes doivent totaliser exactement 100 %.',
        );

        // Le total est une ligne du <tbody> : un <tfoot> fait rejouer
        // l'en-tête de page par mPDF (logo dessiné deux fois).
        //
        // On retire les commentaires Blade avant de chercher la balise — le
        // gabarit EXPLIQUE justement pourquoi il n'en a pas, et le mot y
        // figure donc en toutes lettres.
        $sansCommentaires = preg_replace('/\{\{--.*?--\}\}/s', '', (string) $gabarit);
        $this->assertStringNotContainsString('<tfoot', (string) $sansCommentaires);

        // 15 lignes = un lot ordinaire (une journée de caisse). Le document
        // de référence en tient une vingtaine par page ; deux pages ici
        // signifient que des cellules repassent à la ligne.
        $student = $this->student('Salma');
        $inscription = $this->inscription($student);

        for ($i = 0; $i < 15; $i++) {
            // Le libellé le plus long des données réelles (28 caractères) :
            // c'est lui qui faisait doubler la hauteur des lignes.
            $fee = $this->fee($inscription, 300.00, "Frais d'inscription A1/A2/B1 {$i}");
            $this->reglement($student, $fee, 300.00);
        }

        $pdf = app(\App\Domain\Reports\Exports\RapportPdfRenderer::class)->render(
            'backoffice.rapports.encaissements-pdf',
            app(\App\Domain\Reports\Queries\GetEncaissementsReport::class)(
                $this->userWith('reports.view'), '2025-09-01', '2026-08-31',
            ),
            app(\App\Domain\Reports\Exports\RapportPdfRenderer::class)->entete(
                'Relevé des Encaissements',
                $this->centre,
                '01/09/2025',
                '31/08/2026',
                [],
                ['totalMontant' => '4 500,00 DH'],
            ),
        );

        $this->assertSame(
            1,
            preg_match_all('#/Type\s*/Page[^s]#', $pdf),
            '15 lignes doivent tenir sur UNE page : au-delà, des cellules repassent à la ligne.',
        );
    }

    public function test_it_downloads_an_excel_workbook(): void
    {
        $this->avance($this->student('Salma'), 1200.00);

        $response = $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.excel', $this->window()));

        $response->assertOk();
        $this->assertStringContainsString('releve-encaissements', $response->headers->get('content-disposition') ?? '');
    }

    /**
     * Le compteur de l'écran et le document partent de la MÊME requête Domain :
     * ce que l'utilisateur voit avant de cliquer est ce qu'il télécharge.
     */
    public function test_the_screen_count_matches_the_document(): void
    {
        $salma = $this->student('Salma');
        $fee = $this->fee($this->inscription($salma), 300.00);
        $this->reglement($salma, $fee, 300.00);
        $this->avance($this->student('Loubna'), 1200.00);

        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', $this->window()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('nombreLignes', 2)
                ->where('montantTotal', '1 500,00 DH'));

        $this->assertCount(2, $this->lignes());
    }

    /**
     * Un autre rapport n'affiche pas de total : il n'a pas de colonne
     * monétaire, et « 0,00 DH » se lirait comme « rien encaissé ».
     */
    public function test_other_reports_carry_no_total(): void
    {
        $this->actingAs($this->userWith('reports.view'))
            ->get(route('backoffice.rapports.index', ['rapport' => 'liste-inscriptions', 'dateFrom' => '2025-09-01', 'dateTo' => '2026-08-31']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('montantTotal', ''));
    }

    // ─────────────────────────── Portée ───────────────────────────

    /** Un rapport n'a aucun privilège : il n'imprime que les centres atteints. */
    public function test_it_never_prints_a_centre_the_user_cannot_reach(): void
    {
        $autreCentre = Etablissement::factory()->create();
        $etranger = Student::factory()->create(['etablissement_id' => $autreCentre->id]);
        $this->avance($etranger, 999.00);

        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'etablissement_id' => $this->centre->id,
        ]);
        $employee->syncEtablissements([$this->centre->id]);

        $this->assertCount(0, $this->lignes(as: $user->fresh()));
    }

    // ─────────────────────────── Utilitaires ───────────────────────────

    /**
     * Les lignes RÉELLES du document, lues via la requête Domain — la même que
     * celle qui sert le PDF et le classeur.
     *
     * @return array<int, array<string, mixed>>
     */
    private function lignes(array $extra = [], ?User $as = null): array
    {
        $user = $as ?? $this->userWith('reports.view');
        $filters = $this->window($extra);

        return $this->actingAs($user)->app
            ->make(\App\Domain\Reports\Queries\GetEncaissementsReport::class)(
                $user,
                $filters['dateFrom'],
                $filters['dateTo'],
                $filters['methodeFilter'] ?? '',
                $filters['caisseFilter'] ?? '',
                $filters['typeFilter'] ?? '',
            )->values()->all();
    }

    private function montantTotal(array $extra = []): string
    {
        $user = $this->userWith('reports.view');
        $filters = $this->window($extra);

        return $this->actingAs($user)->app
            ->make(\App\Domain\Reports\Queries\GetEncaissementsReport::class)
            ->total(
                $user,
                $filters['dateFrom'],
                $filters['dateTo'],
                $filters['methodeFilter'] ?? '',
                $filters['caisseFilter'] ?? '',
                $filters['typeFilter'] ?? '',
            );
    }
}
