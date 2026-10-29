@extends('layouts.admin')

@section('title', 'Admin Console — GmatGroup Education Platform')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('content')

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">GmatGroup Platform Console</h3>
            <p class="text-muted small mb-0">Overview of 414 GMAT Focus curriculum guides, topics, candidate registrations, and study groups.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('home') }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                <i class="bi bi-box-arrow-up-right me-1"></i> Live Study Portal
            </a>
        </div>
    </div>

    <!-- Top KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border rounded-3 p-3 bg-white shadow-xs">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Total Articles</span>
                    <div class="rounded-3 p-2 bg-primary-subtle text-primary">
                        <i class="bi bi-journal-text fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">{{ number_format($metrics['total_articles']) }}</h3>
                <span class="text-success small"><i class="bi bi-check-circle me-1"></i> {{ $metrics['published_articles'] }} Published</span>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border rounded-3 p-3 bg-white shadow-xs">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Tested Topics</span>
                    <div class="rounded-3 p-2 bg-success-subtle text-success">
                        <i class="bi bi-diagram-3 fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">{{ $metrics['total_topics'] }}</h3>
                <span class="text-muted small">Across 4 Core Pillars</span>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border rounded-3 p-3 bg-white shadow-xs">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Study Formats</span>
                    <div class="rounded-3 p-2 bg-warning-subtle text-warning">
                        <i class="bi bi-collection fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">{{ $metrics['total_content_types'] }}</h3>
                <span class="text-muted small">Guides, Drills, Cheat Sheets</span>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border rounded-3 p-3 bg-white shadow-xs">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Total Article Reads</span>
                    <div class="rounded-3 p-2 bg-info-subtle text-info">
                        <i class="bi bi-eye fs-5"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">{{ number_format($metrics['total_views']) }}</h3>
                <span class="text-muted small">Candidate Engagements</span>
            </div>
        </div>
    </div>

    <!-- Pillars & Content Types Breakdown -->
    <div class="row g-4 mb-4">
        <!-- Core Pillars -->
        <div class="col-lg-7">
            <div class="card border rounded-4 bg-white p-4 shadow-xs h-100">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-diagram-3-fill text-primary me-2"></i> Core Pillars & Sub-modules</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pillar Section</th>
                                <th class="text-center">Sub-modules</th>
                                <th class="text-end">Articles</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pillars as $pillar)
                                <tr>
                                    <td class="fw-bold text-dark">
                                        <i class="fa-solid {{ $pillar->icon ?? 'fa-book' }} text-primary me-2"></i>
                                        {{ $pillar->name }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary border">{{ $pillar->children->count() }} modules</span>
                                    </td>
                                    <td class="text-end fw-bold text-primary">
                                        {{ $pillar->children->sum('articles_count') + $pillar->articles_count }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Content Types Distribution -->
        <div class="col-lg-5">
            <div class="card border rounded-4 bg-white p-4 shadow-xs h-100">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-pie-chart-fill text-warning me-2"></i> Content Formats</h5>
                <div class="d-flex flex-column gap-2">
                    @foreach ($contentTypes as $type)
                        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
                            <span class="fw-semibold text-dark small">
                                <i class="fa-solid {{ $type->icon ?? 'fa-file' }} text-primary me-2"></i>
                                {{ $type->name }}
                            </span>
                            <span class="badge bg-white text-dark border">{{ $type->articles_count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Articles & Users -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border rounded-4 bg-white p-4 shadow-xs">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history text-primary me-2"></i> Recent Curriculum Articles</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Topic</th>
                                <th>Format</th>
                                <th class="text-end">Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentArticles as $art)
                                <tr>
                                    <td class="fw-semibold text-dark text-truncate" style="max-width: 280px;">
                                        <a href="{{ route('articles.show', $art->slug) }}" target="_blank" class="text-decoration-none text-dark">
                                            {{ $art->title }}
                                        </a>
                                    </td>
                                    <td><span class="badge bg-light text-secondary border">{{ $art->topic->name ?? 'General' }}</span></td>
                                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $art->contentType->name ?? 'Guide' }}</span></td>
                                    <td class="text-end text-muted">{{ number_format($art->views_count) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border rounded-4 bg-white p-4 shadow-xs">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-people-fill text-success me-2"></i> Registered Accounts</h5>
                <div class="d-flex flex-column gap-2">
                    @foreach ($recentUsers as $usr)
                        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                    {{ strtoupper(substr($usr->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark small">{{ $usr->name }}</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $usr->email }}</div>
                                </div>
                            </div>
                            <span class="badge {{ $usr->isAdmin() ? 'bg-danger text-white' : 'bg-primary text-white' }} small text-uppercase">
                                {{ $usr->role }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
