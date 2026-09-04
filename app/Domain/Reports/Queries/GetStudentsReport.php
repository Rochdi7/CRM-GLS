<?php

declare(strict_types=1);

namespace App\Domain\Reports\Queries;

use App\Models\Inscription;
use App\Models\Student;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * « Liste des étudiants » — deuxième rapport de Gestion des rapports.
 *
 * Read-model UNIQUE des trois sorties (aperçu écran, PDF, Excel), comme
 * GetInscriptionsReport : le document imprimé ne peut jamais différer du
 * compteur que l'utilisateur vient de voir.
 *
 * ⚠ Portée : centres accessibles (CenterAccessService) puis CENTRE actif, et
 * RIEN D'AUTRE — surtout PAS l'année du sélecteur. Un étudiant ne porte pas
 * d'année scolaire, seules ses INSCRIPTIONS en portent une (CLAUDE.md §11,
 * « Students carry NO année at all ») : la même personne inscrite en 2025/2026
 * est la même en 2026/2027. Ajouter ici un whereHas('inscriptions', …année…)
 * ferait disparaître du rapport des étudiants bien réels — c'est exactement ce
 * que GetStudentsList s'interdit.
 *
 * Les filtres sont ceux de l'écran de référence, MOINS « Catégorie d'âge »
 * (hors périmètre demandé) : Sexe et État d'inscription, tous deux optionnels,
 * plus la fenêtre de dates commune à tous les rapports.
 *
 * La fenêtre de dates porte donc sur `created_at` — la date à laquelle la
 * fiche étudiante a été ouverte, la seule date que porte un étudiant. Elle
 * borne le volume, ce qui est sa raison d'être ici : un rapport n'est pas
 * paginé, c'est un document complet.
 *
 * `dateTo` est comparé en `< lendemain` et non en `<= dateTo` : `created_at`
 * est un TIMESTAMP, donc une fiche créée le 04/09 à 14 h est postérieure à
 * « 2026-09-04 00:00:00 » et tomberait hors du rapport alors que
 * l'utilisateur a explicitement demandé ce jour-là. La borne reste sargable
 * (pas de whereDate(), CLAUDE.md §17).
 */
final class GetStudentsReport
{
    /** Clé du rapport dans le sélecteur « Rapport ». */
    public const KEY = 'liste-etudiants';

    /**
     * Les valeurs acceptées par le filtre « État d'inscription ». MÊMES clés
     * machine que la liste Étudiants (GetStudentsList), pour que le rapport et
     * la liste ne puissent pas nommer différemment le même filtre.
     *
     * @var list<string>
     */
    public const ETATS_INSCRIPTION = ['active', 'cancelled', 'none'];

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * Les lignes du document : les fiches les plus anciennes en premier, comme
     * le rapport des inscriptions (N° 1 = la plus ancienne de la période).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function __invoke(
        User $user,
        string $dateFrom,
        string $dateTo,
        string $sexeFilter = '',
        string $inscriptionFilter = '',
    ): Collection {
        $rows = $this->baseQuery($user, $dateFrom, $dateTo, $sexeFilter, $inscriptionFilter)
            ->orderBy('created_at')
            ->orderBy('reference')
            ->get([
                'id', 'reference', 'nom', 'prenom', 'sexe', 'date_naissance',
                'cin', 'telephone', 'niveau', 'created_at',
            ]);

        return $rows->values()->map(fn (Student $student, int $index): array => [
            // Rang DANS LE DOCUMENT (1..n), pas un id — comme la colonne « N° »
            // du rapport des inscriptions.
            'numero' => $index + 1,
            'reference' => (string) $student->reference,
            'etudiant' => $student->nomComplet(),
            'sexe' => (string) ($student->sexe ?? ''),
            'dateNaissance' => $this->formatDate($student->date_naissance),
            // `age()` calcule depuis date_naissance, sans requête : aucun
            // accesseur coûteux en boucle (CLAUDE.md §17).
            'age' => ($age = $student->age()) !== null ? (string) $age : '',
            'cin' => (string) ($student->cin ?? ''),
            'telephone' => (string) ($student->telephone ?? ''),
            'niveau' => (string) ($student->niveau ?? ''),
            'dateCreation' => $this->formatDate($student->created_at),
        ]);
    }

    /**
     * Nombre de lignes du document — l'aperçu de la page compte sans rapatrier
     * les lignes.
     */
    public function count(
        User $user,
        string $dateFrom,
        string $dateTo,
        string $sexeFilter = '',
        string $inscriptionFilter = '',
    ): int {
        return $this->baseQuery($user, $dateFrom, $dateTo, $sexeFilter, $inscriptionFilter)->count();
    }

    /** @return Builder<Student> */
    private function baseQuery(
        User $user,
        string $dateFrom,
        string $dateTo,
        string $sexeFilter,
        string $inscriptionFilter,
    ): Builder {
        return Student::query()
            ->tap(fn (Builder $q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn (Builder $q) => $this->scopeToActiveCenter($q))
            // Plage sargable (CLAUDE.md §17 « Date-query rules ») — jamais
            // whereDate() sur une colonne timestamp.
            ->when($dateFrom !== '', fn (Builder $q) => $q->where('created_at', '>=', $dateFrom.' 00:00:00'))
            ->when($dateTo !== '', fn (Builder $q) => $q->where('created_at', '<', $this->lendemain($dateTo)))
            ->when($sexeFilter !== '', fn (Builder $q) => $q->where('sexe', $sexeFilter))
            // MÊMES trois états que la liste Étudiants, à la lettre.
            ->when($inscriptionFilter === 'active', fn (Builder $q) => $q->whereHas(
                'inscriptions',
                fn ($i) => $i->where('statut', Inscription::STATUT_ACTIVE),
            ))
            ->when($inscriptionFilter === 'cancelled', fn (Builder $q) => $q->whereHas(
                'inscriptions',
                fn ($i) => $i->whereIn('statut', [Inscription::STATUT_ANNULEE, Inscription::STATUT_ARCHIVEE]),
            ))
            ->when($inscriptionFilter === 'none', fn (Builder $q) => $q->whereDoesntHave('inscriptions'));
    }

    /**
     * Le centre du sélecteur du haut. Un étudiant sans centre (ligne héritée)
     * reste visible, exactement comme dans GetStudentsList — le rapport ne
     * cache pas ce que la liste montre.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    private function scopeToActiveCenter(Builder $query): void
    {
        if ($this->context->isAllCenters()) {
            return;
        }

        $query->where(fn ($sub) => $sub
            ->whereNull('etablissement_id')
            ->orWhere('etablissement_id', $this->context->etablissementId()));
    }

    /** Borne haute exclusive : le jour demandé est inclus en entier. */
    private function lendemain(string $date): string
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed === false
            ? $date.' 23:59:59'
            : $parsed->modify('+1 day')->format('Y-m-d H:i:s');
    }

    private function formatDate(mixed $date): string
    {
        return $date instanceof \DateTimeInterface ? $date->format('d/m/Y') : '';
    }
}
