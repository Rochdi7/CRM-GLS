<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Models\Cheque;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-model for the Chèques list page — same center/context scoping
 * conventions as every other finance list (GetEncaissementsList et al.).
 */
final class GetChequesList
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    public function __invoke(
        User $user,
        string $numeroFilter = '',
        string $proprietaireFilter = '',
        string $banqueFilter = '',
        string $typeFilter = '',
        string $statutFilter = '',
        string $dateEcheanceFrom = '',
        string $dateEcheanceTo = '',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): LengthAwarePaginator {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $cheques = Cheque::query()
            ->with(['student'])
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(function ($q): void {
                $id = $this->context->etablissementId();
                if ($id !== null) {
                    $q->where(fn ($sub) => $sub->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
                }
            })
            ->when($numeroFilter !== '', fn ($q) => $q->where('numero_cheque', 'ilike', "%{$numeroFilter}%"))
            ->when($proprietaireFilter !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('proprietaire_nom', 'ilike', "%{$proprietaireFilter}%")
                ->orWhereHas('student', fn ($s) => $s
                    ->where('nom', 'ilike', "%{$proprietaireFilter}%")
                    ->orWhere('prenom', 'ilike', "%{$proprietaireFilter}%"))))
            ->when($banqueFilter !== '', fn ($q) => $q->where('banque', $banqueFilter))
            ->when($typeFilter !== '', fn ($q) => $q->where('type', $typeFilter))
            ->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter))
            ->when($dateEcheanceFrom !== '', fn ($q) => $q->whereDate('date_echeance', '>=', $dateEcheanceFrom))
            ->when($dateEcheanceTo !== '', fn ($q) => $q->whereDate('date_echeance', '<=', $dateEcheanceTo))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $cheques->through(fn (Cheque $cheque): array => [
            'id' => $cheque->id,
            'reference' => $cheque->reference,
            'source' => $cheque->source,
            'studentId' => $cheque->student_id,
            'proprietaire' => $cheque->proprietaireLabel(),
            'proprietaireNom' => $cheque->proprietaire_nom,
            'telephone' => $cheque->student?->telephone,
            'whatsapp' => $cheque->student?->whatsapp,
            'numeroCheque' => $cheque->numero_cheque,
            'montant' => number_format((float) $cheque->montant, 2, '.', ''),
            'reste' => number_format($cheque->montantRestant(), 2, '.', ''),
            'banque' => $cheque->banque,
            'dateReception' => $cheque->date_reception?->toDateString(),
            'type' => $cheque->type,
            'dateEcheance' => $cheque->date_echeance?->toDateString(),
            'statut' => $cheque->statut,
            'note' => $cheque->note ?? '',
        ]);

        return $cheques;
    }
}
