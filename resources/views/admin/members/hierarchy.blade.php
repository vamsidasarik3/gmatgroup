@extends('layouts.admin')

@section('title', 'Network Hierarchy Visualizer')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.members.index') }}" class="text-decoration-none text-muted">Members</a></li>
    <li class="breadcrumb-item active" aria-current="page">Hierarchy Visualizer</li>
@endsection

@push('styles')
<style>
    .admin-hierarchy-container {
        position: relative;
        height: calc(100vh - 160px);
        min-height: 600px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid var(--card-border);
        overflow: hidden;
        box-shadow: var(--card-shadow);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">Hierarchy Network Visualizer</h4>
            <p class="text-muted small mb-0">Pan, zoom, inspect branch relationships, and audit commission propagation paths</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.members.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-people me-1"></i> Member Directory
            </a>
            <a href="{{ route('hierarchy.index') }}" target="_blank" class="btn btn-sm btn-primary">
                <i class="bi bi-arrows-fullscreen me-1"></i> Fullscreen Mode
            </a>
        </div>
    </div>

    <!-- Embedded Hierarchy Container -->
    <div class="admin-hierarchy-container">
        <iframe src="{{ route('hierarchy.index') }}" style="width: 100%; height: 100%; border: none;"></iframe>
    </div>

</div>
@endsection
