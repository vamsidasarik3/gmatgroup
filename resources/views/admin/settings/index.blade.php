@extends('layouts.admin')

@section('title', 'System Settings & Audit')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Settings</li>
@endsection

@section('content')
<div class="container-fluid px-0" style="max-width: 900px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">System Settings & Calculation Defaults</h4>
            <p class="text-muted small mb-0">Financial conventions, decimal rounding constraints, and database status</p>
        </div>
    </div>

    <!-- Calculation Conventions Card -->
    <div class="card-custom mb-4 p-4">
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
            <i class="bi bi-calculator me-1 text-primary"></i> 1. Financial & Calculation Standards
        </h6>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Currency Code</label>
                <input type="text" class="form-control form-control-sm mono" value="USD ($)" readonly>
                <small class="text-muted">Standard monetary unit for all transaction records.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Database Monetary Precision</label>
                <input type="text" class="form-control form-control-sm mono" value="DECIMAL(15, 2)" readonly>
                <small class="text-muted">High-precision monetary values strictly avoiding floating-point drift.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Commission Rate Precision</label>
                <input type="text" class="form-control form-control-sm mono" value="DECIMAL(8, 4) (Percentage convention e.g. 5.0000%)" readonly>
                <small class="text-muted">Percentage format applied as: Base × (Rate ÷ 100).</small>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Rounding Mode</label>
                <input type="text" class="form-control form-control-sm mono" value="ROUND_HALF_UP (Banker's Standard)" readonly>
                <small class="text-muted">Rounds half values upward to the nearest cent.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Default Generation Ceiling Limit</label>
                <input type="text" class="form-control form-control-sm mono" value="5 Generations" readonly>
                <small class="text-muted">Ceiling depth before upline cutoff is enforced.</small>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-semibold text-secondary">Calculation Authority</label>
                <input type="text" class="form-control form-control-sm" value="Server-Side PHP Service Classes Only" readonly>
                <small class="text-muted">Frontend and Blade views strictly display results without authority.</small>
            </div>
        </div>
    </div>

    <!-- Database Audit Card -->
    <div class="card-custom mb-4 p-4">
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
            <i class="bi bi-database-check me-1 text-primary"></i> 2. Database Connection & Record Status
        </h6>

        <div class="row g-3">
            <div class="col-sm-6 col-md-3">
                <div class="bg-light p-3 rounded border text-center">
                    <span class="text-muted small d-block">Database</span>
                    <strong class="mono text-dark">{{ $dbStatus['database_name'] }}</strong>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="bg-light p-3 rounded border text-center">
                    <span class="text-muted small d-block">Driver</span>
                    <strong class="mono text-dark">{{ strtoupper($dbStatus['driver']) }}</strong>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="bg-light p-3 rounded border text-center">
                    <span class="text-muted small d-block">Network Members</span>
                    <strong class="mono text-primary">{{ $dbStatus['members_count'] }}</strong>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="bg-light p-3 rounded border text-center">
                    <span class="text-muted small d-block">Sales Records</span>
                    <strong class="mono text-primary">{{ $dbStatus['sales_count'] }}</strong>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="bg-light p-3 rounded border text-center">
                    <span class="text-muted small d-block">Calculation Runs</span>
                    <strong class="mono text-success">{{ $dbStatus['runs_count'] }}</strong>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="bg-light p-3 rounded border text-center">
                    <span class="text-muted small d-block">Ledger Entries</span>
                    <strong class="mono text-success">{{ $dbStatus['ledger_count'] }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Security & Administrator Session -->
    <div class="card-custom p-4">
        <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
            <i class="bi bi-shield-lock me-1 text-primary"></i> 3. Security & Access Session
        </h6>

        <div class="d-flex align-items-center justify-content-between">
            <div>
                <div class="fw-bold text-dark">{{ auth()->user()->name ?? 'Administrator' }}</div>
                <small class="text-muted">{{ auth()->user()->email ?? 'admin@gnatgroup.com' }} • Active authenticated session</small>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
