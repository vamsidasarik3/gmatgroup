@extends('layouts.app')

@section('title', $article->title . ' — GmatGroup')

@section('content')

<div class="bg-white border-bottom py-3">
    <div class="container-fluid px-lg-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('articles.index') }}" class="text-decoration-none">Library</a></li>
                @if ($article->topic && $article->topic->parent)
                    <li class="breadcrumb-item"><a href="{{ route('topics.show', $article->topic->parent->slug) }}" class="text-decoration-none">{{ $article->topic->parent->name }}</a></li>
                @endif
                @if ($article->topic)
                    <li class="breadcrumb-item"><a href="{{ route('topics.show', $article->topic->slug) }}" class="text-decoration-none">{{ $article->topic->name }}</a></li>
                @endif
                <li class="breadcrumb-item active text-truncate" style="max-width: 250px;" aria-current="page">{{ $article->title }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container-fluid px-lg-5 py-4">
    <div class="row g-4 justify-content-center">
        <!-- Main Article Content -->
        <div class="col-lg-8">
            <article class="bg-white border rounded-4 p-4 p-md-5 shadow-xs mb-4">
                <!-- Meta Header -->
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold">
                        <i class="fa-solid {{ $article->contentType->icon ?? 'fa-file' }} me-1"></i>
                        {{ $article->contentType->name ?? 'Study Guide' }}
                    </span>
                    @if ($article->topic)
                        <a href="{{ route('topics.show', $article->topic->slug) }}" class="badge bg-light text-secondary border rounded-pill px-3 py-1 text-decoration-none">
                            <i class="bi bi-tag me-1"></i> {{ $article->topic->name }}
                        </a>
                    @endif
                    <span class="text-muted small ms-auto">
                        <i class="bi bi-clock me-1"></i> {{ $article->reading_time_minutes }} min read
                    </span>
                    <span class="text-muted small">
                        <i class="bi bi-eye me-1"></i> {{ number_format($article->views_count) }} views
                    </span>
                </div>

                <h1 class="fw-extrabold text-dark tracking-tight mb-3 fs-2">
                    {{ $article->title }}
                </h1>

                @if ($article->summary)
                    <div class="alert alert-light border-start border-primary border-4 py-3 px-3 mb-4 rounded-3 text-secondary small bg-light">
                        <strong class="text-dark d-block mb-1"><i class="bi bi-lightbulb-fill text-warning me-1"></i> Core Takeaway:</strong>
                        {{ $article->summary }}
                    </div>
                @endif

                <!-- Article Body Content -->
                <div class="article-body lh-lg text-secondary mb-4" style="font-size: 1.02rem;">
                    {!! nl2br(e($article->content)) !!}
                </div>

                <!-- Tags -->
                @if ($article->tags && $article->tags->isNotEmpty())
                    <div class="pt-3 border-top mb-4">
                        <span class="small fw-semibold text-muted me-2">Tags:</span>
                        @foreach ($article->tags as $tag)
                            <span class="badge bg-light text-dark border small me-1">#{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif

                <!-- Feedback / Reactions Bar -->
                <div class="p-3 bg-light border rounded-3 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">
                    <div class="small fw-semibold text-secondary">
                        <i class="bi bi-chat-heart text-danger me-1"></i> Was this explanation helpful?
                    </div>
                    <div class="d-flex gap-2">
                        <form action="{{ route('articles.react', $article->slug) }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="helpful">
                            <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3">
                                <i class="bi bi-hand-thumbs-up me-1"></i> Helpful ({{ $reactionCounts['helpful'] ?? 0 }})
                            </button>
                        </form>
                        <form action="{{ route('articles.react', $article->slug) }}" method="POST">
                            @csrf
                            <input type="hidden" name="type" value="insightful">
                            <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="bi bi-star me-1"></i> Insightful ({{ $reactionCounts['insightful'] ?? 0 }})
                            </button>
                        </form>
                    </div>
                </div>
            </article>

            <!-- Community Discussion Section -->
            <div class="bg-white border rounded-4 p-4 p-md-5 shadow-xs mb-4">
                <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-4">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-chat-left-dots text-primary me-2"></i> Questions & Discussions ({{ $article->discussions->count() }})
                    </h5>
                    <span class="badge bg-light text-secondary border">Peer Learning</span>
                </div>

                <!-- Post Comment Form -->
                @auth
                    <form action="{{ route('articles.discussions.store', $article->slug) }}" method="POST" class="mb-4">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Ask a question or share an alternative solution:</label>
                            <textarea name="body" class="form-control" rows="3" placeholder="Explain your doubt, step in question, or timing tactic..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold">
                            Post to Discussion
                        </button>
                    </form>
                @else
                    <div class="card bg-light border p-3 rounded-3 text-center mb-4">
                        <p class="text-muted small mb-2">Have a question regarding this GMAT concept? Join the discussion.</p>
                        <div>
                            <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary rounded-pill px-4">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Join Discussion
                            </a>
                        </div>
                    </div>
                @endauth

                <!-- Discussions List -->
                @if ($article->discussions->isEmpty())
                    <p class="text-muted small text-center py-3 mb-0">No questions posted yet. Be the first to start the discussion!</p>
                @else
                    <div class="d-flex flex-column gap-3">
                        @foreach ($article->discussions as $discussion)
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                            {{ strtoupper(substr($discussion->user->name ?? 'Candidate', 0, 1)) }}
                                        </div>
                                        <span class="fw-semibold small text-dark">{{ $discussion->user->name ?? 'Candidate' }}</span>
                                    </div>
                                    <span class="text-muted small" style="font-size: 0.75rem;">
                                        {{ $discussion->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="text-secondary small mb-0">{{ $discussion->body }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Side: Related Guides & Topic Outline -->
        <div class="col-lg-4">
            <!-- Topic Info Card -->
            @if ($article->topic)
                <div class="card border rounded-3 p-3 bg-white mb-3">
                    <div class="small text-uppercase text-primary fw-bold mb-1">Current Syllabus Section</div>
                    <h6 class="fw-bold text-dark mb-2">{{ $article->topic->name }}</h6>
                    <p class="text-muted small mb-3">{{ Str::limit($article->topic->description, 130) }}</p>
                    <a href="{{ route('topics.show', $article->topic->slug) }}" class="btn btn-sm btn-outline-secondary w-100 rounded-pill">
                        Browse Section Curriculum <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            @endif

            <!-- Related Articles -->
            @if ($relatedArticles->isNotEmpty())
                <div class="card border rounded-3 p-3 bg-white mb-3">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-collection me-1 text-primary"></i> Related Lessons</h6>
                    <div class="d-flex flex-column gap-2">
                        @foreach ($relatedArticles as $rel)
                            <div class="p-2 border-bottom">
                                <a href="{{ route('articles.show', $rel->slug) }}" class="text-decoration-none text-dark fw-semibold small d-block mb-1">
                                    {{ $rel->title }}
                                </a>
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    <i class="bi bi-clock me-1"></i> {{ $rel->reading_time_minutes }}m read
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Study Group CTA -->
            <div class="card border-0 rounded-4 p-4 text-white" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
                <i class="bi bi-people display-6 text-primary mb-2"></i>
                <h6 class="fw-bold mb-2">GMAT Prep Peer Group</h6>
                <p class="small text-light opacity-75 mb-3">
                    Don't study alone. Collaborate with candidates tackling the same Quant and Verbal traps.
                </p>
                <a href="{{ route('study-groups.index') }}" class="btn btn-sm btn-primary rounded-pill fw-semibold">
                    Explore Study Groups <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

