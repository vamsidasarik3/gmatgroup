<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Member Sign In — GmatGroup</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .login-card-header {
            padding: 2.25rem 2rem 1.5rem;
            text-align: center;
            background: #ffffff;
        }
        .brand-badge {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #0284c7, #2563eb);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.6rem;
            margin-bottom: 1rem;
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }
        .login-card-body {
            padding: 0 2rem 2.25rem;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="login-card-header">
            <div class="brand-badge">
                <i class="bi bi-person-badge-fill"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">GmatGroup Member Portal</h4>
            <p class="text-muted small mb-0">Sign in to access your commission & network hierarchy</p>
        </div>

        <div class="login-card-body">
            @if (session('error'))
                <div class="alert alert-danger py-2 small mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success py-2 small mb-3">
                    <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger py-2 small mb-3">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold text-secondary">Member Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" class="form-control border-start-0" id="email" name="email" value="{{ old('email') }}" placeholder="member@example.com" required autofocus autocomplete="email">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label small fw-semibold text-secondary mb-0">Password</label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label small text-muted" for="remember">
                            Remember Me
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mb-3" style="background: linear-gradient(135deg, #0284c7, #2563eb); border: none;">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Member Portal
                </button>
            </form>
        </div>
    </div>

</body>
</html>
