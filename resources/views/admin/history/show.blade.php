@extends('layouts.admin')

@section('title', 'Run ' . $run->run_number)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.history.index') }}" class="text-decoration-none text-muted">Calculation History</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $run->run_number }}</li>
@endsection

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Execution Audit — {{ $run->run_number }}</h4>
            <p class="text-muted small mb-0">Immutable snapshot of inputs, runtime rules, and generated commission payouts</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hierarchy.index') }}" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-diagram-3 me-1"></i> Interactive Visualizer
            </a>
            <a href="{{ route('admin.history.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to History
            </a>
        </div>
    </div>

    <!-- 3 KPI Cards for the Run -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card-custom p-3 bg-light border">
                <span class="text-muted small fw-semibold text-uppercase">Total Sales Evaluated</span>
                <h4 class="fw-bold text-dark mt-1 mb-0 mono">${{ number_format($run->total_sales_amount, 2) }}</h4>
                <small class="text-muted">Original transaction base</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom p-3 bg-light border">
                <span class="text-muted small fw-semibold text-uppercase">Total Commission Payable</span>
                <h4 class="fw-bold text-success mt-1 mb-0 mono">${{ number_format($run->total_commission_payable, 2) }}</h4>
                <small class="text-success">{{ $run->total_beneficiaries_count }} eligible beneficiaries</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-custom p-3 bg-light border">
                <span class="text-muted small fw-semibold text-uppercase">Execution Status</span>
                <div class="mt-1 d-flex align-items-center gap-2">
                    <span class="badge {{ $run->status === 'completed' ? 'bg-success' : 'bg-danger' }} fs-6 px-3 py-1.5">
                        {{ strtoupper($run->status) }}
                    </span>
                    <span class="small text-muted mono">Depth Limit: {{ $run->generation_limit }} Gen</span>
                </div>
                <small class="text-muted">Started: {{ \Carbon\Carbon::parse($run->started_at)->format('H:i:s') }} • Finished: {{ \Carbon\Carbon::parse($run->completed_at)->format('H:i:s') }}</small>
            </div>
        </div>
    </div>

    <!-- Inputs & Settings Snapshot -->
    <div class="card-custom mb-4 p-4">
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
            <i class="bi bi-file-earmark-code me-1 text-primary"></i> Runtime Settings Snapshot
        </h6>
        <div class="row g-3 small">
            <div class="col-md-3">
                <span class="text-muted d-block">Commission Model:</span>
                <span class="fw-bold text-dark">{{ $run->commissionModel->name ?? 'N/A' }}</span>
            </div>
            <div class="col-md-3">
                <span class="text-muted d-block">Period Bounds:</span>
                <span class="mono">{{ $run->period_start ?: 'All' }} to {{ $run->period_end ?: 'All' }}</span>
            </div>
            <div class="col-md-3">
                <span class="text-muted d-block">Run UUID:</span>
                <span class="mono text-muted">{{ $run->run_uuid }}</span>
            </div>
            <div class="col-md-3">
                <span class="text-muted d-block">Settings Payload:</span>
                <span class="mono">{{ json_encode($run->settings_snapshot) }}</span>
            </div>
        </div>
    </div>

    <!-- Generated Commission Ledger Entries -->
    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <div>
                <h6 class="fw-bold mb-0 text-dark">Generated Commission Ledger Line Items</h6>
                <small class="text-muted">Showing {{ $ledgerEntries->total() }} ledger entries produced by this calculation run</small>
            </div>
            <a href="{{ route('admin.ledger.index', ['run_id' => $run->id]) }}" class="btn btn-sm btn-outline-secondary">
                Filter Ledger
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Gen</th>
                        <th>Beneficiary Earner</th>
                        <th>Originating Seller</th>
                        <th>Source Sale Code</th>
                        <th class="text-end">Sale Base</th>
                        <th class="text-center">Rate</th>
                        <th class="text-end">Payout Amount</th>
                        <th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ledgerEntries as $entry)
                        <tr>
                            <td class="ps-3 fw-bold mono">G{{ $entry->generation }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $entry->beneficiaryMember->name ?? 'Member' }}</div>
                                <small class="text-muted mono">{{ $entry->beneficiaryMember->member_code ?? '' }}</small>
                            </td>
                            <td>
                                <div>{{ $entry->sellerMember->name ?? 'Seller' }}</div>
                                <small class="text-muted mono">{{ $entry->sellerMember->member_code ?? '' }}</small>
                            </td>
                            <td class="mono small">{{ $entry->sourceSale->transaction_code ?? 'TXN' }}</td>
                            <td class="text-end mono">${{ number_format($entry->sale_amount, 2) }}</td>
                            <td class="text-center mono fw-semibold text-primary">{{ number_format($entry->rate_applied, 2) }}%</td>
                            <td class="text-end mono fw-bold {{ $entry->is_eligible ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($entry->commission_amount, 2) }}
                            </td>
                            <td class="text-center pe-3">
                                <span class="badge {{ $entry->is_eligible ? 'bg-success' : 'bg-danger' }} py-1 px-2" style="font-size: 0.68rem;">
                                    {{ $entry->is_eligible ? 'APPROVED' : 'CUTOFF' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No ledger entries recorded for this calculation run.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($ledgerEntries->hasPages())
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                {{ $ledgerEntries->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

</div>
@endsection
