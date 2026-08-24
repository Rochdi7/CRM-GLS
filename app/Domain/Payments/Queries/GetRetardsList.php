<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-model for "Gestion des recouvrements" (gls-crm-schema.md §9/§11 —
 * InscriptionFee.montant vs its Encaissement rows). A fee is "en retard"
 * when its date_echeance is in the past AND its reste-à-payer is still > 0
 * (fully unpaid or partially paid both count). Both list tabs ("Retards
 * selon la durée" and "Retards selon les critères") share this one query —
 * the durée tab is the same result set pre-filtered to one non-overlapping
 * bucket via $dureeBucket.
 */
final class GetRetardsList
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 25;

    /** Non-overlapping day-late ranges — each overdue fee falls in exactly one. */
    public const BUCKET_1J = '1j';

    public const BUCKET_7J = '7j';

    public const BUCKET_15J = '15j';

    public const BUCKET_30J = '30j';

    public const BUCKET_PLUS_30J = 'plus30j';

    public const BUCKETS = [
        self::BUCKET_1J,
        self::BUCKET_7J,
        self::BUCKET_15J,
        self::BUCKET_30J,
        self::BUCKET_PLUS_30J,
    ];

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    public function __invoke(
        User $user,
        string $groupFilter = '',
        string $fraisFilter = '',
        string $statutFilter = '',
        string $dateFrom = '',
        string $dateTo = '',
        string $dureeBucket = '',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): LengthAwarePaginator {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $today = now()->toDateString();

        $fees = InscriptionFee::query()
            ->with(['inscription.student', 'inscription.group', 'frais'])
            // Paid total per fee computed by the DB in the same query — the
            // per-row montantPaye() this replaced fired one SUM per overdue
            // fee (hundreds of queries on a real centre).
            ->withSum('encaissements', 'montant')
            ->whereNull('masque_le')
            ->whereNotNull('date_echeance')
            ->where('date_echeance', '<', $today)
            ->whereHas('inscription', function (Builder $q) use ($user): void {
                $q->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
                    ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
                    ->tap(fn ($q) => $this->scopeToActiveCenter($q));
            })
            ->when($groupFilter !== '', fn ($q) => $q->whereHas('inscription', fn ($q) => $q->where('group_id', (int) $groupFilter)))
            ->when($fraisFilter !== '', fn ($q) => $q->where('frais_id', (int) $fraisFilter))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_echeance', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('date_echeance', '<=', $dateTo))
            ->orderBy('date_echeance')
            ->get()
            ->map(function (InscriptionFee $fee): ?array {
                $paye = (float) ($fee->encaissements_sum_montant ?? 0);
                $reste = round(max(0, (float) $fee->montant - $paye), 2);

                if ($reste <= 0) {
                    return null;
                }

                $retardJours = (int) $fee->date_echeance->diffInDays(now());
                $statut = $paye > 0 ? InscriptionFee::STATUT_PAYE_PARTIELLEMENT : InscriptionFee::STATUT_NON_PAYE;

                return [
                    'fee' => $fee,
                    'reste' => $reste,
                    'retardJours' => $retardJours,
                    'statut' => $statut,
                    'bucket' => self::bucketForDays($retardJours),
                ];
            })
            ->filter()
            ->when($statutFilter !== '', fn ($rows) => $rows->where('statut', $statutFilter))
            ->when($dureeBucket !== '', fn ($rows) => $rows->where('bucket', $dureeBucket))
            ->values();

        $page = (int) request()->integer('page', 1);
        $slice = $fees->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice->map(fn (array $row): array => $this->mapRow($row)),
            $fees->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );

        return $paginator;
    }

    /**
     * Per-bucket counts for the "Retards selon la durée" tab badges — same
     * filters as __invoke() (minus the bucket itself), ignoring pagination.
     *
     * @return array<string, int>
     */
    public function bucketCounts(
        User $user,
        string $groupFilter = '',
        string $fraisFilter = '',
        string $statutFilter = '',
        string $dateFrom = '',
        string $dateTo = '',
    ): array {
        $today = now()->toDateString();

        $rows = InscriptionFee::query()
            ->withSum('encaissements', 'montant')
            ->whereNull('masque_le')
            ->whereNotNull('date_echeance')
            ->where('date_echeance', '<', $today)
            ->whereHas('inscription', function (Builder $q) use ($user): void {
                $q->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
                    ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
                    ->tap(fn ($q) => $this->scopeToActiveCenter($q));
            })
            ->when($groupFilter !== '', fn ($q) => $q->whereHas('inscription', fn ($q) => $q->where('group_id', (int) $groupFilter)))
            ->when($fraisFilter !== '', fn ($q) => $q->where('frais_id', (int) $fraisFilter))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_echeance', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('date_echeance', '<=', $dateTo))
            ->get();

        $counts = array_fill_keys(self::BUCKETS, 0);

        foreach ($rows as $fee) {
            $paye = (float) ($fee->encaissements_sum_montant ?? 0);
            $reste = round(max(0, (float) $fee->montant - $paye), 2);

            if ($reste <= 0) {
                continue;
            }

            $statut = $paye > 0 ? InscriptionFee::STATUT_PAYE_PARTIELLEMENT : InscriptionFee::STATUT_NON_PAYE;

            if ($statutFilter !== '' && $statut !== $statutFilter) {
                continue;
            }

            $retardJours = (int) $fee->date_echeance->diffInDays(now());
            $counts[self::bucketForDays($retardJours)]++;
        }

        return $counts;
    }

    /** @return array<int, array{value:int,label:string}> */
    public function groupOptions(User $user): array
    {
        return Group::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Group $g): array => ['value' => $g->id, 'label' => $g->nom])
            ->all();
    }

    /** @return array<int, array{value:int,label:string}> */
    public function fraisOptions(): array
    {
        return Frais::query()
            ->where('statut', Frais::STATUT_ACTIF)
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Frais $f): array => ['value' => $f->id, 'label' => $f->nom])
            ->all();
    }

    private static function bucketForDays(int $days): string
    {
        return match (true) {
            $days <= 6 => self::BUCKET_1J,
            $days <= 14 => self::BUCKET_7J,
            $days <= 29 => self::BUCKET_15J,
            $days <= 30 => self::BUCKET_30J,
            default => self::BUCKET_PLUS_30J,
        };
    }

    /**
     * @param array{fee: InscriptionFee, reste: float, retardJours: int, statut: string, bucket: string} $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        $fee = $row['fee'];
        $inscription = $fee->inscription;
        $student = $inscription?->student;

        return [
            'id' => $fee->id,
            'reference' => $inscription?->reference,
            'studentId' => $student?->id,
            'studentNom' => $student?->nomComplet(),
            'studentShowUrl' => $student ? route('backoffice.students.show', $student) : null,
            'telephone' => $student?->telephone,
            'whatsapp' => $student?->whatsapp,
            'groupe' => $inscription?->group?->nom,
            'frais' => $fee->nom,
            'statut' => $row['statut'],
            'dateEcheance' => $fee->date_echeance?->toDateString(),
            'retardJours' => $row['retardJours'],
            'resteAPayer' => number_format($row['reste'], 2, '.', ''),
            'inscriptionShowUrl' => $inscription ? route('backoffice.inscriptions.show', $inscription) : null,
        ];
    }

    private function scopeToActiveCenter(Builder $query): void
    {
        $id = $this->context->etablissementId();

        if ($id === null) {
            return;
        }

        $query->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
    }
}
