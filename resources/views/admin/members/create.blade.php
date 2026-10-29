@extends('layouts.admin')

@section('title', 'Add New Member')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.members.index') }}" class="text-decoration-none text-muted">Members</a></li>
    <li class="breadcrumb-item active" aria-current="page">Add Member</li>
@endsection

@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Register New Member</h4>
            <p class="text-muted small mb-0">Add a network participant and define their placement in the hierarchy</p>
        </div>
        <a href="{{ route('admin.members.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Directory
        </a>
    </div>

    <div class="card-custom p-4">
        <form action="{{ route('admin.members.store') }}" method="POST">
            @csrf

            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">1. Profile Identification</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="member_code" class="form-label small fw-semibold text-secondary">Member Code / Identifier <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm mono" id="member_code" name="member_code" value="{{ old('member_code', 'MEM-' . rand(100, 999)) }}" required placeholder="e.g. MEM-026">
                    <small class="text-muted">Unique alphanumeric code for network tracking.</small>
                </div>
                <div class="col-md-6">
                    <label for="name" class="form-label small fw-semibold text-secondary">Full Legal / Business Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-sm" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Sarah Jenkins">
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label small fw-semibold text-secondary">Email Address</label>
                    <input type="email" class="form-control form-control-sm" id="email" name="email" value="{{ old('email') }}" placeholder="sarah.j@gnatgroup.com">
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label small fw-semibold text-secondary">Phone Number</label>
                    <input type="text" class="form-control form-control-sm" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+1 (555) 000-0000">
                </div>
                <div class="col-md-6">
                    <label for="status" class="form-label small fw-semibold text-secondary">Membership Status <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="status" name="status" required>
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active (Eligible for Override Payouts)</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive (Held / Excluded)</option>
                    </select>
                </div>
            </div>

            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">2. Hierarchy Placement & Relationship Rate</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="parent_id" class="form-label small fw-semibold text-secondary">Direct Upline Sponsor</label>
                    <select class="form-select form-select-sm" id="parent_id" name="parent_id">
                        <option value="">-- No Direct Upline (Root Leader) --</option>
                        @foreach ($potentialParents as $p)
                            <option value="{{ $p->id }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->member_code }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">The upline sponsor who will receive override commissions on this member's team.</small>
                </div>

                <div class="col-md-6">
                    <label for="override_rate" class="form-label small fw-semibold text-secondary">Override Commission Rate (%)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.01" min="0" max="100" class="form-control mono" id="override_rate" name="override_rate" value="{{ old('override_rate', '4.00') }}" placeholder="4.00">
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted">Rate payable to the direct upline on sales from this branch.</small>
                </div>

                <div class="col-md-6">
                    <label for="commission_model_id" class="form-label small fw-semibold text-secondary">Commission Model Association</label>
                    <select class="form-select form-select-sm" id="commission_model_id" name="commission_model_id">
                        <option value="">-- Default Active Model --</option>
                        @foreach ($models as $mod)
                            <option value="{{ $mod->id }}" {{ old('commission_model_id') == $mod->id ? 'selected' : '' }}>
                                {{ $mod->name }} ({{ ucwords(str_replace('_', ' ', $mod->model_type)) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="{{ route('admin.members.index') }}" class="btn btn-sm btn-light border px-4">Cancel</a>
                <button type="submit" class="btn btn-sm btn-primary px-4 fw-semibold">Save Member & Placement</button>
            </div>
        </form>
    </div>

</div>
@endsection
