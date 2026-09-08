<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Frais;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Services\Import\SheetReader;
use App\Services\Import\Support\CellNormalizer;
use App\Services\Import\Support\LegacyLabels;
use Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Compares the legacy export against what the CRM holds and re-attaches
 * each payment to the fee its SOURCE ROW names.
 *
 * Written after three manual repairs (07–08/09/2026) that were the same
 * problem wearing different clothes:
 *   - ISMAIL AMARIR (Kénitra): the payer cell holds STUDENT + PAYER
 *     (« ISMAIL AMARIR ZAKARIA AMARIR », his brother paid), so the importer
 *     found two real students and refused to guess — 10 payments /
 *     10 100 DH never landed.
 *   - RAJA & CHAIMA EL ABLAOUI (Casablanca): the group prices 12 fees but
 *     the import only creates the lines a payment touches, so « Frais
 *     d'Octobre » did not exist and the loose fallback dropped 1 300 DH on
 *     « Frais d'inscription B2 ».
 *   - HAMZA LACHKAR (Marrakech): an avance split across the wrong fees of
 *     the wrong inscription.
 *
 * THE EXPORT IS THE TRUTH. This command never invents an amount, a date or
 * a caisse: it reads them from the .xlsx and only ever changes which FEE a
 * payment is attached to.
 *
 * Non-negotiable guarantees (CLAUDE.md §11):
 *   - money records are never deleted — a wrong attachment is DETACHED
 *     (ConvertirEncaissementsEnAvance) then re-applied (AppliquerAvance);
 *   - `caisses.solde` never moves — re-allocating money already in the till
 *     is not a cash movement, so the balance is untouched by design;
 *   - `date_paiement`, `montant`, `methode` and `caisse_id` are READ-ONLY
 *     here — the payment keeps the date and the till it was recorded with
 *     (for legacy rows: Mr Rafik's till, exactly as the import filed them);
 *   - a CLOSED academic year is refused, never silently reopened.
 *
 * Usage:
 *   php artisan paiements:reconcilier --etudiant=E812 --dossier=/var/www/crm-gls/data
 *   php artisan paiements:reconcilier --centre=Marrakech --dossier=/var/www/crm-gls/data
 *   php artisan paiements:reconcilier --centre=Marrakech --dossier=... --apply
 */
final class ReconcilierPaiementsLegacy extends Command
{
    protected $signature = 'paiements:reconcilier
        {--dossier= : Dossier contenant "old data/" et "active data/" (obligatoire)}
        {--centre= : Centre (id ou partie du nom, ex. "Marrakech")}
        {--etudiant= : Un seul étudiant (réf. legacy "E812", réf. CRM "ETU-695", ou id)}
        {--apply : Écrire les corrections (par défaut : simulation)}';

    protected $description = "Compare l'export de l'ancien CRM aux paiements en base et rattache chaque paiement au frais que nomme son fichier source.";

    /** Sub-directory name in the export => how the centre is matched in DB. */
    private const array DOSSIERS_CENTRES = [
        'marrakech' => 'Marrakech',
        'rabat' => 'Rabat',
        'casa' => 'Casablanca',
        'kenitra' => 'Kénitra',
        'agadir' => 'Agadir',
        'sale' => 'Salé',
        'online' => 'Online',
    ];

    private const array COLONNES_PAIEMENTS = ['Réf.', 'Élève / Payeur', 'Type', 'Montant', 'Méthode', 'Frais', 'Date', 'Opérateur'];

    /** @var array<string, Frais> */
    private array $catalogue = [];

    private int $ecarts = 0;

    private int $corriges = 0;

    private float $montantCorrige = 0.0;

    /** @var list<string> */
    private array $bloques = [];

    public function handle(SheetReader $reader, ConvertirEncaissementsEnAvance $convertir, AppliquerAvance $appliquer): int
    {
        $dossier = rtrim((string) $this->option('dossier'), '/\\');

        if ($dossier === '' || ! is_dir($dossier)) {
            $this->error('--dossier est obligatoire et doit exister.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $this->catalogue = Frais::all()->keyBy(fn (Frais $f): string => LegacyLabels::cle($f->nom))->all();

        $cibles = $this->resolveCentres($dossier);

        if ($cibles === []) {
            return self::FAILURE;
        }

        $this->line('');
        $this->info(sprintf('Réconciliation export ↔ base%s', $apply ? '' : '   [SIMULATION]'));

        foreach ($cibles as $sousDossier => $centre) {
            $fichier = $this->fichierPaiements($dossier, $sousDossier);

            if ($fichier === null) {
                $this->warn(sprintf('  %s : aucun fichier de paiements — ignoré.', $centre->nom_centre));

                continue;
            }

            $this->line('');
            $this->line(str_repeat('-', 78));
            $this->info(sprintf('%s  (%s)', $centre->nom_centre, basename($fichier)));
            $this->line(str_repeat('-', 78));

            $this->traiterCentre($reader, $fichier, $centre, $apply, $convertir, $appliquer);
        }

        $this->line('');

        if ($this->ecarts === 0) {
            $this->info("Aucun écart : la base est conforme à l'export.");

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s%d écart(s) — %d corrigé(s) pour %s MAD.',
            $apply ? '' : '[SIMULATION] ',
            $this->ecarts,
            $this->corriges,
            number_format($this->montantCorrige, 2, '.', '')
        ));

        foreach ($this->bloques as $message) {
            $this->warn('  '.$message);
        }

        if (! $apply) {
            $this->line('');
            $this->comment('  Relancer avec --apply pour écrire (faire un pg_dump avant).');
        }

        return self::SUCCESS;
    }

    private function traiterCentre(
        SheetReader $reader,
        string $fichier,
        Etablissement $centre,
        bool $apply,
        ConvertirEncaissementsEnAvance $convertir,
        AppliquerAvance $appliquer,
    ): void {
        $filtreEtudiant = $this->resolveEtudiant($centre);

        if (trim((string) $this->option('etudiant')) !== '' && $filtreEtudiant === null) {
            $this->warn('  Étudiant introuvable dans ce centre — ignoré.');

            return;
        }

        foreach ($this->lignesDuFichier($reader, $fichier) as $ligne) {
            $ref = $ligne['ref'];

            if ($ref === '') {
                continue;
            }

            $paiement = Encaissement::query()
                ->with(['fee.inscription.anneeScolaire', 'student'])
                ->where('etablissement_id', $centre->id)
                ->where('legacy_ref', $ref)
                ->first();

            if ($paiement === null) {
                // Never imported. Reporting it IS the point — silently
                // skipping is how 10 100 DH stayed invisible for two weeks.
                if ($filtreEtudiant === null || $this->ligneConcerne($ligne, $filtreEtudiant)) {
                    $this->ecarts++;
                    $this->ligneEcart($ref, $ligne['montant'], $ligne['date'], 'ABSENT DE LA BASE', 'n/a');
                    $this->bloques[] = sprintf('%s : jamais importé — relancer import:centre, puis cette commande.', $ref);
                }

                continue;
            }

            if ($filtreEtudiant !== null && $paiement->student_id !== $filtreEtudiant->id) {
                continue;
            }

            $attendu = $this->fraisAttendu($ligne['frais_brut']);
            $actuel = $paiement->fee?->nom;

            if ($this->conforme($attendu, $actuel)) {
                continue;
            }

            $this->ecarts++;
            $this->ligneEcart(
                $ref,
                $ligne['montant'],
                $ligne['date'],
                $attendu ?? 'AVANCE (aucun frais au fichier)',
                $actuel ?? 'AVANCE'
            );

            if ($attendu === null) {
                $this->detacher($paiement, $apply, $convertir);

                continue;
            }

            $this->reattacher($paiement, $attendu, $apply, $convertir, $appliquer);
        }
    }

    /** The file says this payment carries no fee: detach, leave it an avance. */
    private function detacher(Encaissement $paiement, bool $apply, ConvertirEncaissementsEnAvance $convertir): void
    {
        if ($paiement->fee === null) {
            return;
        }

        if ($blocage = $this->blocage($paiement->fee->inscription)) {
            $this->bloques[] = sprintf('%s : %s', $paiement->legacy_ref, $blocage);

            return;
        }

        if ($apply) {
            $convertir->handle($paiement->fee->inscription, [$paiement->id]);
        }

        $this->corriges++;
        $this->montantCorrige += (float) $paiement->montant;
    }

    private function reattacher(
        Encaissement $paiement,
        string $fraisNom,
        bool $apply,
        ConvertirEncaissementsEnAvance $convertir,
        AppliquerAvance $appliquer,
    ): void {
        $frais = $this->catalogue[LegacyLabels::cle($fraisNom)] ?? null;

        if ($frais === null) {
            $this->bloques[] = sprintf('%s : « %s » absent du catalogue — laissé tel quel.', $paiement->legacy_ref, $fraisNom);

            return;
        }

        $inscription = $paiement->fee?->inscription ?? $this->inscriptionDe($paiement);

        if ($inscription === null) {
            $this->bloques[] = sprintf('%s : aucune inscription pour cet étudiant.', $paiement->legacy_ref);

            return;
        }

        if ($blocage = $this->blocage($inscription)) {
            $this->bloques[] = sprintf('%s : %s', $paiement->legacy_ref, $blocage);

            return;
        }

        if (! $apply) {
            $this->corriges++;
            $this->montantCorrige += (float) $paiement->montant;

            return;
        }

        DB::transaction(function () use ($convertir, $appliquer, $paiement, $inscription, $frais): void {
            if ($paiement->fee !== null) {
                $convertir->handle($paiement->fee->inscription, [$paiement->id]);
            }

            $cible = $this->ligneCible($inscription, $frais);

            // Re-read: after detaching, the re-allocatable money lives on
            // THIS row (a detached application row IS an avance) while the
            // parent's remaining stays 0 — reading the parent here is the
            // mistake that cost an extra round-trip on HAMZA LACHKAR.
            $avance = $paiement->fresh();
            $part = min($avance->montantRestant(), (float) $avance->montant);

            if ($part > 0.0) {
                $appliquer->handle($avance, $cible->fresh(), $part);
            }
        });

        $this->corriges++;
        $this->montantCorrige += (float) $paiement->montant;
    }

    /**
     * The fee line for $frais on $inscription: the visible one, else
     * un-hide the hidden one, else create it from the group's own price —
     * the import only creates lines a payment touches, so a legitimately
     * priced month may simply be missing (EL ABLAOUI, 07/09/2026).
     */
    private function ligneCible(Inscription $inscription, Frais $frais): InscriptionFee
    {
        $lignes = InscriptionFee::where('inscription_id', $inscription->id)->get()
            ->filter(fn (InscriptionFee $f): bool => LegacyLabels::cle($f->nom) === LegacyLabels::cle($frais->nom));

        $visible = $lignes->first(fn (InscriptionFee $f): bool => $f->masque_le === null);

        if ($visible !== null) {
            return $visible;
        }

        $masquee = $lignes->first();

        if ($masquee !== null) {
            $masquee->update(['masque_le' => null]);

            return $masquee;
        }

        $pivot = $inscription->group?->frais->firstWhere('id', $frais->id);
        $montant = $pivot?->pivot->montant ?? $frais->montant_defaut;

        return InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'frais_id' => $frais->id,
            'nom' => $frais->nom,
            'montant_initial' => $montant,
            'montant' => $montant,
            'date_echeance' => $pivot?->pivot->date_echeance,
            'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);
    }

    /**
     * A closed year is refused, never reopened: it is a business invariant
     * (AnneeScolaire::estCloturee), and reopening is a deliberate, audited
     * gesture made from Paramètres — not something a batch decides.
     */
    private function blocage(Inscription $inscription): ?string
    {
        if ($inscription->anneeScolaire?->estCloturee()) {
            return sprintf(
                'année %s CLÔTURÉE — rouvrir dans Paramètres → Années scolaires, relancer, puis reclôturer.',
                $inscription->anneeScolaire->nom
            );
        }

        return null;
    }

    private function inscriptionDe(Encaissement $paiement): ?Inscription
    {
        return Inscription::query()
            ->where('student_id', $paiement->student_id)
            ->orderByRaw('case statut when ? then 0 else 1 end', [Inscription::STATUT_ACTIVE])
            ->first();
    }

    /**
     * Catalogue name the source row names, or NULL when the cell is empty
     * (« - » is a missing value in every column of this export).
     * A comma-separated multi-fee cell is left alone: reconciling it would
     * mean re-splitting the money, which the importer already does.
     */
    private function fraisAttendu(string $brut): ?string
    {
        if (LegacyLabels::estVide($brut)) {
            return null;
        }

        if (str_contains($brut, ',')) {
            return null;
        }

        return LegacyLabels::fraisCanonique($brut);
    }

    private function conforme(?string $attendu, ?string $actuel): bool
    {
        if ($attendu === null) {
            return $actuel === null;
        }

        return $actuel !== null && LegacyLabels::cle($attendu) === LegacyLabels::cle($actuel);
    }

    /** @param array{ref: string, payeur: string, montant: string, date: string, frais_brut: string} $ligne */
    private function ligneConcerne(array $ligne, Student $etudiant): bool
    {
        $nom = LegacyLabels::cle($etudiant->prenom.' '.$etudiant->nom);

        return $nom !== '' && str_contains(LegacyLabels::cle($ligne['payeur']), $nom);
    }

    private function ligneEcart(string $ref, string $montant, string $date, string $attendu, string $actuel): void
    {
        $this->line(sprintf(
            '  %-8s %10s DH  %-10s  fichier: %-30s base: %s',
            $ref,
            $montant,
            $date,
            mb_substr($attendu, 0, 30),
            mb_substr($actuel, 0, 30)
        ));
    }

    /**
     * @return Generator<int, array{ref: string, payeur: string, montant: string, date: string, frais_brut: string}>
     */
    private function lignesDuFichier(SheetReader $reader, string $fichier): Generator
    {
        $entete = $reader->detectHeader($fichier, self::COLONNES_PAIEMENTS);

        foreach ($reader->readDataRows($fichier, $entete['headerRowNumber'], $entete['headerMap']) as $brut) {
            yield [
                'ref' => CellNormalizer::text($brut[self::COLONNES_PAIEMENTS[0]] ?? ''),
                'payeur' => CellNormalizer::text($brut[self::COLONNES_PAIEMENTS[1]] ?? ''),
                'montant' => CellNormalizer::parseMoney($brut['Montant'] ?? ''),
                'date' => CellNormalizer::parseDate($brut['Date'] ?? '')?->format('d/m/Y') ?? '?',
                'frais_brut' => CellNormalizer::text($brut['Frais'] ?? ''),
            ];
        }
    }

    private function fichierPaiements(string $dossier, string $sousDossier): ?string
    {
        foreach (['old data', 'active data'] as $bac) {
            $trouves = glob($dossier.'/'.$bac.'/'.$sousDossier.'/liste-paiements*.xlsx') ?: [];

            if ($trouves !== []) {
                sort($trouves);

                return (string) end($trouves);
            }
        }

        return null;
    }

    /** @return array<string, Etablissement> */
    private function resolveCentres(string $dossier): array
    {
        $filtre = trim((string) $this->option('centre'));
        $cibles = [];

        foreach (self::DOSSIERS_CENTRES as $sousDossier => $nom) {
            if ($filtre !== ''
                && ! str_contains(mb_strtolower($sousDossier), mb_strtolower($filtre))
                && ! str_contains(mb_strtolower($nom), mb_strtolower($filtre))) {
                continue;
            }

            if (! is_dir($dossier.'/old data/'.$sousDossier) && ! is_dir($dossier.'/active data/'.$sousDossier)) {
                continue;
            }

            $centre = Etablissement::query()->where('nom_centre', 'ilike', '%'.$nom.'%')->first();

            if ($centre !== null) {
                $cibles[$sousDossier] = $centre;
            }
        }

        if ($cibles === []) {
            $this->error('Aucun centre trouvé — vérifier --dossier et --centre.');
        }

        return $cibles;
    }

    private function resolveEtudiant(Etablissement $centre): ?Student
    {
        $valeur = trim((string) $this->option('etudiant'));

        if ($valeur === '') {
            return null;
        }

        return Student::query()
            ->withoutGlobalScopes()
            ->where('etablissement_id', $centre->id)
            ->where(function ($q) use ($valeur): void {
                $q->where('legacy_ref', $valeur)
                    ->orWhere('reference', $valeur)
                    ->when(is_numeric($valeur), fn ($w) => $w->orWhereKey((int) $valeur));
            })
            ->first();
    }
}
