@extends('layouts.admin')

@section('title', 'Calculate Commissions')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Calculate Commissions</li>
@endsection

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Execute Commission Calculation Run</h4>
            <p class="text-muted small mb-0">Select model, configure parameters, and process network commission ledger entries</p>
        </div>
        <a href="{{ route('hierarchy.index') }}" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-diagram-3 me-1"></i> Open Visualizer
        </a>
    </div>

    <div class="row g-4">
        <!-- Configuration & Execution Form -->
        <div class="col-lg-7">
            <div class="card-custom p-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-sliders me-1 text-primary"></i> 1. Select Model & Calculation Parameters
                </h6>

                <form action="{{ route('admin.calculate.run') }}" method="POST" id="calc-form">
                    @csrf

                    <!-- Model Choice -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary mb-2">Commission Model <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            @foreach ($models as $idx => $m)
                                @php
                                    $isOverride = ($m->model_type === 'level_override' || $m->model_type === 'generation_override');
                                @endphp
                                <div class="col-sm-6">
                                    <label class="card p-3 border rounded-3 h-100 cursor-pointer model-card" style="cursor: pointer;">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="commission_model_id" id="model_{{ $m->id }}" value="{{ $m->id }}" {{ $idx === 0 ? 'checked' : '' }}>
                                            <span class="form-check-label fw-bold text-dark small">{{ $m->name }}</span>
                                        </div>
                                        <span class="badge {{ $isOverride ? 'bg-primary-subtle text-primary' : 'bg-indigo-subtle text-indigo-700' }} border align-self-start mb-2" style="font-size: 0.65rem;">
                                            {{ ucwords(str_replace('_', ' ', $m->model_type)) }}
                                        </span>
                                        <p class="text-muted small mb-0" style="font-size: 0.75rem;">
                                            {{ $isOverride ? 'Generational upline traversal based on relationship rates.' : 'Evaluates weakest parallel branch leg volumes.' }}
                                        </p>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Parameters -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="generation_limit" class="form-label small fw-semibold text-secondary">Generation Ceiling Depth <span class="text-danger">*</span></label>
                            <input type="number" min="1" max="20" class="form-control form-control-sm mono" id="generation_limit" name="generation_limit" value="5" required>
                            <small class="text-muted">Maximum generations to compensate (Standard: 5 tiers).</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Target Sales Volume</label>
                            <div class="bg-light p-2 rounded border mono small fw-bold text-dark">
                                ${{ number_format($totalSalesVolume, 2) }} ({{ $completedSalesCount }} completed sales)
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="period_start" class="form-label small fw-semibold text-secondary">Period Start Date</label>
                            <input type="date" class="form-control form-control-sm" id="period_start" name="period_start" value="{{ now()->startOfMonth()->toDateString() }}">
                        </div>

                        <div class="col-md-6">
                            <label for="period_end" class="form-label small fw-semibold text-secondary">Period End Date</label>
                            <input type="date" class="form-control form-control-sm" id="period_end" name="period_end" value="{{ now()->endOfMonth()->toDateString() }}">
                        </div>
                    </div>

                    <div class="alert alert-info border p-3 small mb-4">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        <strong>Execution Guarantee:</strong> Calculation runs in an isolated database transaction. All generated ledger entries are marked with immutable run IDs and prevent duplicate processing.
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2" id="btn-run">
                        <i class="bi bi-play-circle-fill fs-6"></i>
                        <span>Run Commission Calculation</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Recent Runs & Information -->
        <div class="col-lg-5">
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0 text-dark">Recent Executions</h6>
                    <a href="{{ route('admin.history.index') }}" class="btn btn-sm btn-outline-secondary">History</a>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($recentRuns as $run)
                        <a href="{{ route('admin.history.show', $run->id) }}" class="list-group-item list-group-item-action p-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="mono fw-bold text-primary">{{ $run->run_number }}</span>
                                <span class="badge bg-success py-1 px-2" style="font-size: 0.68rem;">{{ strtoupper($run->status) }}</span>
                            </div>
                            <div class="small text-muted mb-1">{{ $run->commissionModel->name ?? 'Model' }}</div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Total Payable:</span>
                                <span class="mono fw-bold text-success">${{ number_format($run->total_commission_payable, 2) }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="p-4 text-center text-muted small">No calculation runs executed yet.</div>
                    @endforelse
                </div>
            </div>

            <div class="card-custom p-4 bg-primary-subtle text-primary border-primary-subtle">
                <h6 class="fw-bold mb-2"><i class="bi bi-eye-fill me-2"></i>Hierarchy Visualizer Link</h6>
                <p class="small text-dark mb-3">After running calculations, you can inspect individual member cards and traced sales in real time using the interactive visualizer.</p>
                <a href="{{ route('admin.members.hierarchy') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-diagram-3 me-1"></i> Launch Hierarchy Visualizer
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
