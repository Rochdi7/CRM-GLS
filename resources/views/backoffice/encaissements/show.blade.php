{{--
    Read-only payment receipt. Matches EncaissementController@show, which passes
    $encaissement->load(['student', 'fee.inscription.group', 'caisse', 'agent']).
    A payment is never deleted and its amount/till are frozen — this page has no
    destructive action.
--}}
<x-backoffice.layout.app :title="$encaissement->reference">
    <x-backoffice.layout.page-header
        :title="__('Payment').' '.$encaissement->reference"
        :breadcrumbs="[
            __('Dashboard') => route('backoffice.dashboard'),
            __('Payments') => route('backoffice.encaissements.index'),
            $encaissement->reference => null,
        ]" />

    @php
        $fee = $encaissement->fee;
        $inscription = $fee?->inscription;
        $du = (float) ($fee?->montant ?? 0);
        $paye = $fee ? (float) $fee->encaissements()->sum('montant') : 0.0;
        $reste = max(0, $du - $paye);
        $feeStatut = $fee?->statut;
    @endphp

    <div class="row">
        {{-- Receipt --}}
        <div class="col-xl-7">
            <x-backoffice.ui.card :title="__('Receipt')">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1">{{ number_format((float) $encaissement->montant, 2) }} MAD</h4>
                        <p class="text-muted mb-0">{{ __('Received on') }} {{ $encaissement->date_paiement?->format('d/m/Y') }}</p>
                    </div>
                    <x-backoffice.ui.badge variant="info">{{ $encaissement->methode }}</x-backoffice.ui.badge>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Reference') }}</span>
                    <span class="fw-medium"><code>{{ $encaissement->reference }}</code></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Student') }}</span>
                    @if ($encaissement->student)
                        <a href="{{ route('backoffice.students.show', $encaissement->student) }}" class="fw-medium">
                            {{ $encaissement->student->nomComplet() }}
                        </a>
                    @else
                        <span class="fw-medium">—</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Registration') }}</span>
                    @if ($inscription)
                        <a href="{{ route('backoffice.inscriptions.show', $inscription) }}" class="fw-medium">
                            {{ $inscription->reference }}
                        </a>
                    @else
                        <span class="fw-medium">—</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Group') }}</span>
                    <span class="fw-medium">{{ $inscription?->group?->nom ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Till') }}</span>
                    <span class="fw-medium">{{ $encaissement->caisse?->nom ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Recorded by') }}</span>
                    <span class="fw-medium">{{ $encaissement->agent?->nomComplet() ?? '—' }}</span>
                </div>

                @if ($encaissement->methode === \App\Models\Encaissement::METHODE_CHEQUE)
                    <div class="border-top pt-2 mt-2">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Cheque number') }}</span>
                            <span class="fw-medium">{{ $encaissement->numero_cheque ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Bank') }}</span>
                            <span class="fw-medium">{{ $encaissement->banque ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Cheque due date') }}</span>
                            <span class="fw-medium">{{ $encaissement->date_echeance_cheque?->format('d/m/Y') ?? '—' }}</span>
                        </div>
                    </div>
                @endif

                @if ($encaissement->note)
                    <div class="border-top pt-2 mt-2">
                        <span class="text-muted d-block mb-1">{{ __('Note') }}</span>
                        <p class="mb-0">{{ $encaissement->note }}</p>
                    </div>
                @endif
            </x-backoffice.ui.card>
        </div>

        {{-- Fee settled by this payment --}}
        <div class="col-xl-5">
            <x-backoffice.ui.card :title="__('Fee settled')">
                @if ($fee === null)
                    <x-backoffice.ui.empty-state :title="__('No fee linked')" icon="ti ti-receipt" />
                @else
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Fee name') }}</span>
                        <span class="fw-medium">{{ $fee->nom }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Due date') }}</span>
                        <span class="fw-medium">{{ $fee->date_echeance?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Total due') }}</span>
                        <span class="fw-semibold">{{ number_format($du, 2) }} MAD</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Paid') }}</span>
                        <span class="fw-semibold text-success">{{ number_format($paye, 2) }} MAD</span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2 mb-2">
                        <span class="text-muted">{{ __('Remaining') }}</span>
                        <span class="fw-semibold {{ $reste > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($reste, 2) }} MAD</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">{{ __('Status') }}</span>
                        <x-backoffice.ui.badge :variant="$feeStatut === \App\Models\InscriptionFee::STATUT_PAYE ? 'success' : ($feeStatut === \App\Models\InscriptionFee::STATUT_PAYE_PARTIELLEMENT ? 'warning' : 'danger')">
                            {{ $feeStatut }}
                        </x-backoffice.ui.badge>
                    </div>
                @endif
            </x-backoffice.ui.card>
        </div>
    </div>
</x-backoffice.layout.app>
