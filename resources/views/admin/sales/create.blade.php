@extends('layouts.admin')

@section('title', 'Record Sales Transaction')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.sales.index') }}" class="text-decoration-none text-muted">Sales Transactions</a></li>
    <li class="breadcrumb-item active" aria-current="page">New Sale</li>
@endsection

@section('content')
<div class="container-fluid px-0" style="max-width: 760px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Record New Sales Transaction</h4>
            <p class="text-muted small mb-0">Capture client transaction amount to serve as the immutable commission basis</p>
        </div>
        <a href="{{ route('admin.sales.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to History
        </a>
    </div>

    <div class="card-custom p-4">
        <form action="{{ route('admin.sales.store') }}" method="POST">
            @csrf

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="transaction_code" class="form-label small fw-semibold text-secondary">Transaction Reference Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm mono" id="transaction_code" name="transaction_code" value="{{ old('transaction_code', $suggestedCode) }}" required>
                    <small class="text-muted">Unique identifier for audit and reconciliation.</small>
                </div>

                <div class="col-md-6">
                    <label for="member_id" class="form-label small fw-semibold text-secondary">Originating Network Participant <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="member_id" name="member_id" required>
                        <option value="">-- Select Originating Seller --</option>
                        @foreach ($members as $m)
                            <option value="{{ $m->id }}" {{ old('member_id') == $m->id ? 'selected' : '' }}>
                                {{ $m->name }} ({{ $m->member_code }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Direct distributor generating this sale.</small>
                </div>

                <div class="col-md-6">
                    <label for="sale_amount" class="form-label small fw-semibold text-secondary">Original Sale Amount ($) <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0.01" class="form-control mono fs-6 fw-bold" id="sale_amount" name="sale_amount" value="{{ old('sale_amount', '1000.00') }}" required>
                    </div>
                    <small class="text-muted">Monetary amount stored using exact <code>DECIMAL(15,2)</code> precision.</small>
                </div>

                <div class="col-md-6">
                    <label for="transaction_date" class="form-label small fw-semibold text-secondary">Transaction Date & Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" class="form-control form-control-sm" id="transaction_date" name="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d\TH:i')) }}" required>
                </div>

                <div class="col-md-6">
                    <label for="status" class="form-label small fw-semibold text-secondary">Transaction Status <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="status" name="status" required>
                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed (Eligible for Commission Runs)</option>
                        <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>

                <div class="col-12">
                    <label for="reference_note" class="form-label small fw-semibold text-secondary">Contract / Product Description</label>
                    <textarea class="form-control form-control-sm" id="reference_note" name="reference_note" rows="2" placeholder="e.g. Annual Enterprise SaaS Subscription Tier 2">{{ old('reference_note') }}</textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="{{ route('admin.sales.index') }}" class="btn btn-sm btn-light border px-4">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary px-4 fw-semibold">Save Sales Transaction</button>
            </div>
        </form>
    </div>

</div>
@endsection
