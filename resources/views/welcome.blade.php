@extends('layouts.app')

@section('title', 'Welcome to GMAT Group - Your Success Starts Here')

@section('content')
<div class="hero-section text-center">
    <div class="container">
        <h1 class="display-3 fw-bold mb-4">Master the GMAT</h1>
        <p class="lead mb-5">Personalized coaching and premium study materials for your MBA journey.</p>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <button type="button" class="btn btn-primary btn-lg px-4 gap-3">Get Started</button>
            <button type="button" class="btn btn-outline-light btn-lg px-4">Learn More</button>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row text-center mb-5">
        <div class="col-12">
            <h2 class="fw-bold">Why Choose GMAT Group?</h2>
            <hr class="w-25 mx-auto text-primary">
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm p-4">
                <div class="text-primary mb-3">
                    <i class="fas fa-user-graduate fa-3x"></i>
                </div>
                <h4 class="fw-bold">Expert Instructors</h4>
                <p class="text-muted">Learn from top-scoring instructors with years of experience in GMAT preparation.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm p-4">
                <div class="text-primary mb-3">
                    <i class="fas fa-book-open fa-3x"></i>
                </div>
                <h4 class="fw-bold">Comprehensive Material</h4>
                <p class="text-muted">Access over 5,000+ practice questions and 50+ full-length mock exams.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm p-4">
                <div class="text-primary mb-3">
                    <i class="fas fa-chart-line fa-3x"></i>
                </div>
                <h4 class="fw-bold">Adaptive Learning</h4>
                <p class="text-muted">Our AI-driven platform adapts to your strengths and weaknesses for maximum efficiency.</p>
            </div>
        </div>
    </div>
</div>

<div class="bg-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">Start Your Journey Today</h2>
                <p class="lead text-muted mb-4">Don't leave your MBA dreams to chance. Join thousands of successful students who have achieved their target scores with GMAT Group.</p>
                <ul class="list-unstyled mb-4">
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Free Diagnostic Test</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> 24/7 Student Support</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Score Improvement Guarantee</li>
                </ul>
                <a href="#" class="btn btn-primary btn-lg">Join Now</a>
            </div>
            <div class="col-lg-6 mt-4 mt-lg-0">
                <img src="https://img.freepik.com/free-vector/education-learning-concept-banner-poster-flyer-with-books-lamp-pencil-icon-flat-design-vector-illustration_460848-11116.jpg" alt="Education" class="img-fluid rounded-4 shadow">
            </div>
        </div>
    </div>
</div>
@endsection
