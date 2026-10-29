@extends('layouts.admin')

@section('title', 'Commission Ledger')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Commission Ledger</li>
@endsection

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Commission Ledger</h4>
            <p class="text-muted small mb-0">Immutable line-item commission entries with generational lineage and rate tracking</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.calculate.index') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-play-circle me-1"></i> New Calculation Run
            </a>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="card-custom mb-4 p-3">
        <form action="{{ route('admin.ledger.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="run_id" class="form-select form-select-sm">
                    <option value="">-- All Calculation Runs --</option>
                    @foreach ($allRuns as $r)
                        <option value="{{ $r->id }}" {{ $selectedRunId == $r->id ? 'selected' : '' }}>
                            {{ $r->run_number }} ({{ \Carbon\Carbon::parse($r->created_at)->format('M d') }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="beneficiary_id" class="form-select form-select-sm">
                    <option value="">-- All Beneficiary Earners --</option>
                    @foreach ($allMembers as $m)
                        <option value="{{ $m->id }}" {{ $selectedBeneficiaryId == $m->id ? 'selected' : '' }}>
                            {{ $m->name }} ({{ $m->member_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="generation" class="form-select form-select-sm">
                    <option value="">-- All Generations --</option>
                    @for ($g = 1; $g <= 7; $g++)
                        <option value="{{ $g }}" {{ $selectedGeneration == $g ? 'selected' : '' }}>Generation {{ $g }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- All Eligibility --</option>
                    <option value="eligible" {{ $selectedStatus === 'eligible' ? 'selected' : '' }}>Eligible Only</option>
                    <option value="ineligible" {{ $selectedStatus === 'ineligible' ? 'selected' : '' }}>Cutoff / Ineligible</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark flex-fill">Filter</button>
                @if ($selectedRunId || $selectedBeneficiaryId || $selectedGeneration || $selectedStatus)
                    <a href="{{ route('admin.ledger.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Ledger Table Card -->
    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <div>
                <h6 class="fw-bold mb-0 text-dark">Ledger Records</h6>
                <small class="text-muted">Total Payable: <strong class="text-success">${{ number_format($totalPayable, 2) }}</strong> across {{ $totalEntriesCount }} entries</small>
            </div>
            <span class="badge bg-light text-dark border">Immutable Financial Audit</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Run Ref</th>
                        <th>Beneficiary Earner</th>
                        <th>Source Sale</th>
                        <th class="text-center">Gen</th>
                        <th class="text-center">Rate</th>
                        <th class="text-end">Payout Amount</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Audit Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr class="{{ ! $entry->is_eligible ? 'table-light opacity-75' : '' }}">
                            <td class="ps-3 mono small">
                                <a href="{{ route('admin.history.show', $entry->calculation_run_id) }}" class="text-decoration-none fw-bold text-primary">
                                    {{ $entry->calculationRun->run_number ?? 'RUN-' . $entry->calculation_run_id }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $entry->beneficiaryMember->name ?? 'Unknown' }}</div>
                                <small class="text-muted mono">{{ $entry->beneficiaryMember->member_code ?? '' }}</small>
                            </td>
                            <td>
                                <div class="mono fw-semibold text-dark">{{ $entry->sourceSale->transaction_code ?? 'TXN' }}</div>
                                <small class="text-muted">
                                    Base: ${{ number_format($entry->sale_amount, 2) }} (Seller: {{ $entry->sellerMember->name ?? 'N/A' }})
                                </small>
                            </td>
                            <td class="text-center fw-bold mono">
                                G{{ $entry->generation }}
                            </td>
                            <td class="text-center mono fw-bold text-indigo-700">
                                {{ number_format($entry->rate_applied, 2) }}%
                            </td>
                            <td class="text-end mono fw-bold {{ $entry->is_eligible ? 'text-success' : 'text-danger' }} fs-6">
                                ${{ number_format($entry->commission_amount, 2) }}
                            </td>
                            <td class="text-center">
                                @if ($entry->is_eligible)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 0.68rem;">
                                        APPROVED
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-1 px-2" style="font-size: 0.68rem;" title="{{ $entry->ineligibility_reason }}">
                                        CUTOFF
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" data-bs-toggle="modal" data-bs-target="#ledgerModal-{{ $entry->id }}">
                                    <i class="bi bi-info-circle"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Calculation Detail Modal -->
                        <div class="modal fade" id="ledgerModal-{{ $entry->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header border-bottom">
                                        <h6 class="modal-title fw-bold text-dark">
                                            <i class="bi bi-calculator-fill text-primary me-2"></i>Commission Calculation Audit
                                        </h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded-3 bg-light border">
                                            <div>
                                                <span class="small text-muted d-block">Calculated Payout:</span>
                                                <span class="fs-4 fw-bold {{ $entry->is_eligible ? 'text-success' : 'text-danger' }} mono">
                                                    ${{ number_format($entry->commission_amount, 2) }}
                                                </span>
                                            </div>
                                            <span class="badge {{ $entry->is_eligible ? 'bg-success' : 'bg-danger' }}">
                                                {{ $entry->is_eligible ? 'ELIGIBLE PAYOUT' : 'CUTOFF (EXCLUDED)' }}
                                            </span>
                                        </div>

                                        <div class="list-group list-group-flush border-top border-bottom small mb-3">
                                            <div class="list-group-item px-0 d-flex justify-content-between">
                                                <span class="text-muted">Calculation Run:</span>
                                                <span class="mono fw-bold">{{ $entry->calculationRun->run_number ?? 'N/A' }}</span>
                                            </div>
                                            <div class="list-group-item px-0 d-flex justify-content-between">
                                                <span class="text-muted">Beneficiary Earner:</span>
                                                <span class="fw-bold">{{ $entry->beneficiaryMember->name ?? 'N/A' }} ({{ $entry->beneficiaryMember->member_code ?? '' }})</span>
                                            </div>
                                            <div class="list-group-item px-0 d-flex justify-content-between">
                                                <span class="text-muted">Originating Seller:</span>
                                                <span>{{ $entry->sellerMember->name ?? 'N/A' }} ({{ $entry->sellerMember->member_code ?? '' }})</span>
                                            </div>
                                            <div class="list-group-item px-0 d-flex justify-content-between">
                                                <span class="text-muted">Source Sale Code:</span>
                                                <span class="mono">{{ $entry->sourceSale->transaction_code ?? 'N/A' }}</span>
                                            </div>
                                            <div class="list-group-item px-0 d-flex justify-content-between">
                                                <span class="text-muted">Original Sale Amount Base:</span>
                                                <span class="mono fw-bold">${{ number_format($entry->sale_amount, 2) }}</span>
                                            </div>
                                            <div class="list-group-item px-0 d-flex justify-content-between">
                                                <span class="text-muted">Generational Distance:</span>
                                                <span class="mono fw-bold">Generation {{ $entry->generation }}</span>
                                            </div>
                                            <div class="list-group-item px-0 d-flex justify-content-between">
                                                <span class="text-muted">Relationship Commission Rate:</span>
                                                <span class="mono fw-bold text-primary">{{ number_format($entry->rate_applied, 2) }}%</span>
                                            </div>
                                        </div>

                                        <div class="card bg-light border p-3 small">
                                            <strong class="d-block mb-1 text-dark">Calculation Formula:</strong>
                                            <code>${{ number_format($entry->sale_amount, 2) }} × ({{ number_format($entry->rate_applied, 2) }} ÷ 100) = ${{ number_format($entry->commission_amount, 2) }}</code>
                                            @if (! $entry->is_eligible)
                                                <div class="text-danger mt-2">
                                                    <i class="bi bi-x-circle me-1"></i> Ineligibility Reason: {{ $entry->ineligibility_reason ?: 'Exceeded generation ceiling limit' }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top p-2">
                                        <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No commission ledger entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($entries->hasPages())
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                {{ $entries->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

</div>
@endsection
