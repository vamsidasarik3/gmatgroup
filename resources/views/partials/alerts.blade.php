@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm border-0" role="alert">
        <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
        <div>
            <strong>Success:</strong> {{ session('success') }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm border-0" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
        <div>
            <strong>Error:</strong> {{ session('error') }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center mb-4 shadow-sm border-0" role="alert">
        <i class="bi bi-exclamation-circle-fill fs-5 me-2 text-warning"></i>
        <div>
            <strong>Notice:</strong> {{ session('warning') }}
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm border-0" role="alert">
        <div class="d-flex align-items-center mb-1">
            <i class="bi bi-x-circle-fill fs-5 me-2 text-danger"></i>
            <strong>Please resolve the following errors:</strong>
        </div>
        <ul class="mb-0 ps-4 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
