@extends('layouts.app')

@section('title', 'GMAT Focus Edition Complete Syllabus & Curriculum Map — GmatGroup')

@section('content')

<div class="bg-white border-bottom py-4">
    <div class="container-fluid px-lg-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-2">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Curriculum Map</li>
            </ol>
        </nav>
        <h2 class="fw-bold text-dark mb-1">GMAT Focus Edition Curriculum Map</h2>
        <p class="text-muted small mb-0">Systematic breakdown of all 20 tested sections across 4 core preparation pillars.</p>
    </div>
</div>

<div class="container-fluid px-lg-5 py-5">
    <div class="row g-4">
        @foreach ($rootTopics as $pillar)
            <div class="col-lg-6">
                <div class="card h-100 border rounded-4 bg-white p-4 shadow-xs">
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width: 44px; height: 44px; background: linear-gradient(135deg, #0284c7, #2563eb);">
                                <i class="fa-solid {{ $pillar->icon ?? 'fa-book' }} fs-5"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0">
                                    <a href="{{ route('topics.show', $pillar->slug) }}" class="text-decoration-none text-dark">
                                        {{ $pillar->name }}
                                    </a>
                                </h5>
                                <span class="text-muted small">{{ $pillar->children->count() }} Sub-Modules</span>
                            </div>
                        </div>

                        <a href="{{ route('topics.show', $pillar->slug) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            Explore Section <i class="bi bi-chevron-right ms-1"></i>
                        </a>
                    </div>

                    <p class="text-muted small mb-4">{{ $pillar->description }}</p>

                    <!-- Sub-topics List -->
                    <div class="d-flex flex-column gap-3">
                        @foreach ($pillar->children as $child)
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="fw-bold text-dark mb-0">
                                        <a href="{{ route('topics.show', $child->slug) }}" class="text-decoration-none text-dark">
                                            {{ $child->name }}
                                        </a>
                                    </h6>
                                    <span class="badge bg-white text-primary border small">
                                        {{ $child->articles->count() }} Lessons
                                    </span>
                                </div>
                                <p class="text-muted small mb-2" style="font-size: 0.8rem;">
                                    {{ $child->description }}
                                </p>

                                @if ($child->children->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1 mt-2">
                                        @foreach ($child->children as $sub)
                                            <a href="{{ route('topics.show', $sub->slug) }}" class="badge bg-white text-secondary border text-decoration-none small py-1 px-2">
                                                {{ $sub->name }} ({{ $sub->articles->count() }})
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@endsection

