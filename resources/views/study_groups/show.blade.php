@extends('layouts.app')

@section('title', $group->name . ' — GmatGroup Study Group')

@section('content')

<div class="bg-white border-bottom py-4">
    <div class="container-fluid px-lg-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-2">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('study-groups.index') }}" class="text-decoration-none">Study Groups</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $group->name }}</li>
            </ol>
        </nav>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small">
                        <i class="bi bi-people-fill me-1"></i> {{ $group->members_count }}/{{ $group->capacity }} Members
                    </span>
                    @if ($group->is_private)
                        <span class="badge bg-light text-muted border small"><i class="bi bi-lock me-1"></i> Private</span>
                    @else
                        <span class="badge bg-success-subtle text-success border border-success-subtle small"><i class="bi bi-globe me-1"></i> Public</span>
                    @endif
                </div>
                <h2 class="fw-bold text-dark mb-1">{{ $group->name }}</h2>
                <p class="text-muted small mb-0">{{ $group->description }}</p>
            </div>

            <div>
                @auth
                    @if ($isMember)
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 rounded-pill">
                            <i class="bi bi-check-circle-fill me-1"></i> You are a Member
                        </span>
                    @else
                        <form action="{{ route('study-groups.join', $group->slug) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                                <i class="bi bi-person-plus-fill me-1"></i> Join This Group
                            </button>
                        </form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Join
                    </a>
                @endauth
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-lg-5 py-4">
    <div class="row g-4">
        <!-- Main Chat / Discussion Board -->
        <div class="col-lg-8">
            <div class="card border rounded-4 bg-white p-4 shadow-xs mb-4">
                <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-4">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-chat-dots-fill text-primary me-2"></i> Group Discussion Board
                    </h5>
                    <span class="badge bg-light text-secondary border">Real-time Forum</span>
                </div>

                @auth
                    <form action="{{ route('study-groups.discussions.store', $group->slug) }}" method="POST" class="mb-4">
                        @csrf
                        <div class="mb-3">
                            <textarea name="body" class="form-control" rows="3" placeholder="Share a problem from your mock, ask for a study partner session, or post resources..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold">
                            Post Update
                        </button>
                    </form>
                @else
                    <div class="card bg-light border p-3 rounded-3 text-center mb-4">
                        <p class="text-muted small mb-2">Sign in to participate in this study group discussion.</p>
                        <div>
                            <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Sign In</a>
                        </div>
                    </div>
                @endauth

                <!-- Messages -->
                @if ($group->discussions->isEmpty())
                    <p class="text-muted small text-center py-4 mb-0">No discussions posted in this group yet. Start the conversation!</p>
                @else
                    <div class="d-flex flex-column gap-3">
                        @foreach ($group->discussions as $msg)
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                            {{ strtoupper(substr($msg->user->name ?? 'Student', 0, 1)) }}
                                        </div>
                                        <span class="fw-semibold small text-dark">{{ $msg->user->name ?? 'Student' }}</span>
                                    </div>
                                    <span class="text-muted small" style="font-size: 0.75rem;">
                                        {{ $msg->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="text-secondary small mb-0">{{ $msg->body }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Group Members Sidebar -->
        <div class="col-lg-4">
            <div class="card border rounded-4 bg-white p-4 shadow-xs mb-3">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-people me-1 text-primary"></i> Members ({{ $group->members->count() }})</h6>
                    <span class="badge bg-light text-secondary border">{{ $group->capacity - $group->members->count() }} spots left</span>
                </div>

                <div class="d-flex flex-column gap-2">
                    @foreach ($group->members as $member)
                        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </div>
                                <span class="small fw-semibold text-dark">{{ $member->name }}</span>
                            </div>
                            <span class="badge bg-white text-secondary border small text-capitalize">{{ $member->pivot->role ?? 'member' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

