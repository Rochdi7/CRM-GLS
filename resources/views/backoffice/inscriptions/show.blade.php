<x-backoffice.layout.app :title="$inscription->reference">
    <x-backoffice.layout.page-header
        :title="__('Registration').' '.$inscription->reference"
        :breadcrumbs="[
            __('Dashboard') => route('backoffice.dashboard'),
            __('Registrations') => route('backoffice.inscriptions.index'),
            $inscription->reference => null,
        ]" />

    @php
        $totalDu = (float) $inscription->fees->sum('montant');
        $totalPaye = (float) $inscription->fees->sum(fn ($f) => $f->encaissements->sum('montant'));
        $reste = max(0, $totalDu - $totalPaye);
    @endphp

    <div class="row">
        {{-- Summary --}}
        <div class="col-xl-4">
            <x-backoffice.ui.card :title="__('Registration')">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Student') }}</span>
                    <a href="{{ route('backoffice.students.show', $inscription->student) }}" class="fw-medium">{{ $inscription->student?->nomComplet() }}</a>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Group') }}</span>
                    <span class="fw-medium">{{ $inscription->group?->nom ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Academic Year') }}</span>
                    <span class="fw-medium">{{ $inscription->anneeScolaire?->nom ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Date') }}</span>
                    <span class="fw-medium">{{ $inscription->date_inscription?->format('d/m/Y') }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">{{ __('Status') }}</span>
                    <span class="badge badge-soft-info">{{ $inscription->statut }}</span>
                </div>
            </x-backoffice.ui.card>

            {{-- Payment summary --}}
            <x-backoffice.ui.card :title="__('Payment summary')">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Total due') }}</span>
                    <span class="fw-semibold">{{ number_format($totalDu, 2) }} MAD</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Paid') }}</span>
                    <span class="fw-semibold text-success">{{ number_format($totalPaye, 2) }} MAD</span>
                </div>
                <div class="d-flex justify-content-between border-top pt-2">
                    <span class="text-muted">{{ __('Remaining') }}</span>
                    <span class="fw-semibold {{ $reste > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($reste, 2) }} MAD</span>
                </div>
            </x-backoffice.ui.card>
        </div>

        {{-- Fee lines --}}
        <div class="col-xl-8">
            <x-backoffice.ui.card :title="__('Fee lines')">
                @if ($inscription->fees->isEmpty())
                    <x-backoffice.ui.empty-state :title="__('No fee lines')" icon="ti ti-receipt" />
                @else
                    <x-backoffice.ui.table>
                        <x-slot:head>
                            <tr>
                                <th>{{ __('Fee name') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Paid') }}</th>
                                <th>{{ __('Due date') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($inscription->fees as $fee)
                            @php $feePaid = (float) $fee->encaissements->sum('montant'); @endphp
                            <tr>
                                <td class="fw-medium">{{ $fee->nom }}</td>
                                <td>{{ number_format((float) $fee->montant, 2) }} MAD</td>
                                <td>{{ number_format($feePaid, 2) }} MAD</td>
                                <td>{{ $fee->date_echeance?->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge {{ $fee->statut === \App\Models\InscriptionFee::STATUT_PAYE ? 'badge-soft-success' : ($fee->statut === \App\Models\InscriptionFee::STATUT_PAYE_PARTIELLEMENT ? 'badge-soft-warning' : 'badge-soft-danger') }}">
                                        {{ $fee->statut }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </x-backoffice.ui.table>
                @endif
            </x-backoffice.ui.card>
        </div>
    </div>
</x-backoffice.layout.app>
