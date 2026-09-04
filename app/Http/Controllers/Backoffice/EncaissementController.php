<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Domain\Payments\Actions\DetacherEncaissementDuFrais;
use App\Domain\Payments\Actions\CorrigerMontantEncaissement;
use App\Domain\Payments\Actions\RequalifierMethodeEncaissement;
use App\Domain\Finance\Support\CaisseResolver;
use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Domain\Payments\Mail\EncaissementRecuMail;
use App\Domain\Payments\Support\RecuPdfRenderer;
use App\Domain\Payments\Support\SituationFraisRecu;
use App\Domain\Payments\Support\RecuWhatsAppLink;
use App\Domain\Payments\Actions\SupprimerEncaissement;
use App\Domain\Payments\Queries\GetEncaissementDetails;
use App\Domain\Payments\Queries\GetEncaissementsList;
use App\Domain\Payments\Queries\GetInscriptionPayments;
use App\Domain\Payments\Queries\GetInscriptionUnpaidFees;
use App\Domain\Settings\Queries\GetBanquesList;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Controllers\Backoffice\Concerns\RedirectsPreservingFilters;
use App\Http\Requests\Backoffice\Encaissements\ApplyAvanceRequest;
use App\Http\Requests\Backoffice\Encaissements\ConvertAvanceRequest;
use App\Http\Requests\Backoffice\Encaissements\SendRecuEmailRequest;
use App\Http\Requests\Backoffice\Encaissements\StoreAvanceRequest;
use App\Http\Requests\Backoffice\Encaissements\StoreEncaissementRequest;
use App\Http\Requests\Backoffice\Encaissements\UpdateEncaissementRequest;
use App\Models\Cheque;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Services\Authorization\CenterAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Payments list + create/edit with the cascading multi-row payment form
 * (Phase 10, docs/phase-10-finance-audit.md §2.4) — mirrors
 * EncaissementsIndex one-for-one, including the multi-row single-submit
 * DB::transaction (an invalid row rolls back every row already processed in
 * this submit) and the per-row `fee->inscription_id === inscription_id`
 * ownership check. No destroy(): a recorded payment is never deleted —
 * corrections go through a remboursement + new encaissement.
 */
final class EncaissementController extends Controller
{
    use AssertsContextScope;
    use RedirectsPreservingFilters;

    public function __construct()
    {
        $this->authorizeResource(Encaissement::class, 'encaissement');
    }

    /** '-' means the user cleared this filter; anything else is a literal value. */
    private static function filterValue(mixed $value): string
    {
        $value = (string) $value;

        return $value === '-' ? '' : $value;
    }

