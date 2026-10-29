@extends('layouts.admin')

@section('title', 'Edit Member — ' . $member->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.members.index') }}" class="text-decoration-none text-muted">Members</a></li>
    <li class="breadcrumb-item active" aria-current="page">Edit {{ $member->member_code }}</li>
@endsection

@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Edit Member Profile</h4>
            <p class="text-muted small mb-0">{{ $member->name }} • {{ $member->member_code }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.members.relationships', $member->id) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-diagram-2 me-1"></i> Manage Relationships
            </a>
            <a href="{{ route('admin.members.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card-custom p-4">
        <form action="{{ route('admin.members.update', $member->id) }}" method="POST">
            @csrf
            @method('PUT')

            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Identification & Contact Details</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="member_code" class="form-label small fw-semibold text-secondary">Member Code / Identifier <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm mono" id="member_code" name="member_code" value="{{ old('member_code', $member->member_code) }}" required>
                </div>
                <div class="col-md-6">
                    <label for="name" class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="name" name="name" value="{{ old('name', $member->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label small fw-semibold text-secondary">Email Address</label>
                    <input type="email" class="form-control form-control-sm" id="email" name="email" value="{{ old('email', $member->email) }}">
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label small fw-semibold text-secondary">Phone Number</label>
                    <input type="text" class="form-control form-control-sm" id="phone" name="phone" value="{{ old('phone', $member->phone) }}">
                </div>
                <div class="col-md-6">
                    <label for="status" class="form-label small fw-semibold text-secondary">Membership Status <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="status" name="status" required>
                        <option value="active" {{ old('status', $member->status) === 'active' ? 'selected' : '' }}>Active (Eligible for Override Payouts)</option>
                        <option value="inactive" {{ old('status', $member->status) === 'inactive' ? 'selected' : '' }}>Inactive (Held / Excluded)</option>
                    </select>
                </div>
            </div>

            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Direct Upline Sponsor & Override Rate</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="parent_id" class="form-label small fw-semibold text-secondary">Direct Upline Sponsor</label>
                    <select class="form-select form-select-sm" id="parent_id" name="parent_id">
                        <option value="">-- No Direct Upline (Root Leader) --</option>
                        @foreach ($potentialParents as $p)
                            <option value="{{ $p->id }}" {{ old('parent_id', $currentParentRel?->parent_member_id) == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->member_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="override_rate" class="form-label small fw-semibold text-secondary">Direct Override Commission Rate (%)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.01" min="0" max="100" class="form-control mono" id="override_rate" name="override_rate" value="{{ old('override_rate', $currentParentRel?->override_rate ?? '4.00') }}">
                        <span class="input-group-text">%</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <label for="commission_model_id" class="form-label small fw-semibold text-secondary">Model Scope</label>
                    <select class="form-select form-select-sm" id="commission_model_id" name="commission_model_id">
                        <option value="">-- All Active Models --</option>
                        @foreach ($models as $mod)
                            <option value="{{ $mod->id }}" {{ old('commission_model_id', $currentParentRel?->commission_model_id) == $mod->id ? 'selected' : '' }}>
                                {{ $mod->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="{{ route('admin.members.index') }}" class="btn btn-sm btn-light border px-4">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary px-4 fw-semibold">Save Changes</button>
            </div>
        </form>
    </div>

</div>
@endsection
