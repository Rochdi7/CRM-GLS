<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Payments\Actions\AppliquerAvance;
use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Places payments exactly where the old CRM's own « Détails paiement » matrix
 * shows them — the authoritative fix for a student enrolled in SEVERAL running
 * groups, where no rule can infer which group a payment belongs to.
 *
 * Input: one XLSX per group, exported from the old CRM's group statistics
 * screen, NAMED AFTER THE GROUP (« OUASSIMA 13H.xlsx »). The file itself
 * carries no group name, so the filename is the only link — matched against
 * groups.nom in the active centre, never guessed.
 *
 * Each cell is a (student, fee, amount) triple. For every non-zero cell the
 * command finds the student's inscription IN THAT GROUP and settles the
 * same-named fee with the student's own payments currently attached
 * elsewhere, using the two existing money actions so all §11 invariants hold:
 * detach (row never deleted) then apply, capped at what the fee owes and
 * COPYING the original date_paiement. `caisses.solde` never moves.
 *
 * ⚠ The matrix shows the SAME money under several groups when a student is
 * enrolled in more than one — an inscription fee paid once appears on every
 * group's row. A payment can only ever be attached to ONE fee line, so it is
 * placed once and the conflict is reported. Money is never duplicated: that
 * is the whole point of reading a real payment rather than a matrix cell.
 *
 * Usage:
 *   php artisan matrice:appliquer --dossier="data test local/groups active/marrakech" --dry-run
 *   php artisan matrice:appliquer --dossier="data test local/groups active/marrakech"
 */
final class AppliquerMatriceGroupe extends Command
{
    protected $signature = 'matrice:appliquer
        {--dossier= : Dossier des XLSX, un par groupe, nommés comme le groupe}
        {--centre= : Centre (id ou partie du nom) — défaut : celui des groupes trouvés}
        {--dry-run : Afficher ce qui serait déplacé sans rien modifier}';

    protected $description = "Place les paiements là où la matrice « Détails paiement » de l'ancien CRM les montre.";

