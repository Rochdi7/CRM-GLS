<x-backoffice.layout.app :title="$student->nomComplet()">
    <x-backoffice.layout.page-header
        :title="$student->nomComplet()"
        :breadcrumbs="[
            __('Dashboard') => route('backoffice.dashboard'),
            __('Students') => route('backoffice.students.index'),
            $student->nomComplet() => null,
        ]" />

    <div class="row">
        {{-- Identity --}}
        <div class="col-xl-4">
            <x-backoffice.ui.card>
                <div class="text-center mb-3">
                    <span class="avatar avatar-xxl rounded-circle bg-primary-transparent d-inline-flex align-items-center justify-content-center overflow-hidden mb-2">
                        @if ($url = $student->getFirstMediaUrl('photo'))
                            <img src="{{ $url }}" alt="" class="w-100 h-100" style="object-fit:cover;">
                        @else
                            <span class="fs-24 fw-bold text-primary">{{ strtoupper(mb_substr($student->prenom, 0, 1)) }}</span>
                        @endif
                    </span>
                    <h5 class="mb-1">{{ $student->nomComplet() }}</h5>
                    <p class="text-muted mb-2"><code>{{ $student->reference }}</code></p>
                    @if ($student->niveau)
                        <span class="badge badge-soft-info">{{ $student->niveau }}</span>
                        {{-- Arbeit/Ausbildung → field, Studium → entrance exam --}}
                        @if ($orientation = $student->orientation())
                            <span class="badge badge-soft-secondary ms-1">{{ __($orientation) }}</span>
                        @endif
                    @endif
                </div>
                <div class="border-top pt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Gender') }}</span>
                        <span class="fw-medium text-end ms-2"><x-backoffice.ui.sexe-icon :sexe="$student->sexe" /></span>
                    </div>
                    @foreach ([
                        __('Date of birth') => $student->date_naissance?->format('d/m/Y'),
                        __('ID card number') => $student->cin,
                        __('Phone') => $student->telephone,
                        __('WhatsApp') => $student->whatsapp,
                        __('Email') => $student->email,
                        __('Address') => $student->adresse,
                        __('Center') => $student->etablissement?->nom_centre,
                    ] as $label => $value)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ $label }}</span>
                            <span class="fw-medium text-end ms-2 text-truncate">{{ $value ?: '—' }}</span>
                        </div>
                    @endforeach
                </div>
                @if ($student->parent_nom || $student->parent_telephone || $student->parent_relation || $student->parent_cin)
                    <div class="border-top pt-3 mt-1">
                        <h6 class="text-muted mb-2">{{ __('Parent / guardian') }}</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Category') }}</span>
                            <span class="fw-medium">{{ $student->parent_relation ?: '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Parent name') }}</span>
                            <span class="fw-medium">{{ $student->parent_nom ?: '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Gender') }}</span>
                            <span class="fw-medium">{{ $student->parent_sexe ? ($student->parent_sexe === 'Homme' ? __('Masculine') : __('Feminine')) : '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('CIN') }}</span>
                            <span class="fw-medium">{{ $student->parent_cin ?: '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Parent phone') }}</span>
                            <span class="fw-medium">{{ $student->parent_telephone ?: '—' }}</span>
                        </div>
                    </div>
                @endif
            </x-backoffice.ui.card>
        </div>

        <div class="col-xl-8">
            {{-- Inscriptions --}}
            <x-backoffice.ui.card :title="__('Registrations')">
                <x-slot:tools>
                    <span class="badge badge-soft-secondary">{{ $student->inscriptions->count() }}</span>
                </x-slot:tools>
                @if ($student->inscriptions->isEmpty())
                    <x-backoffice.ui.empty-state :title="__('No registrations yet')" icon="ti ti-clipboard-list" />
                @else
                    <x-backoffice.ui.table>
                        <x-slot:head>
                            <tr>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Group') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Total') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($student->inscriptions as $insc)
                            <tr>
                                <td><code>{{ $insc->reference }}</code></td>
                                <td>{{ $insc->group?->nom ?? '—' }}</td>
                                <td>{{ $insc->date_inscription?->format('d/m/Y') }}</td>
                                <td>{{ $insc->montant_total !== null ? number_format((float) $insc->montant_total, 2).' MAD' : '—' }}</td>
                                <td><span class="badge badge-soft-info">{{ $insc->statut }}</span></td>
                            </tr>
                        @endforeach
                    </x-backoffice.ui.table>
                @endif
            </x-backoffice.ui.card>

            {{-- Payments --}}
            <x-backoffice.ui.card :title="__('Payments')">
                <x-slot:tools>
                    <span class="badge badge-soft-success">
                        {{ number_format((float) $student->encaissements->sum('montant'), 2) }} MAD
                    </span>
                </x-slot:tools>
                @if ($student->encaissements->isEmpty())
                    <x-backoffice.ui.empty-state :title="__('No payments yet')" icon="ti ti-report-money" />
                @else
                    <x-backoffice.ui.table>
                        <x-slot:head>
                            <tr>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Amount') }}</th>
                                <th>{{ __('Method') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Cash register') }}</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($student->encaissements as $enc)
                            <tr>
                                <td><code>{{ $enc->reference }}</code></td>
                                <td class="fw-medium">{{ number_format((float) $enc->montant, 2) }} MAD</td>
                                <td>{{ $enc->methode }}</td>
                                <td>{{ $enc->date_paiement?->format('d/m/Y') }}</td>
                                <td>{{ $enc->caisse?->nom ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </x-backoffice.ui.table>
                @endif
            </x-backoffice.ui.card>
        </div>
    </div>
</x-backoffice.layout.app>
