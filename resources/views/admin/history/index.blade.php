@extends('layouts.admin')

@section('title', 'Calculation History')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Calculation History</li>
@endsection

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Calculation Run History</h4>
            <p class="text-muted small mb-0">Audit completed and failed calculation runs, runtime inputs, and summary results</p>
        </div>
        <a href="{{ route('admin.calculate.index') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-play-circle me-1"></i> New Calculation Run
        </a>
    </div>

    <!-- Quick Stats & Filter -->
    <div class="card-custom mb-4 p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <span class="badge badge-soft-success px-3 py-2 small">
                <i class="bi bi-check-circle me-1"></i> {{ $completedCount }} Completed
            </span>
            <span class="badge badge-soft-danger px-3 py-2 small">
                <i class="bi bi-x-circle me-1"></i> {{ $failedCount }} Failed
            </span>
            <span class="small text-muted border-start ps-3">
                Total Output: <strong class="text-success fs-6 mono">${{ number_format($totalCalculated, 2) }}</strong>
            </span>
        </div>

        <form action="{{ route('admin.history.index') }}" method="GET" class="d-flex align-items-center gap-2">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">-- All Statuses --</option>
                <option value="completed" {{ $selectedStatus === 'completed' ? 'selected' : '' }}>Completed Only</option>
                <option value="failed" {{ $selectedStatus === 'failed' ? 'selected' : '' }}>Failed Only</option>
            </select>
            @if ($selectedStatus)
                <a href="{{ route('admin.history.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
        </form>
    </div>

    <!-- Runs Table Card -->
    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <h6 class="fw-bold mb-0 text-dark">Execution Records</h6>
            <small class="text-muted">Showing {{ $runs->firstItem() ?? 0 }} to {{ $runs->lastItem() ?? 0 }} of {{ $runs->total() }} runs</small>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Run Number</th>
                        <th>Model Definition</th>
                        <th>Period</th>
                        <th class="text-end">Sales Evaluated</th>
                        <th class="text-end">Total Payable</th>
                        <th class="text-center">Beneficiaries</th>
                        <th>Execution Time</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($runs as $run)
                        @php
                            $duration = ($run->started_at && $run->completed_at)
                                ? \Carbon\Carbon::parse($run->started_at)->diffInSeconds(\Carbon\Carbon::parse($run->completed_at)) . 's'
                                : 'N/A';
                        @endphp
                        <tr>
                            <td class="ps-3 mono fw-bold text-dark">
                                <a href="{{ route('admin.history.show', $run->id) }}" class="text-decoration-none text-primary">
                                    {{ $run->run_number }}
                                </a>
                                <div class="text-muted mono" style="font-size: 0.68rem;">{{ substr($run->run_uuid, 0, 8) }}...</div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $run->commissionModel->name ?? 'Unknown Model' }}</div>
                                <span class="badge bg-light text-dark border" style="font-size: 0.68rem;">
                                    Ceiling: {{ $run->generation_limit }} Gen
                                </span>
                            </td>
                            <td class="small text-muted">
                                {{ $run->period_start ? \Carbon\Carbon::parse($run->period_start)->format('M d, Y') : 'N/A' }}<br>
                                {{ $run->period_end ? \Carbon\Carbon::parse($run->period_end)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td class="text-end mono small">
                                ${{ number_format($run->total_sales_amount, 2) }}
                            </td>
                            <td class="text-end mono fw-bold text-success fs-6">
                                ${{ number_format($run->total_commission_payable, 2) }}
                            </td>
                            <td class="text-center fw-bold mono">
                                {{ $run->total_beneficiaries_count }}
                            </td>
                            <td class="small text-muted">
                                <div>{{ \Carbon\Carbon::parse($run->created_at)->format('M d, Y H:i') }}</div>
                                <small class="text-secondary mono" style="font-size: 0.7rem;">Duration: {{ $duration }}</small>
                            </td>
                            <td class="text-center">
                                @if ($run->status === 'completed')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" style="font-size: 0.7rem;">
                                        COMPLETED
                                    </span>
                                @elseif ($run->status === 'failed')
                                    <span class="badge bg-danger py-1 px-2" style="font-size: 0.7rem;">
                                        FAILED
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark py-1 px-2" style="font-size: 0.7rem;">
                                        {{ strtoupper($run->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('admin.history.show', $run->id) }}" class="btn btn-sm btn-outline-secondary py-1 px-2">
                                    <i class="bi bi-eye"></i> Audit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No calculation run history found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($runs->hasPages())
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                {{ $runs->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

</div>
@endsection
