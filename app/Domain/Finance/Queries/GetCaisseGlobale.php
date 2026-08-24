<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Caisse;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use App\Support\Access\HiddenAccount;

/**
 * « Caisse globale » tab of Gestion de la caisse — where the money of the
 * active centre is, one card per kind of account and, under each card, the
 * accounts of that kind with their stored (CaisseLedger-kept) solde:
 *
 *   Caisse personnelle  = the employees' physical tills (Caissière)
 *   Caisse TPE          = the centre's card account
 *   Caisse bancaire     = the centre's Virement account
 *   Caisse chèque       = the centre's Chèque account
 *
 * Every dirham appears once: the figures are the accounts' own balances,
 * nothing is derived on top (CLAUDE.md §11). Scope = the centres the user
 * may reach, narrowed to the active centre — same rule as the 'all' journal.
 */
final class GetCaisseGlobale
{
    /**
     * Display order + French card labels, keyed by Caisse type.
     *
     * « Caisse externe » is deliberately NOT listed for now (GLS does not use
     * external cash accounts yet — 24/08/2026); add
     * `Caisse::TYPE_EXTERNE => 'Caisse externe'` here when they do. Externe
     * rows still exist and stay visible on « Comptes de caisse ».
     */
    public const LABELS = [
        Caisse::TYPE_CAISSIERE => 'Caisse personnelle',
        Caisse::TYPE_TPE => 'Caisse TPE',
        Caisse::TYPE_VIREMENT => 'Caisse bancaire',
        Caisse::TYPE_CHEQUE => 'Caisse chèque',
    ];

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @return array{
     *     cards: list<array{type: string, label: string, total: string, count: int}>,
     *     comptes: array<string, list<array{id: int, nom: string, centre: string|null, responsable: string|null, solde: string, showUrl: string}>>,
     *     total: string,
     * }
     */
    public function __invoke(User $user): array
    {
        $caisses = Caisse::query()
            ->with(['etablissement', 'responsable'])
            ->tap(fn ($q) => HiddenAccount::hideCaisses($q))
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->when($this->context->etablissementId(), fn ($q, $id) => $q
                ->where(fn ($w) => $w->whereNull('etablissement_id')->orWhere('etablissement_id', $id)))
            ->orderByDesc('solde')
            ->orderBy('nom')
            ->get();

        $byType = $caisses->groupBy('type');
        $cards = [];
        $comptes = [];

        foreach (self::LABELS as $type => $label) {
            $group = $byType->get($type, collect());

            $cards[] = [
                'type' => $type,
                'label' => $label,
                'total' => $this->money((float) $group->sum('solde')),
                'count' => $group->count(),
            ];

            $comptes[$type] = $group->map(fn (Caisse $c): array => [
                'id' => $c->id,
                'nom' => $c->nom,
                'centre' => $c->etablissement?->nom_centre,
                'responsable' => $c->responsable?->nomComplet(),
                'solde' => $this->money((float) $c->solde),
                'showUrl' => route('backoffice.caisses.show', $c),
            ])->values()->all();
        }

        return [
            'cards' => $cards,
            'comptes' => $comptes,
            // Σ of the listed kinds only (an unlisted Externe account is not
            // silently folded into a total the cards don't show).
            'total' => $this->money((float) $caisses->whereIn('type', array_keys(self::LABELS))->sum('solde')),
        ];
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