    public function index(
        Request $request,
        GetEncaissementsList $getEncaissementsList,
        GetBanquesList $getBanquesList,
    ): Response|RedirectResponse {
        // The today-default date window applies ONLY to a bare first visit
        // (no query string at all — the sidebar link), as ONE redirect to the
        // canonical URL carrying it explicitly. Every later request then
        // reads the LITERAL values it was sent — keying the default on
        // has('dateFrom') meant any request that lost the key (a stale
        // ?page=2 pagination link, a hard reload) silently re-injected
        // today's date after the user had cleared the filter (26/08/2026).
        // ⚠ Only a TRULY bare visit redirects, and never a partial reload.
        // `route()` drops empty-string parameters, so the page's own
        // reload({dateFrom: '', dateTo: ''}) arrives with no date keys at
        // all: clearing both dates while every other filter was empty looked
        // exactly like a first visit, so the controller redirected and
        // re-injected today — the very thing the canonical URL exists to
        // prevent. Worse, Inertia follows that redirect as a fresh FULL
        // visit, dropping the X-Inertia-Partial-Data header, so the rows and
        // the total came from two different requests and disagreed on screen
        // (27/08/2026).
        //
        // `X-Inertia-Partial-Data` marks a reload driven by the page itself,
        // which always sends the full filter set — an absent key there means
        // "cleared", never "unset".
        if ($request->query() === [] && ! $request->hasHeader('X-Inertia-Partial-Data')) {
            return redirect()->route('backoffice.encaissements.index', [
                'dateFrom' => now()->toDateString(),
                'dateTo' => now()->toDateString(),
            ]);
        }

        $search = (string) $request->string('search');
        $caisseFilter = (string) $request->string('caisseFilter');
        $methodeFilter = (string) $request->string('methodeFilter');
        // '-' is the page's explicit "cleared" marker (see reload() in
        // Encaissements/Index.tsx): Inertia omits empty strings from the
        // query string, so a cleared date would otherwise be indistinguishable
        // from one that was never set.
        $dateFrom = self::filterValue($request->string('dateFrom'));
        $dateTo = self::filterValue($request->string('dateTo'));
        $referenceFilter = (string) $request->string('referenceFilter');
        $studentFilter = (string) $request->string('studentFilter');
        $groupFilter = (string) $request->string('groupFilter');
        $fraisFilter = (string) $request->string('fraisFilter');
        $numeroChequeFilter = (string) $request->string('numeroChequeFilter');
        $banqueFilter = (string) $request->string('banqueFilter');
        $perPage = (int) $request->integer('perPage', GetEncaissementsList::DEFAULT_PER_PAGE);
        // Page view tabs: Paiements (fee-allocated rows) / Avances / Chèques.
        $view = (string) $request->string('view');
        if (! in_array($view, ['', 'avance', 'cheque'], true)) {
            $view = '';
        }
        // Avances tab only: 'restant' (default — avances with money still to
        // allocate) | 'epuise' (fully used) | 'tous'.
        $soldeFilter = (string) $request->string('soldeFilter');
        if (! in_array($soldeFilter, ['restant', 'epuise', 'tous'], true)) {
            $soldeFilter = 'restant';
        }

        $encaissementsList = $getEncaissementsList(
            $request->user(),
            $search,
            $caisseFilter,
            $methodeFilter,
            $dateFrom,
            $dateTo,
            $perPage,
            $view,
            $referenceFilter,
            $studentFilter,
            $numeroChequeFilter,
            $banqueFilter,
            $soldeFilter,
            $groupFilter,
            $fraisFilter,
        );

        return Inertia::render('Backoffice/Encaissements/Index', [
            'encaissements' => $encaissementsList['data'],
            'montantTotal' => $encaissementsList['montantTotal'],
            // Closures: the page reloads on every search/filter/page change
            // with `only: ['encaissements', 'filters']`, so these option
            // lists (every student of the centre!) are computed on the first
            // visit only, never again per keystroke.
            'caisses' => fn () => $getEncaissementsList->caisseOptions($request->user()),
            'students' => fn () => $getEncaissementsList->studentOptions($request->user()),
            'groups' => fn () => $getEncaissementsList->groupOptions($request->user()),
            'frais' => fn () => $getEncaissementsList->fraisOptions(),
            'methodes' => Encaissement::METHODES,
            'banques' => fn () => $getBanquesList->activeNames(),
            'filters' => [
                'search' => $search,
                'caisseFilter' => $caisseFilter,
                'methodeFilter' => $methodeFilter,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'perPage' => $perPage,
                'view' => $view,
                'referenceFilter' => $referenceFilter,
                'studentFilter' => $studentFilter,
                'numeroChequeFilter' => $numeroChequeFilter,
                'banqueFilter' => $banqueFilter,
                'soldeFilter' => $soldeFilter,
                'groupFilter' => $groupFilter,
                'fraisFilter' => $fraisFilter,
            ],
            // UI convenience only — destroy() re-authorizes server-side and the
            // route itself is behind permission:payments.delete.
            'can' => [
                // Permission only (not the policy): the policy's delete() needs a
                // concrete row for its center check, which destroy() applies
                // per-record. Super-admin passes via Gate::before.
                'delete' => $request->user()?->can('payments.delete') ?? false,
                // Re-dating a payment is super-admin only (30/08/2026) — the
                // edit modal disables the Date field without it. UI
                // convenience only: update() drops the field server-side.
                'updateDate' => $request->user()?->can('payments.update-date') ?? false,
                // Requalifier la méthode déplace l'argent entre la caisse
                // physique et le compte de méthode du centre : rôles de
                // direction + super-admin (01/09/2026). Confort d'interface
                // seulement — update() refuse une méthode différente sans la
                // permission.
                'updateMethode' => $request->user()?->can('payments.update-method') ?? false,
                // Corriger le montant deplace l'ecart sur la caisse D'ORIGINE
                // de la ligne (celle de l'employe qui a encaisse, jamais celle
                // du correcteur) : super-admin uniquement (02/09/2026).
                // Confort d'interface seulement — update() refuse un montant
                // different sans la permission.
                'updateAmount' => $request->user()?->can('payments.update-amount') ?? false,
            ],
        ]);
    }

    /**
     * Seule une inscription ACTIVE peut recevoir un encaissement.
     *
     * Une inscription Annulée / Archivée / Expirée / Changement n'a plus de
     * frais dus : encaisser dessus rouvrirait un dossier clos et ferait
     * réapparaître son reliquat dans les impayés et le récapitulatif annuel.
     * L'argent reçu pour un tel dossier s'enregistre en AVANCE, puis
     * s'applique à une inscription active (AppliquerAvance) — la correction
     * par écriture compensatoire, jamais par réécriture (§11).
     *
     * Le dropdown est déjà filtré par GetEncaissementsList::studentInscriptions(),
     * mais un id forgé ou une liste chargée avant l'annulation doit être
     * refusée ici : le filtre client n'est qu'un confort d'interface (§5).
     */
    private function assertInscriptionPayable(Inscription $inscription, string $field = 'inscription_id'): void
    {
        if ($inscription->statut === Inscription::STATUT_ACTIVE) {
            return;
        }

        throw ValidationException::withMessages([
            $field => __('This registration is no longer active — record the money as an advance instead.'),
        ]);
    }

    /**
     * Cascade step 1→2 lookup: a student's registrations, then (via
     * inscriptionFees()) one row per unpaid fee — mirrors
     * EncaissementsIndex::updatedStudentId()/updatedInscriptionId().
     */
    public function studentInscriptions(Request $request, int $student, GetEncaissementsList $getEncaissementsList): JsonResponse
    {
        $this->authorize('create', Encaissement::class);
        // Center scope: a cashier only lists registrations of students
        // within the centers they can access.
        $this->assertCenterAccess($request, Student::query()->findOrFail($student)->etablissement_id);

        return response()->json(['inscriptions' => $getEncaissementsList->studentInscriptions($student)]);
    }

