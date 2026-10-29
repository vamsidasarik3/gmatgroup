@extends('layouts.admin')

@section('title', 'Configure Model — ' . $model->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.commission-models.index') }}" class="text-decoration-none text-muted">Commission Models</a></li>
    <li class="breadcrumb-item active" aria-current="page">Configure {{ $model->name }}</li>
@endsection

@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Configure Commission Model</h4>
            <p class="text-muted small mb-0">{{ $model->name }}</p>
        </div>
        <a href="{{ route('admin.commission-models.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Models
        </a>
    </div>

    @php
        $settings = is_array($model->settings) ? $model->settings : [];
        $isOverride = ($model->model_type === 'level_override' || $model->model_type === 'generation_override');
    @endphp

    <div class="card-custom p-4">
        <form action="{{ route('admin.commission-models.update', $model->id) }}" method="POST">
            @csrf
            @method('PUT')

            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">1. General Model Parameters</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label for="name" class="form-label small fw-semibold text-secondary">Model Display Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="name" name="name" value="{{ old('name', $model->name) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label small fw-semibold text-secondary">Operational Status <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="status" name="status" required>
                        <option value="active" {{ old('status', $model->status) === 'active' ? 'selected' : '' }}>Active (Enabled)</option>
                        <option value="inactive" {{ old('status', $model->status) === 'inactive' ? 'selected' : '' }}>Inactive (Disabled)</option>
                    </select>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label small fw-semibold text-secondary">Business Logic Description</label>
                    <textarea class="form-control form-control-sm" id="description" name="description" rows="2">{{ old('description', $model->description) }}</textarea>
                </div>
            </div>

            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Financial Calculation Rules & Ceilings</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="commission_rate" class="form-label small fw-semibold text-secondary">Default Base Commission Rate (%) <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.0001" min="0" max="100" class="form-control mono" id="commission_rate" name="commission_rate" value="{{ old('commission_rate', $model->commission_rate) }}" required>
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted">Standard rate applied where custom relationship rates are absent.</small>
                </div>

                @if ($isOverride)
                    <div class="col-md-6">
                        <label for="max_generations" class="form-label small fw-semibold text-secondary">Maximum Generations Depth Ceiling <span class="text-danger">*</span></label>
                        <input type="number" min="1" max="20" class="form-control form-control-sm mono" id="max_generations" name="max_generations" value="{{ old('max_generations', $model->max_generations ?? 5) }}" required>
                        <small class="text-muted">Generations beyond this ceiling are cut off and ineligible.</small>
                    </div>
                @else
                    <div class="col-md-6">
                        <label for="minimum_leg_count" class="form-label small fw-semibold text-secondary">Minimum Required Child Legs <span class="text-danger">*</span></label>
                        <input type="number" min="1" max="10" class="form-control form-control-sm mono" id="minimum_leg_count" name="minimum_leg_count" value="{{ old('minimum_leg_count', $settings['minimum_leg_count'] ?? 2) }}" required>
                        <small class="text-muted">Number of parallel child legs required to qualify.</small>
                    </div>
                @endif

                <div class="col-md-4">
                    <label for="currency" class="form-label small fw-semibold text-secondary">Currency Code</label>
                    <input type="text" class="form-control form-control-sm mono" id="currency" name="currency" value="{{ old('currency', $settings['currency'] ?? 'USD') }}" maxlength="3">
                </div>

                <div class="col-md-4">
                    <label for="payout_threshold" class="form-label small fw-semibold text-secondary">Minimum Payout Threshold ($)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" step="0.01" min="0" class="form-control mono" id="payout_threshold" name="payout_threshold" value="{{ old('payout_threshold', $settings['payout_threshold'] ?? '10.00') }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <label for="rounding_mode" class="form-label small fw-semibold text-secondary">Rounding Policy</label>
                    <select class="form-select form-select-sm" id="rounding_mode" name="rounding_mode">
                        <option value="ROUND_HALF_UP" {{ old('rounding_mode', $settings['rounding_mode'] ?? '') === 'ROUND_HALF_UP' ? 'selected' : '' }}>ROUND_HALF_UP (Standard)</option>
                        <option value="ROUND_FLOOR" {{ old('rounding_mode', $settings['rounding_mode'] ?? '') === 'ROUND_FLOOR' ? 'selected' : '' }}>ROUND_FLOOR (Conservative)</option>
                        <option value="ROUND_CEILING" {{ old('rounding_mode', $settings['rounding_mode'] ?? '') === 'ROUND_CEILING' ? 'selected' : '' }}>ROUND_CEILING</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="{{ route('admin.commission-models.index') }}" class="btn btn-sm btn-light border px-4">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary px-4 fw-semibold">Update Configuration</button>
            </div>
        </form>
    </div>

</div>
@endsection
