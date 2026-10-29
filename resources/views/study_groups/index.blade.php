@extends('layouts.app')

@section('title', 'Collaborative GMAT Study Groups — GmatGroup')

@section('content')

<div class="bg-white border-bottom py-4">
    <div class="container-fluid px-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb small mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Study Groups</li>
                    </ol>
                </nav>
                <h2 class="fw-bold text-dark mb-1">Peer Study Groups</h2>
                <p class="text-muted small mb-0">Prepare collaboratively with other candidates aiming for 705+ on the GMAT Focus Edition.</p>
            </div>

            <div>
                @auth
                    <button class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                        <i class="bi bi-plus-circle me-1"></i> Form a Study Group
                    </button>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Form Group
                    </a>
                @endauth
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-lg-5 py-5">
    @if ($studyGroups->isEmpty())
        <div class="card border rounded-4 p-5 text-center bg-white shadow-xs">
            <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px;">
                <i class="bi bi-people display-6"></i>
            </div>
            <h4 class="fw-bold text-dark mb-2">No active study groups yet</h4>
            <p class="text-muted small mb-4" style="max-width: 480px; margin: 0 auto;">
                Be the pioneer! Form the first peer group for candidates tackling Data Insights, Critical Reasoning, or Quant strategies.
            </p>
            @auth
                <div>
                    <button class="btn btn-primary rounded-pill px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                        <i class="bi bi-plus-lg me-1"></i> Form First Group
                    </button>
                </div>
            @endauth
        </div>
    @else
        <div class="row g-4">
            @foreach ($studyGroups as $group)
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 border rounded-4 bg-white p-4 shadow-xs d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small">
                                <i class="bi bi-people-fill me-1"></i> {{ $group->members_count }}/{{ $group->capacity }} Members
                            </span>
                            @if ($group->is_private)
                                <span class="badge bg-light text-muted border small"><i class="bi bi-lock me-1"></i> Private</span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle small"><i class="bi bi-globe me-1"></i> Open</span>
                            @endif
                        </div>

                        <h5 class="fw-bold text-dark mb-2">
                            <a href="{{ route('study-groups.show', $group->slug) }}" class="text-decoration-none text-dark">
                                {{ $group->name }}
                            </a>
                        </h5>

                        <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.85rem;">
                            {{ Str::limit($group->description, 120) }}
                        </p>

                        <div class="pt-3 border-top d-flex align-items-center justify-content-between text-muted small">
                            <span>
                                Creator: <strong>{{ $group->creator->name ?? 'Candidate' }}</strong>
                            </span>
                            <a href="{{ route('study-groups.show', $group->slug) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                View Group <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $studyGroups->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

<!-- Create Group Modal -->
@auth
<div class="modal fade" id="createGroupModal" tabindex="-1" aria-labelledby="createGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="createGroupModalLabel">
                    <i class="bi bi-plus-circle text-primary me-2"></i> Form a GMAT Study Group
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('study-groups.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Group Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Focus Quant 85+ Sprint Group" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Study Group Focus & Objectives</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Target exam timeline, specific sections (Quant, CR, Data Insights), meeting frequency..." required></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Max Capacity</label>
                            <input type="number" name="capacity" class="form-control" value="20" min="2" max="50">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check pb-2">
                                <input class="form-check-input" type="checkbox" name="is_private" value="1" id="isPrivateCheck">
                                <label class="form-check-label small text-secondary" for="isPrivateCheck">
                                    Private Group
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Create Study Group</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endauth

@endsection