    /**
     * "Convertir en avance" cascade step 1 — like studentInscriptions() but
     * WITHOUT the statut=Active filter: converting frees the money of a
     * closed dossier (changement de groupe, annulation, année terminée), so
     * the closed inscriptions are exactly the ones this modal must list.
     * Still scoped to the active year: to convert last year's payments the
     * cashier switches the top-bar to that year (which convertAvance()'s
     * assertInscriptionInContext() requires anyway), then applies the
     * resulting avance after switching back to the new year.
     */
    public function studentInscriptionsForConversion(Request $request, int $student, GetEncaissementsList $getEncaissementsList): JsonResponse
    {
        $this->authorize('create', Encaissement::class);
        $this->assertCenterAccess($request, Student::query()->findOrFail($student)->etablissement_id);

        return response()->json(['inscriptions' => $getEncaissementsList->studentInscriptionsForConversion($student)]);
    }

    public function inscriptionFees(Request $request, Inscription $inscription, GetInscriptionUnpaidFees $getInscriptionUnpaidFees): JsonResponse
    {
        $this->authorize('create', Encaissement::class);
        $this->assertCenterAccess($request, $inscription->etablissement_id);

        return response()->json(['fees' => $getInscriptionUnpaidFees($inscription)]);
    }

    /**
     * Every fee-attached payment of one inscription — the "Convertir en
     * avance" modal's checklist source.
     */
    public function inscriptionPayments(Request $request, Inscription $inscription, GetInscriptionPayments $getInscriptionPayments): JsonResponse
    {
        $this->authorize('create', Encaissement::class);
        $this->assertCenterAccess($request, $inscription->etablissement_id);

        return response()->json(['payments' => $getInscriptionPayments($inscription)]);
    }

    public function store(StoreEncaissementRequest $request, EnregistrerEncaissement $action): RedirectResponse
    {
        $agent = $request->user()->employee;

        if ($agent === null) {
            throw ValidationException::withMessages([
                'payment_lines' => __('Your account is not linked to any employee record.'),
            ]);
        }

        // The account is NEVER chosen client-side, for any role (the modal
        // shows no caisse field): each line's `methode` decides it —
        // Espèces → the agent's own till, TPE/Chèque/Virement → the active
        // centre's method account (CaisseResolver).
        $resolver = app(CaisseResolver::class);

        $data = $request->validated();
        $inscriptionId = (int) $data['inscription_id'];

        // The registration must be the selected student's own (a tampered
        // inscription_id would record student A paying student B's fees —
        // wrong on both students' pages and receipts) and within the
        // cashier's centers.
        $inscription = Inscription::query()->findOrFail($inscriptionId);

        if ($inscription->student_id !== (int) $data['student_id']) {
            throw ValidationException::withMessages([
                'inscription_id' => __('This registration does not belong to the selected student.'),
            ]);
        }

        // Centre reach + ACTIVE context (centre + année): the inscription
        // being paid decides the payment's year on every list, so paying a
        // registration of another year/centre from here would file the
        // money where the current screen never shows it (AssertsContextScope).
        $this->assertInscriptionInContext($request, $inscription);
        $this->assertInscriptionPayable($inscription);

        $touchedLines = collect($data['payment_lines'])->filter(fn ($l) => ($l['montant'] ?? '') !== '');

        // Every Chèque-method row must reference a tracked chèque (Chèques
        // module, no manual numéro/banque/échéance entry anymore) — numero/
        // banque/échéance are always read off that Cheque row. Two rows can
        // reference two different chèques, so cheques are loaded and their
        // per-cheque totals validated up front, before anything is written.
        $chequeIds = $touchedLines
            ->filter(fn ($l) => $l['methode'] === Encaissement::METHODE_CHEQUE)
            ->pluck('cheque_id')
            ->unique();

        // Per-line amounts targeting the SAME fee are summed, so two rows
        // for one fee can't each pass the per-row cap independently.
        $parFee = $touchedLines
            ->groupBy(fn ($l) => (int) $l['fee_id'])
            ->map(fn ($lines) => round((float) $lines->sum(fn ($l) => (float) $l['montant']), 2));

        DB::transaction(function () use ($touchedLines, $data, $inscriptionId, $agent, $resolver, $action, $chequeIds, $parFee): void {
            // Cheques are re-read under a row lock INSIDE the transaction and
            // their remaining balance re-checked here: a double submit (or two
            // cashiers) would otherwise both see the full balance and spend
            // the same cheque twice.
            $cheques = Cheque::query()->whereKey($chequeIds->all())->lockForUpdate()->get()->keyBy('id');

            foreach ($chequeIds as $chequeId) {
                $cheque = $cheques[$chequeId] ?? null;

                if ($cheque === null) {
                    throw ValidationException::withMessages([
                        'payment_lines' => __('Select a recorded cheque to pay with.'),
                    ]);
                }

                if ($cheque->student_id !== (int) $data['student_id']) {
                    throw ValidationException::withMessages([
                        'payment_lines' => __('This cheque does not belong to the selected student.'),
                    ]);
                }

                if ($cheque->statut === Cheque::STATUT_REJETE) {
                    throw ValidationException::withMessages([
                        'payment_lines' => __('A rejected cheque cannot be used to pay.'),
                    ]);
                }

                $chequeTotal = round((float) $touchedLines
                    ->filter(fn ($l) => $l['methode'] === Encaissement::METHODE_CHEQUE && (int) $l['cheque_id'] === $chequeId)
                    ->sum(fn ($l) => (float) $l['montant']), 2);

                if ($chequeTotal > $cheque->montantRestant()) {
                    throw ValidationException::withMessages([
                        'payment_lines' => __("The amount cannot exceed the cheque's remaining balance."),
                    ]);
                }
            }

            // Same for the fees: the Form Request's max:reste was computed
            // when the rules were built; re-check under lock so a concurrent
            // payment on the same fee can't push it past its montant.
            foreach ($parFee as $feeId => $total) {
                $lockedFee = InscriptionFee::query()->whereKey($feeId)->lockForUpdate()->firstOrFail();

                // A hidden line (fee removed from the group, or exempted on
                // this registration) is not owed: refuse under the same lock
                // so a form loaded before the removal cannot pay it (R-01).
                if ($lockedFee->estMasque()) {
                    throw ValidationException::withMessages([
                        'payment_lines' => __('This fee is no longer active.'),
                    ]);
                }

                $reste = round(max(0.0, (float) $lockedFee->montant - $lockedFee->montantPaye()), 2);

                if ($total > $reste) {
                    throw ValidationException::withMessages([
                        'payment_lines' => __('The amount cannot exceed the remaining balance of this fee.'),
                    ]);
                }
            }

            foreach ($touchedLines as $line) {
                $fee = InscriptionFee::findOrFail($line['fee_id']);

                // A tampered client could inject a fee id belonging to a
                // different registration — refused exactly like
                // EncaissementsIndex::save()'s own inline guard, rolling
                // back every row already processed in this submit.
                if ($fee->inscription_id !== $inscriptionId) {
                    throw ValidationException::withMessages([
                        'payment_lines' => __('One of the selected fees does not belong to this registration.'),
                    ]);
                }

                $isCheque = $line['methode'] === Encaissement::METHODE_CHEQUE;
                $cheque = $isCheque ? $cheques[(int) $line['cheque_id']] : null;
                $caisse = $resolver->resolveFor($agent, (string) $line['methode']);

                $action->handle([
                    'student_id' => $data['student_id'],
                    'inscription_fee_id' => $fee->id,
                    'cheque_id' => $cheque?->id,
                    'montant' => $line['montant'],
                    'methode' => $line['methode'],
                    'date_paiement' => $line['date_paiement'],
                    'caisse_id' => $caisse->id,
                    'numero_cheque' => $cheque?->numero_cheque,
                    'banque' => $cheque?->banque,
                    'date_echeance_cheque' => $cheque?->date_echeance?->toDateString(),
                    'note' => $data['note'] ?? null,
                ], $agent);
            }
        });

        return $this->backToListPreservingFilters($request, 'backoffice.encaissements.index')
            ->with('success', __('Payment recorded.'));
    }

