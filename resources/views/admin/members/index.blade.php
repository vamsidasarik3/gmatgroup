@extends('layouts.admin')

@section('title', 'Members & Hierarchy Management')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Members</li>
@endsection

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Action Buttons -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">Network Members & Hierarchy</h4>
            <p class="text-muted small mb-0">Manage network participants, upline sponsors, downline branches, and override rates</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.members.hierarchy') }}" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-diagram-3"></i>
                <span>Inspect Hierarchy Visualizer</span>
            </a>
            <a href="{{ route('admin.members.create') }}" class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-person-plus-fill"></i>
                <span>Add Member</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card-custom mb-4 p-3">
        <form action="{{ route('admin.members.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, member code (e.g. MEM-001), email..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- All Statuses --</option>
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark flex-fill">Apply Filters</button>
                @if ($search || $status)
                    <a href="{{ route('admin.members.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Members Table Card -->
    <div class="card-custom mb-4">
        <div class="card-header-custom">
            <div>
                <h6 class="fw-bold mb-0 text-dark">Directory Records</h6>
                <small class="text-muted">Showing {{ $members->firstItem() ?? 0 }} to {{ $members->lastItem() ?? 0 }} of {{ $members->total() }} participants</small>
            </div>
            <div class="d-flex gap-2">
                <span class="badge badge-soft-success small">{{ $activeCount }} Active</span>
                <span class="badge badge-soft-danger small">{{ $inactiveCount }} Inactive</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Code / ID</th>
                        <th>Member Name</th>
                        <th>Contact</th>
                        <th>Direct Upline</th>
                        <th class="text-center">Team Downlines</th>
                        <th class="text-end">Personal Sales</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $m)
                        @php
                            $parentRel = $m->parentRelationships->first();
                            $upline = $parentRel?->parentMember;
                            $overrideRate = $parentRel?->override_rate;
                        @endphp
                        <tr>
                            <td class="ps-3 mono fw-bold text-dark">
                                {{ $m->member_code }}
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $m->name }}</div>
                                <small class="text-muted">Joined: {{ $m->joined_at ? \Carbon\Carbon::parse($m->joined_at)->format('M d, Y') : 'N/A' }}</small>
                            </td>
                            <td>
                                <div class="small text-secondary">{{ $m->email ?? 'No email' }}</div>
                                <small class="text-muted">{{ $m->phone ?? 'No phone' }}</small>
                            </td>
                            <td>
                                @if ($upline)
                                    <div class="fw-semibold text-primary small">{{ $upline->name }}</div>
                                    <span class="badge bg-light text-dark border mono" style="font-size: 0.7rem;">
                                        {{ $upline->member_code }} • {{ number_format($overrideRate, 2) }}% Rate
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Root Leader</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border fw-bold px-2">
                                    {{ $m->childRelationships->count() }} direct
                                </span>
                            </td>
                            <td class="text-end mono fw-semibold text-dark">
                                ${{ number_format($m->sales_transactions_sum_sale_amount ?? 0, 2) }}
                                <div class="text-muted small" style="font-size: 0.72rem;">{{ $m->sales_transactions_count }} txns</div>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $m->status === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger' }} py-1 px-2" style="font-size: 0.7rem;">
                                    {{ strtoupper($m->status) }}
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.members.relationships', $m->id) }}" class="btn btn-outline-secondary" title="Manage Hierarchy Relationships & Rates">
                                        <i class="bi bi-diagram-2"></i>
                                    </a>
                                    <a href="{{ route('admin.members.edit', $m->id) }}" class="btn btn-outline-primary" title="Edit Member">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No members found matching your search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($members->hasPages())
            <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center">
                {{ $members->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

</div>
@endsection
