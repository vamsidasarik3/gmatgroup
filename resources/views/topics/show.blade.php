@extends('layouts.app')

@section('title', $topic->name . ' — GmatGroup Curriculum')

@section('content')

<div class="bg-white border-bottom py-4">
    <div class="container-fluid px-lg-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-2">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('topics.index') }}" class="text-decoration-none">Curriculum</a></li>
                @if ($topic->parent)
                    <li class="breadcrumb-item"><a href="{{ route('topics.show', $topic->parent->slug) }}" class="text-decoration-none">{{ $topic->parent->name }}</a></li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ $topic->name }}</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width: 44px; height: 44px; background: linear-gradient(135deg, #0284c7, #2563eb);">
                <i class="fa-solid {{ $topic->icon ?? 'fa-book' }} fs-5"></i>
            </div>
            <div>
                <h2 class="fw-bold text-dark mb-1">{{ $topic->name }}</h2>
                <p class="text-muted small mb-0">{{ $topic->description }}</p>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-lg-5 py-4">
    <div class="row g-4">
        <!-- Subtopics Sidebar if present -->
        @if ($topic->children->isNotEmpty())
            <div class="col-lg-3">
                <div class="card border rounded-3 p-3 bg-white mb-3">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-diagram-2 me-1 text-primary"></i> Sub-Modules</h6>
                    <div class="list-group list-group-flush small">
                        <a href="{{ route('topics.show', $topic->slug) }}" class="list-group-item list-group-item-action fw-bold text-primary bg-light">
                            All {{ $topic->name }}
                        </a>
                        @foreach ($topic->children as $child)
                            <a href="{{ route('topics.show', $child->slug) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span>{{ $child->name }}</span>
                                <span class="badge bg-secondary-subtle text-dark">{{ $child->articles()->count() }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="{{ $topic->children->isNotEmpty() ? 'col-lg-9' : 'col-12' }}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small fw-semibold">Lessons & Question Breakdowns ({{ $articles->total() }})</span>
                <a href="{{ route('articles.index', ['topic' => $topic->slug]) }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                    Filter in Library <i class="bi bi-funnel ms-1"></i>
                </a>
            </div>

            @if ($articles->isEmpty())
                <div class="card border rounded-3 p-5 text-center bg-white">
                    <p class="text-muted small mb-0">No articles currently published under this sub-topic.</p>
                </div>
            @else
                <div class="row g-3">
                    @foreach ($articles as $article)
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 article-card p-3 d-flex flex-column">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle tag-pill">
                                        {{ $article->contentType->name ?? 'Lesson' }}
                                    </span>
                                    <span class="text-muted small">
                                        <i class="bi bi-clock me-1"></i> {{ $article->reading_time_minutes }}m
                                    </span>
                                </div>

                                <h6 class="fw-bold text-dark mb-2">
                                    <a href="{{ route('articles.show', $article->slug) }}" class="text-decoration-none text-dark">
                                        {{ $article->title }}
                                    </a>
                                </h6>

                                <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">
                                    {{ Str::limit($article->summary ?? strip_tags($article->content), 100) }}
                                </p>

                                <div class="pt-2 border-top d-flex align-items-center justify-content-between text-muted" style="font-size: 0.75rem;">
                                    <span>
                                        <i class="bi bi-eye me-1"></i> {{ number_format($article->views_count) }} views
                                    </span>
                                    <a href="{{ route('articles.show', $article->slug) }}" class="text-primary fw-semibold text-decoration-none">
                                        Read Guide <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-center mt-4">
                    {{ $articles->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>

@endsection

