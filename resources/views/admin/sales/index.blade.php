@extends('layouts.admin')

@section('title', 'Sales Transactions')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Sales Transactions</li>
@endsection

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Sales Transactions History</h4>
            <p class="text-muted small mb-0">Record and review original transactions triggering commission calculations</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Record New Sale
            </a>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="card-custom mb-4 p-3">
        <form action="{{ route('admin.sales.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search txn code, notes..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="member_id" class="form-select form-select-sm">
                    <option value="">-- All Originating Sellers --</option>
                    @foreach ($members as $m)
                        <option value="{{ $m->id }}" {{ $selectedMemberId == $m->id ? 'selected' : '' }}>
                            {{ $m->name }} ({{ $m->member_code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- All Statuses --</option>
                    <option value="completed" {{ $selectedStatus === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending" {{ $selectedStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="refunded" {{ $selectedStatus === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark flex-fill">Filter</button>
                @if ($search || $selectedMemberId || $selectedStatus)
                    <a href="{{ route('admin.sales.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Sales Table -->
    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <div>
                <h6 class="fw-bold mb-0 text-dark">Transaction Records</h6>
                <small class="text-muted">Total Volume Recorded: <strong class="text-dark">${{ number_format($totalVolume, 2) }}</strong></small>
            </div>
            <span class="badge badge-soft-success">{{ $completedCount }} Completed</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Transaction Code</th>
                        <th>Originating Member</th>
                        <th class="text-end">Sale Amount</th>
                        <th class="text-center">Commissions</th>
                        <th>Transaction Date</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="ps-3 mono fw-bold text-dark">
                                <a href="{{ route('admin.sales.show', $sale->id) }}" class="text-decoration-none text-primary">
                                    {{ $sale->transaction_code }}
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $sale->member->name ?? 'Unknown Member' }}</div>
                                <small class="text-muted mono">{{ $sale->member->member_code ?? '' }}</small>
                            </td>
                            <td class="text-end mono fw-bold text-dark fs-6">
                                ${{ number_format($sale->sale_amount, 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border mono">
                                    {{ $sale->commissionLedgerEntries->count() }} payouts
                                </span>
                            </td>
                            <td class="small text-muted">
                                {{ \Carbon\Carbon::parse($sale->transaction_date)->format('M d, Y H:i') }}
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $sale->status === 'completed' ? 'bg-success' : 'bg-warning text-dark' }} py-1 px-2" style="font-size: 0.7rem;">
                                    {{ strtoupper($sale->status) }}
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('admin.sales.show', $sale->id) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="View Audit Breakdown">
                                    <i class="bi bi-eye"></i> Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No sales transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sales->hasPages())
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                {{ $sales->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

</div>
@endsection