    public function handle(ConvertirEncaissementsEnAvance $convertir, AppliquerAvance $appliquer): int
    {
        $dossier = rtrim((string) $this->option('dossier'), '/\\');

        if ($dossier === '' || ! is_dir($dossier)) {
            $this->error('--dossier est obligatoire et doit exister.');

            return self::FAILURE;
        }

        $fichiers = glob($dossier.DIRECTORY_SEPARATOR.'*.xlsx') ?: [];

        if ($fichiers === []) {
            $this->error('Aucun .xlsx dans '.$dossier);

            return self::FAILURE;
        }

        $deplaces = 0;
        $montantTotal = 0.0;
        $introuvables = 0;
        $dejaEnPlace = 0;
        $conflits = [];

        foreach ($fichiers as $fichier) {
            $nomGroupe = pathinfo($fichier, PATHINFO_FILENAME);

            $groupe = Group::query()
                ->where('nom', $nomGroupe)
                ->when($this->option('centre'), function ($q, $centre): void {
                    $q->whereHas('etablissement', fn ($e) => is_numeric($centre)
                        ? $e->whereKey((int) $centre)
                        : $e->where('nom_centre', 'ilike', '%'.$centre.'%'));
                })
                ->first();

            if ($groupe === null) {
                $this->warn(sprintf('  Groupe « %s » introuvable — fichier ignoré.', $nomGroupe));

                continue;
            }

            $cellules = $this->lireMatrice($fichier);
            $lignes = [];

            foreach ($cellules as [$nomEtudiant, $nomFrais, $montantAttendu]) {
                $student = Student::query()
                    ->where('etablissement_id', $groupe->etablissement_id)
                    ->whereRaw('lower(trim(prenom||\' \'||nom)) = ? or lower(trim(nom||\' \'||prenom)) = ?', [
                        $this->cle($nomEtudiant), $this->cle($nomEtudiant),
                    ])
                    ->first();

                if ($student === null) {
                    $introuvables++;

                    continue;
                }

                $inscription = Inscription::query()
                    ->where('group_id', $groupe->id)
                    ->where('student_id', $student->id)
                    ->orderByRaw('case statut when ? then 0 else 1 end', [Inscription::STATUT_ACTIVE])
                    ->first();

                if ($inscription === null) {
                    $introuvables++;

                    continue;
                }

                $fee = InscriptionFee::query()
                    ->where('inscription_id', $inscription->id)
                    ->whereNull('masque_le')
                    ->get()
                    ->first(fn (InscriptionFee $f): bool => $this->cleFrais($f->nom) === $this->cleFrais($nomFrais));

                if ($fee === null) {
                    $introuvables++;

                    continue;
                }

                $manque = round($montantAttendu - $fee->montantPaye(), 2);

                if ($manque <= 0.0) {
                    $dejaEnPlace++;

                    continue;
                }

                // The student's payments on that same fee name, sitting on
                // ANOTHER inscription. Oldest first: the old CRM's cell is a
                // total, so the earliest payments are the ones it represents.
                $ailleurs = Encaissement::query()
                    ->where('student_id', $student->id)
                    ->whereNotNull('inscription_fee_id')
                    ->whereHas('fee', fn ($f) => $f->where('inscription_id', '!=', $inscription->id))
                    ->with('fee.inscription.group:id,nom')
                    ->orderBy('date_paiement')
                    ->get()
                    ->filter(fn (Encaissement $e): bool => $this->cleFrais((string) ($e->fee?->nom ?? '')) === $this->cleFrais($nomFrais));

                foreach ($ailleurs as $paiement) {
                    if ($manque <= 0.0) {
                        break;
                    }

                    $part = min($manque, (float) $paiement->montant);

                    if ($part <= 0.0) {
                        continue;
                    }

                    $lignes[] = [$paiement, $fee, $part, $nomEtudiant, $nomFrais];
                    $manque = round($manque - $part, 2);

                    $source = $paiement->fee?->inscription?->group?->nom;

                    if ($source !== null && $source !== $groupe->nom) {
                        $conflits[] = sprintf('%s — %s : %s -> %s', $nomEtudiant, $nomFrais, $source, $groupe->nom);
                    }
                }
            }

            if ($lignes === []) {
                continue;
            }

            $this->line(sprintf('  %s (%d)', $groupe->nom, count($lignes)));

            foreach ($lignes as [$paiement, $fee, $part, $nomEtudiant, $nomFrais]) {
                $this->line(sprintf(
                    '      %-11s %9s  %-22s %-26s %s',
                    $paiement->reference,
                    number_format($part, 2, '.', ''),
                    mb_substr($nomEtudiant, 0, 22),
                    mb_substr($nomFrais, 0, 26),
                    substr((string) $paiement->date_paiement, 0, 10)
                ));

                if (! $this->option('dry-run')) {
                    DB::transaction(function () use ($convertir, $appliquer, $paiement, $fee, $part): void {
                        $source = $paiement->fee?->inscription;

                        if ($source === null) {
                            return;
                        }

                        $convertir->handle($source, [$paiement->id]);

                        $avance = $paiement->fresh();
                        $cible = $fee->fresh();

                        if ($avance === null || $cible === null) {
                            return;
                        }

                        $part = min(
                            $part,
                            (float) $avance->montantRestant(),
                            round((float) $cible->montant - $cible->montantPaye(), 2),
                        );

                        if ($part > 0.0) {
                            $appliquer->handle($avance, $cible, $part);
                        }
                    });
                }

                $deplaces++;
                $montantTotal += $part;
            }
        }

        $this->line('');
        $this->info(sprintf(
            '%s%d paiement(s) placé(s) pour %s MAD.',
            $this->option('dry-run') ? '[DRY-RUN] ' : '',
            $deplaces,
            number_format($montantTotal, 2, '.', '')
        ));

        if ($dejaEnPlace > 0) {
            $this->line(sprintf('  %d cellule(s) déjà correcte(s).', $dejaEnPlace));
        }

        if ($introuvables > 0) {
            $this->warn(sprintf('  %d cellule(s) sans étudiant/inscription/frais correspondant.', $introuvables));
        }

        if ($conflits !== []) {
            $this->line('');
            $this->warn(sprintf('%d paiement(s) réclamé(s) par un autre groupe — placés une seule fois :', count($conflits)));

            foreach (array_slice($conflits, 0, 12) as $c) {
                $this->line('      '.$c);
            }
        }

        return self::SUCCESS;
    }

    /**
     * The matrix as (student, fee, amount) triples, zeros dropped.
     *
     * @return list<array{0: string, 1: string, 2: float}>
     */
    private function lireMatrice(string $chemin): array
    {
        $reader = new Reader();
        $reader->open($chemin);
        $entetes = null;
        $cellules = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $c = array_map(static fn (mixed $v): string => trim((string) $v), $row->toArray());

                    if ($entetes === null) {
                        if (($c[1] ?? '') === '' && ($c[0] ?? '') === '') {
                            continue;
                        }

                        $entetes = $c;

                        continue;
                    }

                    $etudiant = $c[1] ?? '';

                    if ($etudiant === '') {
                        continue;
                    }

                    foreach ($entetes as $i => $entete) {
                        if (! str_starts_with($entete, 'Frais')) {
                            continue;
                        }

                        $brut = $c[$i] ?? '';

                        if ($brut === '' || ! preg_match('/([\d.]+)/', $brut, $m)) {
                            continue;
                        }

                        $montant = (float) $m[1];

                        if ($montant > 0) {
                            $cellules[] = [$etudiant, $entete, $montant];
                        }
                    }
                }

                break; // first sheet only
            }
        } finally {
            $reader->close();
        }

        return $cellules;
    }

    private function cle(string $valeur): string
    {
        return (string) preg_replace('/\s+/u', ' ', trim(mb_strtolower($valeur)));
    }

    /** Fee names, accent- and space-insensitive (the export mangles both). */
    private function cleFrais(string $valeur): string
    {
        $valeur = mb_strtolower($valeur);
        $valeur = strtr($valeur, [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ô' => 'o', 'û' => 'u',
            'ù' => 'u', 'ç' => 'c', 'â' => 'a', 'î' => 'i', 'ï' => 'i',
        ]);

        return (string) preg_replace('/[^a-z0-9]/', '', $valeur);
    }
}
