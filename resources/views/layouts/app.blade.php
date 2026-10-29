<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'GmatGroup — GMAT Focus Edition Prep & Study Hub')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Font Awesome 6 (for topics & content types icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --primary-accent: #2563eb;
            --primary-dark: #1e40af;
            --bg-body: #f8fafc;
            --card-border: #e2e8f0;
            --text-main: #0f172a;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar-brand-badge {
            background: linear-gradient(135deg, #0284c7, #2563eb);
            color: #ffffff;
            font-size: 0.7rem;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .pillar-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid var(--card-border);
            border-radius: 12px;
        }
        .pillar-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }

        .article-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .article-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -4px rgba(0, 0, 0, 0.06);
            border-color: #93c5fd;
        }

        .tag-pill {
            font-size: 0.72rem;
            font-weight: 600;
            border-radius: 6px;
            padding: 0.2rem 0.5rem;
        }

        .footer-link {
            color: #64748b;
            text-decoration: none;
            transition: color 0.15s ease;
        }
        .footer-link:hover {
            color: #0284c7;
        }
    </style>

    @stack('styles')
</head>
<body>

    <!-- Header Navbar -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top py-2 shadow-xs">
        <div class="container-fluid px-lg-5">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-dark me-3" href="{{ route('home') }}">
                <div class="rounded-3 d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 36px; height: 36px; background: linear-gradient(135deg, #0284c7, #2563eb);">
                    <i class="fa-solid fa-graduation-cap fs-6"></i>
                </div>
                <span class="fs-5 tracking-tight">Gmat<span class="text-primary">Group</span></span>
                <span class="navbar-brand-badge d-none d-sm-inline">Focus Hub</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link fw-semibold {{ request()->routeIs('home') ? 'active text-primary' : 'text-secondary' }}" href="{{ route('home') }}">
                            <i class="bi bi-house-door me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold {{ request()->is('topics/quant*') ? 'active text-primary' : 'text-secondary' }}" href="{{ route('topics.show', 'quant') }}">
                            <i class="fa-solid fa-calculator me-1 text-primary"></i> Quant
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold {{ request()->is('topics/verbal*') ? 'active text-primary' : 'text-secondary' }}" href="{{ route('topics.show', 'verbal') }}">
                            <i class="fa-solid fa-feather-pointed me-1 text-success"></i> Verbal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold {{ request()->is('topics/data-insights*') ? 'active text-primary' : 'text-secondary' }}" href="{{ route('topics.show', 'data-insights') }}">
                            <i class="fa-solid fa-chart-column me-1 text-warning"></i> Data Insights
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold {{ request()->is('topics/mba-admissions*') ? 'active text-primary' : 'text-secondary' }}" href="{{ route('topics.show', 'mba-admissions') }}">
                            <i class="fa-solid fa-school-flag me-1 text-danger"></i> MBA Admissions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold {{ request()->routeIs('study-groups.*') ? 'active text-primary' : 'text-secondary' }}" href="{{ route('study-groups.index') }}">
                            <i class="bi bi-people me-1 text-info"></i> Study Groups
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold {{ request()->routeIs('articles.index') ? 'active text-primary' : 'text-secondary' }}" href="{{ route('articles.index') }}">
                            <i class="bi bi-journal-text me-1"></i> Library (414)
                        </a>
                    </li>
                </ul>

                <!-- Right Side Actions & Search -->
                <div class="d-flex align-items-center gap-3">
                    <form action="{{ route('articles.index') }}" method="GET" class="d-none d-xl-flex" style="width: 220px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0 text-muted">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" name="q" class="form-control bg-light border-start-0" placeholder="Search guides..." value="{{ request('q') }}">
                        </div>
                    </form>

                    @auth
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2 rounded-pill px-3 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle fs-6"></i>
                                <span>{{ auth()->user()->name }}</span>
                                <span class="badge bg-primary text-white text-uppercase" style="font-size: 0.65rem;">{{ auth()->user()->role }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                                <li><h6 class="dropdown-header">{{ auth()->user()->email }}</h6></li>
                                @if (auth()->user()->isAdmin())
                                    <li><a class="dropdown-item small" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2 text-primary"></i> Admin Console</a></li>
                                @endif
                                <li><a class="dropdown-item small" href="{{ route('study-groups.index') }}"><i class="bi bi-people me-2"></i> My Study Groups</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item small text-danger"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Student Sign In
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Global Alerts -->
    <div class="container-fluid px-lg-5 pt-3">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1">
        @yield('content')
    </main>

    <!-- Site Footer -->
    <footer class="bg-white border-top mt-5 py-4">
        <div class="container-fluid px-lg-5">
            <div class="row g-4 justify-content-between">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-2 fw-bold text-dark mb-2">
                        <div class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width: 32px; height: 32px; background: linear-gradient(135deg, #0284c7, #2563eb);">
                            <i class="fa-solid fa-graduation-cap fs-6"></i>
                        </div>
                        <span class="fs-5">GmatGroup</span>
                    </div>
                    <p class="text-muted small mb-3">
                        The comprehensive open curriculum and study community for the GMAT Focus Edition. Master Quantitative Reasoning, Verbal Reasoning, Data Insights, and top MBA admissions strategies.
                    </p>
                    <div class="d-flex gap-2">
                        <span class="badge bg-light text-secondary border">414 Guides</span>
                        <span class="badge bg-light text-secondary border">20 Core Topics</span>
                        <span class="badge bg-light text-secondary border">Community Powered</span>
                    </div>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="fw-bold text-dark small text-uppercase mb-3">Core Pillars</h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                        <li><a class="footer-link" href="{{ route('topics.show', 'quant') }}"><i class="fa-solid fa-calculator me-1"></i> Quant Focus</a></li>
                        <li><a class="footer-link" href="{{ route('topics.show', 'verbal') }}"><i class="fa-solid fa-feather-pointed me-1"></i> Verbal Reasoning</a></li>
                        <li><a class="footer-link" href="{{ route('topics.show', 'data-insights') }}"><i class="fa-solid fa-chart-column me-1"></i> Data Insights</a></li>
                        <li><a class="footer-link" href="{{ route('topics.show', 'mba-admissions') }}"><i class="fa-solid fa-school-flag me-1"></i> MBA Admissions</a></li>
                    </ul>
                </div>

                <div class="col-6 col-lg-2">
                    <h6 class="fw-bold text-dark small text-uppercase mb-3">Study Resources</h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                        <li><a class="footer-link" href="{{ route('articles.index', ['type' => 'strategy-guide']) }}">Strategy Guides</a></li>
                        <li><a class="footer-link" href="{{ route('articles.index', ['type' => 'daily-concept']) }}">Daily Concepts</a></li>
                        <li><a class="footer-link" href="{{ route('articles.index', ['type' => 'mock-review']) }}">Mock Reviews</a></li>
                        <li><a class="footer-link" href="{{ route('articles.index', ['type' => 'cheat-sheet']) }}">Formula Cheat Sheets</a></li>
                        <li><a class="footer-link" href="{{ route('study-groups.index') }}">Study Groups</a></li>
                    </ul>
                </div>

                <div class="col-lg-3">
                    <h6 class="fw-bold text-dark small text-uppercase mb-3">Community Portal</h6>
                    <p class="text-muted small">
                        Join peers preparing for target scores of 685+ to 735+. Form study branches, solve daily diagnostic drills, and track your prep.
                    </p>
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="bi bi-person-badge me-1"></i> Member Sign In
                        </a>
                    @endguest
                </div>
            </div>

            <div class="border-top mt-4 pt-3 d-flex flex-column flex-sm-row justify-content-between align-items-center text-muted small">
                <div>&copy; {{ date('Y') }} GmatGroup. All curriculum rights reserved.</div>
                <div class="mt-2 mt-sm-0">
                    <span class="text-secondary">Powered by GmatGroup Platform</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>