    /**
     * Records an avance — a payment with NO fee attached (Encaissement::
     * isAvance()), held as credit against the student to allocate later via
     * applyAvance(). Same server-derived-till rule as store().
     */
    public function storeAvance(StoreAvanceRequest $request, EnregistrerEncaissement $action): RedirectResponse
    {
        $this->authorize('create', Encaissement::class);

        $agent = $request->user()->employee;

        if ($agent === null) {
            throw ValidationException::withMessages([
                'montant' => __('Your account is not linked to any employee record.'),
            ]);
        }

        $data = $request->validated();

        // Centre isolation, same as store()'s inscription check: the student
        // receiving the credit must be within the cashier's centres —
        // otherwise a tampered student_id books an avance for another
        // centre's student (it would then show on THAT centre's pages).
        $this->assertStudentInContext($request, Student::query()->findOrFail((int) $data['student_id']));

        // Same rule as store(): the method decides the account.
        $caisse = app(CaisseResolver::class)->resolveFor($agent, (string) $data['methode']);

        $action->handle([
            'student_id' => $data['student_id'],
            'inscription_fee_id' => null,
            'montant' => $data['montant'],
            'methode' => $data['methode'],
            'date_paiement' => $data['date_paiement'],
            'caisse_id' => $caisse->id,
            'numero_cheque' => $data['methode'] === Encaissement::METHODE_CHEQUE ? ($data['numero_cheque'] ?? null) : null,
            'banque' => $data['methode'] === Encaissement::METHODE_CHEQUE ? ($data['banque'] ?? null) : null,
            'date_echeance_cheque' => $data['methode'] === Encaissement::METHODE_CHEQUE ? ($data['date_echeance_cheque'] ?? null) : null,
            'note' => $data['note'] ?? null,
        ], $agent);

        return $this->backToListPreservingFilters($request, 'backoffice.encaissements.index', ['view' => 'avance'])
            ->with('success', __('Advance recorded.'));
    }

