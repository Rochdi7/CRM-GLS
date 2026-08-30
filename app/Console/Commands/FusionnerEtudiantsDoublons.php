<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Cheque;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionHistorique;
use App\Models\Presence;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Merges the SAME person held twice by the legacy CRM.
 *
 * wimschool lets a branch create a second file for someone already in it
 * (two `legacy_ref`, one phone number, a mistyped birth date). The importer
 * refuses to guess which of the twins a payment or a registration belongs to
 * and leaves the row ECHEC_COMMIT — 77 rows across 15 people after the
 * 30/08/2026 re-import.
 *
 * Match is deliberately narrow: same centre, same normalized full name AND
 * the same phone number. A twin pair with two different phones is two
 * different people (YOUSSEF MOUHIB) and is never merged — pass --forcer with
 * --etudiants=id,id to merge such a pair by hand once verified.
 *
 * The keeper is the OLDEST row (lowest id — the first import). Everything
 * pointing at the other file is re-pointed at the keeper: inscriptions,
 * encaissements, chèques, présences, historique. Nothing is deleted, no
 * amount changes, `caisses.solde` never moves — only `student_id` values.
 * The emptied duplicate is renamed « … (doublon fusionné) » so it stops
 * showing up in searches, and keeps its legacy_ref for the audit trail.
 *
 * Usage:
 *   php artisan etudiants:fusionner-doublons --dry-run
 *   php artisan etudiants:fusionner-doublons --centre=3
 *   php artisan etudiants:fusionner-doublons --etudiants=1916,2290 --forcer
 */
final class FusionnerEtudiantsDoublons extends Command
{
    protected $signature = 'etudiants:fusionner-doublons
        {--centre= : Limiter à un centre (id)}
        {--etudiants= : Fusionner exactement ces fiches (ids séparés par une virgule, la plus ancienne est gardée)}
        {--forcer : Accepter une paire dont les téléphones diffèrent (à réserver aux cas vérifiés à la main)}
        {--dry-run : Afficher sans modifier}';

    protected $description = "Fusionne les fiches étudiant en double de l'ancien CRM (même centre, même nom, même téléphone) sur la plus ancienne.";

    /** Tables carrying a student_id, all re-pointed at the keeper. */
    private const array MODELES = [
        Inscription::class,
        Encaissement::class,
        Cheque::class,
        Presence::class,
        InscriptionHistorique::class,
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $paires = $this->option('etudiants') !== null
            ? $this->paireExplicite((string) $this->option('etudiants'))
            : $this->pairesDetectees();

        if ($paires === []) {
            $this->info('Aucun doublon à fusionner.');

            return self::SUCCESS;
        }

        $fusions = 0;
        $lignes = 0;

        foreach ($paires as [$garde, $doublons]) {
            $this->line('');
            $this->info(sprintf(
                '%s %s — garder #%d (%s), fusionner %s',
                $garde->prenom, $garde->nom, $garde->id, $garde->legacy_ref ?? '—',
                $doublons->map(fn (Student $s): string => '#'.$s->id.' ('.($s->legacy_ref ?? '—').')')->implode(', ')
            ));

            foreach ($doublons as $doublon) {
                foreach (self::MODELES as $modele) {
                    $n = $modele::where('student_id', $doublon->id)->count();

                    if ($n === 0) {
                        continue;
                    }

                    $this->line(sprintf('      %-24s %d ligne(s) -> #%d', class_basename($modele), $n, $garde->id));
                    $lignes += $n;

                    if (! $dry) {
                        DB::transaction(fn () => $modele::where('student_id', $doublon->id)->update(['student_id' => $garde->id]));
                    }
                }

                if (! $dry) {
                    // Keep the row (audit trail, legacy_ref uniqueness) but
                    // take it out of every search and dropdown.
                    $doublon->update(['nom' => $doublon->nom.' (doublon fusionné)']);
                }

                $fusions++;
            }
        }

        $this->line('');
        $this->info(sprintf('%s%d fiche(s) fusionnée(s), %d ligne(s) rattachée(s).', $dry ? '[DRY-RUN] ' : '', $fusions, $lignes));

        if (! $dry && $fusions > 0) {
            $this->comment('Relancez ensuite : php artisan import:centre --tous --dossier="data test local" --caisse=rafik@glszentrum.com --retry-echecs');
        }

        return self::SUCCESS;
    }

    /**
     * Twin pairs: same centre, same normalized name, same phone number.
     *
     * @return list<array{0: Student, 1: Collection<int, Student>}>
     */
    private function pairesDetectees(): array
    {
        $paires = [];

        $etudiants = Student::query()
            ->when($this->option('centre'), fn ($q, $c) => $q->where('etablissement_id', (int) $c))
            ->where('nom', 'not ilike', '%(doublon fusionné)')
            ->orderBy('id')
            ->get();

        foreach ($etudiants->groupBy(fn (Student $s): string => $s->etablissement_id.'|'.$this->cle($s->prenom.$s->nom)) as $groupe) {
            if ($groupe->count() < 2) {
                continue;
            }

            foreach ($groupe->groupBy(fn (Student $s): string => (string) $s->telephone) as $tel => $memeTel) {
                if ($memeTel->count() < 2 || ($tel === '' && ! $this->option('forcer'))) {
                    continue;
                }

                $garde = $memeTel->first();
                $paires[] = [$garde, $memeTel->slice(1)];
            }
        }

        return $paires;
    }

    /** @return list<array{0: Student, 1: Collection<int, Student>}> */
    private function paireExplicite(string $ids): array
    {
        $fiches = Student::whereIn('id', array_map('intval', explode(',', $ids)))->orderBy('id')->get();

        if ($fiches->count() < 2) {
            $this->error('--etudiants doit désigner au moins deux fiches existantes.');

            return [];
        }

        if ($fiches->pluck('etablissement_id')->unique()->count() > 1) {
            $this->error('Les fiches ne sont pas dans le même centre — fusion refusée.');

            return [];
        }

        if ($fiches->pluck('telephone')->unique()->count() > 1 && ! $this->option('forcer')) {
            $this->error('Téléphones différents : ce sont peut-être deux personnes. Ajoutez --forcer si vous avez vérifié.');

            return [];
        }

        return [[$fiches->first(), $fiches->slice(1)]];
    }

    private function cle(string $s): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', strtr(mb_strtolower($s), [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a',
            'ô' => 'o', 'ö' => 'o', 'û' => 'u', 'ù' => 'u', 'ç' => 'c', 'ï' => 'i', 'î' => 'i',
        ]));
    }
}
