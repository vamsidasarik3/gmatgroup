@extends('layouts.admin')

@section('title', 'Commission Models Configuration')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Commission Models</li>
@endsection

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Commission Models Configuration</h4>
            <p class="text-muted small mb-0">Select and calibrate financial algorithms: Weakest Link and Level/Generation Override</p>
        </div>
        <a href="{{ route('admin.calculate.index') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-play-circle me-1"></i> Launch Calculation Run
        </a>
    </div>

    <!-- Models Grid -->
    <div class="row g-4 mb-4">
        @foreach ($models as $m)
            @php
                $settings = is_array($m->settings) ? $m->settings : [];
                $isOverride = ($m->model_type === 'level_override' || $m->model_type === 'generation_override');
            @endphp
            <div class="col-lg-6">
                <div class="card-custom h-100 p-4 border-top border-4 {{ $isOverride ? 'border-primary' : 'border-indigo' }}" style="{{ ! $isOverride ? 'border-top-color: #6366f1 !important;' : '' }}">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge {{ $isOverride ? 'bg-primary-subtle text-primary' : 'bg-indigo-subtle text-indigo-700' }} border py-1.5 px-2.5">
                            {{ $isOverride ? 'Model 2: Level/Generation Override' : 'Model 1: Weakest Link Engine' }}
                        </span>

                        <span class="badge {{ $m->status === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary' }}">
                            {{ strtoupper($m->status) }}
                        </span>
                    </div>

                    <h5 class="fw-bold text-dark mb-2">{{ $m->name }}</h5>
                    <p class="text-muted small mb-4" style="min-height: 40px;">
                        {{ $m->description ?? ($isOverride ? 'Traverses upline generations applying relationship rates from the original sale base.' : 'Bases leader earnings on the minimum performing team leg across multiple branches.') }}
                    </p>

                    <!-- Key Parameters Grid -->
                    <div class="bg-light p-3 rounded-3 mb-4">
                        <div class="row g-2 small">
                            <div class="col-6">
                                <span class="text-muted d-block">Base Rate:</span>
                                <span class="mono fw-bold text-dark">{{ number_format($m->commission_rate, 2) }}%</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Configured Ceiling:</span>
                                <span class="mono fw-bold text-dark">
                                    {{ $isOverride ? ($m->max_generations ?? 5) . ' Generations' : ($m->number_of_levels ?? 10) . ' Levels' }}
                                </span>
                            </div>
                            <div class="col-6 mt-2">
                                <span class="text-muted d-block">Currency:</span>
                                <span class="mono fw-bold text-dark">{{ $settings['currency'] ?? 'USD' }}</span>
                            </div>
                            <div class="col-6 mt-2">
                                <span class="text-muted d-block">Rounding Mode:</span>
                                <span class="mono fw-bold text-dark">{{ $settings['rounding_mode'] ?? 'ROUND_HALF_UP' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                        <div class="small text-muted">
                            <i class="bi bi-cpu me-1 text-primary"></i> <strong>{{ $m->calculation_runs_count }}</strong> runs recorded
                        </div>

                        <div class="d-flex gap-2">
                            <form action="{{ route('admin.commission-models.toggle', $m->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    {{ $m->status === 'active' ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            <a href="{{ route('admin.commission-models.edit', $m->id) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-sliders me-1"></i> Configure Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
