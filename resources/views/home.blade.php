@extends('layouts.app')

@section('title', 'GmatGroup — Master the GMAT Focus Edition with Precision')

@section('content')

<!-- Hero Section -->
<section class="py-5 bg-white border-bottom">
    <div class="container-fluid px-lg-5">
        <div class="row align-items-center justify-content-between g-4">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-primary-subtle text-primary border border-primary-subtle rounded-pill small fw-semibold mb-3">
                    <i class="fa-solid fa-graduation-cap"></i> GMAT Focus Edition Official Prep Hub
                </div>
                <h1 class="display-5 fw-extrabold text-dark tracking-tight mb-3">
                    Master the <span class="text-primary">GMAT Focus Edition</span> with Precision
                </h1>
                <p class="lead text-muted mb-4 fs-6">
                    A comprehensive, open curriculum built for ambitious candidates targeting 685–735+. Dive into <strong>{{ $stats['total_articles'] }} expert walkthroughs</strong>, high-yield cheat sheets, daily diagnostic questions, and community study groups.
                </p>

                <!-- Search Bar -->
                <form action="{{ route('articles.index') }}" method="GET" class="mb-4">
                    <div class="input-group input-group-lg shadow-sm border rounded-pill overflow-hidden bg-white p-1">
                        <span class="input-group-text bg-white border-0 text-muted ps-3">
                            <i class="bi bi-search fs-5"></i>
                        </span>
                        <input type="text" name="q" class="form-control border-0 shadow-none ps-2 fs-6" placeholder="Search concepts (e.g. Inequalities, Assumption Negation, Table Analysis)..." value="{{ $search }}">
                        <button class="btn btn-primary px-4 fw-semibold rounded-pill" type="submit">
                            Search Library
                        </button>
                    </div>
                </form>

                <!-- Resource Types Bar -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="small text-muted fw-semibold me-1">Resource Formats:</span>
                    @foreach ($contentTypes as $type)
                        <a href="{{ route('articles.index', ['type' => $type->slug]) }}" class="badge text-decoration-none bg-light text-dark border px-2 py-1 rounded-pill small">
                            <i class="fa-solid {{ $type->icon ?? 'fa-file-lines' }} me-1 text-primary"></i>
                            {{ $type->name }} ({{ $type->articles_count }})
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Platform Highlights Card -->
            <div class="col-lg-4">
                <div class="card border shadow-sm rounded-4 bg-light p-4">
                    <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
                        <div class="fw-bold text-dark">Platform Curriculum Overview</div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Updated 2026</span>
                    </div>

                    <div class="row g-3 text-center mb-3">
                        <div class="col-6">
                            <div class="bg-white p-3 rounded-3 border">
                                <div class="fs-3 fw-bold text-primary">{{ $stats['total_articles'] }}</div>
                                <div class="text-muted small">Curated Guides</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white p-3 rounded-3 border">
                                <div class="fs-3 fw-bold text-success">{{ $stats['total_topics'] }}</div>
                                <div class="text-muted small">Sub-Topics</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white p-3 rounded-3 border">
                                <div class="fs-3 fw-bold text-warning">{{ $stats['total_content_types'] }}</div>
                                <div class="text-muted small">Study Formats</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white p-3 rounded-3 border">
                                <div class="fs-3 fw-bold text-info">{{ number_format($stats['total_reads']) }}</div>
                                <div class="text-muted small">Total Reads</div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('topics.index') }}" class="btn btn-outline-primary w-100 fw-semibold rounded-pill">
                        <i class="bi bi-diagram-3 me-1"></i> View Full Curriculum Map
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest GMAT Focus Updates & Articles -->
<section class="py-4 bg-light border-bottom">
    <div class="container-fluid px-lg-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger text-white text-uppercase px-2 py-1 rounded-pill small fw-bold">
                    <i class="fa-solid fa-bolt me-1"></i> Latest Updates & Posts
                </span>
                <span class="text-muted small fw-semibold d-none d-sm-inline">Freshly updated GMAT Focus strategies, news & guides</span>
            </div>
            <a href="{{ route('articles.index') }}" class="text-decoration-none small fw-bold text-primary">
                View All {{ $stats['total_articles'] }} Articles <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-3">
            @foreach ($latestArticles as $latest)
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm rounded-3 p-3 bg-white d-flex flex-column" style="transition: transform 0.15s ease, box-shadow 0.15s ease;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.72rem;">
                                {{ $latest->contentType->name ?? 'Article' }}
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">
                                <i class="fa-regular fa-clock me-1"></i> {{ $latest->published_at ? $latest->published_at->diffForHumans() : 'Recent' }}
                            </span>
                        </div>
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 0.92rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 2.8rem;">
                            <a href="{{ route('articles.show', $latest->slug) }}" class="text-decoration-none text-dark">
                                {{ $latest->title }}
                            </a>
                        </h6>
                        <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.78rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            {{ Str::limit($latest->summary ?? strip_tags($latest->content), 95) }}
                        </p>
                        <div class="pt-2 border-top d-flex align-items-center justify-content-between text-muted" style="font-size: 0.72rem;">
                            <span class="text-truncate" style="max-width: 140px;">
                                <i class="bi bi-tag me-1 text-primary"></i> {{ $latest->topic->name ?? 'GMAT Focus' }}
                            </span>
                            <a href="{{ route('articles.show', $latest->slug) }}" class="text-decoration-none fw-semibold text-primary">
                                Read Guide <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- The 4 Core Exam Pillars -->
