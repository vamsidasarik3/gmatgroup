<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Console — GmatGroup Administration</title>

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
            background: linear-gradient(135deg, #090d16 0%, #1e1b4b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .admin-login-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .admin-login-card-header {
            padding: 2.25rem 2rem 1.5rem;
            text-align: center;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
        }
        .brand-badge {
            width: 54px;
            height: 54px;
            background: linear-gradient(135deg, #4f46e5, #312e81);
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.7rem;
            margin-bottom: 1rem;
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
        }
        .admin-login-card-body {
            padding: 2rem;
        }
        .security-banner {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
            color: #64748b;
            text-align: center;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>

    <div class="admin-login-card">
        <div class="admin-login-card-header">
            <div class="brand-badge">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h4 class="fw-bold text-dark mb-1">GmatGroup Admin Console</h4>
            <p class="text-muted small mb-0">Authorized Administrative Personnel Only</p>
        </div>

        <div class="admin-login-card-body">
            <div class="security-banner">
                <i class="bi bi-shield-check text-indigo me-1"></i> Dedicated Administrative Gateway
            </div>

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

            <form action="{{ route('admin.login.post') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold text-secondary">Administrator Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-person-gear"></i>
                        </span>
                        <input type="email" class="form-control border-start-0" id="email" name="email" value="{{ old('email') }}" placeholder="admin@gmatgroup.com" required autofocus autocomplete="email">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label small fw-semibold text-secondary mb-0">Administrative Password</label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-key-fill"></i>
                        </span>
                        <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="Enter password" required autocomplete="current-password">
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

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mb-2" style="background: linear-gradient(135deg, #4f46e5, #3730a3); border: none;">
                    <i class="bi bi-shield-check me-1"></i> Authenticate Console Session
                </button>
            </form>
        </div>
    </div>

</body>
</html>

