{{-- « Ma caisse » / « Journal des transactions » tab of the Gestion de la
     caisse page — read-only unified journal; mutations live on their modules. --}}
<div>
    @if ($caissesInScope->isEmpty())
        <x-backoffice.ui.card>
            <x-backoffice.ui.empty-state :title="__('No cash register')"
                :message="$scope === 'mine' ? __('Your account is not linked to any cash register.') : __('No cash register is visible in the current context.')"
                icon="ti ti-cash" />
        </x-backoffice.ui.card>
    @else
        {{-- Stat tiles: overall totals for the tills in scope --}}
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <span class="avatar avatar-md bg-success-transparent rounded-circle me-3 d-inline-flex align-items-center justify-content-center">
                            <i class="ti ti-cash-banknote text-success fs-20"></i>
                        </span>
                        <div>
                            <p class="mb-0 text-muted">{{ __('Payments received') }}</p>
                            <h5 class="mb-0 text-success">{{ number_format($totalEncaissements, 2) }} DH</h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <span class="avatar avatar-md bg-danger-transparent rounded-circle me-3 d-inline-flex align-items-center justify-content-center">
                            <i class="ti ti-cash-banknote-off text-danger fs-20"></i>
                        </span>
                        <div>
                            <p class="mb-0 text-muted">{{ __('Expenses') }}</p>
                            <h5 class="mb-0 text-danger">{{ number_format($totalDepenses, 2) }} DH</h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body d-flex align-items-center">
                        <span class="avatar avatar-md bg-primary-transparent rounded-circle me-3 d-inline-flex align-items-center justify-content-center">
                            <i class="ti ti-wallet text-primary fs-20"></i>
                        </span>
                        <div>
                            <p class="mb-0 text-muted">{{ __('Balance') }}</p>
                            <h5 class="mb-0 text-primary">{{ number_format($solde, 2) }} DH</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <x-backoffice.ui.card :title="$scope === 'mine' ? __('My cash register') : __('Transactions journal')">
            <x-slot:tools>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <x-backoffice.forms.select2 id="cj-type-{{ $scope }}" model="typeFilter" live inline
                        width="190px" :placeholder="__('All transaction types')">
                        <option value="paiement">{{ __('Payments') }}</option>
                        <option value="depense">{{ __('Expenses') }}</option>
                        <option value="remboursement">{{ __('Refunds') }}</option>
                        <option value="transfert">{{ __('Transfers') }}</option>
                    </x-backoffice.forms.select2>
                    <input type="date" class="form-control" style="min-width: 150px;"
                        wire:model.live.debounce.400ms="dateFrom" title="{{ __('From') }}">
                    <input type="date" class="form-control" style="min-width: 150px;"
                        wire:model.live.debounce.400ms="dateTo" title="{{ __('To') }}">
                </div>
            </x-slot:tools>

            @if ($scope === 'mine')
                <div class="text-muted mb-1">{{ $caissesInScope->pluck('nom')->implode(', ') }}</div>
            @endif

            {{-- Per-type totals over the filtered set (all pages) --}}
            <div class="mb-3 fw-medium">
                {{ __('Payments') }} : {{ number_format($totauxParType->get(\App\Livewire\Backoffice\Caisses\CaisseJournal::TYPE_PAIEMENT, 0), 2) }} DH
                / {{ __('Refunds') }} : {{ number_format($totauxParType->get(\App\Livewire\Backoffice\Caisses\CaisseJournal::TYPE_REMBOURSEMENT, 0), 2) }} DH
                / {{ __('Expenses') }} : {{ number_format($totauxParType->get(\App\Livewire\Backoffice\Caisses\CaisseJournal::TYPE_DEPENSE, 0), 2) }} DH
                / {{ __('Transfers') }} : {{ number_format($totauxParType->get(\App\Livewire\Backoffice\Caisses\CaisseJournal::TYPE_TRANSFERT, 0), 2) }} DH
            </div>

            @if ($rows->isEmpty())
                <x-backoffice.ui.empty-state :title="__('No transactions for this period')" icon="ti ti-report-money" />
            @else
                <x-backoffice.ui.table>
                    <x-slot:head>
                        <tr>
                            <th>{{ __('Reference') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Transaction') }}</th>
                            <th>{{ __('Student / Third party') }}</th>
                            <th class="text-end">{{ __('Amount') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Agent') }}</th>
                        </tr>
                    </x-slot:head>
                    @foreach ($rows as $row)
                        <tr wire:key="cj-{{ $scope }}-{{ $row['type'] }}-{{ $row['reference'] }}">
                            <td>
                                @if ($row['url'])
                                    <a href="{{ $row['url'] }}"><code>{{ $row['reference'] }}</code></a>
                                @else
                                    <code>{{ $row['reference'] }}</code>
                                @endif
                            </td>
                            <td>
                                @php($badge = ['paiement' => 'badge-soft-success', 'depense' => 'badge-soft-danger', 'remboursement' => 'badge-soft-warning', 'transfert' => 'badge-soft-info'][$row['type']] ?? 'badge-soft-secondary')
                                @php($label = ['paiement' => __('Payment'), 'depense' => __('Expense'), 'remboursement' => __('Refund'), 'transfert' => __('Transfer')][$row['type']] ?? $row['type'])
                                <span class="badge {{ $badge }}">{{ $label }}</span>
                            </td>
                            <td>{{ $row['libelle'] ?? '—' }}</td>
                            <td>{{ $row['tiers'] ?? '—' }}</td>
                            <td class="text-end fw-medium {{ $row['sens'] < 0 ? 'text-danger' : 'text-success' }}">
                                {{ $row['sens'] < 0 ? '−' : '+' }}{{ number_format($row['montant'], 2) }} DH
                            </td>
                            <td>{{ $row['date']?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $row['agent'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </x-backoffice.ui.table>

                {{-- Manual pager (the journal merges four tables, so no query paginator) --}}
                <div class="d-flex align-items-center justify-content-between mt-3">
                    <span class="text-muted">{{ trans_choice(':count transaction|:count transactions', $total, ['count' => $total]) }}</span>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="previousPage"
                            @disabled($page <= 1)>{{ __('Previous') }}</button>
                        <span class="btn btn-sm btn-light disabled">{{ $page }} / {{ $lastPage }}</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" wire:click="nextPage"
                            @disabled($page >= $lastPage)>{{ __('Next') }}</button>
                    </div>
                </div>
            @endif
        </x-backoffice.ui.card>
    @endif
</div>