<section class="py-5">
    <div class="container-fluid px-lg-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-primary text-uppercase fw-bold small">Syllabus Breakdown</span>
                <h2 class="fw-bold text-dark mb-1">Four Core Pillars of GMAT Preparation</h2>
                <p class="text-muted small mb-0">Master each section through rigorous theoretical foundation and practical question drills.</p>
            </div>
            <a href="{{ route('topics.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill d-none d-md-inline-block">
                All Topics <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            @php
                $pillarMeta = [
                    'quant' => ['color' => '#0284c7', 'bg' => '#e0f2fe', 'icon' => 'fa-calculator', 'badge' => '21 Questions / 45 Mins'],
                    'verbal' => ['color' => '#16a34a', 'bg' => '#dcfce7', 'icon' => 'fa-feather-pointed', 'badge' => '23 Questions / 45 Mins'],
                    'data-insights' => ['color' => '#ca8a04', 'bg' => '#fef9c3', 'icon' => 'fa-chart-column', 'badge' => '20 Questions / 45 Mins'],
                    'mba-admissions' => ['color' => '#dc2626', 'bg' => '#fee2e2', 'icon' => 'fa-school-flag', 'badge' => 'Strategy & Profiles'],
                ];
            @endphp

            @foreach ($rootTopics as $topic)
                @php
                    $meta = $pillarMeta[$topic->slug] ?? ['color' => '#4f46e5', 'bg' => '#e0e7ff', 'icon' => 'fa-book', 'badge' => 'Curriculum'];
                    $childArticlesCount = $topic->children->sum(function($c) { return $c->articles->count(); });
                    $totalCount = $topic->articles_count + $childArticlesCount;
                @endphp
                <div class="col-md-6 col-xl-3">
                    <div class="card h-100 pillar-card bg-white p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background-color: {{ $meta['bg'] }}; color: {{ $meta['color'] }};">
                                <i class="fa-solid {{ $topic->icon ?? $meta['icon'] }} fs-4"></i>
                            </div>
                            <span class="badge bg-light text-secondary border small">{{ $meta['badge'] }}</span>
                        </div>

                        <h5 class="fw-bold text-dark mb-2">
                            <a href="{{ route('topics.show', $topic->slug) }}" class="text-decoration-none text-dark">
                                {{ $topic->name }}
                            </a>
                        </h5>

                        <p class="text-muted small mb-3 flex-grow-1">
                            {{ Str::limit($topic->description, 110) }}
                        </p>

                        <!-- Subtopics Pills -->
                        <div class="mb-3">
                            <div class="small fw-semibold text-secondary mb-1">Key Modules:</div>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach ($topic->children->take(4) as $sub)
                                    <span class="badge bg-light text-dark border small" style="font-size: 0.7rem;">
                                        {{ $sub->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                            <span class="small fw-bold text-primary">
                                <i class="bi bi-journal-check me-1"></i> {{ $totalCount }} Articles
                            </span>
                            <a href="{{ route('topics.show', $topic->slug) }}" class="btn btn-sm btn-light border rounded-pill px-3">
                                Explore <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Featured Guides & Daily Drills -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container-fluid px-lg-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-primary text-uppercase fw-bold small">Hand-picked Strategies</span>
                <h2 class="fw-bold text-dark mb-1">Featured Lessons & Strategy Breakdowns</h2>
                <p class="text-muted small mb-0">High-yield concepts tested repeatedly in official GMAT Focus mocks.</p>
            </div>
            <a href="{{ route('articles.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                View All {{ $stats['total_articles'] }} Guides <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4 mb-4">
            @foreach ($featuredArticles as $article)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 article-card p-3 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle tag-pill">
                                {{ $article->contentType->name ?? 'Guide' }}
                            </span>
                            <span class="text-muted small">
                                <i class="bi bi-clock me-1"></i> {{ $article->reading_time_minutes }}m read
                            </span>
                        </div>

                        <h6 class="fw-bold text-dark mb-2 line-clamp-2">
                            <a href="{{ route('articles.show', $article->slug) }}" class="text-decoration-none text-dark">
                                {{ $article->title }}
                            </a>
                        </h6>

                        <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;">
                            {{ Str::limit($article->summary ?? strip_tags($article->content), 90) }}
                        </p>

                        <div class="pt-2 border-top d-flex align-items-center justify-content-between text-muted" style="font-size: 0.75rem;">
                            <span class="text-truncate" style="max-width: 140px;">
                                <i class="bi bi-bookmark me-1 text-secondary"></i> {{ $article->topic->name ?? 'General' }}
                            </span>
                            <span>
                                <i class="bi bi-eye me-1"></i> {{ $article->views_count }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Latest From the Comprehensive 414 Library -->
<section class="py-5">
    <div class="container-fluid px-lg-5">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="text-primary text-uppercase fw-bold small">Open Curriculum</span>
                <h2 class="fw-bold text-dark mb-1">Browse Recent Lessons & Question Walkthroughs</h2>
                <p class="text-muted small mb-0">Filtered by latest additions across Quantitative, Verbal, and Data Insights.</p>
            </div>
            <a href="{{ route('articles.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                Explore Full Library <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            @foreach ($articles as $article)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 article-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-light text-dark border tag-pill">
                                <i class="fa-solid {{ $article->contentType->icon ?? 'fa-file-lines' }} me-1 text-primary"></i>
                                {{ $article->contentType->name ?? 'Resource' }}
                            </span>
                            <span class="badge bg-light text-secondary border tag-pill">
                                {{ $article->topic->parent->name ?? $article->topic->name ?? 'GMAT Focus' }}
                            </span>
                        </div>

                        <h6 class="fw-bold text-dark mb-2">
                            <a href="{{ route('articles.show', $article->slug) }}" class="text-decoration-none text-dark">
                                {{ $article->title }}
                            </a>
                        </h6>

                        <p class="text-muted small mb-3 flex-grow-1" style="font-size: 0.82rem;">
                            {{ Str::limit($article->summary ?? strip_tags($article->content), 120) }}
                        </p>

                        <div class="pt-2 border-top d-flex align-items-center justify-content-between text-muted small">
                            <span>
                                <i class="bi bi-clock me-1"></i> {{ $article->reading_time_minutes }} mins
                            </span>
                            <a href="{{ route('articles.show', $article->slug) }}" class="text-decoration-none fw-semibold text-primary">
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
    </div>
</section>

<!-- Community Study Group Banner -->
<section class="py-5 bg-white border-top">
    <div class="container-fluid px-lg-5">
        <div class="card border-0 rounded-4 overflow-hidden text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);">
            <div class="card-body p-4 p-md-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <span class="badge bg-primary text-white text-uppercase px-3 py-1 rounded-pill small mb-3">Community Collaborative Prep</span>
                        <h2 class="display-6 fw-bold mb-3">Study Faster Together in Peer Prep Groups</h2>
                        <p class="lead fs-6 text-light opacity-75 mb-4">
                            Connect with fellow candidates targeting 705+ scores. Share error logs, debate boldface argument interpretations, and analyze complex multi-source reasoning data sets in real-time.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="{{ route('study-groups.index') }}" class="btn btn-primary btn-lg rounded-pill px-4 fs-6 fw-semibold shadow-sm">
                                <i class="bi bi-people-fill me-2"></i> Join a Study Group
                            </a>
                            @guest
                                <a href="{{ route('login') }}" class="btn btn-outline-light btn-lg rounded-pill px-4 fs-6 fw-semibold">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Member Sign In
                                </a>
                            @endguest
                        </div>
                    </div>

                    <div class="col-lg-4 text-center d-none d-lg-block">
                        <i class="fa-solid fa-users-rectangle display-1 text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