    /**
     * Converts selected fee-attached payments of one inscription into
     * unallocated avances — detaches them from their fees (which drop back
     * to Non payé / Payé partiellement) without deleting anything and
     * without touching any till. The freed amounts then show on the Avances
     * tab with montant utilisé/restant, ready to be applied to another
     * inscription's fees (typical after a changement de groupe).
     */
    public function convertAvance(ConvertAvanceRequest $request, ConvertirEncaissementsEnAvance $action): RedirectResponse
    {
        $this->authorize('create', Encaissement::class);

        $data = $request->validated();
        $inscription = Inscription::findOrFail((int) $data['inscription_id']);
        $this->assertInscriptionInContext($request, $inscription);

        // Pas d'assertInscriptionPayable() ici, volontairement : convertir
        // libère l'argent D'UNE inscription close (changement de groupe,
        // annulation) vers des avances réutilisables. L'exiger active
        // emprisonnerait le versement sur le dossier qu'on vient de fermer —
        // c'est le sens inverse d'un encaissement.
        $action->handle($inscription, array_map('intval', $data['encaissement_ids']));

        return $this->backToListPreservingFilters($request, 'backoffice.encaissements.index', ['view' => 'avance'])
            ->with('success', __('Payments converted into advances.'));
    }

    /**
     * Applies part (or all) of an avance's remaining balance to a specific
     * fee — see AppliquerAvance's docblock for why this creates a new row
     * rather than editing the avance.
     */
    public function applyAvance(ApplyAvanceRequest $request, Encaissement $encaissement, AppliquerAvance $action): RedirectResponse
    {
        $this->authorize('update', $encaissement);
        $this->assertContextAnneeOuverte('note');

        $data = $request->validated();
        $fee = InscriptionFee::with('inscription')->findOrFail($data['fee_id']);

        // The fee's registration must be in the active context — an avance
        // applied to last year's fee would book the allocation into a year
        // the cashier is not working in.
        if ($fee->inscription !== null) {
            $this->assertInscriptionInContext($request, $fee->inscription, 'fee_id');
            $this->assertInscriptionPayable($fee->inscription, 'fee_id');
        }

        $action->handle($encaissement, $fee, (float) $data['montant']);

        return $this->backToListPreservingFilters($request, 'backoffice.encaissements.index', ['view' => 'avance'])
            ->with('success', __('Advance applied.'));
    }

    /**
     * Printable payment receipt (reçu) — a standalone Blade print page, NOT
     * an Inertia page: it opens in a new tab sized for paper (A6 ticket, A5,
     * or two copies sharing ONE half-A4 sheet, i.e. an A5 landscape split in
     * two) and auto-opens the browser print dialog,
     * where "Enregistrer en PDF" doubles as the download. Rendered in the
     * browser (not a PDF lib) so the Arabic labels keep correct glyph
     * shaping. Header identity (nom/adresse/tél) comes from the payment's
     * own center — inscription's center first, student's as fallback.
     */
    public function recu(Request $request, Encaissement $encaissement): \Illuminate\Contracts\View\View
    {
        $this->authorize('view', $encaissement);

        $format = (string) $request->string('format', 'a5');
        if (! in_array($format, ['a6', 'a5', 'a5x2'], true)) {
            $format = 'a5';
        }

        // La MEME liste que le PDF : le recu imprime au guichet et le recu
        // envoye doivent nommer les memes frais (Encaissement::libelleFrais).
        $encaissement->load(RecuPdfRenderer::RELATIONS);

        $inscription = $encaissement->fee?->inscription
            ?? $encaissement->applications->sortBy('id')->first()?->fee?->inscription;
        $centre = $inscription?->etablissement ?? $encaissement->student?->etablissement;

        return view('backoffice.encaissements.recu', [
            'format' => $format,
            'encaissement' => $encaissement,
            'centre' => $centre,
            'anneeScolaire' => $inscription?->anneeScolaire?->nom,
            'niveau' => $inscription?->group?->nom ?? $encaissement->student?->niveau,
            'fraisNom' => $encaissement->libelleFrais(),
            'situation' => SituationFraisRecu::pour($encaissement),
        ]);
    }

