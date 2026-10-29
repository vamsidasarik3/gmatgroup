@extends('layouts.admin')

@section('title', 'Manage Relationships — ' . $member->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.members.index') }}" class="text-decoration-none text-muted">Members</a></li>
    <li class="breadcrumb-item active" aria-current="page">Relationships ({{ $member->member_code }})</li>
@endsection

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Hierarchy Relationships & Override Rates</h4>
            <p class="text-muted small mb-0">{{ $member->name }} • <span class="mono fw-semibold">{{ $member->member_code }}</span></p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.members.hierarchy') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-diagram-3 me-1"></i> Visualizer Tree
            </a>
            <a href="{{ route('admin.members.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Members
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Add / Assign Relationship Edge -->
        <div class="col-lg-5">
            <div class="card-custom p-4">
                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="bi bi-plus-circle me-1 text-primary"></i> Assign Relationship & Rate
                </h6>

                <form action="{{ route('admin.members.relationships.store', $member->id) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="relation_type" class="form-label small fw-semibold text-secondary">Relationship Direction <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="relation_type" name="relation_type" required>
                            <option value="parent">Assign as Parent Upline (Sponsor of {{ $member->name }})</option>
                            <option value="child">Assign as Child Downline (Team member under {{ $member->name }})</option>
                        </select>
                        <small class="text-muted">Determines whether the target member is upline or downline.</small>
                    </div>

                    <div class="mb-3">
                        <label for="target_member_id" class="form-label small fw-semibold text-secondary">Target Participant <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="target_member_id" name="target_member_id" required>
                            <option value="">-- Select Member --</option>
                            @foreach ($allOtherMembers as $om)
                                <option value="{{ $om->id }}">
                                    {{ $om->name }} ({{ $om->member_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="override_rate" class="form-label small fw-semibold text-secondary">Commission Override Rate (%) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0" max="100" class="form-control mono" id="override_rate" name="override_rate" value="4.00" required>
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Calculated as: Original Sale Base × (Rate ÷ 100).</small>
                    </div>

                    <div class="mb-4">
                        <label for="commission_model_id" class="form-label small fw-semibold text-secondary">Model Scope</label>
                        <select class="form-select form-select-sm" id="commission_model_id" name="commission_model_id">
                            <option value="">-- Global / All Models --</option>
                            @foreach ($models as $mod)
                                <option value="{{ $mod->id }}">{{ $mod->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                        <i class="bi bi-link-45deg me-1"></i> Save Permitted Relationship
                    </button>
                </form>

                <div class="alert alert-light border mt-3 p-2 small text-muted">
                    <i class="bi bi-shield-lock-fill text-indigo-600 me-1"></i>
                    <strong>Integrity Guard:</strong> Server-side graph validation automatically checks for cycles. Any loops are strictly blocked.
                </div>
            </div>
        </div>

        <!-- Right Column: Existing Relationships Lists -->
        <div class="col-lg-7">
            <!-- Parent Upline Sponsors -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-arrow-up-circle-fill text-primary me-2"></i>Direct Upline Sponsors
                    </h6>
                    <span class="badge bg-light text-dark border">{{ $parentRelationships->count() }} direct</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Sponsor Name</th>
                                <th>Code</th>
                                <th class="text-center">Rate</th>
                                <th>Model</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($parentRelationships as $pr)
                                <tr>
                                    <td class="ps-3 fw-bold text-dark">{{ $pr->parentMember->name ?? 'Unknown' }}</td>
                                    <td class="mono small">{{ $pr->parentMember->member_code ?? '' }}</td>
                                    <td class="text-center mono fw-bold text-primary">{{ number_format($pr->override_rate, 2) }}%</td>
                                    <td class="small text-muted">{{ $pr->commissionModel->name ?? 'All Models' }}</td>
                                    <td class="text-end pe-3">
                                        <form action="{{ route('admin.members.relationships.delete', $pr->id) }}" method="POST" onsubmit="return confirm('Remove this upline relationship edge?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Remove Relationship">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted small">No direct upline sponsor assigned. (Root Leader)</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Direct Children Downlines -->
            <div class="card-custom">
                <div class="card-header-custom">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-arrow-down-circle-fill text-success me-2"></i>Direct Downline Team Members
                    </h6>
                    <span class="badge bg-light text-dark border">{{ $childRelationships->count() }} team members</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Team Member</th>
                                <th>Code</th>
                                <th class="text-center">Rate</th>
                                <th>Model</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($childRelationships as $cr)
                                <tr>
                                    <td class="ps-3 fw-bold text-dark">{{ $cr->childMember->name ?? 'Unknown' }}</td>
                                    <td class="mono small">{{ $cr->childMember->member_code ?? '' }}</td>
                                    <td class="text-center mono fw-bold text-success">{{ number_format($cr->override_rate, 2) }}%</td>
                                    <td class="small text-muted">{{ $cr->commissionModel->name ?? 'All Models' }}</td>
                                    <td class="text-end pe-3">
                                        <form action="{{ route('admin.members.relationships.delete', $cr->id) }}" method="POST" onsubmit="return confirm('Remove this downline relationship edge?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Remove Relationship">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted small">No direct downlines sponsored yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
