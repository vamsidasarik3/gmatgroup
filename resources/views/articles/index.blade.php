@extends('layouts.app')

@section('title', 'GMAT Focus Edition Guides & Practice Library (414 Lessons) — GmatGroup')

@section('content')

<div class="bg-white border-bottom py-4">
    <div class="container-fluid px-lg-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-2">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Articles Library</li>
            </ol>
        </nav>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h2 class="fw-bold text-dark mb-1">GMAT Focus Study Library</h2>
                <p class="text-muted small mb-0">Browse 414 curated strategy breakdowns, formula cheat sheets, and question autopsies.</p>
            </div>

            <!-- Search Form -->
            <form action="{{ route('articles.index') }}" method="GET" class="d-flex gap-2">
                @if (request('topic'))
                    <input type="hidden" name="topic" value="{{ request('topic') }}">
                @endif
                @if (request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                <div class="input-group input-group-sm" style="min-width: 260px;">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Search lessons..." value="{{ $search }}">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container-fluid px-lg-5 py-4">
    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="card border rounded-3 p-3 bg-white mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-funnel me-1 text-primary"></i> Filter by Section</h6>
                    @if ($activeTopic || $activeType || $search)
                        <a href="{{ route('articles.index') }}" class="text-danger small text-decoration-none">Reset</a>
                    @endif
                </div>

                <div class="list-group list-group-flush small">
                    <a href="{{ route('articles.index', array_filter(['type' => request('type'), 'q' => request('q')])) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ !$activeTopic ? 'fw-bold text-primary bg-light' : 'text-secondary' }}">
                        <span>All Sections</span>
                    </a>
                    @foreach ($topics as $root)
                        <a href="{{ route('articles.index', array_filter(['topic' => $root->slug, 'type' => request('type'), 'q' => request('q')])) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $activeTopic && $activeTopic->id === $root->id ? 'fw-bold text-primary bg-light' : 'text-secondary' }}">
                            <span><i class="fa-solid {{ $root->icon ?? 'fa-book' }} me-2 text-primary" style="width: 16px;"></i>{{ $root->name }}</span>
                        </a>
                        @foreach ($root->children as $child)
                            <a href="{{ route('articles.index', array_filter(['topic' => $child->slug, 'type' => request('type'), 'q' => request('q')])) }}" class="list-group-item list-group-item-action ps-4 d-flex justify-content-between align-items-center {{ $activeTopic && $activeTopic->id === $child->id ? 'fw-bold text-primary bg-light' : 'text-muted' }}" style="font-size: 0.8rem;">
                                <span>&mdash; {{ $child->name }}</span>
                            </a>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <!-- Content Type Filter -->
            <div class="card border rounded-3 p-3 bg-white">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-collection me-1 text-primary"></i> Format Type</h6>
                <div class="d-flex flex-column gap-1">
                    <a href="{{ route('articles.index', array_filter(['topic' => request('topic'), 'q' => request('q')])) }}" class="btn btn-sm text-start {{ !$activeType ? 'btn-primary text-white' : 'btn-light text-secondary border' }} small py-1">
                        All Formats
                    </a>
                    @foreach ($contentTypes as $type)
                        <a href="{{ route('articles.index', array_filter(['topic' => request('topic'), 'type' => $type->slug, 'q' => request('q')])) }}" class="btn btn-sm text-start d-flex justify-content-between align-items-center {{ $activeType && $activeType->id === $type->id ? 'btn-primary text-white' : 'btn-light text-secondary border' }} small py-1">
                            <span><i class="fa-solid {{ $type->icon ?? 'fa-file' }} me-1"></i> {{ $type->name }}</span>
                            <span class="badge {{ $activeType && $activeType->id === $type->id ? 'bg-white text-primary' : 'bg-secondary-subtle text-dark' }}">{{ $type->articles_count }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Articles Stream -->
        <div class="col-lg-9">
            <!-- Active Filter Badges -->
            @if ($activeTopic || $activeType || $search)
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 bg-white p-2 border rounded-3 small">
                    <span class="text-muted fw-semibold">Active Filters:</span>
                    @if ($activeTopic)
                        <span class="badge bg-primary text-white">Section: {{ $activeTopic->name }}</span>
                    @endif
                    @if ($activeType)
                        <span class="badge bg-info text-dark">Type: {{ $activeType->name }}</span>
                    @endif
                    @if ($search)
                        <span class="badge bg-warning text-dark">Keyword: "{{ $search }}"</span>
                    @endif
                    <a href="{{ route('articles.index') }}" class="btn btn-sm btn-link text-danger p-0 ms-auto">Clear all</a>
                </div>
            @endif

            @if ($articles->isEmpty())
                <div class="card border rounded-3 p-5 text-center bg-white">
                    <i class="bi bi-search display-4 text-muted mb-3"></i>
                    <h5 class="fw-bold text-dark">No lessons match your criteria</h5>
                    <p class="text-muted small mb-3">Try adjusting your filters or clearing search keywords.</p>
                    <div>
                        <a href="{{ route('articles.index') }}" class="btn btn-sm btn-primary rounded-pill px-4">Browse All Articles</a>
                    </div>
                </div>
            @else
                <div class="row g-3">
                    @foreach ($articles as $article)
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 article-card p-3 d-flex flex-column">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle tag-pill">
                                        <i class="fa-solid {{ $article->contentType->icon ?? 'fa-file' }} me-1"></i>
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
                                    <span class="text-truncate" style="max-width: 140px;">
                                        <i class="bi bi-bookmark text-secondary me-1"></i> {{ $article->topic->name ?? 'General' }}
                                    </span>
                                    <a href="{{ route('articles.show', $article->slug) }}" class="text-primary fw-semibold text-decoration-none">
                                        Read <i class="bi bi-arrow-right"></i>
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