    /**
     * Reçu GROUPÉ — un seul reçu couvrant plusieurs encaissements de la MÊME
     * inscription (?ids=12,13,14&format=a6|a5|a5x2). Sert le cas courant du
     * guichet : l'étudiant règle deux ou trois frais en une seule fois et
     * repart avec UN document, pas trois.
     *
     * ⚠ L'identité d'un reçu est celle d'UN étudiant : le lot est refusé
     * (422) dès que les lignes ne partagent pas la même inscription — d'où
     * le menu « Action » désactivé côté UI quand la sélection mélange deux
     * étudiants. La vérification est ici, jamais seulement dans React.
     * Chaque ligne est autorisée individuellement (`view`), donc un id hors
     * périmètre du centre est refusé comme sur le reçu unitaire.
     */
    public function recuGroupe(Request $request): \Illuminate\Contracts\View\View
    {
        $format = (string) $request->string('format', 'a5');
        if (! in_array($format, ['a6', 'a5', 'a5x2'], true)) {
            $format = 'a5';
        }

        $ids = collect(explode(',', (string) $request->string('ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 404);

        $encaissements = Encaissement::query()
            ->whereIn('id', $ids)
            ->with(RecuPdfRenderer::RELATIONS)
            ->orderBy('date_paiement')
            ->orderBy('id')
            ->get();

        abort_if($encaissements->count() !== $ids->count(), 404);

        foreach ($encaissements as $encaissement) {
            $this->authorize('view', $encaissement);
        }

        // Même inscription pour toutes les lignes. Une avance n'a pas de fee
        // — donc pas d'inscription — et ne peut pas être groupée : elle
        // n'appartient encore à aucun dossier d'inscription.
        $inscriptionIds = $encaissements->map(fn ($e) => $e->fee?->inscription_id)->unique();
        abort_if($inscriptionIds->count() !== 1 || $inscriptionIds->first() === null, 422, __('A grouped receipt must cover payments of a single registration.'));

        $first = $encaissements->first();
        $inscription = $first->fee?->inscription;
        $centre = $inscription?->etablissement ?? $first->student?->etablissement;

        // Reste par frais, en UNE requête agrégée — jamais
        // InscriptionFee::montantPaye() dans la boucle du reçu (CLAUDE.md §17,
        // « read models never call a per-row money accessor in a loop »).
        $feeIds = $encaissements->pluck('inscription_fee_id')->filter()->unique();
        $payeParFee = \App\Models\Encaissement::query()
            ->whereIn('inscription_fee_id', $feeIds)
            ->groupBy('inscription_fee_id')
            ->selectRaw('inscription_fee_id, sum(montant) as paye')
            ->pluck('paye', 'inscription_fee_id');

        return view('backoffice.encaissements.recu-groupe', [
            'format' => $format,
            'encaissements' => $encaissements,
            'student' => $first->student,
            'centre' => $centre,
            'anneeScolaire' => $inscription?->anneeScolaire?->nom,
            'niveau' => $inscription?->group?->nom ?? $first->student?->niveau,
            'montantTotal' => (float) $encaissements->sum('montant'),
            'reference' => $first->reference,
            'payeParFee' => $payeParFee,
            'situation' => SituationFraisRecu::pourLot($encaissements),
        ]);
    }

    /**
     * Emails the same receipt (rendered as a PDF, A5) to a given address —
     * defaults to the student's own email in the UI prompt, but any address
     * can be typed since not every student has one on file. Reuses the
     * "recu" Blade view via EncaissementRecuMail (mPDF attachment); mail
     * is `log` in local dev per project convention (§15).
     *
     * QUEUED, never sent inline: rendering the A5 PDF with mPDF plus the SMTP
     * round-trip takes seconds, and the cashier must not wait on it. The
     * worker (`crm-gls-queue.service`, docs/vps-deployment.md) does the work;
     * the UI gets an immediate "queued" confirmation.
     */
    /**
     * Construit le lien click-to-chat WhatsApp qui envoie le reçu à
     * l'étudiant — la cible du bouton « Envoyer par WhatsApp » de la liste.
     *
     * Rend du JSON : le bouton ouvre l'URL retournée dans un nouvel onglet,
     * rien sur la page ne change. Le lien est construit ICI et jamais dans
     * React, parce qu'il porte une URL SIGNÉE du PDF — la signature dépend
     * d'APP_KEY et ne peut pas quitter le serveur.
     *
     * Deux refus explicites (422) plutôt qu'un lien silencieusement inutile :
     *  - l'étudiant n'a aucun numéro joignable ;
     *  - APP_URL pointe sur la machine locale, donc le lien PDF serait mort
     *    sur le téléphone du destinataire (RecuWhatsAppLink::
     *    pdfUrlIsPubliclyReachable, constaté en situation réelle le
     *    31/08/2026). Mieux vaut dire au caissier que l'envoi est impossible
     *    que lui laisser croire que le reçu est parti.
     */
    public function recuWhatsApp(Encaissement $encaissement, RecuWhatsAppLink $link): JsonResponse
    {
        $this->authorize('view', $encaissement);

        if (! $link->pdfUrlIsPubliclyReachable()) {
            return response()->json([
                'message' => __('The receipt link points to a local address (:url) that the student cannot open. Set APP_URL to the public CRM address.', ['url' => (string) config('app.url')]),
            ], 422);
        }

        $encaissement->load(RecuPdfRenderer::RELATIONS);

        $payload = $link->forEncaissement($encaissement);

        if ($payload === null) {
            return response()->json([
                'message' => __('This student has no reachable phone number.'),
            ], 422);
        }

        return response()->json($payload);
    }

    /**
     * Lien click-to-chat WhatsApp pour un lot d'encaissements — la version
     * groupée de recuWhatsApp(), cible de l'action « Envoyer par WhatsApp »
     * du menu « Action » (?ids=12,13,14).
     *
     * Un seul message, un seul lien PDF (le reçu GROUPÉ) : le caissier a
     * encaissé une fois, l'étudiant reçoit un document.
     *
     * Mêmes gardes que le reçu groupé imprimé, dans le même ordre : chaque
     * ligne autorisée individuellement (un id hors périmètre du centre est
     * refusé), puis lot refusé s'il mélange deux inscriptions. La
     * vérification est ICI, jamais seulement dans React — le menu grisé
     * n'est qu'un confort d'interface.
     */
    public function recuGroupeWhatsApp(Request $request, RecuWhatsAppLink $link): JsonResponse
    {
        if (! $link->pdfUrlIsPubliclyReachable()) {
            return response()->json([
                'message' => __('The receipt link points to a local address (:url) that the student cannot open. Set APP_URL to the public CRM address.', ['url' => (string) config('app.url')]),
            ], 422);
        }

        $ids = collect(explode(',', (string) $request->string('ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 404);

        $encaissements = Encaissement::query()
            ->whereIn('id', $ids)
            ->with(RecuPdfRenderer::RELATIONS)
            ->orderBy('date_paiement')
            ->orderBy('id')
            ->get();

        abort_if($encaissements->count() !== $ids->count(), 404);

        foreach ($encaissements as $encaissement) {
            $this->authorize('view', $encaissement);
        }

        $inscriptionIds = $encaissements->map(fn ($e) => $e->fee?->inscription_id)->unique();

        if ($inscriptionIds->count() !== 1 || $inscriptionIds->first() === null) {
            return response()->json([
                'message' => __('A grouped receipt must cover payments of a single registration.'),
            ], 422);
        }

        $payload = $link->forEncaissements($encaissements);

        if ($payload === null) {
            return response()->json([
                'message' => __('This student has no reachable phone number.'),
            ], 422);
        }

        return response()->json($payload);
    }

    public function sendRecuEmail(SendRecuEmailRequest $request, Encaissement $encaissement): RedirectResponse
    {
        $this->authorize('view', $encaissement);

        // La MEME liste que le lien WhatsApp : le recu par email et le recu
        // telecharge doivent etre le meme document (RecuPdfRenderer).
        $encaissement->load(RecuPdfRenderer::RELATIONS);

        Mail::to($request->validated('email'))->queue(new EncaissementRecuMail($encaissement));

        return back()->with('success', __('Receipt queued for sending to :email.', ['email' => $request->validated('email')]));
    }

    public function show(Request $request, Encaissement $encaissement, GetEncaissementDetails $getEncaissementDetails): Response
    {
        $this->authorize('view', $encaissement);

        return Inertia::render('Backoffice/Encaissements/Show', [
            'encaissement' => $getEncaissementDetails($encaissement),
            // UI convenience only — the real gate is the route's
            // `permission:payments.detach` plus authorize() in detach() (§5).
            'canDetach' => $request->user()?->can('payments.detach') ?? false,
        ]);
    }

    /**
     * Detaches ONE payment from its fee, from that payment's Show page — the
     * per-row counterpart of convertAvance(). Nothing is deleted and no till
     * moves; the fee simply becomes owed again and the money re-applicable.
     *
     * Super-admin only (`payments.detach`, superAdminOnly): it rewrites what
     * a student is billed without any money changing hands.
     */
    public function detach(Request $request, Encaissement $encaissement, DetacherEncaissementDuFrais $action): RedirectResponse
    {
        // Explicit, on top of the route's `permission:payments.detach`
        // (defense in depth, §16).
        $this->authorize('update', $encaissement);
        $this->assertContextAnneeOuverte('id');

        $action->handle($encaissement);

        return back()->with('success', __('Payment detached from its fee — the amount is available as an advance again.'));
    }

    /**
     * The one destroy path on a money record (CLAUDE.md §11). Reachable only
     * with `payments.delete` — a permission no role preset carries, granted by
     * a super-admin. SupprimerEncaissement reverses caisses.solde in the same
     * transaction and refuses entangled rows (applied avances, tracked chèques).
     */
    public function destroy(Request $request, Encaissement $encaissement, SupprimerEncaissement $action): RedirectResponse
    {
        // Explicit, in addition to the route's `permission:payments.delete`
        // and `can:delete,encaissement`: every other mutation in this
        // controller authorizes on its own too (defense in depth, §16).
        $this->authorize('delete', $encaissement);
        $this->assertContextAnneeOuverte('id');

        $action->handle($encaissement);

        return $this->backToListPreservingFilters($request, 'backoffice.encaissements.index')
            ->with('success', __('Payment deleted.'));
    }

    public function update(
        UpdateEncaissementRequest $request,
        Encaissement $encaissement,
        RequalifierMethodeEncaissement $requalifierMethode,
        CorrigerMontantEncaissement $corrigerMontant,
    ): RedirectResponse {
        $this->authorize('update', $encaissement);

        $data = $request->validated();

        // ⚠ `methode` n'est PAS une étiquette : elle a décidé dans quelle
        // caisse l'argent est tombé (CaisseResolver). La corriger DÉPLACE
        // donc l'argent — débit de l'ancienne caisse, crédit de la nouvelle,
        // les deux jambes journalisées — ce que fait
        // RequalifierMethodeEncaissement dans une seule transaction. Jamais
        // une écriture directe sur la colonne : elle laisserait l'argent
        // dans un compte et le libellé sur un autre (CLAUDE.md §11).
        //
        // Réservée à `payments.update-method` (rôles de direction +
        // super-admin, 01/09/2026). Sans la permission le champ est désactivé
        // dans le modal, donc une valeur DIFFÉRENTE qui arrive ici est un
        // formulaire périmé ou une requête forgée : on la refuse
        // explicitement plutôt que de la laisser passer en silence, parce
        // qu'accepter à moitié une correction monétaire est pire que la
        // rejeter. Une valeur identique reste acceptée (le modal la renvoie).
        $methodePostee = $data['methode'] ?? $encaissement->methode;
        unset($data['methode']);

        if ($methodePostee !== $encaissement->methode) {
            if (! $request->user()->can('payments.update-method')) {
                throw ValidationException::withMessages([
                    'methode' => __('You are not allowed to change the payment method of a recorded payment.'),
                ]);
            }

            $agent = $request->user()->employee;

            if ($agent === null) {
                // La nouvelle caisse d'un paiement en espèces est la caisse
                // physique de l'agent : sans fiche employé il n'y en a pas.
                throw ValidationException::withMessages([
                    'methode' => __('Your account is not linked to an employee record, so no cash account can be resolved.'),
                ]);
            }

            $requalifierMethode->handle($encaissement, $methodePostee, $agent);

            // La ligne vient de changer de caisse : on repart de l'état
            // fraîchement écrit pour que les règles chèque ci-dessous lisent
            // la NOUVELLE méthode, pas celle d'avant la requalification.
            $encaissement->refresh();
        }

        // ⚠ `date_paiement` is SUPER-ADMIN ONLY (30/08/2026). Re-dating a
        // recorded payment relocates the row in the caisse journal and in the
        // annual summary — after a month may already have been reconciled —
        // so the 27/08/2026 audit's reporting risk is now closed as a
        // business rule: `payments.update-date` sits in
        // PermissionRegistry::superAdminOnly(), so no role preset holds it
        // and Gate::before answers it for super-admins alone.
        //
        // Everyone else keeps `payments.update` for the fields that genuinely
        // need correcting (note, chèque identity). A posted date is silently
        // dropped rather than rejected: the edit modal disables the field
        // (see Encaissements/Index.tsx `canEditDate`), so a value arriving
        // here is a stale form or a crafted request, never a real intent.
        if (! $request->user()->can('payments.update-date')) {
            unset($data['date_paiement']);
        }

        // Meme raisonnement pour le MONTANT, et meme classe de risque
        // (02/09/2026) : `montant` n'est pas une etiquette, c'est la somme
        // tombee dans la caisse. La corriger DEPLACE donc de l'argent —
        // l'ecart est credite ou debite sur la caisse D'ORIGINE de la ligne,
        // journalise — ce que fait CorrigerMontantEncaissement dans une seule
        // transaction. Jamais une ecriture directe sur la colonne : elle
        // laisserait `caisses.solde` sur l'ancien chiffre, sans rien dans le
        // journal pour expliquer l'ecart (CLAUDE.md §11).
        //
        // `payments.update-amount` est super-admin uniquement
        // (PermissionRegistry::superAdminOnly()) : reecrire une somme deja
        // encaissee court-circuite la correction normale (remboursement +
        // nouvel encaissement). Sans la permission le champ est desactive
        // dans le modal, donc une valeur DIFFERENTE qui arrive ici est un
        // formulaire perime ou une requete forgee : on la refuse
        // explicitement, comme pour `methode`. Une valeur identique reste
        // acceptee (le modal la renvoie).
        $montantPoste = $data['montant'] ?? null;
        unset($data['montant']);

        if ($montantPoste !== null && abs((float) $montantPoste - (float) $encaissement->montant) >= 0.005) {
            if (! $request->user()->can('payments.update-amount')) {
                throw ValidationException::withMessages([
                    'montant' => __('You are not allowed to change the amount of a recorded payment.'),
                ]);
            }

            $corrigerMontant->handle($encaissement, (float) $montantPoste);

            // Le solde et la ligne viennent de bouger : on repart de l'etat
            // fraichement ecrit pour la suite de la mise a jour.
            $encaissement->refresh();
        }

        if ($encaissement->cheque_id !== null) {
            // A payment funded by a tracked chèque (Chèques module) keeps its
            // cheque identity: numéro/banque/échéance are read off the Cheque
            // row, retyping them here would contradict it.
            unset($data['numero_cheque'], $data['banque'], $data['date_echeance_cheque']);
        } elseif ($encaissement->methode !== Encaissement::METHODE_CHEQUE) {
            // Cheque columns only mean something on a Chèque row.
            $data['numero_cheque'] = null;
            $data['banque'] = null;
            $data['date_echeance_cheque'] = null;
        }

        // `caisse_id` n'est JAMAIS editable a la main. `montant` et `methode`
        // le sont, mais uniquement via les deux actions ci-dessus
        // (CorrigerMontantEncaissement / RequalifierMethodeEncaissement), qui
        // deplacent aussi l'argent et journalisent chaque jambe — jamais par
        // cet `update()`, d'ou le `unset()` des deux cles (voir
        // UpdateEncaissementRequest); this edit is audit-logged by LogsActivity.
        $encaissement->update($data);

        return $this->backToListPreservingFilters($request, 'backoffice.encaissements.index')
            ->with('success', __('Payment updated.'));
    }

    /**
     * Center scope for lookups and writes that hang off another module's
     * record: the cashier needs no `registrations.view`/`students.view`
     * permission to take money, only access to the record's center
     * (CenterAccessService — same rule the policies use).
     */
    private function assertCenterAccess(Request $request, ?int $etablissementId): void
    {
        if (! app(CenterAccessService::class)->canAccessCenter($request->user(), $etablissementId)) {
            abort(403);
        }
    }
}
