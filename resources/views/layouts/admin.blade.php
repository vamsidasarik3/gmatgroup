<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Dashboard') — GmatGroup Platform</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Admin Theme CSS -->
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #0f172a;
            --sidebar-color: #94a3b8;
            --sidebar-hover-bg: #1e293b;
            --sidebar-active-bg: #4f46e5;
            --topbar-height: 64px;
            --bg-body: #f8fafc;
            --primary-accent: #4f46e5;
            --card-border: #e2e8f0;
            --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: #1e293b;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Layout Structure */
        #admin-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Persistent Sidebar */
        #admin-sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--sidebar-bg);
            color: var(--sidebar-color);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            height: var(--topbar-height);
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-weight: 800;
            font-size: 1.1rem;
            color: #ffffff;
            text-decoration: none;
        }

        .sidebar-brand .brand-logo-icon {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            color: #ffffff;
        }

        .sidebar-menu {
            list-style: none;
            padding: 1rem 0.75rem;
            margin: 0;
            flex: 1;
            overflow-y: auto;
        }

        .sidebar-section-heading {
            font-size: 0.68rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.8px;
            color: #64748b;
            padding: 0.75rem 0.75rem 0.25rem;
        }

        .sidebar-item {
            margin-bottom: 4px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.65rem 0.85rem;
            border-radius: 8px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.15s ease-in-out;
        }

        .sidebar-link:hover {
            color: #ffffff;
            background-color: var(--sidebar-hover-bg);
        }

        .sidebar-link.active {
            color: #ffffff;
            background-color: var(--sidebar-active-bg);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .sidebar-link i {
            font-size: 1.15rem;
            width: 22px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.75rem;
            color: #64748b;
        }

        /* Main Content Container */
        #admin-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: var(--bg-body);
            transition: margin-left 0.3s ease;
        }

        /* Top Navigation Header */
        #admin-topbar {
            height: var(--topbar-height);
            background: #ffffff;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 990;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-sidebar-toggle {
            background: transparent;
            border: 1px solid var(--card-border);
            border-radius: 6px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
        }

        .btn-sidebar-toggle:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        /* Content Area */
        .admin-content {
            padding: 1.5rem 1.75rem 3rem;
            flex: 1;
        }

        /* Dashboard Cards */
        .stat-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        .stat-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        .card-custom {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .card-header-custom {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--card-border);
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .badge-soft-success {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .badge-soft-primary {
            background-color: #eef2ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
        }

        .badge-soft-warning {
            background-color: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .badge-soft-danger {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Mobile Viewport Adjustments */
        @media (max-width: 991.98px) {
            #admin-sidebar {
                transform: translateX(-100%);
            }
            #admin-sidebar.show {
                transform: translateX(0);
            }
            #admin-main {
                margin-left: 0;
            }
            .sidebar-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.5);
                z-index: 999;
                display: none;
            }
            .sidebar-backdrop.show {
                display: block;
            }
        }
    </style>

    @stack('styles')
</head>
<body>

    <div id="admin-wrapper">

        <!-- Mobile Backdrop -->
        <div id="sidebar-backdrop" class="sidebar-backdrop"></div>

        <!-- Persistent Sidebar -->
        <aside id="admin-sidebar">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-brand">
                <div class="brand-logo-icon">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>
                <span>GmatGroup</span>
            </a>

            <ul class="sidebar-menu">
                <li class="sidebar-section-heading">Platform Administration</li>
                
                <!-- 1. Dashboard -->
                <li class="sidebar-item">
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- 2. Curriculum Articles Library -->
                <li class="sidebar-item">
                    <a href="{{ route('articles.index') }}" target="_blank" class="sidebar-link">
                        <i class="bi bi-journal-text"></i>
                        <span>Articles Library (414)</span>
                    </a>
                </li>

                <!-- 3. Curriculum Syllabus Map -->
                <li class="sidebar-item">
                    <a href="{{ route('topics.index') }}" target="_blank" class="sidebar-link">
                        <i class="bi bi-diagram-3"></i>
                        <span>Curriculum Map (20)</span>
                    </a>
                </li>

                <!-- 4. Study Groups -->
                <li class="sidebar-item">
                    <a href="{{ route('study-groups.index') }}" target="_blank" class="sidebar-link">
                        <i class="bi bi-people-fill"></i>
                        <span>Study Groups</span>
                    </a>
                </li>

                <li class="sidebar-section-heading mt-2">Public Portal</li>

                <li class="sidebar-item">
                    <a href="{{ route('home') }}" target="_blank" class="sidebar-link">
                        <i class="bi bi-globe"></i>
                        <span>View Live Study Portal</span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-white fw-semibold">GmatGroup v2.4</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Online</span>
                </div>
                <div>GMAT Focus Education Platform</div>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div id="admin-main">
            <!-- Topbar Header -->
            <header id="admin-topbar">
                <div class="topbar-left">
                    <button id="btn-toggle-sidebar" class="btn-sidebar-toggle" title="Toggle Navigation Sidebar">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 small">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none text-muted">Admin</a></li>
                            @yield('breadcrumb')
                        </ol>
                    </nav>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('home') }}" target="_blank" class="btn btn-sm btn-outline-primary d-none d-md-inline-flex align-items-center gap-1">
                        <i class="bi bi-globe"></i>
                        <span>Live Study Portal</span>
                        <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.75rem;"></i>
                    </a>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                {{ strtoupper(substr(auth()->user()->name ?? 'Admin', 0, 1)) }}
                            </div>
                            <span class="d-none d-sm-inline fw-semibold small">{{ auth()->user()->name ?? 'Administrator' }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-1">
                            <li><h6 class="dropdown-header">{{ auth()->user()->email ?? 'admin@gmatgroup.com' }}</h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item small" href="{{ route('home') }}" target="_blank"><i class="bi bi-globe me-2"></i> View Study Portal</a></li>
                            <li>
                                <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item small text-danger"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Page Body Content -->
            <main class="admin-content">
                @include('partials.alerts')
                @yield('content')
            </main>
        </div>

    </div>

    <!-- Bootstrap 5.3.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <!-- jQuery 3.7.1 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <script>
        $(document).ready(function () {
            // Sidebar toggle for mobile & compact mode
            $('#btn-toggle-sidebar').on('click', function () {
                $('#admin-sidebar').toggleClass('show');
                $('#sidebar-backdrop').toggleClass('show');
            });

            $('#sidebar-backdrop').on('click', function () {
                $('#admin-sidebar').removeClass('show');
                $(this).removeClass('show');
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
